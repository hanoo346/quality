<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $department_name = $_POST['department_name'];

    $sql = "INSERT INTO Departments (department_name) VALUES ('$department_name')";

    if ($conn->query($sql) === TRUE) {
        $success = "تمت إضافة القسم بنجاح.";
    } else {
        $error = "حدث خطأ أثناء إضافة القسم: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>إضافة قسم</title>
    <link rel="stylesheet" href="s.css">
</head>
<script>
    function goBack() {
        window.history.back();
    }
</script>
<body>
    <div class="container">
        <h1>إضافة قسم جديد</h1>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form action="add_department.php" method="POST">
            <input type="text" name="department_name" placeholder="اسم القسم" required>
            <button type="submit"  class="menu-btn">  حفظ  </button>
        </form>
        
        <button class="back-btn" onclick="goBack()">
    <i class="fa fa-arrow-right"></i> رجوع
</button>
    </div>
    <a href="logout.php" class="logout-btn">
        <i class="fa fa-sign-out-alt"></i>
        <span class="logout-text">خروج</span>
    </a>
</body>
</html>