<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecot_library2";

// Initialize variables
$error_message = '';
$success_message = '';
$valid_token = false;
$user_id = null;
$token = '';

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Check if token is provided in URL
    if (isset($_GET['token'])) {
        $token = trim($_GET['token']);
        
        // Validate token format
        if (ctype_xdigit($token) && strlen($token) === 64) {
            // Check token in database
            $current_time = date("Y-m-d H:i:s");
            $stmt = $conn->prepare("SELECT Application_ID FROM application WHERE reset_token = ? AND reset_expires > ?");
            $stmt->bind_param("ss", $token, $current_time);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user_id = $user['Application_ID'];
                $valid_token = true;
                
                // Store user ID in session for additional security
                $_SESSION['reset_user_id'] = $user_id;
                $_SESSION['reset_token'] = $token;
            } else {
                $error_message = "Invalid or expired reset link. Please request a new password reset.";
            }
            
            $stmt->close();
        } else {
            $error_message = "Invalid reset link format.";
        }
    } else {
        $error_message = "No reset token provided.";
    }

    // Process form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error_message = "Invalid form submission. Please try again.";
        } else {
            $new_password = trim($_POST['new_password']);
            $confirm_password = trim($_POST['confirm_password']);
            
            // Validate passwords
            if (empty($new_password) || empty($confirm_password)) {
                $error_message = "Please enter and confirm your new password.";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "Passwords do not match.";
            } elseif (strlen($new_password) < 8) {
                $error_message = "Password must be at least 8 characters long.";
            } else {
                // Verify session matches token user
                if ($_SESSION['reset_user_id'] == $user_id && $_SESSION['reset_token'] == $token) {
                    // Hash the new password
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password and clear reset token
                    $updateStmt = $conn->prepare("UPDATE application SET Password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                    $updateStmt->bind_param("si", $password_hash, $user_id);
                    
                    if ($updateStmt->execute()) {
                        $success_message = "Your password has been successfully reset.";
                        $valid_token = false; // Invalidate after successful reset
                        
                        // Clear reset session variables
                        unset($_SESSION['reset_user_id']);
                        unset($_SESSION['reset_token']);
                        
                        // Log the user in automatically if desired
                        // $_SESSION['user_id'] = $user_id;
                        // $_SESSION['username'] = $user['username'];
                    } else {
                        $error_message = "Error updating password. Please try again.";
                    }
                    
                    $updateStmt->close();
                } else {
                    $error_message = "Session validation failed. Please request a new password reset.";
                }
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
    <meta name="description" content="ECOT College Library Password Reset">
    <title>Reset Password | ECOT Library</title>
    
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
    
    <!-- Password Strength Meter -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/password-strength-meter/2.1.1/password.min.css">
    
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
        
        .reset-container {
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
        
        .reset-container:hover {
            box-shadow: 0 10px 25px rgba(0, 38, 77, 0.15);
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary-color);
            position: relative;
        }
        
        .reset-header h2 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .reset-header::after {
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
        
        .btn-reset {
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
        
        .btn-reset:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-reset:active {
            transform: translateY(0);
        }
        
        .password-container {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--dark-color);
            opacity: 0.7;
            transition: var(--transition);
        }
        
        .toggle-password:hover {
            opacity: 1;
            color: var(--primary-color);
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
        
        .requirements {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 0.5rem;
        }
        
        .password-strength-meter {
            margin-top: 0.5rem;
            height: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-meter-fill {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
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
        
        @media (max-width: 576px) {
            .reset-container {
                padding: 1.5rem;
            }
            
            .reset-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="library-logo">
            <img src="../Student_Registration/ECOT.jpg" alt="ECOT College Library Logo" class="img-fluid">
        </div>
        
        <?php if ($valid_token && empty($success_message)): ?>
            <div class="reset-header">
                <h2>Reset Your Password</h2>
                <p>Create a new secure password</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?token=' . $token); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="mb-3 password-container">
                    <label for="new_password" class="form-label">
                        <i class="fas fa-lock me-2"></i>New Password
                    </label>
                    <input type="password" class="form-control" id="new_password" name="new_password" 
                           placeholder="Enter new password" required
                           minlength="8"
                           oninput="checkPasswordStrength(this.value)">
                    <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility('new_password')"></i>
                    <div class="password-strength-meter">
                        <div class="password-strength-meter-fill" id="password-strength-bar"></div>
                    </div>
                    <div class="requirements">
                        Password must be at least 8 characters long
                    </div>
                </div>
                
                <div class="mb-3 password-container">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Confirm Password
                    </label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm new password" required
                           minlength="8">
                    <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility('confirm_password')"></i>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-reset" id="submit-btn">
                        <i class="fas fa-key me-2"></i>Reset Password
                    </button>
                </div>
            </form>
            
        <?php elseif (!empty($success_message)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <h3>Password Reset Successful</h3>
                <p><?php echo $success_message; ?></p>
                <div class="d-grid mt-4">
                    <a href="student_login.php" class="btn btn-reset">
                        <i class="fas fa-sign-in-alt me-2"></i>Login Now
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error_message; ?>
                <div class="additional-links mt-3">
                    <a href="forgot-password.php"><i class="fas fa-key me-1"></i>Request a new reset link</a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="additional-links">
            <a href="student_login.php"><i class="fas fa-sign-in-alt"></i> Back to Login</a>
            <span class="mx-2">|</span>
            <a href="APPLY.php"><i class="fas fa-user-plus"></i> Create Account</a>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Password Strength Meter -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePasswordVisibility(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = passwordInput.nextElementSibling;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        
        // Check password strength
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength-bar');
            const submitBtn = document.getElementById('submit-btn');
            
            if (!password) {
                strengthBar.style.width = '0%';
                strengthBar.style.backgroundColor = '';
                return;
            }
            
            // Use zxcvbn for password strength estimation
            const result = zxcvbn(password);
            const strength = result.score; // 0-4
            
            // Update strength meter
            switch(strength) {
                case 0:
                case 1:
                    strengthBar.style.width = '25%';
                    strengthBar.style.backgroundColor = 'var(--danger-color)';
                    submitBtn.disabled = true;
                    break;
                case 2:
                    strengthBar.style.width = '50%';
                    strengthBar.style.backgroundColor = 'var(--warning-color)';
                    submitBtn.disabled = false;
                    break;
                case 3:
                    strengthBar.style.width = '75%';
                    strengthBar.style.backgroundColor = 'var(--success-color)';
                    submitBtn.disabled = false;
                    break;
                case 4:
                    strengthBar.style.width = '100%';
                    strengthBar.style.backgroundColor = 'var(--success-color)';
                    submitBtn.disabled = false;
                    break;
            }
        }
        
        // Validate password match on form submission
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please try again.');
            }
        });
        
        // Focus on password field when page loads
        document.addEventListener('DOMContentLoaded', () => {
            const passwordField = document.getElementById('new_password');
            if (passwordField) {
                passwordField.focus();
            }
        });
    </script>
</body>
</html>