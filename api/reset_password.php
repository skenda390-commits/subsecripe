<?php
// api/reset_password.php

// الشرح باللغة العربية:
// هذا الملف يعالج طلب إعادة تعيين كلمة المرور النهائية.
// يتحقق من صحة التوكن، ويقوم بتحديث كلمة المرور الجديدة والمشفرة في قاعدة البيانات.

require 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? null;
$password = $data['password'] ?? null;

if (!$token || !$password) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Token and new password are required.']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

// التحقق من التوكن وتاريخ صلاحيته (مثلاً، صالح لمدة ساعة واحدة)
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND created_at > NOW() - INTERVAL 1 HOUR");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($email);
    $stmt->fetch();
    $stmt->close();

    // التوكن صالح، قم بتحديث كلمة المرور
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $update_stmt->bind_param("ss", $hashed_password, $email);

    if ($update_stmt->execute()) {
        // تم التحديث بنجاح، احذف التوكن المستخدم
        $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $delete_stmt->bind_param("s", $email);
        $delete_stmt->execute();
        $delete_stmt->close();

        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Password has been reset successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
    }
    $update_stmt->close();

} else {
    // التوكن غير صالح أو منتهي الصلاحية
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired password reset token.']);
    $stmt->close();
}

$conn->close();
?>