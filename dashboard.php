<?php
session_start();
include 'db_connection.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// إعداد مهلة الجلسة (5 دقائق)
$inactive = 300; // 300 ثانية = 5 دقائق
if (isset($_SESSION['timeout'])) {
    $session_life = time() - $_SESSION['timeout'];
    if ($session_life > $inactive) {
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
}
$_SESSION['timeout'] = time(); // تحديث وقت النشاط الأخير

// جلب معلومات المستخدم
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM Users WHERE user_id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
$role = $user['role'];
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="testt.css">
    <style>
      

aside {
   /* عرض القائمة الجانبية */
   width: 10%; /* قلّل العرض حسب الحاجة */
    transition: width 0.3s ease; /* تأثير ناعم عند التغيير */
   background-color: #333;
    color: white;
    padding: 20px;
    height: 100vh; /* جعل القائمة تمتد بطول الصفحة */
    position: fixed;
}
        .logout-container {
            margin-top: 250px;
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
            width: 50px; 
            height: 50px;
            overflow: hidden;
            transition: width 0.3s ease;
            margin-top: 50px;
            margin-bottom:60px;
            margin-left:70px;
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
            width: 90px;  
            justify-content: start;
            padding-left: 15px;
        }

        .logout-btn:hover .logout-text {
            display: inline;
        }

        
body {   
    background-image:url("imgport.png");
    filter: contrast(1.2) brightness(1.1);
    opacity: 1;
    background-repeat: no-repeat;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-size: cover;
    background-position: center;
    font-family: Arial, sans-serif;
    direction: rtl;
    text-align: right;
    margin: 0;
    padding: 0;
}

.logo {
    position: center;
    top: 10px; /* المسافة من الأعلى */
    left: 15px; /* المسافة من اليسار، غيّرها إلى 'right' إذا أردت وضعه في الزاوية اليمنى */
    width: 100%; /* حجم اللوجو */
    height: auto;
}
.dashboard-box {
    width: 80%; /* تكبير الصندوق ليصبح 80% من العرض */
  
    background-color:rgb(5, 41, 81);
    padding: 30px; /* زيادة التباعد الداخلي */
    border-radius: 10px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between; /* توزيع العناصر بحيث يكون هناك تباعد بينها */
    gap: 30px; /* زيادة المسافة بين العناصر داخل الصندوق */
}
.menu-btn i {
    font-size: 2.0rem; /* تكبير الأيقونة */
    margin-bottom: 8px;
}
 
    </style>
</head>
<script>
// تحديث الجلسة كل دقيقة لمنع انتهائها أثناء العمل
setInterval(function() {
    fetch('session_ping.php').catch(() => {});
}, 60000); // كل 60 ثانية

</script>
<body>
   
    <aside class="sidebar">
        <ul>
        <img src="nat.png" class="logo" alt="Logo" style="">
            <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
            <li><a href="about.php"><i class="fa fa-info-circle"></i> About</a></li>
            <li><a href="services.php"><i class="fa fa-cogs"></i> Services</a></li>
            <li><a href="contact.php"><i class="fa fa-envelope"></i> Contact</a></li>
        
     
            <a href="logout.php" class="logout-btn">
                <i class="fa fa-sign-out-alt"></i>
                <span class="logout-text">خروج</span>
            </a>
        </div></ul>
    </aside>

    <div class="main-content" style="">
        <header style=" border: 1px solid black; outline-style: thick; outline-color: red;
outline-width: medium;">

        <h1 style=" text-align:center;"> الجامعة الوطنية<h1>
        <h1 style=" text-align:center;">نظام ادارة الجودة كلية علوم الحاسوب وتقنية المعلومات  <h1></header>
        <h2>مرحبًا، <?php echo $user['full_name']; ?></h2>

        <!-- زر الرجوع -->
     

        <nav>
            <div class="dashboard-box">
                <?php if ($role == 'Admin') { ?>
                    <!-- روابط المدير -->
                    
                    <a href="manage_users.php" class="menu-btn"><i class="fa fa-users"></i> إدارة المستخدمين</a>
                    <a href="manage_courses.php" class="menu-btn"><i class="fa fa-book"></i> إدارة المقررات</a>
                    <a href="student_registration.php" class="menu-btn"><i class="fa fa-user-plus"></i> تسجيل الطلاب</a>
                    <a href="analyze_evaluations.php" class="menu-btn"><i class="fa fa-chart-line"></i> تحليل التقييمات</a>
                    <a href="manage_departments.php" class="menu-btn"><i class="fa fa-building"></i> إدارة الأقسام</a>
                    <a href="manage_survey.php" class="menu-btn"><i class="fa fa-poll"></i> إدارة الاستبيانات</a>
                    <a href="enter_results.php" class="menu-btn"><i class="fa fa-keyboard"></i> إدخال النتائج</a>
                    <a href="generate_reports.php" class="menu-btn"><i class="fa fa-file-alt"></i> استخراج التقارير</a>
                    <a href="manage_semesters.php" class="menu-btn"><i class="fa fa-school"></i> إدارة الفصول الدراسية</a>
                    <a href="QQQ.php" class="menu-btn"><i class="fa fa-search"></i> طلب استعلام</a>

                <?php } elseif ($role == 'Student') { ?>
                    <!-- روابط الطالب -->
                    <a href="course_evaluation.php" class="menu-btn"><i class="fa fa-edit"></i> تقييم المقررات</a>
                    <a href="view_results.php" class="menu-btn"><i class="fa fa-chart-bar"></i> عرض النتائج</a>

                <?php } elseif ($role == 'Dean') { ?>
                    <!-- روابط العميد -->
                    <a href="QQQ.php" class="menu-btn"><i class="fa fa-search"></i> طلب استعلام</a>
                  
                    <a href="generate_reports.php" class="menu-btn"><i class="fa fa-file-alt"></i> استخراج التقارير</a>
                    <a href="analyze_evaluations.php" class="menu-btn"><i class="fa fa-chart-line"></i> تحليل التقييمات</a>
                <?php } ?>
            </div>
        </nav>

        <div class="content">
            <?php if ($role == 'Admin') { ?>
                <h2>لوحة تحكم المدير</h2>
                <p>يمكنك إدارة المستخدمين، الأقسام، المقررات، النتائج، والاستبيانات من هنا.</p>
            <?php } elseif ($role == 'Student') { ?>
                <br><br><br><br><br><br><h2>لوحة تحكم الطالب</h2>
                <p>يمكنك تقييم المقررات وعرض نتائجك من هنا.</p>
            <?php } elseif ($role == 'Dean') { ?>
                <br><br><br><br><br><h2>لوحة تحكم العميد</h2>
                <p>يمكنك تحليل التقييمات وعرض التقارير وإجراء الاستعلامات من هنا.</p>
            <?php } ?>
        </div>
    </div>
</body>
</html>
