<?php
// ملف لإضافة مستخدم مسؤول جديد
session_start();

// استيراد إعدادات قاعدة البيانات
$db_config = include 'config/database.php';

// الاتصال بقاعدة البيانات
$conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// بيانات المستخدم المسؤول
$admin_email = 'ads@4dads.pro';
$admin_username = 'admin';
$admin_password = 'Admin@1979#';
$admin_role = 'admin'; // إضافة دور المسؤول

// تشفير كلمة المرور
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// التحقق من وجود المستخدم
$check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
$check_stmt->bind_param("ss", $admin_email, $admin_username);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // تحديث المستخدم الموجود
    $user = $result->fetch_assoc();
    $update_stmt = $conn->prepare("UPDATE users SET password = ?, subscription_status = 'premium', monthly_image_quota = 1000, monthly_video_quota = 1000 WHERE id = ?");
    $update_stmt->bind_param("si", $hashed_password, $user['id']);
    
    if ($update_stmt->execute()) {
        echo "تم تحديث بيانات المستخدم المسؤول بنجاح!";
    } else {
        echo "حدث خطأ أثناء تحديث المستخدم: " . $conn->error;
    }
    $update_stmt->close();
} else {
    // إضافة مستخدم جديد
    $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, subscription_status, monthly_image_quota, monthly_video_quota) VALUES (?, ?, ?, 'premium', 1000, 1000)");
    $insert_stmt->bind_param("sss", $admin_username, $admin_email, $hashed_password);
    
    if ($insert_stmt->execute()) {
        echo "تم إضافة المستخدم المسؤول بنجاح!";
    } else {
        echo "حدث خطأ أثناء إضافة المستخدم: " . $conn->error;
    }
    $insert_stmt->close();
}

$check_stmt->close();
$conn->close();
?>