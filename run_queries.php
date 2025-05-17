<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Dean')) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $query_type = $_POST['query_type'];

    switch ($query_type) {
        case 'student_performance':
            $sql = "SELECT users.full_name, AVG(results.encrypted_result) AS avg_result 
                    FROM results 
                    JOIN users ON results.student_id = users.user_id 
                    GROUP BY users.full_name";
            break;
        case 'course_evaluation':
            $sql = "SELECT courses.course_name, AVG(surveyresponses.response_text) AS avg_rating 
                    FROM surveyresponses 
                    JOIN surveyquestions ON SurveyResponses.question_id = surveyquestions.question_id 
                    JOIN surveys ON surveyquestions.survey_id = surveys.survey_id 
                    JOIN courses ON surveys.course_id = courses.course_id 
                    GROUP BY courses.course_name";
            break;
        default:
            $sql = "";
            break;
    }

    if (!empty($sql)) {
        $result = $conn->query($sql);
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إجراء الاستعلامات</title>
    <link rel="stylesheet" href="s.css">
</head>
<body>
    <div class="container">
        <h1>إجراء الاستعلامات</h1>
        <form action="run_queries.php" method="POST">
            <label for="query_type">اختر نوع الاستعلام:</label>
            <select name="query_type" required>
                <option value="student_performance">أداء الطلاب</option>
                <option value="course_evaluation">تقييم المقررات</option>
            </select>
            <button type="submit">تنفيذ الاستعلام</button>
        </form>

        <?php if (isset($result) && $result->num_rows > 0) { ?>
            <h2>نتائج الاستعلام</h2>
            <table>
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>المتوسط</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['full_name'] ?? $row['course_name']; ?></td>
                            <td><?php echo number_format($row['avg_result'] ?? $row['avg_rating'], 2); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
        <div class ="button">
        <br><br><a href="logout.php" class="back-button">خروج</a></div>
    </div>
</body>
</html>