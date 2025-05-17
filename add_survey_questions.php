<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$survey_id = $_POST['id']; // معرف الاستبيان المحدد

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = $_POST['question_text'];

    // إضافة السؤال إلى قاعدة البيانات
    $sql = "INSERT INTO SurveyQuestions (survey_id, question_text) VALUES ('$survey_id', '$question_text')";

    if ($conn->query($sql) === TRUE) {
        $success = "تمت إضافة السؤال بنجاح.";
    } else {
        $error = "حدث خطأ أثناء إضافة السؤال: " . $conn->error;
    }
}

// جلب الأسئلة الحالية للاستبيان
$sql = "SELECT * FROM SurveyQuestions WHERE survey_id = '$survey_id'";
$questions_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة أسئلة الاستبيان</title>
    <link rel="stylesheet" href="s.css">
</head>
<body>
    <div class="container">
        <h1>إضافة أسئلة للاستبيان</h1>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

        <!-- نموذج إضافة سؤال جديد -->
        <form action="add_survey_questions.php?id=<?php echo $survey_id; ?>" method="POST">
            <label for="question_text">نص السؤال:</label>
            <textarea name="question_text" placeholder="أدخل نص السؤال" required></textarea>
            <button type="submit">إضافة السؤال</button>
        </form>

        <!-- عرض الأسئلة الحالية -->
        <h2>الأسئلة الحالية</h2>
        <table>
            <thead>
                <tr>
                    <th>نص السؤال</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $questions_result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['question_text']; ?></td>
                        <td>
                            <a href="edit_question.php?id=<?php echo $row['question_id']; ?>">تعديل</a>
                            <a href="delete_question.php?id=<?php echo $row['question_id']; ?>" onclick="return confirm('هل أنت متأكد؟')">حذف</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <a href="manage_surveys.php" class="back-button">العودة إلى إدارة الاستبيانات</a>
    </div>
</body>
</html>