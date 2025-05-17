<?php
session_start();
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; // كلمة المرور المدخلة (نص عادي)

    // البحث عن المستخدم باستخدام اسم المستخدم فقط
    $sql = "SELECT * FROM Users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // تم العثور على المستخدم
        $user = $result->fetch_assoc();
        
        // التحقق من كلمة المرور باستخدام password_verify
        if (password_verify($password, $user['password_hash'])) {
            // كلمة المرور صحيحة
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            
            // توجيه المستخدم إلى الصفحة المناسبة بناءً على دوره
            if ($user['role'] == 'Admin') {
                header("Location: dashboard.php");
            } elseif ($user['role'] == 'Student') {
                header("Location: dashboard.php");
            } elseif ($user['role'] == 'Dean') {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            // كلمة المرور غير صحيحة
            $error = "اسم المستخدم أو كلمة المرور غير صحيحة.";
        }
    } else {
        // اسم المستخدم غير موجود
        $error = "اسم المستخدم أو كلمة المرور غير صحيحة.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
    <link rel="stylesheet" href="s.css">
    <style>
    h1, h2 {
    color: #333;
    text-align: center;
}
.login-container {
    text-align: center;
    background: rgba(255, 255, 255, 0.8);
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
}
input {
     
    border-radius: 5px;
    box-shadow: inset 2px 2px 5px rgba(0, 0, 0, 0.2);
    background-color: #f5f5f5;
}
</style>
</head>
<body>
    
    <div class="login-container">
        <h1>تسجيل الدخول</h1>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form action="login.php" method="POST">
            <input type="text" name="username" placeholder="اسم المستخدم" required><br><br>
            <input type="password" name="password" placeholder="كلمة المرور" required><br><br>
            <button type="submit">تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>