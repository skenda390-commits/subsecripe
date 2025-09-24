<?php
// api/login.php

// الشرح باللغة العربية:
// هذا الملف يعالج طلبات تسجيل دخول المستخدمين.
// يقوم بالتحقق من البريد الإلكتروني وكلمة المرور، وفي حال نجاح التحقق، يتم إنشاء جلسة للمستخدم.

session_start();
require 'db_connect.php';

// الحصول على البيانات من الطلب
$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? null;
$password = $data['password'] ?? null;

// التحقق من أن الحقول المطلوبة موجودة
if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
    exit;
}

// البحث عن المستخدم في قاعدة البيانات
$stmt = $conn->prepare("SELECT id, password, is_admin FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($user_id, $hashed_password, $is_admin);
    $stmt->fetch();

    // التحقق من كلمة المرور
    if (password_verify($password, $hashed_password)) {
        // تم التحقق بنجاح، إنشاء الجلسة
        $_SESSION['user_id'] = $user_id;
        $_SESSION['email'] = $email;
        $_SESSION['is_admin'] = (bool)$is_admin;

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful!',
            'isAdmin' => (bool)$is_admin
        ]);
    } else {
        // كلمة المرور غير صحيحة
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    }
} else {
    // المستخدم غير موجود
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
}

$stmt->close();
$conn->close();
?>