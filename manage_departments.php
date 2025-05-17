<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

// جلب جميع الأقسام
$sql = "SELECT * FROM Departments";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>إدارة الأقسام</title>
    <link rel="stylesheet" href="s.css">
</head>
<script>
    function goBack() {
        window.history.back();
    }
</script>
<body>
    <div class="container">
        <h1>إدارة الأقسام</h1>
        <a href="add_department.php" class="menu-btn">إضافة قسم</a>
        <table>
            <thead>
                <tr>
                    <th>اسم القسم</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['department_name']; ?></td>
                        <td>
                            <a href="edit_department.php?id=<?php echo $row['department_id']; ?>" class="menu-btn" >تعديل</a><br>
                            <a href="delete_department.php?id=<?php echo $row['department_id']; ?>" onclick="return confirm('هل أنت متأكد؟')" class="menu-btn">حذف</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table> </div>
        <a href="logout.php" class="logout-btn">
        <i class="fa fa-sign-out-alt"></i>
        <span class="logout-text">خروج</span>
    </a>
        <button class="back-btn" onclick="goBack()">
    <i class="fa fa-arrow-right"></i> رجوع
</button>
   
</body>
</html>