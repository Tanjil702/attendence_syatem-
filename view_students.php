<?php
session_start();
require_once "config/db.php";

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Fetch all students from the database
$sql = "SELECT * FROM students ORDER BY name ASC";
$result = $conn->query($sql);
?>

<?php include 'includes/header.php'; ?>

<style>
    .styled-table {
        border-collapse: collapse;
        margin: 25px 0;
        font-size: 0.9em;
        font-family: sans-serif;
        min-width: 400px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
        width: 100%;
    }
    .styled-table thead tr {
        background-color: #009879;
        color: #ffffff;
        text-align: left;
    }
    .styled-table th,
    .styled-table td {
        padding: 12px 15px;
    }
    .styled-table tbody tr {
        border-bottom: 1px solid #dddddd;
    }
    .styled-table tbody tr:nth-of-type(even) {
        background-color: #f3f3f3;
    }
    .styled-table tbody tr:last-of-type {
        border-bottom: 2px solid #009879;
    }
    .action-links a {
        color: #009879;
        margin-right: 10px;
        text-decoration: none;
    }
    .action-links a:hover {
        text-decoration: underline;
    }
</style>

<h2>Students List</h2>

<table class="styled-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Roll Number</th>
            <th>Class</th>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['roll_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['class']); ?></td>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                        <td class="action-links">
                            <a href="edit_student.php?id=<?php echo $row['id']; ?>">Edit</a>
                            <a href="delete_student.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?php echo (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') ? '4' : '3'; ?>">No students found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<p><a href="dashboard.php">Back to Dashboard</a></p>

<?php
if ($result) {
    $result->close();
}
$conn->close();
?>

<?php include 'includes/footer.php'; ?> 