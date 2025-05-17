<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$course_id = $_GET['id'];

$sql = "DELETE FROM courses WHERE course_id = '$course_id'";
if ($conn->query($sql) === TRUE) {
    header("Location: manage_courses.php");
    exit();
} else {
    echo "حدث خطأ أثناء حذف المقرر: " . $conn->error;
}
?>