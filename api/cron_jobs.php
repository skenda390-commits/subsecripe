<?php
// api/cron_jobs.php

// الشرح باللغة العربية:
// هذا الملف مخصص ليتم تشغيله بواسطة CRON job.
// يقوم بمهمتين:
// 1. إعادة تعيين عدادات الاستخدام (الصور والفيديو) في اليوم الأول من كل شهر.
// 2. التحقق من الاشتراكات المنتهية وإرجاعها إلى الخطة المجانية.

// --- إعدادات CRON Job ---
// لتشغيل هذا الملف يوميًا في منتصف الليل، يمكنك استخدام الأمر التالي:
// 0 0 * * * /usr/bin/php /path/to/your/project/api/cron_jobs.php
// -------------------------

// زيادة مهلة التنفيذ للملفات التي قد تستغرق وقتًا طويلاً
set_time_limit(600);
// تجاهل إغلاق المستخدم للاتصال
ignore_user_abort(true);

require 'db_connect.php';

// --- المهمة 1: إعادة تعيين حدود الاستخدام الشهرية ---
// يتم تشغيل هذا الجزء فقط في اليوم الأول من الشهر.
if (date('j') == 1) {
    echo "Running monthly usage reset...\n";
    $current_month = date('n');
    $current_year = date('Y');

    // هذه الطريقة تقوم بتحديث السجلات الحالية إلى صفر بدلاً من حذفها
    // ويمكنها إنشاء سجلات جديدة للمستخدمين الذين ليس لديهم سجل استخدام للشهر الجديد.
    // الطريقة الأبسط هي حذف سجلات الشهر الماضي، ولكن هذا يحافظ على السجل التاريخي.

    // جلب جميع المستخدمين النشطين
    $users_result = $conn->query("SELECT id FROM users");
    while ($user = $users_result->fetch_assoc()) {
        $user_id = $user['id'];

        // التحقق من وجود سجل استخدام للشهر الحالي
        $check_stmt = $conn->prepare("SELECT id FROM `usage` WHERE user_id = ? AND month = ? AND year = ?");
        $check_stmt->bind_param("iii", $user_id, $current_month, $current_year);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows == 0) {
            // إذا لم يكن هناك سجل، قم بإنشاء سجل جديد
            $insert_stmt = $conn->prepare("INSERT INTO `usage` (user_id, image_count, video_count, month, year) VALUES (?, 0, 0, ?, ?)");
            $insert_stmt->bind_param("iii", $user_id, $current_month, $current_year);
            $insert_stmt->execute();
            $insert_stmt->close();
            echo "Created new usage record for user_id: $user_id\n";
        }
        $check_stmt->close();
    }
    echo "Monthly usage reset complete.\n";
}


// --- المهمة 2: التعامل مع الاشتراكات المنتهية ---
echo "Checking for expired subscriptions...\n";

// تحديد الخطة المجانية
$free_plan_id = 1;

// جلب جميع المستخدمين الذين لديهم اشتراك منتهي الصلاحية وليسوا بالفعل على الخطة المجانية
$expired_stmt = $conn->prepare(
    "UPDATE users
     SET subscription_id = ?, subscription_end_date = NULL
     WHERE subscription_end_date IS NOT NULL AND subscription_end_date < NOW() AND subscription_id != ?"
);
$expired_stmt->bind_param("ii", $free_plan_id, $free_plan_id);
$expired_stmt->execute();

$affected_rows = $expired_stmt->affected_rows;
echo "$affected_rows user(s) downgraded to the free plan due to subscription expiration.\n";

$expired_stmt->close();
$conn->close();

echo "CRON job execution finished.\n";
?>