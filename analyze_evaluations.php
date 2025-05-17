<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Dean')) {
    header("Location: login.php");
    exit();
}

// جلب السمسترات والمقررات التي تم تقييمها
$semesters = $conn->query("SELECT * FROM Semesters");
$courses = $conn->query("SELECT DISTINCT Courses.course_id, Courses.course_name 
                         FROM Courses 
                         JOIN Surveys ON Courses.course_id = Surveys.course_id 
                         JOIN SurveyQuestions ON Surveys.survey_id = SurveyQuestions.survey_id 
                         JOIN SurveyResponses ON SurveyQuestions.question_id = SurveyResponses.question_id");

// جلب متوسط التقييمات وعدد الطلاب الذين قاموا بالتقييم والعدد الكلي للطلاب
$statistics_sql = "SELECT Courses.course_name, 
                          AVG(SurveyResponses.response_text) AS avg_rating, 
                          COUNT(DISTINCT SurveyResponses.student_id) AS students_responded, 
                          COUNT(DISTINCT StudentRegistrations.student_id) AS total_students 
                   FROM SurveyResponses 
                   JOIN SurveyQuestions ON SurveyResponses.question_id = SurveyQuestions.question_id 
                   JOIN Surveys ON SurveyQuestions.survey_id = Surveys.survey_id 
                   JOIN Courses ON Surveys.course_id = Courses.course_id 
                   LEFT JOIN StudentRegistrations ON Courses.course_id = StudentRegistrations.course_id 
                   GROUP BY Courses.course_name";
$statistics_result = $conn->query($statistics_sql);

// جلب متوسط درجات الطلاب لكل مقرر
$grades_sql = "SELECT Courses.course_name, 
                      AVG(Results.encrypted_result) AS avg_grade 
               FROM Results 
               JOIN Courses ON Results.course_id = Courses.course_id 
               GROUP BY Courses.course_name";
$grades_result = $conn->query($grades_sql);
$grades_data = [];
while ($row = $grades_result->fetch_assoc()) {
    $grades_data[$row['course_name']] = $row['avg_grade'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $semester_id = $_POST['semester_id'];
    $course_id = $_POST['course_id'];
    header("Location: evaluation_detailss.php?semester_id=$semester_id&course_id=$course_id");
    exit();
}


 
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>تحليل التقييمات</title>
    <link rel="stylesheet" href="s.css">
    <script src="jquery-3.6.0.min.js"></script> <!-- إضافة jQuery -->
    <script>
        $(document).ready(function() {
            // عند تغيير السمستر
            $('#semester_id').change(function() {
                var semester_id = $(this).val();

                // طلب المقررات الخاصة بالسمستر
                $.ajax({
                    url: 'get_courses_by_semester.php',
                    type: 'POST',
                    data: { semester_id: semester_id },
                    success: function(response) {
                        $('#course_id').html(response);
                    }
                });
            });
        });

        function showStatistics() {
            // إظهار التقرير الإحصائي
            document.getElementById('statistics').style.display = 'block';
        }
    </script>
</head>
<script>
    function goBack() {
        window.history.back();
    }
</script>
<body>
    <div class="container">
        <h1>تحليل التقييمات</h1>
        <form action="analyze_evaluations.php" method="POST">
      
            <select name="semester_id" id="semester_id" required>
                <option value="">-- اختر السمستر --</option>
                <?php while ($semester = $semesters->fetch_assoc()) { ?>
                    <option value="<?php echo $semester['semester_id']; ?>"><?php echo $semester['semester_name']; ?></option>
                <?php } ?>
            </select>

            <select name="course_id" id="course_id" required>
                <option value="">-- اختر المقرر --</option>
            </select>

            <button type="submit"class="menu-btn">تقييم</button>
        </form>


        <button class="back-btn" onclick="goBack()">
    <i class="fa fa-arrow-right"></i> رجوع
</button>
                </div> 
<a href="logout.php" class="logout-btn">
        <i class="fa fa-sign-out-alt"></i>
        <span class="logout-text">خروج</span>
    </a>
</body>
</html>