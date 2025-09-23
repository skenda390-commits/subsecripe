<?php
// تأكد من أن الطلب جاء بطريقة POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // استلام البيانات من الطلب
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $export_type = isset($_POST['export_type']) ? $_POST['export_type'] : '';
    $count = isset($_POST['count']) ? intval($_POST['count']) : 1;
    
    // التحقق من صحة البيانات
    if ($user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'معرف المستخدم غير صالح']);
        exit;
    }
    
    if (!in_array($export_type, ['image', 'video'])) {
        echo json_encode(['status' => 'error', 'message' => 'نوع التصدير غير صالح']);
        exit;
    }
    
    // الاتصال بقاعدة البيانات
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'design_app';
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // التحقق من الاتصال
    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error]);
        exit;
    }
    
    // ضبط الترميز
    $conn->set_charset("utf8mb4");
    
    // التحقق من وجود سجل للمستخدم في جدول الاستخدام
    $check_sql = "SELECT * FROM usage_stats WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    // الحصول على الحصة الشهرية للمستخدم من جدول المستخدمين
    $quota_sql = "SELECT monthly_image_quota, monthly_video_quota FROM users WHERE id = ?";
    $quota_stmt = $conn->prepare($quota_sql);
    $quota_stmt->bind_param('i', $user_id);
    $quota_stmt->execute();
    $quota_result = $quota_stmt->get_result();
    $quota_data = $quota_result->fetch_assoc();
    
    $monthly_image_quota = isset($quota_data['monthly_image_quota']) ? $quota_data['monthly_image_quota'] : 50;
    $monthly_video_quota = isset($quota_data['monthly_video_quota']) ? $quota_data['monthly_video_quota'] : 10;
    
    // التحقق من تجاوز الحصة الشهرية
    if ($result->num_rows > 0) {
        $usage_data = $result->fetch_assoc();
        
        // التحقق من الحصة المتبقية
        if ($export_type == 'image' && ($usage_data['images_exported'] + $count) > $monthly_image_quota) {
            echo json_encode([
                'status' => 'quota_exceeded', 
                'message' => 'لقد تجاوزت الحصة الشهرية للصور',
                'current_usage' => $usage_data['images_exported'],
                'quota' => $monthly_image_quota,
                'remaining' => $monthly_image_quota - $usage_data['images_exported']
            ]);
            exit;
        }
        
        if ($export_type == 'video' && ($usage_data['videos_exported'] + $count) > $monthly_video_quota) {
            echo json_encode([
                'status' => 'quota_exceeded', 
                'message' => 'لقد تجاوزت الحصة الشهرية للفيديوهات',
                'current_usage' => $usage_data['videos_exported'],
                'quota' => $monthly_video_quota,
                'remaining' => $monthly_video_quota - $usage_data['videos_exported']
            ]);
            exit;
        }
        
        // تحديث سجل الاستخدام
        $update_sql = "";
        if ($export_type == 'image') {
            $update_sql = "UPDATE usage_stats SET images_exported = images_exported + ?, last_export_date = NOW() WHERE user_id = ?";
        } else {
            $update_sql = "UPDATE usage_stats SET videos_exported = videos_exported + ?, last_export_date = NOW() WHERE user_id = ?";
        }
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ii', $count, $user_id);
        
        if ($update_stmt->execute()) {
            // الحصول على البيانات المحدثة
            $check_stmt->execute();
            $updated_result = $check_stmt->get_result();
            $updated_data = $updated_result->fetch_assoc();
            
            // حساب الحصة المتبقية
            $remaining_images = $monthly_image_quota - $updated_data['images_exported'];
            $remaining_videos = $monthly_video_quota - $updated_data['videos_exported'];
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'تم تحديث بيانات الاستخدام بنجاح',
                'usage' => [
                    'images_exported' => $updated_data['images_exported'],
                    'videos_exported' => $updated_data['videos_exported'],
                    'last_export_date' => $updated_data['last_export_date'],
                    'image_quota' => $monthly_image_quota,
                    'video_quota' => $monthly_video_quota,
                    'remaining_images' => $remaining_images,
                    'remaining_videos' => $remaining_videos
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'فشل تحديث بيانات الاستخدام: ' . $conn->error]);
        }
    } else {
        // إنشاء سجل جديد للمستخدم
        $insert_sql = "INSERT INTO usage_stats (user_id, images_exported, videos_exported, last_export_date) VALUES (?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        
        $images_count = ($export_type == 'image') ? $count : 0;
        $videos_count = ($export_type == 'video') ? $count : 0;
        
        $insert_stmt->bind_param('iii', $user_id, $images_count, $videos_count);
        
        if ($insert_stmt->execute()) {
            // حساب الحصة المتبقية
            $remaining_images = $monthly_image_quota - $images_count;
            $remaining_videos = $monthly_video_quota - $videos_count;
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'تم إنشاء سجل استخدام جديد بنجاح',
                'usage' => [
                    'images_exported' => $images_count,
                    'videos_exported' => $videos_count,
                    'last_export_date' => date('Y-m-d H:i:s'),
                    'image_quota' => $monthly_image_quota,
                    'video_quota' => $monthly_video_quota,
                    'remaining_images' => $remaining_images,
                    'remaining_videos' => $remaining_videos
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'فشل إنشاء سجل استخدام جديد: ' . $conn->error]);
        }
    }
    
    // إغلاق الاتصال
    $conn->close();
} else {
    // إذا لم يكن الطلب بطريقة POST
    echo json_encode(['status' => 'error', 'message' => 'طريقة الطلب غير صالحة']);
}
?>