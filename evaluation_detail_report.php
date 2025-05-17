<?php
session_start();
include 'db_connection.php';

// ... (التحقق من الصلاحيات يبقى كما هو)

$semester_id = $_GET['semester_id'];
$course_id = $_GET['course_id'];

// جلب اسم المقرر
$course_sql = "SELECT course_name FROM Courses WHERE course_id = '$course_id'";
$course_result = $conn->query($course_sql);
$course = $course_result->fetch_assoc();
$course_name = $course['course_name'];

// جلب academic_year و department_name من جدول Semesters
$semester_sql = "SELECT academic_year, department_name FROM Semesters WHERE semester_id = '$semester_id'";
$semester_result = $conn->query($semester_sql);
$semester = $semester_result->fetch_assoc();
$academic_year = $semester['academic_year'];
$department_name = $semester['department_name'];

// جلب تقييمات الإجابات
$responses_sql = "SELECT response_text 
                  FROM SurveyResponses 
                  JOIN SurveyQuestions ON SurveyResponses.question_id = SurveyQuestions.question_id 
                  JOIN Surveys ON SurveyQuestions.survey_id = Surveys.survey_id 
                  WHERE Surveys.course_id = '$course_id'";
$responses_result = $conn->query($responses_sql);

// تحويل الإجابات إلى درجات وحساب المتوسط
$total_score = 0;
$total_responses = 0;
while ($row = $responses_result->fetch_assoc()) {
    $score = mapAnswerToScore($row['response_text']);
    $total_score += $score;
    $total_responses++;
}
$avg_rating = ($total_responses > 0) ? $total_score / $total_responses : 0;

// حساب نسبة الرضا
$satisfaction_rate = ($avg_rating / 5) * 100; // أعلى درجة هي 5

// جلب نتائج الطلاب وفك التشفير
$results_sql = "SELECT encrypted_result FROM Results WHERE course_id = '$course_id'";
$results_result = $conn->query($results_sql);

$total_students = 0;
$total_score = 0;
$passed = 0;
$failed = 0;

while ($row = $results_result->fetch_assoc()) {
    $decrypted_result = decryptData($row['encrypted_result']);
    $result = intval($decrypted_result); // تحويل النتيجة إلى عدد صحيح
    
    $total_students++;
    $total_score += $result;
    
    if ($result >= 50) {
        $passed++;
    } else {
        $failed++;
    }
}

// حساب المتوسط والنسب
$avg_result = $total_students > 0 ? $total_score / $total_students : 0;
$pass_rate = $total_students > 0 ? ($passed / $total_students) * 100 : 0;
$fail_rate = $total_students > 0 ? ($failed / $total_students) * 100 : 0;

// الحصول على التاريخ الحالي وتنسيقه
$current_date = date('Y-m-d H:i:s');

// الحصول على اسم منشئ التقرير من الـ session
$report_creator = isset($_SESSION['username']) ? $_SESSION['username'] : 'غير معروف';

// إدراج البيانات أو تحديثها في الجدول course_evaluations_report
$insert_sql = "INSERT INTO course_evaluations_report (
    course_id, 
    course_name, 
    student_responses, 
    average_rating, 
    satisfaction_percentage, 
    average_student_grades, 
    success_rate, 
    failure_rate,
    academic_year, 
    department_name,
    report_date,
    report_creator
) VALUES (
    '$course_id', 
    '$course_name', 
    $total_responses, 
    $avg_rating, 
    $satisfaction_rate, 
    $avg_result, 
    $pass_rate, 
    $fail_rate,
    '$academic_year', 
    '$department_name',
    '$current_date',
    '$report_creator'
)
ON DUPLICATE KEY UPDATE
    course_name = VALUES(course_name),
    student_responses = VALUES(student_responses),
    average_rating = VALUES(average_rating),
    satisfaction_percentage = VALUES(satisfaction_percentage),
    average_student_grades = VALUES(average_student_grades),
    success_rate = VALUES(success_rate),
    failure_rate = VALUES(failure_rate),
    academic_year = VALUES(academic_year),
    department_name = VALUES(department_name),
    report_date = VALUES(report_date),
    report_creator = VALUES(report_creator)";

