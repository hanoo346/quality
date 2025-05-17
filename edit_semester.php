<?php
session_start();
include 'db_connection.php';

// التحقق من صلاحيات المستخدم
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

// جلب بيانات الفصل الدراسي المحدد
if (isset($_GET['id'])) {
    $semester_id = $_GET['id'];
    $sql = "SELECT * FROM semesters WHERE semester_id = '$semester_id'";
    $result = $conn->query($sql);
    $semester = $result->fetch_assoc();
} else {
    header("Location: manage_semesters.php");
    exit();
}

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $semester_name = $_POST['semester_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $department_id = $_POST['department_id'];
    $department_name = $_POST['department_name'];
    $academic_year = $_POST['academic_year'];

    // تحديث البيانات في جدول الفصول الدراسية
    $sql = "UPDATE semesters 
            SET semester_name = '$semester_name', 
                start_date = '$start_date', 
                end_date = '$end_date', 
                department_id = '$department_id', 
                department_name = '$department_name', 
                academic_year = '$academic_year'
            WHERE semester_id = '$semester_id'";

    if ($conn->query($sql)) {
        echo "<p style='color: green;'>تم تحديث الفصل الدراسي بنجاح.</p>";
    } else {
        echo "<p style='color: red;'>حدث خطأ أثناء تحديث الفصل الدراسي: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل فصل دراسي</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="s.css">
</head>
<body>
    <div class="container">
        <h1>تعديل فصل دراسي</h1>
        <form action="" method="POST">
            <label for="semester_name">اسم الفصل الدراسي:</label>
            <input type="text" name="semester_name" id="semester_name" value="<?php echo $semester['semester_name']; ?>" required>

            <label for="start_date">تاريخ بداية الفصل الدراسي:</label>
            <input type="date" name="start_date" id="start_date" value="<?php echo $semester['start_date']; ?>" required>

            <label for="end_date">تاريخ نهاية الفصل الدراسي:</label>
            <input type="date" name="end_date" id="end_date" value="<?php echo $semester['end_date']; ?>" required>

            <label for="department_id">رقم القسم:</label>
            <input type="text" name="department_id" id="department_id" value="<?php echo $semester['department_id']; ?>" required>

            <label for="department_name">اسم القسم:</label>
            <input type="text" name="department_name" id="department_name" value="<?php echo $semester['department_name']; ?>" required>

            <label for="academic_year">السنة الدراسية:</label>
            <input type="text" name="academic_year" id="academic_year" value="<?php echo $semester['academic_year']; ?>" required>

            <button type="submit" class="menu-btn">تحديث</button>
        </form>
        <a href="manage_semesters.php" class="menu-btn">العودة إلى إدارة الفصول</a>
    </div>
</body>
</html>