<?php
session_start();
include 'db_connection.php';

$semester_id = $_POST['semester_id'] ?? 'all';
$query_type = $_POST['query_type'] ?? 'student_performance';

// جلب البيانات حسب الفصل الدراسي المحدد
if ($query_type === 'student_performance') {
    // استعلام عن أداء طالب معين
    $stmt = $conn->prepare("SELECT c.course_name, r.encrypted_result 
                          FROM Results r
                          JOIN Courses c ON r.course_id = c.course_id 
                          WHERE r.student_id = ?");
    $stmt->bind_param("s", $query_value);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($semester_id !== 'all') {
        $sql .= " AND ss.semester_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $semester_id);
    } else {
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $students = $stmt->get_result();
    
    echo '<label for="query_value">اختر الطالب:</label>
          <select name="query_value" required>';
    while($student = $students->fetch_assoc()) {
        echo '<option value="'.$student['user_id'].'">'.$student['full_name'].'</option>';
    }
    echo '</select>';
}
elseif ($query_type === 'multiple_courses_evaluation') {
    $sql = "SELECT DISTINCT c.course_id, c.course_name 
            FROM courses c
            JOIN surveys s ON c.course_id = s.course_id";
    
    if ($semester_id !== 'all') {
        $sql .= " WHERE c.semester_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $semester_id);
    } else {
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $courses = $stmt->get_result();
    
    echo '<label for="query_value">اختر المقررات:</label>
          <select name="query_value[]" multiple required>';
    while($course = $courses->fetch_assoc()) {
        echo '<option value="'.$course['course_id'].'">'.$course['course_name'].'</option>';
    }
    echo '<option value="all">الكل</option></select>';
}
elseif ($query_type === 'course_students_performance') {
    $sql = "SELECT DISTINCT c.course_id, c.course_name 
            FROM courses c
            JOIN Results r ON c.course_id = r.course_id";
    
    if ($semester_id !== 'all') {
        $sql .= " WHERE c.semester_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $semester_id);
    } else {
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $courses = $stmt->get_result();
    
    echo '<label for="query_value">اختر المقرر:</label>
          <select name="query_value[]" multiple required>';
    while($course = $courses->fetch_assoc()) {
        echo '<option value="'.$course['course_id'].'">'.$course['course_name'].'</option>';
    }
    echo '<option value="all">الكل</option></select>';
}
?>