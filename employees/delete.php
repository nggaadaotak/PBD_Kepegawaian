<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/database.php";
$database = new Database();
$db = $database->getConnection();

if (isset($_GET['id'])) {
    try {
        $emp_id = $_GET['id'];
        
        // Begin transaction
        $db->beginTransaction();
        
        // Delete any related records first (if there are foreign key constraints)
        // For example, if you have attendance records, salary history, etc.
        // Add additional delete queries here if needed
        
        // Delete the employee record
        $query = "DELETE FROM employees WHERE emp_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$emp_id]);
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['success'] = "Employee successfully deleted";
        header("Location: index.php");
        exit();
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        
        $_SESSION['error'] = "Error deleting employee: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}

header("Location: index.php");
exit();