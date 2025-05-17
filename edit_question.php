<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$question_id = $_POST['id'];
$sql = "SELECT * FROM SurveyQuestions WHERE question_id = '$question_id'";
$result = $conn->query($sql);
$question = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = $_POST['question_text'];

    $sql = "UPDATE SurveyQuestions 
            SET question_text = '$question_text' 
            WHERE question_id = '$question_id'";

    if ($conn->query($sql) === TRUE) {
        $success = "تم تحديث السؤال بنجاح.";
    } else {
        $error = "حدث خطأ أثناء التحديث: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل سؤال</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>تعديل سؤال</h1>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form action="edit_question.php?id=<?php echo $question_id; ?>" method="POST">
            <textarea name="question_text" required><?php echo $question['question_text']; ?></textarea>
            <button type="submit">حفظ التعديلات</button>
        </form>
        <a href="add_survey_questions.php?id=<?php echo $question['survey_id']; ?>" class="back-button">العودة إلى الأسئلة</a>
    </div>
</body>
</html>