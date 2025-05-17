<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$registration_id = $_GET['id'];

$sql = "DELETE FROM StudentRegistrations WHERE registration_id = '$registration_id'";
if ($conn->query($sql) === TRUE) {
    $success = "تم حذف التسجيل بنجاح.";
} else {
    $error = "حدث خطأ أثناء الحذف: " . $conn->error;
}

header("Location: student_registration.php");
exit();
?>