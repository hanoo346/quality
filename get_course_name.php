<?php
include 'db_con.php';

$course_id = $_POST['course_id'];

// جلب اسم المقرر من قاعدة البيانات
$sql = "SELECT course_name FROM Courses WHERE course_id = '$course_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $course = $result->fetch_assoc();
    echo json_encode(['course_name' => $course['course_name']]);
} else {
    echo json_encode(['error' => 'لا يوجد مقرر بهذا المعرف.']);
}
?>