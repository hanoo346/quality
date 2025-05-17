<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

// جلب الطلاب والمقررات
$students = $conn->query("SELECT * FROM users WHERE role = 'Student'");
$courses = $conn->query("SELECT * FROM courses");

// جلب الفصول الدراسية
$semesters = $conn->query("SELECT * FROM semesters");

// جلب المقررات والطلاب بناءً على الفصل المختار
$selected_semester = $_POST['semester_id'] ?? null;
$courses = [];
$students = [];
$registrations = [];
$search_term = $_POST['search_student'] ?? '';

if ($selected_semester) {
    // جلب المقررات للفصل المختار
    $courses = $conn->query("
        SELECT * FROM courses 
        WHERE semester_id = '$selected_semester'
    ")->fetch_all(MYSQLI_ASSOC);

    // بناء استعلام البحث عن الطلاب
    $student_query = "SELECT * FROM users WHERE role = 'Student'";
    if (!empty($search_term)) {
        $student_query .= " AND full_name LIKE '%$search_term%'";
    }
    $students_result = $conn->query($student_query);

    // جلب الطلاب المسجلين في المقررات للفصل المختار
    $registrations_query = "
        SELECT 
            sr.registration_id,
            u.full_name AS student_name,
            c.course_name,
            d.department_name
        FROM 
            StudentRegistrations sr
        JOIN 
            users u ON sr.student_id = u.user_id
        JOIN 
            courses c ON sr.course_id = c.course_id
        JOIN 
            departments d ON c.department_id = d.department_id
        WHERE 
            c.semester_id = '$selected_semester'";
    
    if (!empty($search_term)) {
        $registrations_query .= " AND u.full_name LIKE '%$search_term%'";
    }
    
    $registrations = $conn->query($registrations_query)->fetch_all(MYSQLI_ASSOC);
}

// معالجة تسجيل الطالب
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];

    // التحقق من وجود تسجيل سابق
    $check_sql = "SELECT * FROM StudentRegistrations 
                  WHERE student_id = '$student_id' AND course_id = '$course_id'";
    $result = $conn->query($check_sql);

    if ($result->num_rows > 0) {
        $error = "الطالب مسجل مسبقًا في هذا المقرر.";
    } else {
        $sql = "INSERT INTO StudentRegistrations (student_id, course_id) 
                VALUES ('$student_id', '$course_id')";
        if ($conn->query($sql) === TRUE) {
            $success = "تم تسجيل الطالب بنجاح.";
            // تحديث البيانات بعد التسجيل
            $registrations_query = "
                SELECT 
                    sr.registration_id,
                    u.full_name AS student_name,
                    c.course_name,
                    d.department_name
                FROM 
                    StudentRegistrations sr
                JOIN 
                    users u ON sr.student_id = u.user_id
                JOIN 
                    courses c ON sr.course_id = c.course_id
                JOIN 
                    departments d ON c.department_id = d.department_id
                WHERE 
                    c.semester_id = '$selected_semester'";
            
            if (!empty($search_term)) {
                $registrations_query .= " AND u.full_name LIKE '%$search_term%'";
            }
            
            $registrations = $conn->query($registrations_query)->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = "حدث خطأ أثناء التسجيل: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>تسجيل الطلاب</title>
    <link rel="stylesheet" href="s.css">
    <style>
        .semester-selector {
            margin-bottom: 20px;
        }
        .registration-form {
            margin-top: 20px;
        }
        .registrations-table {
            margin-top: 30px;
        }
        .search-box {
            margin: 15px 0;
            padding: 8px;
            width: 300px;
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
        <h1>تسجيل الطلاب في المقررات</h1>

        <!-- اختيار الفصل الدراسي -->
        <form method="POST" class="semester-selector">
            <select name="semester_id" onchange="this.form.submit()" required>
                <option value="">-- اختر الفصل الدراسي --</option>
                <?php while ($semester = $semesters->fetch_assoc()): ?>
                    <option value="<?= $semester['semester_id'] ?>" 
                        <?= $selected_semester == $semester['semester_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($semester['semester_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>

        <?php if ($selected_semester): ?>
        <!-- حقل البحث عن الطالب -->
        <form method="POST">
            <input type="hidden" name="semester_id" value="<?= $selected_semester ?>">
            <input type="text" name="search_student" class="search-box" 
                   placeholder="ابحث عن طالب باسمه..." value="<?= htmlspecialchars($search_term) ?>">
            <button type="submit" class="menu-btn">بحث</button>
        </form>

        <!-- نموذج تسجيل الطالب -->
        <form method="POST" class="registration-form">
            <input type="hidden" name="semester_id" value="<?= $selected_semester ?>">
            <input type="hidden" name="search_student" value="<?= htmlspecialchars($search_term) ?>">
            
            <label for="student_id">اختر الطالب</label>
            <select name="student_id" required>
                <?php 
                $students_query = "SELECT * FROM users WHERE role = 'Student'";
                if (!empty($search_term)) {
                    $students_query .= " AND full_name LIKE '%$search_term%'";
                }
                $students = $conn->query($students_query);
                while ($student = $students->fetch_assoc()): ?>
                    <option value="<?= $student['user_id'] ?>">
                        <?= htmlspecialchars($student['full_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="course_id">اختر المقرر</label>
            <select name="course_id" required>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['course_id'] ?>">
                        <?= htmlspecialchars($course['course_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="register" class="menu-btn">حفظ</button>
        </form>

        <!-- عرض الطلاب المسجلين -->
        <div class="registrations-table">
            <h2>الطلاب المسجلين</h2>
            <?php if (!empty($search_term)): ?>
                <p>نتائج البحث عن: "<?= htmlspecialchars($search_term) ?>"</p>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>اسم الطالب</th>
                        <th>اسم المقرر</th>
                        <th>القسم</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registrations)): ?>
                        <tr>
                            <td colspan="5">لا يوجد طلاب مسجلين بعد</td>
                        </tr>
                    <?php else: ?>
                        <?php $counter = 1; ?>
                        <?php foreach ($registrations as $row): ?>
                        <tr>
                            <td><?= $counter ?></td>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td><?= htmlspecialchars($row['course_name']) ?></td>
                            <td><?= htmlspecialchars($row['department_name']) ?></td>
                            <td>
                                <a href="edit_registration.php?id=<?= $row['registration_id'] ?>">تعديل</a>
                                <a href="delete_registration.php?id=<?= $row['registration_id'] ?>" 
                                   onclick="return confirm('هل أنت متأكد؟')">حذف</a>
                            </td>
                        </tr>
                        <?php $counter++; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
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