<?php
session_start();
require_once "config/db.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$attendance_date = date('Y-m-d');
if(isset($_GET['date'])){
    $attendance_date = $_GET['date'];
}

$message = "";

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $attendance_data = $_POST['attendance'];
    $post_date = $_POST['attendance_date'];

    $sql = "INSERT INTO attendance (student_id, attendance_date, status) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)";
    
    if($stmt = $conn->prepare($sql)){
        foreach($attendance_data as $student_id => $status){
            $stmt->bind_param("iss", $student_id, $post_date, $status);
            $stmt->execute();
        }
        $message = "Attendance saved successfully!";
        $stmt->close();
    } else {
        $message = "Error preparing statement: " . $conn->error;
    }
}

// Fetch all students
$students_sql = "SELECT * FROM students ORDER BY name ASC";
$students_result = $conn->query($students_sql);

// Fetch existing attendance for the selected date to pre-fill the form
$existing_attendance = [];
$attendance_sql = "SELECT student_id, status FROM attendance WHERE attendance_date = ?";
if($stmt = $conn->prepare($attendance_sql)){
    $stmt->bind_param("s", $attendance_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $existing_attendance[$row['student_id']] = $row['status'];
    }
    $stmt->close();
}
$conn->close();
?>

<?php include 'includes/header.php'; ?>

<style>
    /* Add some styling for the form and table */
    .date-selector {
        margin-bottom: 20px;
    }
</style>

<h2>Mark Attendance for <?php echo date("F d, Y", strtotime($attendance_date)); ?></h2>

<?php if(!empty($message)): ?>
    <p style="color: green;"><?php echo $message; ?></p>
<?php endif; ?>


<form class="date-selector" method="get" action="">
    <label for="date">Select Date:</label>
    <input type="date" id="date" name="date" value="<?php echo $attendance_date; ?>">
    <input type="submit" value="View">
</form>

<?php if ($students_result->num_rows > 0): ?>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <input type="hidden" name="attendance_date" value="<?php echo $attendance_date; ?>">
    <table class="styled-table">
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Roll Number</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while($student = $students_result->fetch_assoc()): 
                $student_id = $student['id'];
                $current_status = isset($existing_attendance[$student_id]) ? $existing_attendance[$student_id] : 'present'; // Default to 'present'
            ?>
            <tr>
                <td><?php echo htmlspecialchars($student['name']); ?></td>
                <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                <td>
                    <label>
                        <input type="radio" name="attendance[<?php echo $student_id; ?>]" value="present" <?php if($current_status == 'present') echo 'checked'; ?>> Present
                    </label>
                    <label>
                        <input type="radio" name="attendance[<?php echo $student_id; ?>]" value="absent" <?php if($current_status == 'absent') echo 'checked'; ?>> Absent
                    </label>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <div class="form-group">
        <input type="submit" class="btn" value="Save Attendance">
    </div>
</form>
<?php else: ?>
<p>No students found. Please <a href="add_student.php">add a student</a> first.</p>
<?php endif; ?>

<p><a href="dashboard.php">Back to Dashboard</a></p>

<?php include 'includes/footer.php'; ?> 