if ($conn->query($insert_sql)) {
    echo "<p style='color: green;'>تم حفظ أو تحديث التقرير بنجاح في قاعدة البيانات.</p>";
} else {
    echo "<p style='color: red;'>حدث خطأ أثناء حفظ أو تحديث التقرير: " . $conn->error . "</p>";
}

// دالة محسنة لتعيين الدرجات
function mapAnswerToScore($answer) {
    $answer = trim($answer);
    $score_map = [
        'ممتاز' => 5, 'جيد جدا' => 4, 'جيد' => 3,
        'ضعيف' => 2, 'سيء' => 1, 'سهل جدا' => 5, 'سهل' => 4,
        'متوسط' => 3, 'صعب' => 1, 'واضح جدا' => 5,
        'واضح' => 4, 'غير واضح' => 1, 'نعم، تماما' => 5,
        'نعم ، الى حد ما' => 3, 'نعم' => 4, 'لا' => 1,
        'متواذن جدا' => 5, 'متواذن' => 4, 'متواذن الى حد ما' => 3, 'غير متواذن' => 1,
        'محتوى المادة' => 2, 'اسلوب التدريس' => 2, 'الدعم الأكاديمي' => 3, 'لا يوجد' => 5,
        'شرح المادة' => 2, 'التفاعل مع الطلاب' => 3, 'استخدام وسائل تعليمية' => 3,
        'الإنضباط بالمواعيد' => 3, 'التفاعل مع الطلاب او استخدام وسائل تعليمية' => 3,
        'نعم، هناك بعض الموضوعات غير الضرورية' => 3, 'لا، جميع الموضوعات كانت مفيدة' => 5, 'لست متأكدا' => 2,
        'دائما' => 5, 'غالبا' => 4, 'نادرا' => 1, 'فهمت كل شئ' => 5, 'فهمت معظم الأشياء' => 4,
        'فهمت بعض الأشياء فقط' => 3, 'لم افهم' => 1, 'نعم بشكل كبير وبسهولة' => 5, 'نعم بشكل بسيط وبسهولة' => 5, 'نعم الى حد ما ولكن بصعوبة' => 3,
        'نعم تماما' => 5, 'محترم جدا وداعم' => 5, 'محترم وداعم' => 4, 'مقبول' => 3, 'غير داعم' => 1,
        'احيانا' => 2, 'نعم بشكل كاف' => 5, 'نعم ولكن ليس كافيا' => 3
    ];
    return $score_map[$answer] ?? 0;
}

// استعلام أكثر دقة لجلب البيانات
$analysis = [];
$sql = "SELECT 
            q.question_text,
            r.response_text,
            COUNT(r.response_id) as response_count
        FROM SurveyResponses r
        JOIN SurveyQuestions q ON r.question_id = q.question_id
        JOIN Surveys s ON q.survey_id = s.survey_id
        WHERE s.course_id = '$course_id'
        GROUP BY q.question_text, r.response_text";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $question = $row['question_text'];
        $response = trim($row['response_text']);
        
        if (!isset($analysis[$question])) {
            $analysis[$question] = [
                'total' => 0,
                'count' => 0,
                'responses' => []
            ];
        }
        
        $score = mapAnswerToScore($response);
        $analysis[$question]['total'] += $score * $row['response_count'];
        $analysis[$question]['count'] += $row['response_count'];
        $analysis[$question]['responses'][$response] = $row['response_count'];
    }
}

// حساب الإحصائيات
foreach ($analysis as &$item) {
    $item['average'] = $item['count'] > 0 ? $item['total'] / $item['count'] : 0;
    $item['satisfaction'] = ($item['average'] / 5) * 100;
}

// حساب الرضا العام
$total_score = 0;
$total_weight = 0;
foreach ($analysis as $item) {
    $total_score += $item['total'];
    $total_weight += $item['count'] * 5;
}
$overall_satisfaction = $total_weight > 0 ? ($total_score / $total_weight) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تقرير المقرر</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    </style>

