<?php
session_start();
require_once "config/db.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

if($_SESSION['role'] !== 'admin'){
    header("location: dashboard.php");
    exit;
}

// Fetch students for the dropdown
$students = [];
$sql_students = "SELECT id, name FROM students ORDER BY name ASC";
if($result_students = $conn->query($sql_students)){
    while($row = $result_students->fetch_assoc()){
        $students[] = $row;
    }
}

$report_data = [];
$selected_month = date('m');
$selected_year = date('Y');
$selected_student_id = 'all';

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_report'])){
    $selected_month = $_POST['month'];
    $selected_year = $_POST['year'];
    $selected_student_id = $_POST['student_id'];
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);

    $sql = "SELECT s.id as student_id, s.name, DAY(a.attendance_date) as day, a.status 
            FROM students s
            LEFT JOIN attendance a ON s.id = a.student_id 
                                 AND YEAR(a.attendance_date) = ? 
                                 AND MONTH(a.attendance_date) = ? ";
    
    if($selected_student_id != 'all'){
        $sql .= " WHERE s.id = ?";
    }
    
    $sql .= " ORDER BY s.name, DAY(a.attendance_date)";

    if($stmt = $conn->prepare($sql)){
        if($selected_student_id != 'all'){
            $stmt->bind_param("iii", $selected_year, $selected_month, $selected_student_id);
        } else {
            $stmt->bind_param("ii", $selected_year, $selected_month);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $attendance_by_student = [];
        while($row = $result->fetch_assoc()){
            $attendance_by_student[$row['student_id']]['name'] = $row['name'];
            if($row['day']){
                $attendance_by_student[$row['student_id']]['attendance'][$row['day']] = $row['status'];
            }
        }
        $report_data = $attendance_by_student;
        $stmt->close();
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['export_csv'])){
    // Re-fetch data for export
    $selected_month = $_POST['month'];
    $selected_year = $_POST['year'];
    $selected_student_id = $_POST['student_id'];
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);

    $sql = "SELECT s.name, DAY(a.attendance_date) as day, a.status 
            FROM students s
            LEFT JOIN attendance a ON s.id = a.student_id 
                                 AND YEAR(a.attendance_date) = ? 
                                 AND MONTH(a.attendance_date) = ? ";
    if($selected_student_id != 'all'){
        $sql .= " WHERE s.id = ?";
    }
    $sql .= " ORDER BY s.name, DAY(a.attendance_date)";

    if($stmt = $conn->prepare($sql)){
        if($selected_student_id != 'all'){
            $stmt->bind_param("iii", $selected_year, $selected_month, $selected_student_id);
        } else {
            $stmt->bind_param("ii", $selected_year, $selected_month);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $attendance_by_student = [];
        while($row = $result->fetch_assoc()){
            $attendance_by_student[$row['name']][$row['day']] = $row['status'];
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance_report_'.$selected_year.'-'.$selected_month.'.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header Row
        $header = ['Student Name'];
        for($d=1; $d<=$days_in_month; $d++){ $header[] = date("d-m-Y", mktime(0,0,0,$selected_month, $d, $selected_year)); }
        fputcsv($output, $header);

        // Data Rows
        foreach($attendance_by_student as $student_name => $days){
            $row_data = [$student_name];
            for($d=1; $d<=$days_in_month; $d++){
                $status = isset($days[$d]) ? ($days[$d] == 'present' ? 'P' : 'A') : 'N/A';
                $row_data[] = $status;
            }
            fputcsv($output, $row_data);
        }
        
        fclose($output);
        $stmt->close();
        $conn->close();
        exit();
    }
}
?>

<?php include 'includes/header.php'; ?>

<h2>Generate Attendance Report</h2>

<form method="post" action="">
    <div class="form-group">
        <label for="year">Year:</label>
        <select name="year" id="year">
            <?php for($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
            <option value="<?php echo $y; ?>" <?php if($y == $selected_year) echo 'selected'; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="month">Month:</label>
        <select name="month" id="month">
            <?php for($m = 1; $m <= 12; $m++): ?>
            <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" <?php if($m == $selected_month) echo 'selected'; ?>>
                <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
            </option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="student_id">Student:</label>
        <select name="student_id" id="student_id">
            <option value="all">All Students</option>
            <?php foreach($students as $student): ?>
            <option value="<?php echo $student['id']; ?>" <?php if($student['id'] == $selected_student_id) echo 'selected'; ?>>
                <?php echo htmlspecialchars($student['name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <input type="submit" name="generate_report" class="btn" value="Generate Report">
        <?php if(!empty($report_data)): ?>
            <input type="submit" name="export_csv" class="btn" value="Export to CSV">
        <?php endif; ?>
    </div>
</form>

<?php if(!empty($report_data)): ?>
    <h3>Report for <?php echo date('F', mktime(0,0,0,$selected_month,10)) . ' ' . $selected_year; ?></h3>
    <div style="overflow-x:auto;">
    <table class="styled-table">
        <thead>
            <tr>
                <th>Student Name</th>
                <?php for($i = 1; $i <= $days_in_month; $i++): ?>
                <th><?php echo $i; ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($report_data as $student_id => $data): ?>
            <tr>
                <td><?php echo htmlspecialchars($data['name']); ?></td>
                <?php for($i = 1; $i <= $days_in_month; $i++): ?>
                    <td>
                        <?php 
                        if(isset($data['attendance'][$i])){
                            echo ($data['attendance'][$i] == 'present') ? 'P' : '<span style="color:red;">A</span>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                <?php endfor; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php elseif($_SERVER["REQUEST_METHOD"] == "POST"): ?>
    <p>No attendance data found for the selected criteria.</p>
<?php endif; ?>

<p><a href="dashboard.php">Back to Dashboard</a></p>

<?php include 'includes/footer.php'; ?> 