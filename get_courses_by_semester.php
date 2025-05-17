<?php
include 'db_connection.php';

if (isset($_POST['semester_id'])) {
    $semester_id = $_POST['semester_id'];

    $sql = "SELECT DISTINCT courses.course_id, courses.course_name 
            FROM courses 
            JOIN surveys ON courses.course_id = surveys.course_id 
            WHERE surveys.semester_id = '$semester_id'";
    $result = $conn->query($sql);

    $options = "<option value=''>-- اختر المقرر --</option>";
    while ($row = $result->fetch_assoc()) {
        $options .= "<option value='{$row['course_id']}'>{$row['course_name']}</option>";
    }

    echo $options;
}
?>