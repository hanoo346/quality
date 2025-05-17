<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

// جلب جميع المستخدمين
$sql = "SELECT * FROM Users";
$result = $conn->query($sql);
?><!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="s.css">
</head>
<script>
    function goBack() {
        window.history.back();
    }
</script>
<body>
    <div class="container">
        <h1>إدارة المستخدمين</h1>
        <a href="add_user.php" class="menu-btn">إضافة مستخدم</a>
        <table>
            <thead>
                <tr>
                    <th>الرقم</th> <!-- العمود الجديد -->
                    <th>اسم المستخدم</th>
                    <th>الاسم الكامل</th>
                    <th>الدور</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $counter = 1; // متغير عداد لترقيم المستخدمين
                while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $counter; ?></td> <!-- عرض الرقم -->
                        <td><?php echo $row['username']; ?></td>
                        <td><?php echo $row['full_name']; ?></td>
                        <td><?php echo $row['role']; ?></td>
                        <td>
                            <a href="edit_user.php?id=<?php echo $row['user_id']; ?>"class="menu-btn">تعديل</a><br>
                            <a href="delete_user.php?id=<?php echo $row['user_id']; ?>" onclick="return confirm('هل أنت متأكد؟')"class="menu-btn">حذف</a>
                        </td>
                    </tr>
                <?php
                    $counter++; // زيادة العداد
                } ?>
            </tbody>
        </table>
       
        <a href="logout.php" class="logout-btn">
        <i class="fa fa-sign-out-alt"></i>
        <span class="logout-text">خروج</span>
    </a>
        <button class="back-btn" onclick="goBack()">
    <i class="fa fa-arrow-right"></i> رجوع
</button>
</body>
</html>