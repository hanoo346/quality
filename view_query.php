<?php
session_start();
include 'db_connection.php';

// التحقق من صلاحيات المستخدم
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Dean')) {
    header("Location: login.php");
    exit();
}

// التحقق من وجود query_type و query_value في $_GET
if (!isset($_GET['query_type']) || !isset($_GET['query_value'])) {
    die("خطأ: لم يتم توفير نوع الاستعلام أو القيمة المطلوبة.");
}

$query_type = $_GET['query_type'];
$query_value = $_GET['query_value'];

// تهيئة المتغير $result
$result = null;

if ($query_type === 'student_performance') {
    // استعلام عن أداء طالب معين
    $stmt = $conn->prepare("SELECT c.course_name, r.encrypted_result 
                          FROM Results r
                          JOIN Courses c ON r.course_id = c.course_id 
                          WHERE r.student_id = ?");
    $stmt->bind_param("s", $query_value);
    $stmt->execute();
    $result = $stmt->get_result();

} elseif ($query_type === 'multiple_courses_evaluation') {
    // استعلام عن تقييمات عدد من المقررات
    $course_ids = explode(',', $query_value);

    // إنشاء علامات استفهام للاستعلام المعد
    $placeholders = implode(',', array_fill(0, count($course_ids), '?'));
    $sql = "SELECT course_name, student_responses, average_rating, satisfaction_percentage, 
                   average_student_grades, success_rate, failure_rate 
            FROM course_evaluations_report 
            WHERE course_id IN ($placeholders)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($course_ids)), ...$course_ids);
    $stmt->execute();
    $result = $stmt->get_result();

} elseif ($query_type === 'course_students_performance') {
    // استعلام عن أداء جميع الطلاب في مقرر معين
    $course_ids = explode(',', $query_value);

    // إنشاء علامات استفهام للاستعلام المعد
    $placeholders = implode(',', array_fill(0, count($course_ids), '?'));
    $sql = "SELECT c.course_name, u.full_name AS student_name, r.encrypted_result 
            FROM Results r
            JOIN Users u ON r.student_id = u.user_id
            JOIN Courses c ON r.course_id = c.course_id
            WHERE r.course_id IN ($placeholders) AND u.role = 'Student'
            ORDER BY c.course_name, u.full_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($course_ids)), ...$course_ids);
    $stmt->execute();
    $result = $stmt->get_result();

} else {
    die("خطأ: نوع الاستعلام غير صحيح.");
}

// التحقق من وجود نتائج قبل عرضها
if (!$result) {
    die("خطأ: لم يتم العثور على نتائج.");
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>عرض الاستعلام</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: right;
            border: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #e9e9e9;
        }
        .no-results {
            text-align: center;
            font-size: 18px;
            color: #666;
            margin: 30px 0;
        }
        .logout-btn, .back-btn {
            position: fixed;
            padding: 10px 15px;
            background-color:rgb(202, 56, 56);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .chart-btn{
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
    background-color: #007bff; /* لون الزر */
    color: white;
    border: none;
}

.back-btn i {
    font-size: 18px;
}

.back-btn:hover {
    background-color: #0056b3;
}
        .back-btn {
            bottom: 90%;
           
        }
        .logout-btn {
            bottom: 20px;
            left: 20px;
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
        <h1>نتيجة الاستعلام اوالتحليل الإحصائي</h1>
        <?php if ($result->num_rows > 0) { ?>
            <table>
                <thead>
                    <tr>
                        <?php if ($query_type === 'student_performance') { ?>
                            <th>اسم المقرر</th>
                            <th>النتيجة</th>
                        <?php } elseif ($query_type === 'multiple_courses_evaluation') { ?>
                            <th>اسم المقرر</th>
                            <th>عدد إجابات الطلاب</th>
                            <th>متوسط التقييم</th>
                            <th>نسبة الرضا</th>
                            <th>متوسط درجات الطلاب</th>
                            <th>نسبة النجاح</th>
                            <th>نسبة الرسوب</th>
                        <?php } elseif ($query_type === 'course_students_performance') { ?>
                            <th>اسم المقرر</th>
                            <th>اسم الطالب</th>
                            <th>النتيجة</th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <?php if ($query_type === 'student_performance') { ?>
                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                <td><?php echo decryptData($row['encrypted_result']); ?></td>
                            <?php } elseif ($query_type === 'multiple_courses_evaluation') { ?>
                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                <td><?php echo $row['student_responses']; ?></td>
                                <td><?php echo number_format($row['average_rating'], 2); ?></td>
                                <td><?php echo number_format($row['satisfaction_percentage'], 2); ?>%</td>
                                <td><?php echo number_format($row['average_student_grades'], 2); ?></td>
                                <td><?php echo number_format($row['success_rate'], 2); ?>%</td>
                                <td><?php echo number_format($row['failure_rate'], 2); ?>%</td>
                            <?php } elseif ($query_type === 'course_students_performance') { ?>
                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo decryptData($row['encrypted_result']); ?></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="no-results">لا توجد نتائج لعرضها.</p>
        <?php } ?>
    </div> 
    </button>

        <a href="charts.php?query_type=<?php echo $query_type; ?>&query_value=<?php echo urlencode($query_value); ?>" class="chart-btn">
            <i class="fa fa-chart-bar"></i> رسم بياني
    </a>
    <a href="logout.php" class="logout-btn">
        <i class="fa fa-sign-out-alt"></i> خروج
    </a>
    <button class="back-btn" onclick="window.history.back()">
        <i class="fa fa-arrow-right"></i> رجوع
    </button>
   
</body>
</html>