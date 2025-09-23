<?php
session_start();
// التحقق من تسجيل الدخول
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
    FROM usage_stats 
    WHERE user_id = ?");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// الحصول على آخر إحصائيات الاستخدام من جدول usage_stats
$usage_stmt = $conn->prepare("SELECT * FROM usage_stats WHERE user_id = ? ORDER BY last_export_date DESC LIMIT 1");
$usage_stmt->bind_param("i", $user_id);
$usage_stmt->execute();
$usage_result = $usage_stmt->get_result();
$usage_data = $usage_result->fetch_assoc();

// الحصول على الشهر الحالي والسنة
$current_month = date('m');
$current_year = date('Y');

// الحصول على إحصائيات الاستخدام الشهرية
$monthly_stats_stmt = $conn->prepare("SELECT 
    SUM(CASE WHEN export_type = 'image' THEN 1 ELSE 0 END) as monthly_images_used,
    SUM(CASE WHEN export_type = 'video' THEN 1 ELSE 0 END) as monthly_videos_used
    FROM usage_stats 
    WHERE user_id = ? AND MONTH(created_at) = ? AND YEAR(created_at) = ?");
$monthly_stats_stmt->bind_param("iii", $user_id, $current_month, $current_year);
$monthly_stats_stmt->execute();
$monthly_stats_result = $monthly_stats_stmt->get_result();
$monthly_stats = $monthly_stats_result->fetch_assoc();

// حساب الاستخدام الشهري
$monthly_image_quota = $user['monthly_image_quota'] ?? 10;
$monthly_video_quota = $user['monthly_video_quota'] ?? 3;
$monthly_images_used = $monthly_stats['monthly_images_used'] ?? 0;
$monthly_videos_used = $monthly_stats['monthly_videos_used'] ?? 0;

// حساب المتبقي من الحصة
$image_quota_remaining = max(0, $monthly_image_quota - $monthly_images_used);
$video_quota_remaining = max(0, $monthly_video_quota - $monthly_videos_used);

// حساب النسب المئوية للاستخدام
$image_quota_percentage = ($monthly_image_quota > 0) ? min(100, round(($monthly_images_used / $monthly_image_quota) * 100)) : 0;
$video_quota_percentage = ($monthly_video_quota > 0) ? min(100, round(($monthly_videos_used / $monthly_video_quota) * 100)) : 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - عرض ثلاثي الأبعاد</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/usage-stats.css">
    <script src="https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=USD"></script>
    <script src="js/jquery.min.js"></script>
    <script src="libs/threejs/three.min.js"></script>
    <script src="libs/threejs/GLTFLoader.js"></script>
    <script src="libs/threejs/OrbitControls.js"></script>
    <script src="libs/ccapture/CCapture.all.min.js"></script>
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #182949;
        }
        .user-menu {
            display: flex;
            align-items: center;
        }
        .user-menu .username {
            margin-left: 15px;
        }
        .btn {
            display: inline-block;
            background-color: #182949;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #0d1b36;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
        }
        .sidebar {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .main-content {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #182949;
        }
        .usage-stats {
            margin-bottom: 30px;
        }
        .stats-item {
            margin-bottom: 15px;
        }
        .stats-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .progress-bar {
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background-color: #4caf50;
            border-radius: 5px;
        }
        .progress-fill.warning {
            background-color: #ffc107;
        }
        .progress-fill.danger {
            background-color: #dc3545;
        }
        .stats-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .stats-value {
            font-size: 24px;
            font-weight: bold;
            color: #182949;
            margin-bottom: 5px;
        }
        .stats-description {
            font-size: 14px;
            color: #6c757d;
        }
        .scene-container {
            width: 100%;
            height: 400px;
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        #scene {
            width: 100%;
            height: 100%;
        }
        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .subscription-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .paypal-button-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">نظام العرض ثلاثي الأبعاد</div>
            <div class="user-menu">
                <span class="username">مرحباً، <?php echo htmlspecialchars($user['username']); ?></span>
                <a href="logout.php" class="btn btn-danger">تسجيل الخروج</a>
            </div>
        </header>
        
        <div class="dashboard-grid">
            <div class="sidebar">
                <div class="section-title">إحصائيات الاستخدام</div>
                
                <div class="usage-stats">
                    <div class="stats-item">
                        <div class="stats-label">
                            <span>الصور المصدرة هذا الشهر</span>
                            <span><?php echo $monthly_images_used; ?> / <?php echo $monthly_image_quota; ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?php echo $image_quota_percentage >= 90 ? 'danger' : ($image_quota_percentage >= 70 ? 'warning' : ''); ?>" style="width: <?php echo $image_quota_percentage; ?>%"></div>
                        </div>
                        <div class="stats-footer">
                            <span class="stats-remaining">المتبقي: <?php echo $image_quota_remaining; ?> صورة</span>
                        </div>
                    </div>
                    
                    <div class="stats-item">
                        <div class="stats-label">
                            <span>الفيديوهات المصدرة هذا الشهر</span>
                            <span><?php echo $monthly_videos_used; ?> / <?php echo $monthly_video_quota; ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?php echo $video_quota_percentage >= 90 ? 'danger' : ($video_quota_percentage >= 70 ? 'warning' : ''); ?>" style="width: <?php echo $video_quota_percentage; ?>%"></div>
                        </div>
                        <div class="stats-footer">
                            <span class="stats-remaining">المتبقي: <?php echo $video_quota_remaining; ?> فيديو</span>
                        </div>
                    </div>
                </div>
                
                <div class="section-title">إحصائيات إجمالية</div>
                <div class="stats-item">
                    <div>إجمالي الصور: <?php echo $stats['image_exports'] ?? 0; ?></div>
                    <div>إجمالي الفيديوهات: <?php echo $stats['video_exports'] ?? 0; ?></div>
                    <div>إجمالي التصدير: <?php echo $stats['total_exports'] ?? 0; ?></div>
                </div>
                
                <div class="section-title">الاشتراك</div>
                <div class="subscription-info">
                    <div>نوع الاشتراك: <?php echo $user['subscription_status'] === 'premium' ? 'مميز' : 'مجاني'; ?></div>
                    <?php if ($user['subscription_status'] !== 'premium'): ?>
                        <div>ترقية إلى الاشتراك المميز للحصول على:</div>
                        <ul>
                            <li>50 صورة شهرياً</li>
                            <li>15 فيديو شهرياً</li>
                            <li>جودة تصدير عالية</li>
                        </ul>
                        <div id="paypal-button-container" class="paypal-button-container"></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="main-content">
                <div class="section-title">إحصائيات الاستخدام المفصلة</div>
                
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stats-card">
                        <div class="stats-value"><?php echo $stats['image_exports'] ?? 0; ?></div>
                        <div class="stats-description">إجمالي الصور المصدرة</div>
                        <div class="stats-description">آخر تصدير: <?php echo isset($usage_data['last_export_date']) ? date('Y-m-d H:i', strtotime($usage_data['last_export_date'])) : 'لا يوجد'; ?></div>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-value"><?php echo $stats['video_exports'] ?? 0; ?></div>
                        <div class="stats-description">إجمالي الفيديوهات المصدرة</div>
                        <div class="stats-description">المتبقي من الحصة الشهرية: <?php echo $video_quota_remaining; ?> فيديو</div>
                    </div>
                </div>
                
                <div class="section-title">عرض المشهد ثلاثي الأبعاد</div>
                
                <div class="scene-container">
                    <div id="scene"></div>
                </div>
                
                <div class="controls">
                    <button id="capture-image" class="btn btn-success">تصدير صورة</button>
                    <button id="start-recording" class="btn btn-success">بدء تسجيل فيديو</button>
                    <button id="stop-recording" class="btn btn-danger" style="display: none;">إيقاف التسجيل</button>
                </div>
                
                <div id="export-result"></div>
            </div>
        </div>
    </div>
    
    <script>
        // إعداد PayPal
        if (document.getElementById('paypal-button-container')) {
            paypal.Buttons({
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: '9.99'
                            },
                            description: 'اشتراك مميز - شهر واحد'
                        }]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        // تسجيل المدفوعات في قاعدة البيانات
                        fetch('api/process_payment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                paymentID: details.id,
                                status: details.status,
                                amount: details.purchase_units[0].amount.value
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('تمت عملية الدفع بنجاح! سيتم تحديث حسابك.');
                                window.location.reload();
                            } else {
                                alert('حدث خطأ أثناء معالجة الدفع. يرجى المحاولة مرة أخرى.');
                            }
                        });
                    });
                }
            }).render('#paypal-button-container');
        }
        
        // إعداد المشهد ثلاثي الأبعاد
        let scene, camera, renderer, controls, model;
        let capturer = null;
        let isRecording = false;
        
        function initScene() {
            // إنشاء المشهد
            scene = new THREE.Scene();
            scene.background = new THREE.Color(0xf0f0f0);
            
            // إعداد الكاميرا
            camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 5;
            
            // إعداد العارض
            renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(document.getElementById('scene').clientWidth, document.getElementById('scene').clientHeight);
            document.getElementById('scene').appendChild(renderer.domElement);
            
            // إضافة التحكم بالمشهد
            controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.25;
            
            // إضافة الإضاءة
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);
            
            const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
            directionalLight.position.set(1, 1, 1);
            scene.add(directionalLight);
            
            // تحميل النموذج GLTF
            const loader = new THREE.GLTFLoader();
            loader.load('models/0/model.bin', function(gltf) {
                model = gltf.scene;
                model.scale.set(1, 1, 1);
                scene.add(model);
                
                // تحريك الكاميرا لتناسب النموذج
                const box = new THREE.Box3().setFromObject(model);
                const center = box.getCenter(new THREE.Vector3());
                const size = box.getSize(new THREE.Vector3());
                
                const maxDim = Math.max(size.x, size.y, size.z);
                const fov = camera.fov * (Math.PI / 180);
                let cameraZ = Math.abs(maxDim / 2 / Math.tan(fov / 2));
                
                camera.position.z = cameraZ * 1.5;
                
                // تحديث التحكم
                controls.update();
            });
            
            // تحديث الحجم عند تغيير حجم النافذة
            window.addEventListener('resize', onWindowResize);
            
            // بدء حلقة الرسم
            animate();
        }
        
        function onWindowResize() {
            camera.aspect = document.getElementById('scene').clientWidth / document.getElementById('scene').clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(document.getElementById('scene').clientWidth, document.getElementById('scene').clientHeight);
        }
        
        function animate() {
            requestAnimationFrame(animate);
            controls.update();
            
            if (isRecording && capturer) {
                capturer.capture(renderer.domElement);
            }
            
            renderer.render(scene, camera);
        }
        
        // إعداد CCapture.js
        function setupCapturer() {
            capturer = new CCapture({
                format: 'webm',
                framerate: 30,
                verbose: true
            });
        }
        
        // تصدير صورة
        document.getElementById('capture-image').addEventListener('click', function() {
            // التحقق من الحصة الشهرية
            fetch('api/check_quota.php?type=image')
                .then(response => response.json())
                .then(data => {
                    if (data.allowed) {
                        captureImage();
                    } else {
                        alert('لقد تجاوزت الحصة الشهرية للصور. يرجى الترقية إلى الاشتراك المميز.');
                    }
                });
        });
        
        function captureImage() {
            renderer.render(scene, camera);
            const dataURL = renderer.domElement.toDataURL('image/png');
            
            // عرض الصورة المصدرة
            const resultDiv = document.getElementById('export-result');
            resultDiv.innerHTML = '<div style="margin-top: 20px;"><p>تم تصدير الصورة بنجاح:</p><img src="' + dataURL + '" style="max-width: 100%; border-radius: 8px;"></div>';
            
            // تسجيل الاستخدام
            trackUsage('image', dataURL);
            
            // تنزيل الصورة
            const link = document.createElement('a');
            link.download = 'exported-image-' + Date.now() + '.png';
            link.href = dataURL;
            link.click();
        }
        
        // بدء تسجيل الفيديو
        document.getElementById('start-recording').addEventListener('click', function() {
            // التحقق من الحصة الشهرية
            fetch('api/check_quota.php?type=video')
                .then(response => response.json())
                .then(data => {
                    if (data.allowed) {
                        startVideoCapture();
                    } else {
                        alert('لقد تجاوزت الحصة الشهرية للفيديوهات. يرجى الترقية إلى الاشتراك المميز.');
                    }
                });
        });
        
        function startVideoCapture() {
            setupCapturer();
            isRecording = true;
            capturer.start();
            
            document.getElementById('start-recording').style.display = 'none';
            document.getElementById('stop-recording').style.display = 'inline-block';
        }
        
        // إيقاف تسجيل الفيديو
        document.getElementById('stop-recording').addEventListener('click', function() {
            isRecording = false;
            capturer.stop();
            
            document.getElementById('start-recording').style.display = 'inline-block';
            document.getElementById('stop-recording').style.display = 'none';
            
            // معالجة الفيديو المسجل
            capturer.save(function(blob) {
                const videoURL = URL.createObjectURL(blob);
                
                // عرض الفيديو المصدر
                const resultDiv = document.getElementById('export-result');
                resultDiv.innerHTML = '<div style="margin-top: 20px;"><p>تم تصدير الفيديو بنجاح:</p><video controls style="max-width: 100%; border-radius: 8px;"><source src="' + videoURL + '" type="video/webm"></video></div>';
                
                // تسجيل الاستخدام
                trackUsage('video', videoURL);
                
                // تنزيل الفيديو
                const link = document.createElement('a');
                link.download = 'exported-video-' + Date.now() + '.webm';
                link.href = videoURL;
                link.click();
            });
        });
        
        // تسجيل الاستخدام
        function trackUsage(type, dataUrl) {
            fetch('api/track_usage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: type,
                    size: type === 'image' ? dataUrl.length : 0
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // تحديث إحصائيات الاستخدام
                    updateUsageStats();
                }
            });
        }
        
        // تحديث إحصائيات الاستخدام
        function updateUsageStats() {
            fetch('api/get_usage_stats.php')
                .then(response => response.json())
                .then(data => {
                    // تحديث الإحصائيات في الواجهة
                    // يمكن تنفيذ هذا بشكل أكثر تفصيلاً في تطبيق حقيقي
                    window.location.reload();
                });
        }
        
        // تهيئة المشهد عند تحميل الصفحة
        window.onload = function() {
            initScene();
        };
    </script>
</body>
</html>