<style>
    /* تنسيق عام للصفحة */
    body {
        font-family: Arial, sans-serif;
        direction: rtl;
        margin: 20px;
    }

    .container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
    }

    .table-container {
        margin-bottom: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    th, td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: center;
        color: rgb(83, 97, 204);
    }

    th {
        background-color: #242475;
        color: white;
    }

    .chart-container {
        width: 50%; /* تحديد العرض بنسبة 50% */
        margin: 0 auto; /* توسيط العنصر */
        margin-bottom: 20px;
    }

    canvas {
        width: 100% !important; /* التأكد من أن الرسم البياني يأخذ العرض الكامل للعنصر */
        height: auto !important;
    }

    button {
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        cursor: pointer;
        margin: 10px 0;
    }

    button:hover {
        background-color: #0056b3;
    }
    .menu-btn { 
background-color: white;
color: #0056b3;
text-decoration: none;
padding: 12px 15px;
font-size: 16px;
font-weight: bold;
border-radius: 5px;
display: flex;
align-items: center;
gap: 8px;
cursor: pointer;
transition: 0.3s;
border: none;

}

.menu-btn i {

font-size: 18px;
}

.menu-btn:hover {
background-color: #004494;
color: white;
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
.back-btn{
background-color: #007bff; /* لون الزر */
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
aside{
text-align:left;
}
#detailed-analysis{
margin-bottom:20px;
width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;       
}
.question-analysis { 
font-size:20px; padding: 10px;
        border: 1px solid #ddd;
        text-align: center;
        color: rgb(4, 4, 4);
}
.response-distribution{
font-size:20px;
background-color: #242475;
        color: white;
    }
    .logo {
    position: absolute;
    top: 10px; /* المسافة من الأعلى */
    left: 15px; /* المسافة من اليسار، غيّرها إلى 'right' إذا أردت وضعه في الزاوية اليمنى */
    width: 90px; /* حجم اللوجو */
    height: auto;
}
.title-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px; /* المسافة بين الأيقونات والعنوان */
            font-size: 18px;
            font-weight: bold;
            color: white;
            background-color: rgb(5, 41, 81);
            padding: 10px;
            border-radius: 8px;
        }

        .icon {
            font-size: 28px;
            color: gold;
    width: 90px; /* حجم اللوجو */
        }
</style>
</head>
<script>
    function goBack() {
        window.history.back();
    }
