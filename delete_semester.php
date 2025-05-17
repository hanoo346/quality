<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_GET['id'];

$sql = "DELETE FROM Users WHERE user_id = '$user_id'";
if ($conn->query($sql) === TRUE) {
    header("Location: manage_users.php");
    exit();
} else {
    echo "حدث خطأ أثناء حذف المستخدم: " . $conn->error;
}
?>