<?php
session_start();
include 'db_connection.php';

// التحقق من صلاحيات المستخدم
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Dean')) {
    header("Location: login.php");
    exit();
}

// جلب قائمة الطلاب
$students = $conn->query("SELECT * FROM users WHERE role = 'Student'");

// جلب قائمة المقررات التي لها نتائج
$courses_with_results = $conn->query("SELECT DISTINCT c.course_id, c.course_name 
                                    FROM courses c
                                    JOIN Results r ON c.course_id = r.course_id");

// معالجة طلب الاستعلام
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['request_query'])) {
        $query_type = $_POST['query_type'];
        $query_value = $_POST['query_value'];

        // معالجة اختيار "All" لخيار أداء الطلاب في المقرر
        if ($query_type === 'course_students_performance' && is_array($query_value) && in_array('all', $query_value)) {
            $all_courses = $conn->query("SELECT DISTINCT course_id FROM Results");
            $query_value = [];
            while ($course = $all_courses->fetch_assoc()) {
                $query_value[] = $course['course_id'];
            }
        }
        // معالجة اختيار "All" لخيار الأداء العام للمقررات
        elseif ($query_type === 'multiple_courses_evaluation' && is_array($query_value) && in_array('all', $query_value)) {
            $all_courses = $conn->query("SELECT course_id FROM courses");
            $query_value = [];
            while ($course = $all_courses->fetch_assoc()) {
                $query_value[] = $course['course_id'];
            }
        }

        // التحقق من وجود القيم المطلوبة
        if (empty($query_type) || empty($query_value)) {
            die("خطأ: لم يتم توفير نوع الاستعلام أو القيمة المطلوبة.");
        }

        // توجيه المستخدم إلى الصفحة المناسبة
        $redirect_url = "view_query.php?query_type=" . urlencode($query_type);
        
        if (is_array($query_value)) {
            $redirect_url .= "&query_value=" . urlencode(implode(',', $query_value));
        } else {
            $redirect_url .= "&query_value=" . urlencode($query_value);
        }

        header("Location: " . $redirect_url);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>إنشاء التقارير</title>
    <link rel="stylesheet" href="s.css">
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }
        select, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        select[multiple] {
            height: 150px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        .logout-btn, .back-btn {
            position: fixed;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .back-btn {
            bottom: 90%;
           width:8%;
           height:6%
        }
        .logout-btn {
            bottom: 20px;
            left: 20px;
        }
        .back-btn i {
    font-size: 18px;
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
        <form action="" method="POST">
            <h2 style=" color: #000;">طلب استعلام</h2>
            <label for="query_type">اختر نوع الاستعلام:</label>
            <select name="query_type" id="query_type" required>
                <option value="student_performance">أداء طالب معين</option>
                <option value="multiple_courses_evaluation">الأداء العام للمقرر/ المقررات</option>
                <option value="course_students_performance">أداء الطلاب في المقرر /المقررات</option>
            </select>

            <div id="query_value_container">
                <!-- سيتم ملء هذا القسم بناءً على نوع الاستعلام -->
            </div>

            <button type="submit" name="request_query">طلب استعلام</button>
        </form>
        
    </div>

    <script>
        document.getElementById('query_type').addEventListener('change', function() {
            const queryType = this.value;
            const container = document.getElementById('query_value_container');
            let html = '';

            if (queryType === 'student_performance') {
                html = '<label for="query_value">اختر الطالب:</label>' +
                       '<select name="query_value" required>' +
                       '<?php $students->data_seek(0); while ($student = $students->fetch_assoc()) { ?>' +
                       '<option value="<?php echo $student["user_id"]; ?>"><?php echo $student["full_name"]; ?></option>' +
                       '<?php } ?>' +
                       '</select>';
            } 
            else if (queryType === 'multiple_courses_evaluation') {
                html = '<label for="query_value">اختر المقررات:</label>' +
                       '<select name="query_value[]" multiple required>' +
                       '<?php $courses_with_results->data_seek(0); while ($course = $courses_with_results->fetch_assoc()) { ?>' +
                       '<option value="<?php echo $course["course_id"]; ?>"><?php echo $course["course_name"]; ?></option>' +
                       '<?php } ?>' +
                       '<option value="all">الكل</option>' +
                       '</select>';
            }
            else if (queryType === 'course_students_performance') {
                html = '<label for="query_value">اختر المقرر:</label>' +
                       '<select name="query_value[]" multiple required>' +
                       '<?php $courses_with_results->data_seek(0); while ($course = $courses_with_results->fetch_assoc()) { ?>' +
                       '<option value="<?php echo $course["course_id"]; ?>"><?php echo $course["course_name"]; ?></option>' +
                       '<?php } ?>' +
                       '<option value="all">الكل</option>' +
                       '</select>';
            }

            container.innerHTML = html;
        });

        // تهيئة الحقل عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('query_type').dispatchEvent(new Event('change'));
        });
    </script>
<button class="back-btn" onclick="window.history.back()">
        <i class="fa fa-arrow-right"></i> رجوع
    </button>
<a href="logout.php" class="logout-btn" style="   background-color:rgb(218, 69, 28);">
        <i class="fa fa-sign-out-alt"></i> خروج
    </a>

</body>
</html>