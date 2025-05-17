<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$registration_id = $_GET['id'];

// جلب بيانات التسجيل الحالية
$sql = "SELECT * FROM StudentRegistrations WHERE registration_id = '$registration_id'";
$result = $conn->query($sql);
$registration = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];

    $sql = "UPDATE StudentRegistrations 
            SET student_id = '$student_id', course_id = '$course_id' 
            WHERE registration_id = '$registration_id'";
    if ($conn->query($sql) === TRUE) {
        $success = "تم تحديث التسجيل بنجاح.";
    } else {
        $error = "حدث خطأ أثناء التحديث: " . $conn->error;
    }
}

// جلب الطلاب والمقررات
$students = $conn->query("SELECT * FROM users WHERE role = 'Student'");
$courses = $conn->query("SELECT * FROM courses");
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل التسجيل</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>تعديل تسجيل الطالب</h1>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form action="edit_registration.php?id=<?php echo $registration_id; ?>" method="POST">
            <label for="student_id">اختر الطالب:</label>
            <select name="student_id" required>
                <?php while ($student = $students->fetch_assoc()) { ?>
                    <option value="<?php echo $student['user_id']; ?>" <?php if ($student['user_id'] == $registration['student_id']) echo 'selected'; ?>>
                        <?php echo $student['full_name']; ?>
                    </option>
                <?php } ?>
            </select>
            <label for="course_id">اختر المقرر:</label>
            <select name="course_id" required>
                <?php while ($course = $courses->fetch_assoc()) { ?>
                    <option value="<?php echo $course['course_id']; ?>" <?php if ($course['course_id'] == $registration['course_id']) echo 'selected'; ?>>
                        <?php echo $course['course_name']; ?>
                    </option>
                <?php } ?>
            </select>
            <button type="submit">حفظ التعديلات</button>
        </form>
    </div>
</body>
</html>