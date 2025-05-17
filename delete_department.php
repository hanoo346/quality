<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$department_id = $_GET['id'];

$sql = "DELETE FROM Departments WHERE department_id = '$department_id'";
if ($conn->query($sql) === TRUE) {
    header("Location: manage_departments.php");
    exit();
} else {
    echo "حدث خطأ أثناء حذف القسم: " . $conn->error;
}
?>