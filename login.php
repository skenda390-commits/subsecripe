<?php
session_start();
// إذا كان المستخدم مسجل دخول بالفعل، توجيه إلى الصفحة الرئيسية
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// الاتصال بقاعدة البيانات
$db_config = include 'config/database.php';
$conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// تضمين ملف التحقق من بصمة الجهاز
require_once 'includes/device_check.php';

$errors = [];
$deviceFingerprint = generateDeviceFingerprint();

// معالجة نموذج تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // التحقق من البيانات
    if (empty($email)) {
        $errors[] = "البريد الإلكتروني مطلوب";
    }

    if (empty($password)) {
        $errors[] = "كلمة المرور مطلوبة";
    }

    // التحقق من بيانات المستخدم
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // التحقق من بصمة الجهاز للمستخدمين المجانيين
                if ($user['subscription_type'] === 'free' && !validateFreeUserAccess($user['id'], $deviceFingerprint)) {
                    $errors[] = "لا يمكن تسجيل الدخول من هذا الجهاز. الاشتراك المجاني محدود بجهاز واحد فقط. يرجى الترقية إلى خطة مدفوعة للوصول من أجهزة متعددة.";
                } else {
                    // تسجيل الدخول
                    $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['subscription_type'] = $user['subscription_type'];
                
                // توجيه إلى الصفحة الرئيسية
                header('Location: dashboard.php');
                exit;
                }
            } else {
                $errors[] = "كلمة المرور غير صحيحة";
            }
        } else {
            $errors[] = "البريد الإلكتروني غير مسجل";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            text-align: center;
            color: #182949;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn {
            display: inline-block;
            background-color: #182949;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background-color: #0d1b36;
        }
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .register-link, .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a, .forgot-password a {
            color: #182949;
            text-decoration: none;
        }
        .register-link a:hover, .forgot-password a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>تسجيل الدخول</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">تسجيل الدخول</button>
        </form>
        
        <div class="forgot-password">
            <a href="forgot_password.php">نسيت كلمة المرور؟</a>
        </div>
        
        <div class="register-link">
            ليس لديك حساب؟ <a href="register.php">تسجيل حساب جديد</a>
        </div>
    </div>
</body>
</html>