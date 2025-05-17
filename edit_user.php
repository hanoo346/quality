<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_GET['id'];
$sql = "SELECT * FROM Users WHERE user_id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $role = $_POST['role'];
    //$password = md5($_POST['password']); // تشفير كلمة المرور باستخدام md5
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // تشفير كلمة المرور
    // تحديث بيانات المستخدم
    $sql = "UPDATE Users 
            SET username = '$username', 
                full_name = '$full_name', 
                role = '$role', 
                password_hash = '$password' 
            WHERE user_id = '$user_id'";

    if ($conn->query($sql) === TRUE) {
        $success = "تم تحديث بيانات المستخدم بنجاح.";
    } else {
        $error = "حدث خطأ أثناء التحديث: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل مستخدم</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>تعديل بيانات المستخدم</h1>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form action="edit_user.php?id=<?php echo $user_id; ?>" method="POST">
            <label for="username">اسم المستخدم:</label>
            <input type="text" name="username" value="<?php echo $user['username']; ?>" required>

            <label for="full_name">الاسم الكامل:</label>
            <input type="text" name="full_name" value="<?php echo $user['full_name']; ?>" required>

            <label for="password">كلمة المرور:</label>
            <input type="password" name="password" placeholder="أدخل كلمة المرور الجديدة" required>

            <label for="role">الدور:</label>
            <select name="role" required>
                <option value="Admin" <?php if ($user['role'] == 'Admin') echo 'selected'; ?>>مدير</option>
                <option value="Student" <?php if ($user['role'] == 'Student') echo 'selected'; ?>>طالب</option>
                <option value="Dean" <?php if ($user['role'] == 'Dean') echo 'selected'; ?>>عميد</option>
            </select>

            <button type="submit">حفظ التعديلات</button>
        </form>
        <a href="manage_users.php" class="back-button">العودة إلى إدارة المستخدمين</a>
    </div>
</body>
</html>