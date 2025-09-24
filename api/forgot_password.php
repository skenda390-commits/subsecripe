<?php
// api/forgot_password.php

// الشرح باللغة العربية:
// هذا الملف يبدأ عملية إعادة تعيين كلمة المرور.
// يقوم بإنشاء توكن آمن، وتخزينه، وإعادة التوكن (في التطبيق الفعلي، سيتم إرساله عبر البريد الإلكتروني).

require 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? null;

if (!$email) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email address is required.']);
    exit;
}

// التحقق من وجود المستخدم
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    // إظهار رسالة عامة لتجنب الكشف عن وجود البريد الإلكتروني
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'If an account with that email exists, a password reset link has been sent.']);
    exit;
}
$stmt->close();

// إنشاء توكن آمن
$token = bin2hex(random_bytes(50));

// تخزين التوكن في قاعدة البيانات مع تاريخ انتهاء صلاحية (مثلاً، ساعة واحدة)
$stmt = $conn->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
$stmt->bind_param("ss", $email, $token);

if ($stmt->execute()) {
    // --- في سيناريو حقيقي، أرسل بريدًا إلكترونيًا هنا ---
    $reset_link = "http://yourdomain.com/public_html/reset_password.html?token=" . $token;
    // mail($email, "Password Reset Request", "Click here to reset your password: " . $reset_link);
    // --------------------------------------------------

    // لأغراض هذا النموذج الأولي، سنعيد التوكن والرابط في الاستجابة
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Password reset token generated. In a real app, this would be emailed.',
        'token' => $token, // For testing
        'reset_link' => $reset_link // For testing
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to generate password reset token.']);
}

$stmt->close();
$conn->close();
?>