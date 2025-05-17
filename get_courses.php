<?php
include 'db_con.php';

$department_id = $_POST['department_id'];
$semester_id = $_POST['semester_id'];

// جلب المقررات الخاصة بالقسم والفصل الدراسي
$sql = "SELECT * FROM Courses 
        WHERE department_id = '$department_id' AND semester_id = '$semester_id'";
$result = $conn->query($sql);

$options = '<option value="">اختر المقرر</option>';
if ($result->num_rows > 0) {
    while ($course = $result->fetch_assoc()) {
        $options .= '<option value="' . $course['course_id'] . '">' . $course['course_name'] . '</option>';
    }
} else {
    $options .= '<option value="">لا توجد مقررات</option>';
}

echo $options;
?>