</script>
<body> 
<div class="title-container">
        <img src="nat.png" class="icon" alt="Logo"></i> <!-- أيقونة على اليسار -->
        <span> 
        <h2 style="text-align:center;">الجامعة الوطنية</h2>
        <h2  style="text-align:center;">نظام ادارة الجودة كلية علوم الحاسوب وتقنية المعلومات</h2>    
        </span>
        <img src="nat.png" class="icon" alt="Logo"></i> <!-- أيقونة على اليمين -->    
    </div>
    <div class="container">
        
        <h2>تقرير مقرر - <?php echo $course_name; ?></h2>

        <!-- تقرير شامل -->
        <div id="combined" class="table-container">
           
            <table>
                <thead>
                    <tr>
                    <th>التاريخ</th>
                    <th>منشئ التقرير</th>
                    <th>السنة الدراسية</th>
                    <th>  القسم </th>
                        <th>اسم المقرر</th>
                        <th>عدد إجابات الطلاب</th>
                        <th>متوسط التقييم</th>
                        <th>نسبة الرضا</th>
                        <th>متوسط درجات الطلاب</th>
                        <th>نسبة النجاح</th>
                        <th>نسبة الرسوب</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                    <td><?php echo date('Y-m-d'); ?></td>
                    <td><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'غير معروف'; ?></td>
                    <td><?php echo $academic_year; ?></td>
                    <td><?php echo $department_name; ?></td>
                        <td><?php echo $course_name; ?></td>
                        <td><?php echo $total_responses; ?></td>
                        <td><?php echo number_format($avg_rating, 2); ?></td>
                        <td><?php echo number_format($satisfaction_rate, 2); ?>%</td>
                        <td><?php echo number_format($avg_result, 2); ?></td>
                        <td><?php echo number_format($pass_rate, 2); ?>%</td>
                        <td><?php echo number_format($fail_rate, 2); ?>%</td>
                        
                       
                    
                    </tr>
                </tbody>
            </table>
        </div>
        <hr>
        <!-- تقرير ناتج مقارنة -->
        <div id="report" class="table-container">
            <h2>تقرير ناتج مقارنة</h2>
            <p>
                من ناتج تفاصيل الجدول اعلاه هل نجاح أو رسوب الطلاب يتوافق مع رضاهم عن هذا المقرر ؟<br>
                <?php
                if ($pass_rate >= 50 && $satisfaction_rate >= 50) {
                    echo "نعم،  حسب نسب نجاح الطلاب ومتوسط النجاح الكلي استنتج ان جميع الطلاب الناجحون كانوا راضين عن المقرر.";
                } elseif ($pass_rate < 50 && $satisfaction_rate < 50) {
                    echo "لا،حسب متوسط النجاح ونسب رسوب معظم الطلاب استنتج ان الطلاب الراسبون لم يكونو  راضين عن المقرر.";
                } else {
                    echo " لا يوجد توافق واضح بين النجاح والرضا حيث ان نسب النجاح والرسوب متقاربة مما يدل على اختلاف الأراء حول الطلاب لهذا المقرر بين الرضا وعدمه ربما يكون هنالك بعض الإخفاق  انصح بمراجعة تقييم هذا المقرر تحديدا قسم التحليل التفصيلي.";
           
                }
                ?>
            </p>
        </div>
        <hr>
        <!-- تقرير رسومي -->
        <div id="chart-analysis" class="table-container">
            <h2>التقرير رسوميًا</h2>
            <h2>تقرير مقرر - <?php echo $course_name; ?></h2>
            <div class="chart-container">
                <canvas id="satisfactionChart"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="resultsChart"></canvas>
            </div>
        </div>
       
       
        <!-- زر طباعة -->
        <button onclick="generatePDF() "class="menu-btn">طباعة التقرير</button>

        <!-- زر التحليل التفصيلي -->
        <button onclick="showTable('detailed-analysis')" class="menu-btn">تقرير تفصيلي</button>
        
    
    
        <!-- قسم التحليل التفصيلي -->
        <div id="detailed-analysis" class="table-container" style="display:none;">
            <h2>تقرير التحليل التفصيلي لكل سؤال</h2>
            <?php foreach($analysis as $question => $data): ?>
            <div class="question-analysis">
                <h3><?= htmlspecialchars($question) ?></h3>
                <p>متوسط التقييم: <?= number_format($data['average'], 2) ?></p>
                <p>نسبة الرضا: <?= number_format($data['satisfaction'], 2) ?>%</p>
              
                <h4>توزيع الإجابات:</h4>
                <table class="response-distribution">
                    <?php foreach($data['responses'] as $response => $count): ?>
                    <tr>
                        <td><?= htmlspecialchars($response) ?></td>
                        <td><?= $count ?> طالب</td>
                        <td><?= number_format(($count/$data['count'])*100, 2) ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // تعريف المتغيرات في JavaScript
        const total_responses = <?php echo $total_responses; ?>;
        const avg_rating = <?php echo $avg_rating; ?>;
        const satisfaction_rate = <?php echo $satisfaction_rate; ?>;
        const avg_result = <?php echo $avg_result; ?>;
        const pass_rate = <?php echo $pass_rate; ?>;
        const fail_rate = <?php echo $fail_rate; ?>;
        const course_name = "<?php echo $course_name; ?>";
       

        // دالة لإنشاء ملف PDF
        function generatePDF() {
            // إعطاء الوقت الكافي للرسوم البيانية لرسم نفسها
            setTimeout(() => {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                doc.setFontSize(18);
                doc.text('Course Report  ', 10, 10);

                doc.setFontSize(12);
                let y = 20;
                doc.text(`  Date: ${new Date().toLocaleDateString()}`, 10, y);
                y += 10;
                doc.text(`College: Faculty of Computer Science and Information Technology`, 10, y);
                y += 10;
                doc.text(`Batch: Batch 4`, 10, y);
                y += 10;
                

                 doc.text(`  Semester: ${getCurrentSemester()}`, 10, y);
                //doc.text(`  Semester: ${getCurrentSemester()}`, 10, y);
                y += 10;
                doc.text(`Course name  : ${course_name}`, 10, y);
                y += 20;

                doc.setFontSize(14);
                doc.text('Evaluation details  :', 10, y);
                y += 10;

                doc.setFontSize(12);
                doc.text(` Number of Student Responses : ${total_responses}`, 10, y);
                y += 10;
                doc.text(`  Evaluation Percentage: ${avg_rating.toFixed(2)}`, 10, y);
                y += 10;
                doc.text(` Satisfaction Percentage: ${satisfaction_rate.toFixed(2)}%`, 10, y);
                y += 10;
                doc.text(`    Average Student Grades: ${avg_result.toFixed(2)}`, 10, y);
                y += 10;
                doc.text(`Success Rate  : ${pass_rate.toFixed(2)}%`, 10, y);
                y += 10;
                doc.text(`  Failure Rate: ${fail_rate.toFixed(2)}%`, 10, y);

                // إضافة تقرير ناتج مقارنة
                y += 20;
                doc.setFontSize(14);
                doc.text('  Comparison Result Report  :', 10, y);
               
                y += 10;
                doc.text(`  Based on the table details above, does the students' success or failure align with their satisfaction with this course?  `, 10, y);
                y += 10;
                if (pass_rate >= 50 && satisfaction_rate >= 50) {
                    doc.text(` Yes, based on the students' success rates and the overall average success rate, I conclude that all successful students were satisfied with the course. `, 10, y);
                } else if (pass_rate < 50 && satisfaction_rate < 50) {
                    doc.text(`No, based on the average success rate and the high failure rates, I conclude that most failing students were not satisfied with the course.`, 10, y);
                } else {
                    doc.text(`   There is no clear correlation between success and satisfaction, as the success and failure rates are close, indicating differing opinions among students regarding this course. There may be some shortcomings; I recommend reviewing the evaluation of this course, specifically in the detailed analysis section .`, 10, y);
                }

                // إضافة الرسوم البيانية
                y += 20;
                doc.setFontSize(14);
                doc.text('Graphical Report :', 10, y);
                y += 10;

                // تصدير الرسوم البيانية كصور
                const satisfactionChart = document.getElementById('satisfactionChart');
                const resultsChart = document.getElementById('resultsChart');

                const satisfactionImage = satisfactionChart.toDataURL('image/png');
                const resultsImage = resultsChart.toDataURL('image/png');

                doc.addImage(satisfactionImage, 'PNG', 10, y, 180, 100);
                y += 110;
                doc.addImage(resultsImage, 'PNG', 10, y, 180, 100);

                doc.save('report.pdf');
            }, 1000); // انتظر ثانية واحدة قبل تصدير الرسوم البيانية
        }

        // دالة للحصول على الفصل الدراسي الحالي
        function getCurrentSemester() {
            const today = new Date();
            const month = today.getMonth() + 1;
            return (month >= 9 && month <= 12) ? 'الفصل الأول' : 'الفصل الثاني';
        }

        // دالة لعرض الجداول
        function showTable(tableId) {
            document.querySelectorAll('.table-container').forEach(table => {
                table.style.display = 'none';
            });
            const target = document.getElementById(tableId);
            if (target) target.style.display = 'block';
        }

        // إنشاء الرسوم البيانية
        function renderCharts() {
            // بيانات نسبة الرضا
            const satisfactionData = {
                labels: ['نسبة الرضا'],
                datasets: [{
                    label: 'نسبة الرضا',
                    data: [<?php echo $satisfaction_rate; ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            };

            // بيانات النجاح والرسوب
            const resultsData = {
                labels: ['نسبة النجاح', 'نسبة الرسوب'],
                datasets: [{
                    label: 'النتائج',
                    data: [<?php echo $pass_rate; ?>, <?php echo $fail_rate; ?>],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(255, 99, 132, 0.2)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            };

            // رسم بياني لنسبة الرضا
            const satisfactionCtx = document.getElementById('satisfactionChart').getContext('2d');
            new Chart(satisfactionCtx, {
                type: 'bar',
                data: satisfactionData,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'النسبة المئوية'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'نسبة الرضا عن المقرر'
                        }
                    }
                }
            });

            // رسم بياني للنجاح والرسوب
            const resultsCtx = document.getElementById('resultsChart').getContext('2d');
            new Chart(resultsCtx, {
                type: 'pie',
                data: resultsData,
                options: {
                    plugins: {
                        title: {
                            display: true,
                            text: 'نسبة النجاح والرسوب'
                        }
                    }
                }
            });
        }

        // عرض الرسوم البيانية عند تحميل الصفحة
        renderCharts();
    </script>
       
    </div>
    <button class="back-btn" onclick="window.history.back()">
        <i class="fa fa-arrow-right"></i> رجوع
    </button>
<a href="logout.php" class="logout-btn">
        <i class="fa fa-sign-out-alt"></i>
        <span class="logout-text">خروج</span>   
   
</body>
</html>