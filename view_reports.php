<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Dean')) {
    header("Location: login.php");
    exit();
}

$semester_id = $_GET['semester_id'];
$course_ids = explode(',', $_GET['course_ids']);

// جلب بيانات التقرير
$reports = [];
foreach ($course_ids as $course_id) {
    $sql = "SELECT Courses.course_name, 
                   AVG(SurveyResponses.response_text) AS avg_rating, 
                   COUNT(DISTINCT SurveyResponses.student_id) AS students_responded, 
                   COUNT(DISTINCT StudentRegistrations.student_id) AS total_students 
            FROM SurveyResponses 
            JOIN SurveyQuestions ON SurveyResponses.question_id = SurveyQuestions.question_id 
            JOIN Surveys ON SurveyQuestions.survey_id = Surveys.survey_id 
            JOIN Courses ON Surveys.course_id = Courses.course_id 
            LEFT JOIN StudentRegistrations ON Courses.course_id = StudentRegistrations.course_id 
            WHERE Courses.course_id = '$course_id' 
            GROUP BY Courses.course_name";
    $result = $conn->query($sql);
    $reports[] = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض التقرير</title>
    <link rel="stylesheet" href="s.css">
</head>
<body>
    <div class="container">
        <h1>التقرير الشامل</h1>
        <?php foreach ($reports as $report) { ?>
            <h2><?php echo $report['course_name']; ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>متوسط التقييم</th>
                        <th>عدد الطلاب الذين قاموا بالتقييم</th>
                        <th>العدد الكلي للطلاب</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo number_format($report['avg_rating'], 2); ?></td>
                        <td><?php echo $report['students_responded']; ?></td>
                        <td><?php echo $report['total_students']; ?></td>
                    </tr>
                </tbody>
            </table>
        <?php } ?>
    </div>
</body>
</html>