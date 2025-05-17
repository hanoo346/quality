<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'];

// جلب اسم المقرر
$course_sql = "SELECT course_name FROM Courses WHERE course_id = '$course_id'";
$course_result = $conn->query($course_sql);
$course = $course_result->fetch_assoc();
$course_name = $course['course_name'];

// التحقق مما إذا كان الطالب قد أجاب على استبيان هذا المقرر مسبقًا
$check_sql = "SELECT * FROM SurveyResponses 
              WHERE student_id = '$student_id' 
              AND question_id IN (SELECT question_id FROM SurveyQuestions 
                                  WHERE survey_id = (SELECT survey_id FROM Surveys 
                                                    WHERE course_id = '$course_id'))";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows > 0) {
    $error = "لقد قمت بالإجابة على استبيان هذا المقرر مسبقًا.";
} else {
    // جلب أسئلة الاستبيان للمقرر المحدد
    $sql = "SELECT * FROM SurveyQuestions 
            WHERE survey_id = (SELECT survey_id FROM Surveys WHERE course_id = '$course_id')";
    $questions_result = $conn->query($sql);

    if ($questions_result->num_rows == 0) {
        $error = "هذا المقرر لم يعد استبيانه بعد.";
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        foreach ($_POST['answers'] as $question_id => $answer) {
            $sql = "INSERT INTO SurveyResponses (student_id, question_id, response_text) 
                    VALUES ('$student_id', '$question_id', '$answer')";
            $conn->query($sql);
        }
        $success = "تم إرسال التقييم بنجاح.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>أسئلة استبيان  <?php echo $course_name; ?></title>
    <link rel="stylesheet" href="q_c.css">
   <style>
    
.back-btn {
    background-color: #007bff; /* لون الزر */
    color: white;
    width: 6%;
    height: 6%;
    border: none;
    padding: 10px 15px;
    font-size: 16px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: 0.3s;
    position: absolute;
    top: 20px;
    right: 20px;
}

.back-btn i {
    font-size: 18px;
}

.back-btn:hover {
    background-color: #0056b3;
}

.logout-container {
    margin-top:250px;
    position: relative;
    display: inline-block;
}

.logout-btn {
    background-color: #d9534f;
    color: white;
    text-decoration: none;
    padding: 10px;
    font-size: 16px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;  /* حجم الزر قبل الـ hover */
    height: 50px;
    overflow: hidden;
    transition: width 0.3s ease;
    margin-top:15px;
}

.logout-btn i {
    font-size: 15px;
}

.logout-text {
    display: none;
    margin-left: 8px;
    white-space: nowrap;
}

.logout-btn:hover {
    width: 90px;  /* يوسع الزر عند التمرير */
    justify-content: start;
    padding-left: 15px;
}

.logout-btn:hover .logout-text {
    display: inline;
}

/* أسلوب ترقيم الأسئلة */
.question {
    counter-increment: question-counter;
    margin-bottom: 20px;
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 8px;
}

.question p::before {
    content: "سؤال " counter(question-counter) ": ";
    font-weight: bold;
}
button { 
    padding: 10px 20px;
    margin: 5px;
    width: 15%;
    height: 14%;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

button:hover {
    background-color: #0056b3;
    transition: 0.3s;
}
    </style>
</head>
<script>
    function goBack() {
        window.history.back();
    }
</script>
<body>
    <div class="container">
        <h1 style=""><?php echo $course_name; ?>أسئلة استبيان مقرر </h1>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>

        <?php if (!isset($error) && $questions_result->num_rows > 0) { ?>
            <form action="survey_questions.php?course_id=<?php echo $course_id; ?>" method="POST">
                <?php 
                $question_number = 1; // بداية الترقيم من 1
                while ($question = $questions_result->fetch_assoc()) { ?>
                    <div class="question">
                        <p><?php echo $question['question_text']; ?></p>
                        <label>
                            <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="<?php echo $question['answer1']; ?>" required>
                            <?php echo $question['answer1']; ?>
                        </label>
                        <label>
                            <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="<?php echo $question['answer2']; ?>" required>
                            <?php echo $question['answer2']; ?>
                        </label>
                        <label>
                            <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="<?php echo $question['answer3']; ?>" required>
                            <?php echo $question['answer3']; ?>
                        </label>
                        <label>
                            <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="<?php echo $question['answer4']; ?>" required>
                            <?php echo $question['answer4']; ?>
                        </label>
                    </div>
                <?php 
                $question_number++; // زيادة العداد
                } ?>
                <button type="submit">إرسال التقييم</button>
            </form>
        <?php } ?>
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