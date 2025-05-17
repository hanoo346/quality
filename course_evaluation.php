<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// جلب المقررات المسجل بها الطالب
$sql = "SELECT Courses.course_id, Courses.course_name 
        FROM StudentRegistrations 
        JOIN Courses ON StudentRegistrations.course_id = Courses.course_id 
        WHERE StudentRegistrations.student_id = '$student_id'";
$courses_result = $conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = $_POST['course_id'];
    header("Location: survey_questions.php?course_id=$course_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>تقييم المقررات</title>
    <link rel="stylesheet" href="styleC_E.css">
    <link rel="stylesheet" href="s.css">
</head>
<script>
    function goBack() {
        window.history.back();
    }
</script>
<body><button class="back-btn" onclick="goBack()">
    <i class="fa fa-arrow-right"></i> رجوع
</button>
    <div class="container">
        <h1>تقييم المقررات</h1>
        <form action="course_evaluation.php" method="POST">
            <label for="course_id">اختر المقرر:</label>
            <select name="course_id" required>
                <?php while ($course = $courses_result->fetch_assoc()) { ?>
                    <option value="<?php echo $course['course_id']; ?>"><?php echo $course['course_name']; ?></option>
                <?php } ?>
            </select>
            <button type="submit">تقييم المقرر</button>
        </form>
    </div>
    
    <div class="logout-container">
            <a href="logout.php" class="logout-btn">
                <i class="fa fa-sign-out-alt"></i>
                <span class="logout-text">خروج</span>
            </a>
        </div>
</body>
</html>