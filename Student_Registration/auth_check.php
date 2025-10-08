<?php
// auth_check.php
session_start();

require_once '../Book-Registration/includes/db_connect.php';

if (!isset($_SESSION['username'])) {
    header('Location: student_login.php');
    exit();
}

// Verify student status is still active
$stmt = $conn->prepare("SELECT Status FROM application WHERE Application_ID = ?");
$stmt->bind_param("i", $_SESSION['application_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Student record not found
    session_destroy();
    header("Location: login.php?error=account_not_found");
    exit();
}

$student = $result->fetch_assoc();
if ($student['Status'] !== 'Active') {
    // Account is inactive
    session_destroy();
    header("Location: login.php?error=account_inactive");
    exit();
}