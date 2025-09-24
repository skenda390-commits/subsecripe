<?php
// api/db_connect.php

// الشرح باللغة العربية:
// هذا الملف يقوم بإنشاء اتصال بقاعدة البيانات.
// يتم استخدامه في جميع ملفات الـ API الأخرى لتجنب تكرار الكود.

header('Content-Type: application/json');

// --- يجب تعديل هذه المتغيرات لتطابق إعدادات قاعدة البيانات الفعلية ---
$servername = "localhost";
$username = "root"; // اسم مستخدم قاعدة البيانات
$password = ""; // كلمة مرور قاعدة البيانات
$dbname = "saas_project"; // اسم قاعدة البيانات
// --------------------------------------------------------------------

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    // إيقاف التنفيذ وإرجاع خطأ إذا فشل الاتصال
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// تعيين ترميز الاتصال إلى utf8mb4 لضمان دعم اللغة العربية بشكل صحيح
$conn->set_charset("utf8mb4");
?>