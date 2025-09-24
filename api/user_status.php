<?php
// api/user_status.php

// الشرح باللغة العربية:
// هذا الملف يقوم بالتحقق من حالة تسجيل دخول المستخدم.
// إذا كان المستخدم مسجلاً، فإنه يعيد بيانات المستخدم، تفاصيل اشتراكه، وإحصائيات الاستخدام الحالية.

session_start();
require 'db_connect.php';

// التحقق مما إذا كان المستخدم قد سجل دخوله
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.', 'loggedIn' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_month = date('n');
$current_year = date('Y');

// الاستعلام عن بيانات المستخدم وتفاصيل اشتراكه
$stmt = $conn->prepare(
    "SELECT
        u.id, u.email, u.is_admin, u.subscription_end_date,
        s.id as subscription_id, s.name as subscription_name, s.image_limit, s.video_limit, s.resolution, s.has_ads
    FROM users u
    JOIN subscriptions s ON u.subscription_id = s.id
    WHERE u.id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

if (!$user_data) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    $conn->close();
    exit;
}

// الاستعلام عن الاستخدام الحالي للمستخدم
$usage_stmt = $conn->prepare(
    "SELECT image_count, video_count FROM `usage` WHERE user_id = ? AND month = ? AND year = ?"
);
$usage_stmt->bind_param("iii", $user_id, $current_month, $current_year);
$usage_stmt->execute();
$usage_result = $usage_stmt->get_result();
$usage_data = $usage_result->fetch_assoc();
$usage_stmt->close();

// إذا لم يكن هناك سجل استخدام لهذا الشهر، قم بإنشائه
if (!$usage_data) {
    $insert_usage_stmt = $conn->prepare("INSERT INTO `usage` (user_id, month, year) VALUES (?, ?, ?)");
    $insert_usage_stmt->bind_param("iii", $user_id, $current_month, $current_year);
    $insert_usage_stmt->execute();
    $insert_usage_stmt->close();
    $usage_data = ['image_count' => 0, 'video_count' => 0];
}


// حساب المتبقي من الرصيد
$remaining_images = $user_data['image_limit'] - $usage_data['image_count'];
$remaining_videos = $user_data['video_limit'] - $usage_data['video_count'];

// تجميع البيانات لإرسالها
$response = [
    'status' => 'success',
    'loggedIn' => true,
    'user' => [
        'id' => $user_data['id'],
        'email' => $user_data['email'],
        'isAdmin' => (bool)$user_data['is_admin']
    ],
    'subscription' => [
        'id' => $user_data['subscription_id'],
        'name' => $user_data['subscription_name'],
        'endDate' => $user_data['subscription_end_date'],
        'hasAds' => (bool)$user_data['has_ads'],
        'resolution' => $user_data['resolution']
    ],
    'usage' => [
        'imageLimit' => (int)$user_data['image_limit'],
        'videoLimit' => (int)$user_data['video_limit'],
        'imagesUsed' => (int)$usage_data['image_count'],
        'videosUsed' => (int)$usage_data['video_count'],
        'imagesRemaining' => $remaining_images > 0 ? $remaining_images : 0,
        'videosRemaining' => $remaining_videos > 0 ? $remaining_videos : 0
    ]
];

http_response_code(200);
echo json_encode($response);

$conn->close();
?>