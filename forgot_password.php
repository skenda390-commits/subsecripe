<?php
session_start();
// الاتصال بقاعدة البيانات
$db_config = include 'config/database.php';
$conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

$errors = [];
$success = false;

// معالجة نموذج نسيان كلمة المرور
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    // التحقق من البريد الإلكتروني
    if (empty($email)) {
        $errors[] = "البريد الإلكتروني مطلوب";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صالح";
    }

    // التحقق من وجود البريد الإلكتروني
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $errors[] = "البريد الإلكتروني غير مسجل";
        }
    }

    // إنشاء رمز إعادة تعيين كلمة المرور
    if (empty($errors)) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $expiry, $email);
        
        if ($stmt->execute()) {
            // في بيئة حقيقية، سيتم إرسال بريد إلكتروني هنا
            // لكن في هذا المثال، سنعرض الرابط مباشرة
            $reset_link = "http://localhost/reset_password.php?token=" . $token;
            $success = true;
        } else {
            $errors[] = "حدث خطأ أثناء إعادة تعيين كلمة المرور. يرجى المحاولة مرة أخرى.";
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
    <title>نسيت كلمة المرور</title>
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
        input[type="email"] {
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
        .reset-link {
            word-break: break-all;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>نسيت كلمة المرور</h1>
        
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
                تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.
                <!-- في بيئة تطوير فقط، نعرض الرابط مباشرة -->
                <div class="reset-link">
                    رابط إعادة التعيين: <a href="<?php echo $reset_link; ?>"><?php echo $reset_link; ?></a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">البريد الإلكتروني</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <button type="submit" class="btn">إرسال رابط إعادة التعيين</button>
            </form>
        <?php endif; ?>
        
        <div class="login-link">
            <a href="login.php">العودة إلى تسجيل الدخول</a>
        </div>
    </div>
</body>
</html>