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
$success = false;

// الحصول على بصمة الجهاز وعنوان IP
$deviceFingerprint = generateDeviceFingerprint();
$ipAddress = getRealIpAddress();

// معالجة نموذج التسجيل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // التحقق من البيانات
    if (empty($username)) {
        $errors[] = "اسم المستخدم مطلوب";
    } elseif (strlen($username) < 3) {
        $errors[] = "اسم المستخدم يجب أن يكون 3 أحرف على الأقل";
    }

    if (empty($email)) {
        $errors[] = "البريد الإلكتروني مطلوب";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صالح";
    }

    if (empty($password)) {
        $errors[] = "كلمة المرور مطلوبة";
    } elseif (strlen($password) < 6) {
        $errors[] = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
    }

    if ($password !== $confirm_password) {
        $errors[] = "كلمات المرور غير متطابقة";
    }
    
    // التحقق من عدد الاشتراكات المجانية لهذا الجهاز/عنوان IP
    if (!canCreateFreeAccount($deviceFingerprint, $ipAddress)) {
        $errors[] = "لقد تجاوزت الحد الأقصى للحسابات المجانية المسموح بها لكل جهاز/عنوان IP (" . MAX_FREE_ACCOUNTS_PER_DEVICE . " حسابات). يرجى الترقية إلى خطة مدفوعة.";
    }

    // التحقق من وجود اسم المستخدم أو البريد الإلكتروني
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل";
        }
    }

    // إنشاء المستخدم إذا لم تكن هناك أخطاء
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $subscription_type = 'free'; // الاشتراك المجاني افتراضيًا
        $query = "INSERT INTO users (username, email, password, subscription_type, device_fingerprint, registration_ip, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssss", $username, $email, $hashed_password, $subscription_type, $deviceFingerprint, $ipAddress);
        
        if ($stmt->execute()) {
            $success = true;
            $_SESSION['registration_success'] = true;
            header('Location: login.php');
            exit;
        } else {
            $errors[] = "حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.";
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
    <title>تسجيل حساب جديد</title>
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
        input[type="text"],
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
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #182949;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>تسجيل حساب جديد</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                تم التسجيل بنجاح! جاري تحويلك...
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">تأكيد كلمة المرور</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">تسجيل</button>
        </form>
        
        <div class="login-link">
            لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a>
        </div>
    </div>
</body>
</html>