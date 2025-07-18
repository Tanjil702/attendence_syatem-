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

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $sql = "DELETE FROM students WHERE id = ?";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("i", $param_id);
        
        $param_id = trim($_GET["id"]);
        
        if($stmt->execute()){
            header("location: view_students.php");
            exit();
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
     
    $stmt->close();
    
    $conn->close();
} else{
    header("location: view_students.php");
    exit();
}
?> 