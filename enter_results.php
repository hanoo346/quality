<?php
session_start();
include 'db_con.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

function encryptData($data) {
    $iv_length = openssl_cipher_iv_length(CIPHER_METHOD);
    $iv = openssl_random_pseudo_bytes($iv_length); // إنشاء متجه ابتدائي (IV)
    $encrypted = openssl_encrypt($data, CIPHER_METHOD, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($iv . $encrypted); // إرجاع النص المشفر مع IV
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];
    $result = $_POST['result'];

    // التحقق من أن النتيجة بين 0 و 100
    if (!is_numeric($result) || $result < 0 || $result > 100) {
        $error = "الرجاء إدخال قيمة ما بين 0 إلى 100 فقط.";
    } else {
        // التحقق من وجود نتيجة سابقة للطالب في هذا المقرر
        $check_sql = "SELECT * FROM Results WHERE student_id = '$student_id' AND course_id = '$course_id'";
        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows > 0) {
            // إذا كانت هناك نتيجة سابقة، قم بتحديث النتيجة
            $encrypted_result = encryptData($result);
            $update_sql = "UPDATE Results SET encrypted_result = '$encrypted_result' 
                            WHERE student_id = '$student_id' AND course_id = '$course_id'";
            if ($conn->query($update_sql) === TRUE) {
                $success = "تم تحديث النتيجة بنجاح.";
            } else {
                $error = "حدث خطأ أثناء تحديث النتيجة: " . $conn->error;
            }
        } else {
            // إذا لم تكن هناك نتيجة سابقة، قم بإدخال النتيجة الجديدة
            $encrypted_result = encryptData($result);
            $insert_sql = "INSERT INTO Results (student_id, course_id, encrypted_result) 
                           VALUES ('$student_id', '$course_id', '$encrypted_result')";
            if ($conn->query($insert_sql) === TRUE) {
                $success = "تم إدخال النتيجة بنجاح.";
            } else {
                $error = "حدث خطأ أثناء إدخال النتيجة: " . $conn->error;
            }
        }
    }
}

// جلب الطلاب والمقررات
$students = $conn->query("SELECT * FROM Users WHERE role = 'Student'");
$courses = $conn->query("SELECT * FROM Courses");
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>إدخال النتائج</title>
    <link rel="stylesheet" href="s.css">
    <script>
        function validateResult() {
            var result = document.getElementsByName("result")[0].value;
            if (isNaN(result) || result < 0 || result > 100) {
                alert("الرجاء إدخال قيمة ما بين 0 إلى 100 فقط.");
                return false;
            }
            return true;
        }
    </script>
    <script>
    function goBack() {
        window.history.back();
    }
</script>
</head>
<body>
    <div class="container">
        <h1>إدخال النتائج</h1>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form action="enter_results.php" method="POST" onsubmit="return validateResult()">
            <label for="student_id">اختر الطالب</label>
            <select name="student_id" required>
                <?php while ($student = $students->fetch_assoc()) { ?>
                    <option value="<?php echo $student['user_id']; ?>"><?php echo $student['full_name']; ?></option>
                <?php } ?>
            </select>

            <label for="course_id">اختر المقرر</label>
            <select name="course_id" required><br>
                <?php while ($course = $courses->fetch_assoc()) { ?>
                    <option value="<?php echo $course['course_id']; ?>"><?php echo $course['course_name']; ?></option>
                <?php } ?>
            </select>

            
            <br><br><input type="text" name="result" placeholder="أدخل النتيجة" required><br>

            <button type="submit" class="menu-btn">حفظ</button>
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