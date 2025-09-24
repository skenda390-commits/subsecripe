<?php
// api/register.php

// الشرح باللغة العربية:
// هذا الملف يعالج طلبات تسجيل المستخدمين الجدد.
// يقوم بالتحقق من البيانات، وتشفير كلمة المرور، والتحقق من عدد الحسابات المجانية لكل جهاز/IP.

session_start();
require 'db_connect.php';

// الحصول على البيانات من الطلب
$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? null;
$password = $data['password'] ?? null;
$device_fingerprint = $data['device_fingerprint'] ?? null;
$ip_address = $_SERVER['REMOTE_ADDR'];

// التحقق من أن جميع الحقول المطلوبة موجودة
if (!$email || !$password || !$device_fingerprint) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields.']);
    exit;
}

// التحقق من صحة البريد الإلكتروني
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}

// التحقق من قوة كلمة المرور (مثال: 8 أحرف على الأقل)
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

// التحقق مما إذا كان البريد الإلكتروني مسجلاً بالفعل
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'This email is already registered.']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();


// التحقق من عدد الحسابات المجانية المرتبطة ببصمة الجهاز أو عنوان IP
// يتم تطبيق هذا القيد فقط عند محاولة إنشاء حساب مجاني
$free_account_limit = 3;
$stmt = $conn->prepare("SELECT COUNT(id) FROM users WHERE (device_fingerprint = ? OR ip_address = ?) AND subscription_id = 1");
$stmt->bind_param("ss", $device_fingerprint, $ip_address);
$stmt->execute();
$stmt->bind_result($account_count);
$stmt->fetch();
$stmt->close();

if ($account_count >= $free_account_limit) {
    http_response_code(429);
    echo json_encode([
        'status' => 'error',
        'message' => 'You have reached the limit of free accounts for this device/IP address.'
    ]);
    $conn->close();
    exit;
}


// تشفير كلمة المرور
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// إدراج المستخدم الجديد في قاعدة البيانات باشتراك مجاني افتراضي
$default_subscription_id = 1;
$stmt = $conn->prepare("INSERT INTO users (email, password, device_fingerprint, ip_address, subscription_id) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssssi", $email, $hashed_password, $device_fingerprint, $ip_address, $default_subscription_id);

if ($stmt->execute()) {
    // إنشاء سجل استخدام للشهر الحالي
    $user_id = $stmt->insert_id;
    $current_month = date('n');
    $current_year = date('Y');
    $usage_stmt = $conn->prepare("INSERT INTO `usage` (user_id, month, year) VALUES (?, ?, ?)");
    $usage_stmt->bind_param("iii", $user_id, $current_month, $current_year);
    $usage_stmt->execute();
    $usage_stmt->close();

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Registration successful! You can now log in.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An error occurred during registration. Please try again.']);
}

$stmt->close();
$conn->close();
?>