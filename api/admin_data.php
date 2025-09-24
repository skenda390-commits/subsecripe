<?php
// api/admin_data.php

// الشرح باللغة العربية:
// هذا الملف هو نقطة النهاية الرئيسية للوحة تحكم المدير.
// يقوم بالتحقق من أن المستخدم هو مدير، ثم يعيد البيانات المطلوبة بناءً على المعلمات.

session_start();
require 'db_connect.php';

// التحقق من أن المستخدم مدير
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Administrator privileges required.']);
    exit;
}

$action = $_GET['action'] ?? 'stats'; // Default action is to get stats

switch ($action) {
    case 'stats':
        getStats($conn);
        break;
    case 'users':
        getUsers($conn, $_GET['search'] ?? '');
        break;
    case 'plans':
        getPlans($conn);
        break;
    // Other cases for offers, etc., can be added here
    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
        break;
}

$conn->close();


// --- Functions to fetch data ---

function getStats($conn) {
    // Total Revenue
    $total_rev_res = $conn->query("SELECT SUM(amount) as total FROM revenue");
    $total_revenue = $total_rev_res->fetch_assoc()['total'] ?? 0;

    // Monthly Revenue
    $monthly_rev_res = $conn->query("SELECT SUM(amount) as total FROM revenue WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
    $monthly_revenue = $monthly_rev_res->fetch_assoc()['total'] ?? 0;

    // Total Users
    $total_users_res = $conn->query("SELECT COUNT(id) as total FROM users WHERE is_admin = 0");
    $total_users = $total_users_res->fetch_assoc()['total'] ?? 0;

    // Revenue chart data (e.g., last 12 months)
    $chart_data = [];
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m-01', strtotime("-$i months"));
        $month_name = date('M Y', strtotime($month));
        $res = $conn->query("SELECT SUM(amount) as total FROM revenue WHERE YEAR(payment_date) = YEAR('$month') AND MONTH(payment_date) = MONTH('$month')");
        $total = $res->fetch_assoc()['total'] ?? 0;
        $chart_data['labels'][] = $month_name;
        $chart_data['values'][] = $total;
    }


    echo json_encode([
        'status' => 'success',
        'totalRevenue' => number_format($total_revenue, 2),
        'monthlyRevenue' => number_format($monthly_revenue, 2),
        'totalUsers' => $total_users,
        'chartData' => $chart_data
    ]);
}

function getUsers($conn, $search) {
    $search_term = "%{$search}%";
    $query = "SELECT u.id, u.email, u.ip_address, u.device_fingerprint, s.name as subscription_name, u.created_at
              FROM users u
              JOIN subscriptions s ON u.subscription_id = s.id
              WHERE u.is_admin = 0";

    if (!empty($search)) {
        $query .= " AND (u.email LIKE ? OR u.ip_address LIKE ? OR u.device_fingerprint LIKE ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    } else {
        $stmt = $conn->prepare($query);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['status' => 'success', 'users' => $users]);
}

function getPlans($conn) {
    $result = $conn->query("SELECT * FROM subscriptions ORDER BY price ASC");
    $plans = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['status' => 'success', 'plans' => $plans]);
}

?>