<?php
session_start();
require_once "config/db.php";

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Check if the user is an admin, if not then redirect to dashboard page
if($_SESSION["role"] !== 'admin'){
    header("location: dashboard.php");
    exit;
}

$name = $roll_number = $class = "";
$name_err = $roll_number_err = $class_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

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
        // Check if roll number is already taken
        $sql = "SELECT id FROM students WHERE roll_number = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("s", $param_roll_number);
            $param_roll_number = trim($_POST["roll_number"]);
            if($stmt->execute()){
                $stmt->store_result();
                if($stmt->num_rows == 1){
                    $roll_number_err = "This roll number is already taken.";
                } else{
                    $roll_number = trim($_POST["roll_number"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }

    // Validate class
    if(empty(trim($_POST["class"]))){
        $class_err = "Please enter a class.";
    } else{
        $class = trim($_POST["class"]);
    }

    // Check input errors before inserting in database
    if(empty($name_err) && empty($roll_number_err) && empty($class_err)){
        $sql = "INSERT INTO students (name, roll_number, class) VALUES (?, ?, ?)";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("sss", $param_name, $param_roll, $param_class);
            
            $param_name = $name;
            $param_roll = $roll_number;
            $param_class = $class;
            
            if($stmt->execute()){
                header("location: view_students.php");
            } else{
                echo "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    
    $conn->close();
}
?>

<?php include 'includes/header.php'; ?>

<h2>Add Student</h2>
<p>Please fill this form to add a student.</p>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
        <span class="error"><?php echo $name_err; ?></span>
    </div>
    <div class="form-group">
        <label>Roll Number</label>
        <input type="text" name="roll_number" class="form-control <?php echo (!empty($roll_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $roll_number; ?>">
        <span class="error"><?php echo $roll_number_err; ?></span>
    </div>
    <div class="form-group">
        <label>Class</label>
        <input type="text" name="class" class="form-control <?php echo (!empty($class_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $class; ?>">
        <span class="error"><?php echo $class_err; ?></span>
    </div>
    <div class="form-group">
        <input type="submit" class="btn" value="Add Student">
    </div>
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</form>

<?php include 'includes/footer.php'; ?> 