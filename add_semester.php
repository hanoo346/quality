<?php
session_start();
include 'db_connection.php';

// التحقق من صلاحيات المستخدم
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

// جلب بيانات الأقسام من جدول departments
$departments_sql = "SELECT department_id, department_name FROM departments";
$departments_result = $conn->query($departments_sql);

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $semester_name = $_POST['semester_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $department_id = $_POST['department_id'];
    $academic_year = $_POST['academic_year'];

    // جلب اسم القسم بناءً على department_id
    $department_sql = "SELECT department_name FROM departments WHERE department_id = '$department_id'";
    $department_result = $conn->query($department_sql);
    $department = $department_result->fetch_assoc();
    $department_name = $department['department_name'];

    // إدخال البيانات في جدول الفصول الدراسية
    $sql = "INSERT INTO semesters (semester_name, start_date, end_date, department_id, department_name, academic_year)
            VALUES ('$semester_name', '$start_date', '$end_date', '$department_id', '$department_name', '$academic_year')";

    if ($conn->query($sql)) {
        echo "<p style='color: green;'>تمت إضافة الفصل الدراسي بنجاح.</p>";
    } else {
        echo "<p style='color: red;'>حدث خطأ أثناء إضافة الفصل الدراسي: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة فصل دراسي جديد</title>
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
        <h1>إضافة فصل دراسي جديد</h1>
        <form action="" method="POST">
            <label for="semester_name">اسم الفصل الدراسي</label>
            <input type="text" name="semester_name" id="semester_name" required>

            <label for="start_date">تاريخ بداية الفصل الدراسي</label>
            <input type="date" name="start_date" id="start_date" required>

            <label for="end_date">تاريخ نهاية الفصل الدراسي</label>
            <input type="date" name="end_date" id="end_date" required>

          
            <select name="department_id" id="department_id" required>
                <option value="">-- اختر القسم --</option>
                <?php while ($department = $departments_result->fetch_assoc()) { ?>
                    <option value="<?php echo $department['department_id']; ?>">
                        <?php echo $department['department_name']; ?> (رقم القسم: <?php echo $department['department_id']; ?>)
                    </option>
                <?php } ?>
            </select>

            <label for="academic_year">السنة الدراسية</label>
            <input type="text" name="academic_year" id="academic_year" required>

            <button type="submit" class="menu-btn">إضافة</button>
        </form>
        
    </div>
    <a href="logout.php" class="logout-btn">
        <i class="fa fa-sign-out-alt"></i>
        <span class="logout-text">خروج</span>
    </a>
        <button class="back-btn" onclick="goBack()">
    <i class="fa fa-arrow-right"></i> العودة إلى إدارة الفصول</button>
</body>
</html>