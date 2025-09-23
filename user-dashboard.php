<?php
// التحقق من تسجيل الدخول
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// الاتصال بقاعدة البيانات
$db_config = include 'config/database.php';
$conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// الحصول على بيانات المستخدم
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// الحصول على إحصائيات الاستخدام
$stats_stmt = $conn->prepare("SELECT 
    COUNT(*) as total_exports,
    SUM(CASE WHEN export_type = 'image' THEN 1 ELSE 0 END) as image_exports,
    SUM(CASE WHEN export_type = 'video' THEN 1 ELSE 0 END) as video_exports
    FROM usage_tracking 
    WHERE user_id = ?");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// الحصول على بيانات الحصة الشهرية
$quota_stmt = $conn->prepare("SELECT 
    monthly_image_quota, 
    monthly_video_quota 
    FROM users 
    WHERE id = ?");
$quota_stmt->bind_param("i", $user_id);
$quota_stmt->execute();
$quota_result = $quota_stmt->get_result();
$quota = $quota_result->fetch_assoc();

// الحصول على بيانات الاستخدام الحالي من جدول usage_stats
$usage_stmt = $conn->prepare("SELECT 
    images_exported, 
    videos_exported 
    FROM usage_stats 
    WHERE user_id = ?");
$usage_stmt->bind_param("i", $user_id);
$usage_stmt->execute();
$usage_result = $usage_stmt->get_result();

// إذا لم يكن هناك سجل، إنشاء سجل جديد
if ($usage_result->num_rows == 0) {
    $insert_stmt = $conn->prepare("INSERT INTO usage_stats (user_id, images_exported, videos_exported, last_reset_date) VALUES (?, 0, 0, NOW())");
    $insert_stmt->bind_param("i", $user_id);
    $insert_stmt->execute();
    $insert_stmt->close();
    
    $usage = [
        'images_exported' => 0,
        'videos_exported' => 0
    ];
} else {
    $usage = $usage_result->fetch_assoc();
}

// حساب المتبقي من الحصة الشهرية
$remaining_images = $quota['monthly_image_quota'] - $usage['images_exported'];
$remaining_videos = $quota['monthly_video_quota'] - $usage['videos_exported'];

// حساب النسب المئوية للاستخدام
$image_percentage = ($usage['images_exported'] / $quota['monthly_image_quota']) * 100;
$video_percentage = ($usage['videos_exported'] / $quota['monthly_video_quota']) * 100;

// إغلاق الاتصال
$stmt->close();
$stats_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة المستخدم | 4Dads.pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/usage-stats.css">
    <style>
        :root {
            --primary-bg: #182949;
            --secondary-bg: #3b527c;
            --accent-color: #7b8eb2;
            --text-color: #d3a77b;
            --border-color: #4a5c7a;
            --button-hover: #3b527c;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .dashboard-header {
            background-color: var(--primary-bg);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dashboard-header h1 {
            margin: 0;
            color: var(--text-color);
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-card h2 {
            color: var(--primary-bg);
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .btn {
            background-color: var(--primary-bg);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--button-hover);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>مرحباً، <?php echo htmlspecialchars($user['name']); ?></h1>
            <a href="logout.php" class="btn">تسجيل الخروج</a>
        </div>
        
        <div class="dashboard-content">
            <div class="dashboard-card">
                <h2>معلومات الحساب</h2>
                <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>تاريخ التسجيل:</strong> <?php echo date('Y-m-d', strtotime($user['created_at'])); ?></p>
                <a href="edit-profile.php" class="btn">تعديل الملف الشخصي</a>
            </div>
            
            <div class="dashboard-card">
                <h2>إحصائيات الاستخدام</h2>
                <div class="usage-stats-container">
                    <div class="usage-stats-title">الحصة الشهرية</div>
                    <div id="usage-stats">
                        <div class="stats-item">
                            <div class="stats-header">
                                <span class="stats-label">تصدير الصور:</span>
                                <span class="stats-value"><?php echo $usage['images_exported']; ?> / <?php echo $quota['monthly_image_quota']; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $image_percentage; ?>%"></div>
                            </div>
                            <div class="stats-footer">
                                <span class="stats-remaining">المتبقي: <?php echo $remaining_images; ?></span>
                            </div>
                        </div>
                        <div class="stats-item">
                            <div class="stats-header">
                                <span class="stats-label">تصدير الفيديو:</span>
                                <span class="stats-value"><?php echo $usage['videos_exported']; ?> / <?php echo $quota['monthly_video_quota']; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $video_percentage; ?>%"></div>
                            </div>
                            <div class="stats-footer">
                                <span class="stats-remaining">المتبقي: <?php echo $remaining_videos; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="usage-stats-title mt-4">إحصائيات التصدير الكلية</div>
                    <div class="stats-item">
                        <span class="stats-label">إجمالي عمليات التصدير:</span>
                        <span class="stats-value"><?php echo $stats['total_exports'] ?? 0; ?></span>
                    </div>
                </div>
                <a href="index.html" class="btn mt-3">العودة إلى المحرر</a>
            </div>
            
            <div class="dashboard-card">
                <h2>آخر النشاطات</h2>
                <p>قريباً...</p>
            </div>
        </div>
    </div>
</body>
</html>