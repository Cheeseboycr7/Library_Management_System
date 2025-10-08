<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecot_library2";

// Initialize variables
$email = '';
$error_message = '';
$success_message = '';
$show_form = true;

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Process form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error_message = "Invalid form submission. Please try again.";
        } else {
            $email = trim($_POST['email']);
            
            // Validate email
            if (empty($email)) {
                $error_message = "Please enter your email address.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Please enter a valid email address.";
            } else {
                // Check if email exists in database
                $stmt = $conn->prepare("SELECT Application_ID , Username FROM application WHERE Email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    // Generate reset token (expires in 1 hour)
                    $token = bin2hex(random_bytes(32));
                    $expires = date("Y-m-d H:i:s", time() + 3600); // 1 hour from now
                    
                    // Store token in database
                    $updateStmt = $conn->prepare("UPDATE application SET reset_token = ?, reset_expires = ? WHERE Application_ID = ?");
                    $updateStmt->bind_param("ssi", $token, $expires, $user['Application_ID']);
                    
                    if ($updateStmt->execute()) {
                        // Send reset email (in a real application, you would implement this)
                        $reset_link = "https://shezimandla20gmail.com.com/reset-password.php?token=$token";
                        
                        // For demonstration, we'll just show the link
                        $success_message = "Password reset link has been sent to your email.<br><br>
                                          <small>For demo purposes: <a href='$reset_link'>$reset_link</a></small>";
                        $show_form = false;
                    } else {
                        $error_message = "Error generating reset token. Please try again.";
                    }
                    
                    $updateStmt->close();
                } else {
                    // Don't reveal whether email exists for security
                    $success_message = "If your email exists in our system, you will receive a password reset link.";
                    $show_form = false;
                }
                
                $stmt->close();
            }
        }
    }
    
    // Generate CSRF token for new form
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
} catch (Exception $e) {
    error_log($e->getMessage());
    $error_message = "A system error occurred. Please try again later.";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ECOT College Library Password Recovery">
    <title>Forgot Password | ECOT Library</title>
    
    <!-- Favicon -->
    <link rel="icon" href="assets/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #00264d;
            --secondary-color: #4169E1;
            --accent-color: #E63946;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --border-radius: 12px;
            --box-shadow: 0 6px 15px rgba(0, 38, 77, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        .password-container {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }
        
        .password-container:hover {
            box-shadow: 0 10px 25px rgba(0, 38, 77, 0.15);
        }
        
        .password-header {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary-color);
            position: relative;
        }
        
        .password-header h2 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .password-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--secondary-color);
            border-radius: 3px;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.25rem rgba(65, 105, 225, 0.25);
        }
        
        .btn-submit {
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: var(--transition);
            width: 100%;
            margin-top: 1rem;
            color: white;
        }
        
        .btn-submit:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .additional-links {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .additional-links a {
            color: var(--secondary-color);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .additional-links a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 8px;
        }
        
        .library-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .library-logo img {
            height: 60px;
            width: auto;
        }
        
        .instructions {
            background-color: rgba(0, 38, 77, 0.05);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .success-message {
            text-align: center;
            padding: 2rem;
        }
        
        .success-message i {
            font-size: 3rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }

        
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .floating-shapes div {
            position: absolute;
            display: block;
            width: 20px;
            height: 20px;
            background: rgba(0, 38, 77, 0.05);
            bottom: -150px;
            animation: float 15s linear infinite;
            border-radius: 50%;
        }
        
        .floating-shapes div:nth-child(1) {
            left: 25%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
        }
        
        .floating-shapes div:nth-child(2) {
            left: 10%;
            width: 20px;
            height: 20px;
            animation-delay: 2s;
            animation-duration: 12s;
        }
        
        .floating-shapes div:nth-child(3) {
            left: 70%;
            width: 20px;
            height: 20px;
            animation-delay: 4s;
        }
        
        .floating-shapes div:nth-child(4) {
            left: 40%;
            width: 60px;
            height: 60px;
            animation-delay: 0s;
            animation-duration: 18s;
        }
        
        .floating-shapes div:nth-child(5) {
            left: 65%;
            width: 20px;
            height: 20px;
            animation-delay: 0s;
        }
        
        .floating-shapes div:nth-child(6) {
            left: 75%;
            width: 110px;
            height: 110px;
            animation-delay: 3s;
        }
        
        .floating-shapes div:nth-child(7) {
            left: 35%;
            width: 150px;
            height: 150px;
            animation-delay: 7s;
        }
        
        .floating-shapes div:nth-child(8) {
            left: 50%;
            width: 25px;
            height: 25px;
            animation-delay: 15s;
            animation-duration: 45s;
        }
        
        .floating-shapes div:nth-child(9) {
            left: 20%;
            width: 15px;
            height: 15px;
            animation-delay: 2s;
            animation-duration: 35s;
        }
        
        .floating-shapes div:nth-child(10) {
            left: 85%;
            width: 150px;
            height: 150px;
            animation-delay: 0s;
            animation-duration: 11s;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 0;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }
        
        
        @media (max-width: 576px) {
            .password-container {
                padding: 1.5rem;
            }
            
            .password-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<!-- Floating background shapes -->
<div class="floating-shapes">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
    </div>
    <div class="password-container">
        <div class="library-logo">
            <img src="../Student_Registration/ECOT.jpg" alt="ECOT College Library Logo" class="img-fluid">
        </div>
        
        <div class="password-header">
            <h2>Forgot Password</h2>
            <p>Recover your account access</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($show_form): ?>
            <div class="instructions">
                <p><i class="fas fa-info-circle me-2"></i> Enter the email address associated with your account. We'll send you a link to reset your password.</p>
            </div>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email Address
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Enter your registered email" required
                           value="<?php echo htmlspecialchars($email); ?>">
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <h3>Request Submitted</h3>
                <p><?php echo $success_message; ?></p>
                <div class="additional-links mt-4">
                    <a href="student_login.php"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="additional-links">
            <a href="student_login.php"><i class="fas fa-sign-in-alt"></i> Remember your password? Login</a>
            <span class="mx-2">|</span>
            <a href="APPLY.php"><i class="fas fa-user-plus"></i> Need an account? Register</a>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Focus on email field when page loads
        document.addEventListener('DOMContentLoaded', () => {
            const emailField = document.getElementById('email');
            if (emailField) {
                emailField.focus();
            }
        });
    </script>
</body>
</html>