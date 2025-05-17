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
// ثوابت التشفير

/**
 * فك تشفير النص باستخدام AES-256
 */
if (!function_exists('decryptData')) {
    function decryptData($data) {
        $data = base64_decode($data);
        $iv_length = openssl_cipher_iv_length(CIPHER_METHOD);
        $iv = substr($data, 0, $iv_length); // استخراج IV
        $encrypted = substr($data, $iv_length); // استخراج النص المشفر
        return openssl_decrypt($encrypted, CIPHER_METHOD, ENCRYPTION_KEY, 0, $iv);
    }
}

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

// جلب بيانات المقرر
$course_name = "غير معروف";
$course_sql = "SELECT course_name FROM Courses WHERE course_id = '$course_id'";
if($course_result = $conn->query($course_sql)){
    if($course_result->num_rows > 0){
        $course = $course_result->fetch_assoc();
        $course_name = $course['course_name'];
    }
}

// دالة محسنة لتعيين الدرجات
function mapAnswerToScore($answer) {
    $answer = trim($answer);
    $score_map = [
        'ممتاز' => 5,      'جيد جدا' => 4,    'جيد' => 3,
        'ضعيف' => 2,      'سيء' => 1,        '  سهل جدا' => 5,'سهل ' => 4,
        'متوسط' => 3,     'صعب' => 1,        'واضح جدا' => 5,
        'واضح' => 4,      'غير واضح' => 1,    'نعم، تماما' => 5,
        'نعم ، الى حد ما' => 3,    'نعم' => 4,            'لا' => 1,'ضعيف ' => 1,
        'متواذن جدا' => 5, 'متواذن ' => 4,'متواذن الى حد ما' => 3, 'غير متواذن' => 1, 
        'محتوى المادة' =>2,'اسلوب التدريس'=>2,'الدعم الأكاديمي'=>3,'لا يوجد' => 5,
        'شرح المادة'=>2,'ضعيفة'=>2,'مقبولة'=>3,'ممتازة'=>5,
        'التفاعل مع الطلاب'=>3,
        'استخدام وسائل تعليمية'=>3,
        'الإنضباط بالمواعيد'=>3, 'التفاعل مع الطلاب او استخدام وسائل تعليمية' => 3,
        'نعم، هناك بعض الموضوعات غير الضرورية ' => 3,'لا، جميع الموضوعات كانت مفيدة' => 5,'لست متأكدا' => 2,
              'دائما' => 5,      'غالبا' => 4,'نادرا' => 1,'فهمت كل شئ' => 5,'فهمت معظم الأشياء' => 4,
        'فهمت بعض الأشياء فقط' => 3,'لم افهم' => 1,'نعم بشكل كبير وبسهولة' => 5,'نعم بشكل بسيط وبسهولة' => 5,'نعم الى حد ما ولكن بصعوبة' => 3,
        'نعم تماما' => 5,'محترم جدا وداعم' => 5,'محترم وداعم' => 4,'مقبول ' => 3,'غير داعم' => 1,
        'احيانا' => 2,    'نعم بشكل كاف' => 5,    'نعم ولكن ليس كافيا' => 3
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

if($result = $conn->query($sql)){
    while($row = $result->fetch_assoc()){
        $question = $row['question_text'];
        $response = trim($row['response_text']);
        
        if(!isset($analysis[$question])){
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
foreach($analysis as &$item){
    $item['average'] = $item['count'] > 0 ? $item['total'] / $item['count'] : 0;
    $item['satisfaction'] = ($item['average'] / 5) * 100;
}

// حساب الرضا العام
$total_score = 0;
$total_weight = 0;
foreach($analysis as $item){
    $total_score += $item['total'];
    $total_weight += $item['count'] * 5;
}
$overall_satisfaction = $total_weight > 0 ? ($total_score / $total_weight) * 100 : 0;

// ... (بقية استعلامات النتائج تبقى كما هي)
?>
<!DOCTYPE html>
<html lang="ar">
<head><!-- إضافة مكتبة Chart.js -->
    <script src="chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="ev_d.css">
</head>
<style>
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
            right: 0%;
            bottom: 95%;
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
<script>
    function goBack() {
        window.history.back();
    }
</script>
<body>
    <div class="container">
        <!-- ... (الأزرار والجداول الأصلية) -->

        <h1>تفاصيل التقييم - <?php echo $course_name; ?></h1>
        <div class="buttons">
            <button onclick="showTable('responses')">تقييم الإجابات</button>
            <button onclick="showTable('results')">تقييم النتائج</button>
            <button onclick="showTable('combined')">تقييم شامل</button>
            <button onclick="showReport()">ناتج مقارنة</button>
            <button onclick="showTable('chart-analysis')">التحليل رسوميًا</button>
        </div>

        <!-- جدول تقييم الإجابات -->
        <div id="responses" class="table-container">
            <h2>تقييم الإجابات</h2>
            <table>
                <thead>
                    <tr>
                        <th>اسم المقرر</th>
                        <th>عدد إجابات الطلاب</th>
                        <th>متوسط التقييم</th>
                        <th>نسبة الرضا</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $course_name; ?></td>
                        <td><?php echo $total_responses; ?></td>
                        <td><?php echo number_format($avg_rating, 2); ?></td>
                        <td><?php echo number_format($satisfaction_rate, 2); ?>%</td>

                    </tr>
                </tbody>
            </table>
        </div>

        <!-- جدول تقييم النتائج -->
        <div id="results" class="table-container" style="display: none;">
            <h2>تقييم النتائج</h2>
            <table>
                <thead>
                    <tr>
                    
                        <th>متوسط درجات الطلاب</th>
                        <th>نسبة النجاح</th>
                        <th>نسبة الرسوب</th>
                        <th>اسم المقرر</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                       
                        <td><?php echo number_format($avg_result, 2); ?></td>
                        <td><?php echo number_format($pass_rate, 2); ?>%</td>
                        <td><?php echo number_format($fail_rate, 2); ?>%</td>
                        <td><?php echo $course_name; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- جدول التقييم الشامل -->
        <div id="combined" class="table-container" style="display: none;">
            <h2>تقييم شامل</h2>
            <table>
                <thead>
                    <tr>
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

        <!-- تقرير المقارنة -->
        <div id="report" class="table-container" style="display: none;">
            <h2>ناتج مقارنة</h2>
            <p>
              <?php echo $course_name; ?>   :اسم المقرر<br>
                <br>من ناتج تفاصيل الجدول اعلاه هل نجاح أو رسوب الطلاب يتوافق مع رضاهم عن هذا المقرر ؟
                <br><br>
                <?php
                if ($pass_rate >= 50 && $satisfaction_rate >= 50) {
                    echo "نعم، حسب نسب نجاح الطلاب ومتوسط النجاح الكلي استنتج ان جميع الطلاب الناجحون كانوا راضين عن المقرر..";
                } elseif ($pass_rate < 50 && $satisfaction_rate < 50) {
                    echo "لا،حسب متوسط النجاح ونسب رسوب معظم الطلاب استنتج ان الطلاب الراسبون لم يكونو  راضين عن المقرر..";
                } else {
                    echo "حسب متوسط النجاح ونسب رسوب معظم الطلاب استنتج ان الطلاب الراسبون لم يكونو  راضين عن المقرر..";
                }
                ?>
            </p>
        </div>
    </div>

    <script>
        function showTable(tableId) {
            // إخفاء جميع الجداول
            document.querySelectorAll('.table-container').forEach(function(table) {
                table.style.display = 'none';
            });

            // إظهار الجدول المحدد
            document.getElementById(tableId).style.display = 'block';
        }

        function showReport() {
            // إخفاء جميع الجداول
            document.querySelectorAll('.table-container').forEach(function(table) {
                table.style.display = 'none';
            });

            // إظهار التقرير
            document.getElementById('report').style.display = 'block';
        }
    </script>

        <!-- زر التحليل التفصيلي -->
        <button onclick="showTable('detailed-analysis')">تحليل تفصيلي</button>

        <!-- قسم التحليل التفصيلي -->
        <div id="detailed-analysis" class="table-container" style="display:none;">
            <h2>التحليل التفصيلي لكل سؤال</h2>
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
    // تعديل الدالة لدعم العرض الجديد
    function showTable(tableId) {
        document.querySelectorAll('.table-container').forEach(table => {
            table.style.display = 'none';
        });
        const target = document.getElementById(tableId);
        if(target) target.style.display = 'block';
    }
    </script>
    <!-- قسم الرسوم البيانية -->
    <div id="chart-analysis" class="table-container" style="display: none;">
            <h2>التحليل الرسومي</h2>
            <div class="chart-container">
                <canvas id="satisfactionChart"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="resultsChart"></canvas>
            </div>
        </div>
    <script>
        // ... (الدوال الحالية تبقى كما هي)

        // عرض الجداول
        function showTable(tableId) {
            document.querySelectorAll('.table-container').forEach(table => {
                table.style.display = 'none';
            });
            const target = document.getElementById(tableId);
            if (target) target.style.display = 'block';

            // إذا كان القسم المعروض هو الرسوم البيانية، قم بإنشاء الرسوم
            if (tableId === 'chart-analysis') {
                renderCharts();
            }
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
    </script>
    <button class="back-btn" onclick="window.history.back()">
        <i class="fa fa-arrow-right"></i> رجوع
    </button>
<a href="logout.php" class="logout-btn" style="   background-color:rgb(218, 69, 28);">
        <i class="fa fa-sign-out-alt"></i> خروج
    </a>
</body>
</html>