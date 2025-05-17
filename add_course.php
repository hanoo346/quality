<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

// جلب بيانات الفصول الدراسية والأقسام
$semesters = $conn->query("SELECT * FROM semesters");
$departments = $conn->query("SELECT * FROM departments");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = $_POST['course_name'];
    $semester_id = $_POST['semester_id'];
    $department_id = $_POST['department_id'];

    // إدخال المقرر الجديد
    $sql = "INSERT INTO courses (course_name, semester_id, department_id) 
            VALUES ('$course_name', '$semester_id', '$department_id')";

    if ($conn->query($sql) === TRUE) {
        $success = "تمت إضافة المقرر بنجاح.";
    } else {
        $error = "حدث خطأ أثناء إضافة المقرر: " . $conn->error;
    }
}
?>
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>إضافة مقرر</title>
    <link rel="stylesheet" href="s.css">
</head>
<script>
    function goBack() {
        window.history.back();
    }
</script>
<body>
    <div class="container">
        <h1>إضافة مقرر جديد</h1>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form action="add_course.php" method="POST">
            
            <input type="text" name="course_name" placeholder="أدخل اسم المقرر" required>

            <label for="semester_id">الفصل الدراسي</label>
            <select name="semester_id" required>
                <?php while ($semester = $semesters->fetch_assoc()) { ?>
                    <option value="<?php echo $semester['semester_id']; ?>">
                        <?php echo $semester['semester_name']; ?>
                    </option>
                <?php } ?>
            </select>

            <label for="department_id">القسم</label>
            <select name="department_id" required>
                <?php while ($department = $departments->fetch_assoc()) { ?>
                    <option value="<?php echo $department['department_id']; ?>">
                        <?php echo $department['department_name']; ?>
                    </option>
                <?php } ?>
            </select>

            <button type="submit"class="menu-btn">حفظ</button>
        </form></div>
      <a href="logout.php" class="logout-btn">
        <i class="fa fa-sign-out-alt"></i>
        <span class="logout-text">خروج</span>
    </a>
        <button class="back-btn" onclick="goBack()">
    <i class="fa fa-arrow-right"></i> رجوع
</button>
</body>
</html>