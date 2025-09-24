<?php
// api/paypal_transaction.php

// الشرح باللغة العربية:
// هذا الملف يعالج تأكيد معاملات PayPal.
// يقوم بتحديث اشتراك المستخدم وتاريخ انتهاء الصلاحية، ويسجل الإيرادات بعد الدفع الناجح.

session_start();
require 'db_connect.php';

// التحقق من أن المستخدم مسجل دخوله
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$orderID = $data['orderID'] ?? null;
$planId = $data['planId'] ?? null; // The PayPal Plan ID like 'P-...'

if (!$orderID || !$planId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing transaction details.']);
    exit;
}

// --- في تطبيق حقيقي، يجب عليك التحقق من OrderID مع PayPal API ---
// هذا الجزء يتطلب cURL لطلب POST إلى PayPal API للتحقق من أن المعاملة صالحة.
// نظرًا لأن هذا نموذج أولي، سنقوم بمحاكاة التحقق الناجح.
$isPaymentVerified = true;
// -----------------------------------------------------------------

if ($isPaymentVerified) {
    $user_id = $_SESSION['user_id'];

    // جلب تفاصيل الخطة من قاعدة البيانات بناءً على PayPal Plan ID
    $stmt = $conn->prepare("SELECT id, price, duration_months FROM subscriptions WHERE paypal_plan_id = ?");
    $stmt->bind_param("s", $planId);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscription = $result->fetch_assoc();
    $stmt->close();

    if (!$subscription) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Subscription plan not found.']);
        exit;
    }

    $subscription_id = $subscription['id'];
    $amount = $subscription['price'];
    $duration_months = $subscription['duration_months'];

    // حساب تاريخ انتهاء الاشتراك الجديد
    $end_date = new DateTime();
    $end_date->add(new DateInterval("P{$duration_months}M")); // P1M for 1 month, P12M for 12 months
    $subscription_end_date = $end_date->format('Y-m-d H:i:s');

    $conn->begin_transaction();
    try {
        // تحديث اشتراك المستخدم
        $update_stmt = $conn->prepare("UPDATE users SET subscription_id = ?, subscription_end_date = ? WHERE id = ?");
        $update_stmt->bind_param("isi", $subscription_id, $subscription_end_date, $user_id);
        $update_stmt->execute();
        $update_stmt->close();

        // تسجيل المعاملة في جدول الإيرادات
        $revenue_stmt = $conn->prepare("INSERT INTO revenue (user_id, subscription_id, amount, paypal_transaction_id) VALUES (?, ?, ?, ?)");
        $revenue_stmt->bind_param("iids", $user_id, $subscription_id, $amount, $orderID);
        $revenue_stmt->execute();
        $revenue_stmt->close();

        $conn->commit();

        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Subscription updated successfully!']);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database update failed: ' . $e->getMessage()]);
    }

} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'PayPal transaction could not be verified.']);
}

$conn->close();
?>