<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}
?>

<?php include 'includes/header.php'; ?>

<h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
<p>Your role is: <strong><?php echo htmlspecialchars($_SESSION["role"]); ?></strong></p>

<div>
    <h3>Dashboard</h3>
    <ul>
        <?php if($_SESSION['role'] == 'admin'): ?>
            <li><a href="add_student.php">Add Student</a></li>
            <li><a href="view_students.php">View Students</a></li>
            <li><a href="mark_attendance.php">Mark Attendance</a></li>
            <li><a href="view_attendance.php">View Attendance</a></li>
            <li><a href="generate_report.php">Generate Report</a></li>
        <?php elseif($_SESSION['role'] == 'teacher'): ?>
            <li><a href="view_students.php">View Students</a></li>
            <li><a href="mark_attendance.php">Mark Attendance</a></li>
            <li><a href="view_attendance.php">View Attendance</a></li>
        <?php endif; ?>
    </ul>
</div>

<p><a href="logout.php">Logout</a></p>

<?php include 'includes/footer.php'; ?> 