<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

// جلب جميع المقررات مع تفاصيل الأقسام والفصول الدراسية
$sql = "SELECT Courses.course_id, Courses.course_name, Departments.department_name, Semesters.semester_name 
        FROM Courses 
        JOIN Departments ON Courses.department_id = Departments.department_id 
        JOIN Semesters ON Courses.semester_id = Semesters.semester_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>إدارة المقررات</title>
    <link rel="stylesheet" href="s.css">
    <style>
   
    </style>
<script>
    function goBack() {
        window.history.back();
    }
</script>
</head>
<body>
    <div class="container">
        <h1>إدارة المقررات</h1>
        <a href="add_course.php" class="menu-btn">إضافة مقرر</a>
        <table>
            <thead>
                <tr>
                    <th>اسم المقرر</th>
                    <th>القسم</th>
                    <th>الفصل الدراسي</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['course_name']; ?></td>
                        <td><?php echo $row['department_name']; ?></td>
                        <td><?php echo $row['semester_name']; ?></td>
                        <td>
                            <a href="edit_courses.php?id=<?php echo $row['course_id']; ?>"class="menu-btn">تعديل</a><br>
                            <a href="delete_courses.php?id=<?php echo $row['course_id']; ?>" onclick="return confirm('هل أنت متأكد؟')" class="menu-btn">حذف</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <a href="logout.php" class="logout-btn">
        <i class="fa fa-sign-out-alt"></i>
        <span class="logout-text">خروج</span>
    </a>
        <button class="back-btn" onclick="goBack()">
    <i class="fa fa-arrow-right"></i> رجوع
</button>
</body>
</html>