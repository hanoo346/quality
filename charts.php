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

if ($query_type !== 'multiple_courses_evaluation') {
    die("خطأ: الرسوم البيانية متاحة فقط لنوع استعلام تقييمات المقررات.");
}

// استعلام عن تقييمات المقررات
$course_ids = explode(',', $query_value);
$placeholders = implode(',', array_fill(0, count($course_ids), '?'));
$sql = "SELECT course_name, student_responses, average_rating, satisfaction_percentage, 
               average_student_grades, success_rate, failure_rate 
        FROM course_evaluations_report 
        WHERE course_id IN ($placeholders)";
    
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($course_ids)), ...$course_ids);
$stmt->execute();
$result = $stmt->get_result();

// جمع البيانات للرسوم البيانية
$courses = [];
$satisfaction = [];
$success = [];
$failure = [];

while ($row = $result->fetch_assoc()) {
    $courses[] = $row['course_name'];
    $satisfaction[] = $row['satisfaction_percentage'];
    $success[] = $row['success_rate'];
    $failure[] = $row['failure_rate'];
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>الرسوم البيانية</title>
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
        .chart-container {
            width: 80%;
            margin: 30px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
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
            bottom: 20px;
            left: 20px;
        }
        .logout-btn {
            bottom: 20px;
            right: 20px;
        }
        .back-btn i, .logout-btn i {
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>الرسوم البيانية لتقييم المقررات</h1>
        
        <div class="chart-container">
            <h2>نسبة الرضا عن المقررات</h2>
            <canvas id="satisfactionChart"></canvas>
        </div>
        
        <div class="chart-container">
            <h2>نسب النجاح والرسوب في المقررات</h2>
            <canvas id="successFailureChart"></canvas>
        </div>
    </div>
    
    <a href="logout.php" class="logout-btn">
        <i class="fa fa-sign-out-alt"></i> خروج
    </a>
    <button class="back-btn" onclick="window.history.back()">
        <i class="fa fa-arrow-right"></i> رجوع
    </button>

    <script>
        // رسم بياني نسبة الرضا
        const satisfactionCtx = document.getElementById('satisfactionChart').getContext('2d');
        const satisfactionChart = new Chart(satisfactionCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($courses); ?>,
                datasets: [{
                    label: 'نسبة الرضا %',
                    data: <?php echo json_encode($satisfaction); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'النسبة المئوية'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'المقررات الدراسية'
                        }
                    }
                }
            }
        });
        
        // رسم بياني النجاح والرسوب
        const successFailureCtx = document.getElementById('successFailureChart').getContext('2d');
        const successFailureChart = new Chart(successFailureCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($courses); ?>,
                datasets: [
                    {
                        label: 'نسبة النجاح %',
                        data: <?php echo json_encode($success); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'نسبة الرسوب %',
                        data: <?php echo json_encode($failure); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'النسبة المئوية'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'المقررات الدراسية'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>