<?php
session_start();
include 'db_con.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

// جلب الأقسام والفصول الدراسية
$departments = $conn->query("SELECT * FROM Departments");
$semesters = $conn->query("SELECT * FROM Semesters");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $department_id = $_POST['department_id'];
    $semester_id = $_POST['semester_id'];
    $course_id = $_POST['course_id'];
    $num_questions = $_POST['num_questions'];

    // تخزين البيانات في الجلسة للاستخدام في الصفحة التالية
    $_SESSION['survey_data'] = [
        'department_id' => $department_id,
        'semester_id' => $semester_id,
        'course_id' => $course_id,
        'num_questions' => $num_questions
    ];

    header("Location: manage_survey_questions.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>إدارة الاستبيان</title>
    <link rel="stylesheet" href="styleM_SU.css">
    <link rel="stylesheet" href="s.css">
    <script src="jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // عند تغيير القسم
            $('#department_id').change(function() {
                var department_id = $(this).val();

                // طلب الفصول الدراسية الخاصة بالقسم
                $.ajax({
                    url: 'get_semesters.php',
                    type: 'POST',
                    data: { department_id: department_id },
                    success: function(response) {
                        $('#semester_id').html(response);
                    }
                });

                // إعادة تعيين المقررات عند تغيير القسم
                $('#course_id').html('<option value="">اختر المقرر</option>');
            });

            // عند تغيير الفصل الدراسي
            $('#semester_id').change(function() {
                var department_id = $('#department_id').val();
                var semester_id = $(this).val();

                // طلب المقررات الخاصة بالقسم والفصل الدراسي
                $.ajax({
                    url: 'get_courses.php',
                    type: 'POST',
                    data: { department_id: department_id, semester_id: semester_id },
                    success: function(response) {
                        $('#course_id').html(response);
                    }
                });
            });
        });
    </script>
</head>
<script>
    function goBack() {
        window.history.back();
    }
</script>
<body>
    <div class="container">
        <h1>إدارة الاستبيان</h1>
        <a href="setup_survey.php" class="menu-btn"> اعداد بيانات الإستبيان</a>
        <form action="manage_survey.php" method="POST">
            <select name="department_id" id="department_id" required>
                <option value="">اختر القسم</option>
                <?php while ($department = $departments->fetch_assoc()) { ?>
                    <option value="<?php echo $department['department_id']; ?>"><?php echo $department['department_name']; ?></option>
                <?php } ?>
            </select>

          
            <select name="semester_id" id="semester_id" required>
                <option value="">اختر الفصل الدراسي</option>
                <?php while ($semester = $semesters->fetch_assoc()) { ?>
                    <option value="<?php echo $semester['semester_id']; ?>"><?php echo $semester['semester_name']; ?></option>
                <?php } ?>
            </select>

           
            <select name="course_id" id="course_id" required>
                <option value="">اختر المقرر</option>
            </select>

            <label for="num_questions">عدد الأسئلة</label>
            <input type="number" name="num_questions" min="1" required>

            <button type="submit" class="menu-btn">متابعة</button>
           
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