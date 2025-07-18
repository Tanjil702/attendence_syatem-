<?php
session_start();
require_once "config/db.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$view_date = date('Y-m-d');
if(isset($_GET['date'])){
    $view_date = $_GET['date'];
}

// SQL to fetch attendance records along with student names
$sql = "SELECT s.name, s.roll_number, a.status
        FROM students s
        LEFT JOIN attendance a ON s.id = a.student_id AND a.attendance_date = ?
        ORDER BY s.name ASC";

$attendance_records = [];
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("s", $view_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $attendance_records[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>

<?php include 'includes/header.php'; ?>

<h2>View Attendance for <?php echo date("F d, Y", strtotime($view_date)); ?></h2>

<form class="date-selector" method="get" action="">
    <label for="date">Select Date:</label>
    <input type="date" id="date" name="date" value="<?php echo $view_date; ?>">
    <input type="submit" value="View Attendance">
</form>

<table class="styled-table">
    <thead>
        <tr>
            <th>Student Name</th>
            <th>Roll Number</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($attendance_records)): ?>
            <?php foreach($attendance_records as $record): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['name']); ?></td>
                    <td><?php echo htmlspecialchars($record['roll_number']); ?></td>
                    <td>
                        <?php 
                        if ($record['status'] == 'present') {
                            echo '<span style="color:green; font-weight:bold;">Present</span>';
                        } elseif ($record['status'] == 'absent') {
                            echo '<span style="color:red; font-weight:bold;">Absent</span>';
                        } else {
                            echo '<span>Not Marked</span>';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="3">No student records found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<p><a href="dashboard.php">Back to Dashboard</a></p>

<?php include 'includes/footer.php'; ?> 