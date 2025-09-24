<?php
// api/logout.php

// الشرح باللغة العربية:
// هذا الملف يقوم بتسجيل خروج المستخدم عن طريق تدمير الجلسة الحالية.

session_start();

// إلغاء تعيين جميع متغيرات الجلسة
$_SESSION = array();

// تدمير الجلسة
if (session_destroy()) {
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Logout successful.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Logout failed.']);
}
?>