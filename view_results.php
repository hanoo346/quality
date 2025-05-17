<?php
session_start();
require_once 'db_con.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// جلب نتائج الطالب مع التحقق من وجود إجابات في جدول responses
$sql = "SELECT c.course_name, r.encrypted_result 
        FROM Results r
        JOIN Courses c ON r.course_id = c.course_id 
        WHERE r.student_id = '$student_id'
        AND EXISTS (
            SELECT 1 FROM surveyresponses res
            JOIN surveyquestions q ON res.question_id = q.question_id
            JOIN surveys s ON q.survey_id = s.survey_id
            WHERE res.student_id = r.student_id 
            AND s.course_id = r.course_id
        )";
$result = $conn->query($sql);

// التحقق من وجود أخطاء في الاستعلام
if (!$result) {
    die("خطأ في استعلام قاعدة البيانات: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>عرض النتائج</title>
    <link rel="stylesheet" href="s.css">
    <style>
        .no-results {
            text-align: center;
            padding: 20px;
            font-size: 18px;
            color: #fff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <button class="back-btn" onclick="goBack()">
        <i class="fa fa-arrow-right"></i> رجوع
    </button>
    
    <div class="container">
        <h1>نتائج الاستبيانات</h1>
        
        <?php if ($result->num_rows > 0) { ?>
            <table>
                <thead>
                    <tr>
                        <th>اسم المقرر</th>
                        <th>النتيجة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(decryptData($row['encrypted_result']), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <div class="no-results">
                <p>لا توجد نتائج متاحة للعرض</p>
                <p>لم تقم بالإجابة على أي استبيان بعد، أو لم يتم رصد النتائج بعد</p>
            </div>
        <?php } ?>
    </div>
    
    <div class="logout-container">
        <a href="logout.php" class="logout-btn">
            <i class="fa fa-sign-out-alt"></i>
            <span class="logout-text">تسجيل الخروج</span>
        </a>
    </div>

    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>