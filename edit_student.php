<?php
session_start();
require_once "config/db.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

if($_SESSION["role"] !== 'admin'){
    header("location: dashboard.php");
    exit;
}

$name = $roll_number = $class = "";
$name_err = $roll_number_err = $class_err = "";
$student_id = $_GET['id'];

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $student_id = $_POST['id'];
    
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter a name.";
    } else{
        $name = trim($_POST["name"]);
    }

    // Validate roll number
    if(empty(trim($_POST["roll_number"]))){
        $roll_number_err = "Please enter a roll number.";
    } else{
        $roll_number = trim($_POST["roll_number"]);
    }

    // Validate class
    if(empty(trim($_POST["class"]))){
        $class_err = "Please enter a class.";
    } else{
        $class = trim($_POST["class"]);
    }

    if(empty($name_err) && empty($roll_number_err) && empty($class_err)){
        $sql = "UPDATE students SET name = ?, roll_number = ?, class = ? WHERE id = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("sssi", $name, $roll_number, $class, $student_id);
            if($stmt->execute()){
                header("location: view_students.php");
            } else{
                echo "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
} else {
    // Fetch existing student data
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        $student_id = trim($_GET["id"]);
        $sql = "SELECT * FROM students WHERE id = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("i", $student_id);
            if($stmt->execute()){
                $result = $stmt->get_result();
                if($result->num_rows == 1){
                    $row = $result->fetch_assoc();
                    $name = $row["name"];
                    $roll_number = $row["roll_number"];
                    $class = $row["class"];
                } else{
                    echo "No records found.";
                    exit();
                }
            } else{
                echo "Oops! Something went wrong.";
                exit();
            }
            $stmt->close();
        }
    } else {
        header("location: view_students.php");
        exit();
    }
}
?>

<?php include 'includes/header.php'; ?>

<h2>Edit Student</h2>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $student_id; ?>" method="post">
    <input type="hidden" name="id" value="<?php echo $student_id; ?>"/>
    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" value="<?php echo $name; ?>">
        <span class="error"><?php echo $name_err; ?></span>
    </div>
    <div class="form-group">
        <label>Roll Number</label>
        <input type="text" name="roll_number" value="<?php echo $roll_number; ?>">
        <span class="error"><?php echo $roll_number_err; ?></span>
    </div>
    <div class="form-group">
        <label>Class</label>
        <input type="text" name="class" value="<?php echo $class; ?>">
        <span class="error"><?php echo $class_err; ?></span>
    </div>
    <div class="form-group">
        <input type="submit" class="btn" value="Update Student">
    </div>
    <p><a href="view_students.php">Back to Students List</a></p>
</form>

<?php include 'includes/footer.php'; ?> 