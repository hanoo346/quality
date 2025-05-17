<?php
include 'db_con.php';

$department_id = $_POST['department_id'];

// جلب جميع الفصول الدراسية الخاصة بالقسم
$sql = "SELECT * FROM Semesters WHERE department_id = '$department_id'";
$result = $conn->query($sql);

$options = '<option value="">اختر الفصل الدراسي</option>';
if ($result->num_rows > 0) {
    while ($semester = $result->fetch_assoc()) {
        $options .= '<option value="' . $semester['semester_id'] . '">' . $semester['semester_name'] . '</option>';
    }
} else {
    $options .= '<option value="">لا توجد فصول دراسية</option>';
}

echo $options;
?>