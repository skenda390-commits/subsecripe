<?php
// api/update_usage.php

// الشرح باللغة العربية:
// هذا الملف يقوم بتحديث إحصائيات استخدام المستخدم (عدد الصور أو الفيديوهات).
// يتم استدعاؤه عبر AJAX بعد كل عملية تصدير ناجحة.

session_start();
require 'db_connect.php';

// التحقق من أن المستخدم مسجل دخوله
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? null; // 'image' or 'video'

if ($type !== 'image' && $type !== 'video') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid export type specified.']);
    exit;
}

// بدء معاملة لضمان سلامة البيانات
$conn->begin_transaction();

try {
    $current_month = date('n');
    $current_year = date('Y');

    // 1. جلب حدود الاشتراك والاستخدام الحالي
    $stmt = $conn->prepare(
        "SELECT s.image_limit, s.video_limit, u.image_count, u.video_count, s.id as subscription_id
         FROM `usage` u
         JOIN users usr ON u.user_id = usr.id
         JOIN subscriptions s ON usr.subscription_id = s.id
         WHERE u.user_id = ? AND u.month = ? AND u.year = ? FOR UPDATE"
    );
    $stmt->bind_param("iii", $user_id, $current_month, $current_year);
    $stmt->execute();
    $result = $stmt->get_result();
    $usage_data = $result->fetch_assoc();
    $stmt->close();

    if (!$usage_data) {
       throw new Exception('Usage record not found for this month.');
    }

    // 2. التحقق من الحدود
    if ($type === 'image' && $usage_data['image_count'] >= $usage_data['image_limit']) {
        throw new Exception('Image credit limit reached.');
    }
    if ($type === 'video') {
        // التحقق من أن الخطة ليست مجانية
        if ($usage_data['subscription_id'] == 1) {
            throw new Exception('Video export is not available for free plans.');
        }
        if ($usage_data['video_count'] >= $usage_data['video_limit']) {
            throw new Exception('Video credit limit reached.');
        }
    }

    // 3. تحديث العداد
    $column_to_update = $type . '_count';
    $update_stmt = $conn->prepare(
        "UPDATE `usage` SET {$column_to_update} = {$column_to_update} + 1
         WHERE user_id = ? AND month = ? AND year = ?"
    );
    $update_stmt->bind_param("iii", $user_id, $current_month, $current_year);
    $update_stmt->execute();

    if($update_stmt->affected_rows === 0){
        throw new Exception('Failed to update usage count.');
    }
    $update_stmt->close();

    // 4. إتمام المعاملة
    $conn->commit();

    // 5. إرجاع بيانات الاستخدام الجديدة
    $new_usage_data = [
        'imagesUsed' => $type === 'image' ? $usage_data['image_count'] + 1 : $usage_data['image_count'],
        'videosUsed' => $type === 'video' ? $usage_data['video_count'] + 1 : $usage_data['video_count'],
        'imageLimit' => (int)$usage_data['image_limit'],
        'videoLimit' => (int)$usage_data['video_limit'],
        'imagesRemaining' => $type === 'image' ? $usage_data['image_limit'] - ($usage_data['image_count'] + 1) : $usage_data['image_limit'] - $usage_data['image_count'],
        'videosRemaining' => $type === 'video' ? $usage_data['video_limit'] - ($usage_data['video_count'] + 1) : $usage_data['video_limit'] - $usage_data['video_count'],
    ];

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Usage updated.', 'newUsage' => $new_usage_data]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>