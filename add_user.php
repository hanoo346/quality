<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
   // $password = md5($_POST['password']); // تشفير كلمة المرور باستخدام md5
   $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // تشفير كلمة المرور
    $full_name = $_POST['full_name'];
    $role = $_POST['role'];

    // التحقق من وجود اسم المستخدم في قاعدة البيانات
    $check_sql = "SELECT * FROM Users WHERE username = '$username'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        // إذا كان اسم المستخدم موجودًا بالفعل
        $error = "اسم المستخدم موجود بالفعل. الرجاء تجربة اسم مستخدم آخر.";
    } else {
        // إذا لم يكن اسم المستخدم موجودًا، قم بإدخاله
        $sql = "INSERT INTO Users (username, password_hash, full_name, role) 
                VALUES ('$username', '$password', '$full_name', '$role')";

        if ($conn->query($sql) === TRUE) {
            $success = "تمت إضافة المستخدم بنجاح.";
        } else {
            $error = "حدث خطأ أثناء إضافة المستخدم: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>إضافة مستخدم</title>
    <link rel="stylesheet" href="s.css">
</head>
<script>
    function goBack() {
        window.history.back();
    }
</script>

<body>
    <div class="container">
        <h1>إضافة مستخدم جديد</h1>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form action="add_user.php" method="POST">
            <input type="text" name="username" placeholder="اسم المستخدم" required>
            <input type="password" name="password" placeholder="كلمة المرور" required>
            <input type="text" name="full_name" placeholder="الاسم الكامل" required>
            <select name="role" required>
                <option value="Admin">مدير</option>
                <option value="Student">طالب</option>
                <option value="Dean">عميد</option>
            </select>
            <button type="submit">إضافة مستخدم</button>
        </form>
        <div>
        <button class="back-btn" onclick="goBack()">
    <i class="fa fa-arrow-right"></i> رجوع
</button></div>
    </div>
    <div class="logout-container">
    <a href="logout.php" class="logout-btn">
        <i class="fa fa-sign-out-alt"></i>
        <span class="logout-text">خروج</span>
    </a>
</div>
</body>
</html>