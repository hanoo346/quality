<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

// جلب بيانات الاستبيان من الجلسة
if (!isset($_SESSION['survey_data'])) {
    header("Location: manage_survey.php");
    exit();
}

$survey_data = $_SESSION['survey_data'];
$course_id = $survey_data['course_id'];
$num_questions = $survey_data['num_questions'];

// جلب جميع الاستبيانات المتاحة
$surveys = $conn->query("SELECT * FROM Surveys");

// جلب الأسئلة الحالية إذا وجدت
$sql = "SELECT * FROM SurveyQuestions WHERE survey_id IN (SELECT survey_id FROM Surveys WHERE course_id = '$course_id')";
$questions_result = $conn->query($sql);
$existing_questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $existing_questions[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['import_questions'])) {
        // استيراد الأسئلة من استبيان آخر
        $import_survey_id = $_POST['import_survey_id'];
        $sql = "SELECT * FROM SurveyQuestions WHERE survey_id = '$import_survey_id'";
        $import_result = $conn->query($sql);
        $existing_questions = [];
        while ($row = $import_result->fetch_assoc()) {
            $existing_questions[] = $row;
        }
    } elseif (isset($_POST['save_questions'])) {
        // حذف الأسئلة القديمة إذا وجدت
        $conn->query("DELETE FROM SurveyQuestions WHERE survey_id IN (SELECT survey_id FROM Surveys WHERE course_id = '$course_id')");

        // إضافة الأسئلة الجديدة
        foreach ($_POST['questions'] as $question) {
            $question_text = $question['text'];
            $answer1 = $question['answer1'];
            $answer2 = $question['answer2'];
            $answer3 = $question['answer3'];
            $answer4 = $question['answer4'];

            // إضافة السؤال إلى قاعدة البيانات
            $sql = "INSERT INTO SurveyQuestions (survey_id, question_text, answer1, answer2, answer3, answer4) 
                    VALUES ((SELECT survey_id FROM Surveys WHERE course_id = '$course_id'), '$question_text', '$answer1', '$answer2', '$answer3', '$answer4')";
           
           $conn->query($sql);
        }
        $success = "تم حفظ الأسئلة بنجاح.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>إدارة أسئلة الاستبيان</title>
    <link rel="stylesheet" href="s.css">
    <style>
        
        form.hhhh {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
    width: 80%;
    margin: auto;
}

.question {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.question label {
    font-size: 18px; /* تكبير حجم السؤال */
    font-weight: bold; /* جعل النص عريض */
    color: #333;
}

.question textarea {
    width: 100%;
    height: 60px;
    padding: 8px;
    border-radius: 5px;
    border: 1px solid #ccc;
    resize: none;
    font-size: 18px;
    font-weight: bold;
}

.question input[type="text"] {
    width: 100%;
    padding: 8px;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 16px;
}

.question input[type="text"]:focus,
.question textarea:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}

button.submit-btn {
    background-color: #007bff;
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    border: none;
    font-size: 16px;
    margin-top: 10px;
    display: block;
    width: 100%;
}

button.submit-btn:hover {
    background-color: #0056b3;
}
    </style>
</head>
<script>
    function goBack() {
        window.history.back();
    }
</script>
<body><a href="manage_survey.php">رجوع</a>
    <div class="container">
        <h1>إدارة أسئلة الاستبيان</h1>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>

        <!-- زر استيراد الأسئلة -->
        <form action="manage_survey_questions.php" method="POST">
            <label for="import_survey_id">استيراد الأسئلة من استبيان آخر:</label>
            <select name="import_survey_id" required>
                <option value="">اختر استبيان</option>
                <?php while ($survey = $surveys->fetch_assoc()) { ?>
                    <option value="<?php echo $survey['survey_id']; ?>"><?php echo $survey['survey_name']; ?></option>
                <?php } ?>
            </select>
            <button type="submit" name="import_questions">استيراد الأسئلة</button>
        </form>

        <!-- نموذج الأسئلة -->
        <form action="manage_survey_questions.php" method="POST" class="hhhh">
            <?php for ($i = 0; $i < $num_questions; $i++) { ?>
                <div class="question" style="font-size: 15px;">
                    <div>
                        <label for="questions[<?php echo $i; ?>][text]">السؤال <?php echo $i + 1; ?>:</label>
                        <textarea name="questions[<?php echo $i; ?>][text]" required><?php echo $existing_questions[$i]['question_text'] ?? ''; ?></textarea>
                    </div>
                    <label for="questions[<?php echo $i; ?>][answer1]">الإجابة الأولى:</label>
                    <input type="text" name="questions[<?php echo $i; ?>][answer1]" value="<?php echo $existing_questions[$i]['answer1'] ?? ''; ?>" required>

                    <label for="questions[<?php echo $i; ?>][answer2]">الإجابة الثانية:</label>
                    <input type="text" name="questions[<?php echo $i; ?>][answer2]" value="<?php echo $existing_questions[$i]['answer2'] ?? ''; ?>" required>

                    <label for="questions[<?php echo $i; ?>][answer3]">الإجابة الثالثة:</label>
                    <input type="text" name="questions[<?php echo $i; ?>][answer3]" value="<?php echo $existing_questions[$i]['answer3'] ?? ''; ?>" required>

                    <label for="questions[<?php echo $i; ?>][answer4]">الإجابة الرابعة:</label>
                    <input type="text" name="questions[<?php echo $i; ?>][answer4]" value="<?php echo $existing_questions[$i]['answer4'] ?? ''; ?>" >
                </div>
            <?php } ?>
            <button type="submit" name="save_questions">حفظ الأسئلة</button>
           
        </form>
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