<?php

$host = "localhost";
$username = "root";
$password = "";
$dbname = "ecot_library2";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



$errors = [];
$successMessage = '';

// Initialize form fields
$fields = [
    'faculty' => '',
    'department' => '',
    'name' => '',
    'surname' => '',
    'cellphone' => '',
    'parent_cell' => '',
    'course' => '',
    'id_number' => '',
    'username' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => '',
    'terms' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    foreach ($fields as $field => $value) {
        if ($field === 'password' || $field === 'confirm_password') {
            $fields[$field] = trim($_POST[$field] ?? '');
        } elseif ($field === 'terms') {
            $fields[$field] = isset($_POST[$field]) ? 1 : 0;
        } else {
            $fields[$field] = trim(htmlspecialchars($_POST[$field] ?? ''));
        }
    }

    // Input validation
    if (empty($fields['name']) || !preg_match('/^[A-Za-z\s\-]{4,20}$/', $fields['name'])) {
        $errors['name'] = 'Name must be alphabetic (4-20 chars), may include spaces/hyphens.';
    }

    if (empty($fields['surname']) || !preg_match('/^[A-Za-z\s\-]{4,20}$/', $fields['surname'])) {
        $errors['surname'] = 'Surname must be alphabetic (4-20 chars), may include spaces/hyphens.';
    }

    if (empty($fields['faculty'])) {
        $errors['faculty'] = 'Faculty is required.';
    }

    if (empty($fields['department'])) {
        $errors['department'] = 'Department is required.';
    }

    if (empty($fields['cellphone']) || !preg_match('/^\d{8}$/', $fields['cellphone'])) {
        $errors['cellphone'] = 'Valid 8-digit cellphone number required.';
    }

    if (empty($fields['parent_cell']) || !preg_match('/^\d{8}$/', $fields['parent_cell'])) {
        $errors['parent_cell'] = 'Valid 8-digit parent cellphone number required.';
    }

    if (empty($fields['course'])) {
        $errors['course'] = 'Course is required.';
    }

    if (empty($fields['id_number']) || !preg_match('/^\d{13}$/', $fields['id_number'])) {
        $errors['id_number'] = 'Valid 13-digit ID number required.';
    } else {
        $year = substr($fields['id_number'], 0, 2);
        $month = substr($fields['id_number'], 2, 2);
        $day = substr($fields['id_number'], 4, 2);
        $fullYear = '20' . $year;
        if (!checkdate((int)$month, (int)$day, (int)$fullYear)) {
            $errors['id_number'] = 'Invalid ID number. First 6 digits must be a valid date (YYMMDD).';
        }
    }

    if (empty($fields['email']) || !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Valid email address required.';
    }

    if (empty($fields['username']) || !preg_match('/^[A-Za-z0-9_]{4,20}$/', $fields['username'])) {
        $errors['username'] = 'Username must be alphanumeric (4-20 chars), underscores allowed.';
    }

    if (empty($fields['password']) || strlen($fields['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($fields['password'] !== $fields['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (empty($fields['terms'])) {
        $errors['terms'] = 'You must accept the terms and conditions.';
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($fields['password'], PASSWORD_BCRYPT);

        try {
            $stmt = $conn->prepare("INSERT INTO application (Name, Surname, Faculty, Department, Cellphone_No, Parent_Cellphone_No, Course, ID_Number, Username, Password, Email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssssssss', $fields['name'], $fields['surname'], $fields['faculty'], $fields['department'], $fields['cellphone'], $fields['parent_cell'], $fields['course'], $fields['id_number'], $fields['username'], $hashedPassword, $fields['email']);
            $stmt->execute();

            $successMessage = 'Application successfully submitted!';
            // Clear fields after successful submission
            $fields = array_map(function() { return ''; }, $fields);
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'ID_Number') !== false) {
                    $errors['id_number'] = 'This ID number is already registered.';
                } elseif (strpos($e->getMessage(), 'Email') !== false) {
                    $errors['email'] = 'This email is already in use.';
                } elseif (strpos($e->getMessage(), 'Username') !== false) {
                    $errors['username'] = 'This username is already taken.';
                }
            } else {
                $errors['database'] = 'An unexpected error occurred. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1, maximum-scale=1">
    <title>Student Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            background-color: #f5f7fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        .form-container {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
            width: 80%;
            max-width: 900px;
            margin: 30px 0;
        }
        .form-header {
            text-align: center;
            margin-bottom: 25px;
            font-size: 1.8rem;
            font-weight: 600;
            color: #00264d;
            position: relative;
        }
        .form-header:after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background: #00264d;
            margin: 10px auto 0;
        }
        .btn-primary {
            background-color: #00264d;
            border-color: #00264d;
            font-size: 1rem;
            font-weight: 600;
            padding: 12px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #001a33;
            border-color: #001a33;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .form-label {
            font-weight: 500;
            color: #00264d;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #d1d5db;
            padding: 12px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            border-color: #00264d;
            box-shadow: 0 0 0 3px rgba(0, 38, 77, 0.15);
        }
        .error {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 5px;
        }
        .success {
            color: #28a745;
            font-size: 1em;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 5px;
        }
        a {
            text-decoration: none;
            color: #00264d;
            font-weight: 500;
            transition: color 0.3s;
        }
        a:hover {
            color: #001a33;
            text-decoration: underline;
        }
        .tooltip-icon {
            color: #6b7280;
            cursor: pointer;
            transition: color 0.3s;
        }
        .tooltip-icon:hover {
            color: #00264d;
        }
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 35px;
            cursor: pointer;
            color: #00264d;
        }
        .progress {
            height: 5px;
            margin-top: 5px;
        }
        .progress-bar {
            transition: width 0.3s ease;
        }
        .input-group-text {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
        }
        .character-count {
            font-size: 0.8rem;
            color: #6c757d;
            text-align: right;
            margin-top: 5px;
        }
        .tooltip-container {
            position: relative;
            display: inline-block;
        }
        .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .tooltip-container:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
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

        .library-logo img {
            height: 80px;
            width: auto;
        }

        .library-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
    </style>
</head>
<body>

<div class="form-container">
       <div class="library-logo">
            <img src="../Student_Registration/ECOT.jpg" alt="ECOT College Library Logo" class="img-fluid">
        </div>
    <h2 class="form-header">Student Application Form</h2>
    
    <?php if (!empty($successMessage)): ?>
        <div class="success"><?php echo $successMessage; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" id="applicationForm">
        <div class="row">
            <?php
            $inputFields = [
                'name' => ['label' => 'Name', 'type' => 'text', 'tip' => 'Your full first name (4-20 alphabetic characters)'],
                'surname' => ['label' => 'Surname', 'type' => 'text', 'tip' => 'Your family name (4-20 alphabetic characters)'],
                'cellphone' => ['label' => 'Cellphone', 'type' => 'tel', 'tip' => 'Your 8-digit phone number (e.g., 78######)'],
                'parent_cell' => ['label' => 'Parent Cellphone', 'type' => 'tel', 'tip' => 'Parent/guardian 8-digit phone number'],
                'course' => ['label' => 'Course', 'type' => 'text', 'tip' => 'The course you doing'],
                'email' => ['label' => 'Email', 'type' => 'email', 'tip' => 'A valid email address we can contact you at'],
                'id_number' => ['label' => 'ID Number', 'type' => 'text', 'tip' => 'Your 13-digit ID number (first 6 digits should be YYMMDD)'],
                'username' => ['label' => 'Username', 'type' => 'text', 'tip' => '4-20 characters, letters, numbers, or underscores']
            ];
            
            foreach ($inputFields as $field => $config): ?>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="<?php echo $field; ?>" class="form-label">
                            <?php echo $config['label']; ?>
                            <span class="tooltip-container">
                                <span class="tooltip-icon">?</span>
                                <span class="tooltip-text"><?php echo $config['tip']; ?></span>
                            </span>
                        </label>
                        <input type="<?php echo $config['type']; ?>" class="form-control" id="<?php echo $field; ?>" 
                               name="<?php echo $field; ?>" value="<?= htmlspecialchars($fields[$field]) ?>"
                               <?php echo $field === 'id_number' ? 'maxlength="13"' : ''; ?>
                               <?php echo in_array($field, ['cellphone', 'parent_cell']) ? 'maxlength="8"' : ''; ?>>
                        <?php if (isset($errors[$field])): ?>
                            <div class="error"><?php echo $errors[$field]; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="faculty" class="form-label">
                        Faculty
                        <span class="tooltip-container">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltip-text">Select your faculty from the list</span>
                        </span>
                    </label>
                    <select class="form-control" id="faculty" name="faculty">
                        <option value="">Select Faculty</option>
                        <option value="ICT" <?= $fields['faculty'] == 'ICT' ? 'selected' : '' ?>>ICT</option>
                        <option value="Business Administration" <?= $fields['faculty'] == 'Business Administration' ? 'selected' : '' ?>>Business Administration</option>
                        <option value="Education" <?= $fields['faculty'] == 'Education' ? 'selected' : '' ?>>Education</option>
                        <option value="Engineering and Science" <?= $fields['faculty'] == 'Engineering and Science' ? 'selected' : '' ?>>Engineering and Science</option>
                        <option value="Building and Civil Engineering" <?= $fields['faculty'] == 'Building and Civil Engineering' ? 'selected' : '' ?>>Building and Civil Engineering</option>
                    </select>
                    <?php if (isset($errors['faculty'])): ?>
                        <div class="error"><?php echo $errors['faculty']; ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label for="department" class="form-label">
                        Department
                        <span class="tooltip-container">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltip-text">Select your department based on chosen faculty</span>
                        </span>
                    </label>
                    <select class="form-control" id="department" name="department">
                        <option value="">Select Department</option>
                        <option value="Commerce" <?= $fields['department'] == 'Commerce' ? 'selected' : '' ?>>Commerce</option>
                        <option value="Computer Science" <?= $fields['department'] == 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                        <option value="Tourism Management" <?= $fields['department'] == 'Tourism Management' ? 'selected' : '' ?>>Tourism Management</option>
                        <option value="Mechanical" <?= $fields['department'] == 'Mechanical' ? 'selected' : '' ?>>Mechanical</option>
                        <option value="Electrical Engineering" <?= $fields['department'] == 'Electrical Engineering' ? 'selected' : '' ?>>Electrical Engineering</option>
                        <option value="Civil Engineering" <?= $fields['department'] == 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                        <option value="Building studies" <?= $fields['department'] == 'Building studies' ? 'selected' : '' ?>>Building studies</option>
                        <option value="Automotive (heavy)" <?= $fields['department'] == 'Automotive (heavy)' ? 'selected' : '' ?>>Automotive (heavy)</option>
                        <option value="Automotive (light)" <?= $fields['department'] == 'Automotive (light)' ? 'selected' : '' ?>>Automotive (light)</option>
                    </select>
                    <?php if (isset($errors['department'])): ?>
                        <div class="error"><?php echo $errors['department']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3 password-container">
                    <label for="password" class="form-label">
                        Password
                        <span class="tooltip-container">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltip-text">At least 8 characters with a mix of letters, numbers, and symbols</span>
                        </span>
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" value="<?= htmlspecialchars($fields['password']) ?>">
                        <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-danger" id="password-strength-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="form-text text-muted" id="password-strength-text">Weak</small>
                        <small class="character-count" id="password-count">0/8</small>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="error"><?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">
                        Confirm Password
                        <span class="tooltip-container">
                            <span class="tooltip-icon">?</span>
                            <span class="tooltip-text">Re-enter your password to confirm</span>
                        </span>
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" value="<?= htmlspecialchars($fields['confirm_password']) ?>">
                        <span class="input-group-text" id="toggleConfirmPassword" style="cursor: pointer;">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                    <div id="password-match" class="form-text"></div>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="error"><?php echo $errors['confirm_password']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="terms" name="terms" <?= $fields['terms'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="terms">
                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                <span class="tooltip-container">
                    <span class="tooltip-icon">?</span>
                    <span class="tooltip-text">You must accept the terms to proceed</span>
                </span>
            </label>
            <?php if (isset($errors['terms'])): ?>
                <div class="error"><?php echo $errors['terms']; ?></div>
            <?php endif; ?>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg">Submit Application</button>
            <button type="reset" class="btn btn-outline-secondary">Reset Form</button>
        </div>
        
        <div class="text-center mt-3">
            <p>
                Already have an account? <a href="student_login.php">Log in here</a>
            </p>
        </div>
    </form>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Student Application Agreement</h6>
                <p>By submitting this application, you agree to the following terms:</p>
                <ol>
                    <li>All information provided is accurate and complete.</li>
                    <li>You consent to the institution processing your personal information.</li>
                </ol>
                <p>For more details, please contact our admissions office.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/zxcvbn@4.4.2/dist/zxcvbn.js"></script>
<script>
    // Password visibility toggle
    const togglePassword = document.querySelector('#togglePassword');
    const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
    const password = document.querySelector('#password');
    const confirmPassword = document.querySelector('#confirm_password');
    
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    }
    
    if (toggleConfirmPassword) {
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            this.querySelector('i').className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    }

    // Password strength and matching
    if (password) {
        password.addEventListener('input', function() {
            const strength = zxcvbn(this.value);
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');
            const count = document.getElementById('password-count');
            
            // Update character count
            if (count) count.textContent = `${this.value.length}/8`;
            
            // Update strength meter
            if (this.value.length === 0) {
                if (strengthBar) strengthBar.style.width = '0%';
                if (strengthBar) strengthBar.className = 'progress-bar bg-danger';
                if (strengthText) strengthText.textContent = '';
            } else {
                const width = Math.min(100, (this.value.length / 12) * 100);
                if (strengthBar) strengthBar.style.width = `${width}%`;
                
                if (strength.score < 2) {
                    if (strengthBar) strengthBar.className = 'progress-bar bg-danger';
                    if (strengthText) strengthText.textContent = 'Weak';
                } else if (strength.score < 4) {
                    if (strengthBar) strengthBar.className = 'progress-bar bg-warning';
                    if (strengthText) strengthText.textContent = 'Moderate';
                } else {
                    if (strengthBar) strengthBar.className = 'progress-bar bg-success';
                    if (strengthText) strengthText.textContent = 'Strong';
                }
            }
            
            // Check password match
            checkPasswordMatch();
        });
    }
    
    if (confirmPassword) {
        confirmPassword.addEventListener('input', checkPasswordMatch);
    }
    
    function checkPasswordMatch() {
        const matchText = document.getElementById('password-match');
        if (password && confirmPassword && matchText) {
            if (password.value && confirmPassword.value) {
                if (password.value === confirmPassword.value) {
                    matchText.textContent = 'Passwords match';
                    matchText.style.color = 'green';
                } else {
                    matchText.textContent = 'Passwords do not match';
                    matchText.style.color = 'red';
                }
            } else {
                matchText.textContent = '';
            }
        }
    }

    // Form validation
    const applicationForm = document.getElementById('applicationForm');
    if (applicationForm) {
        applicationForm.addEventListener('submit', function(e) {
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match. Please correct and try again.');
                confirmPassword.focus();
            }
            
            const terms = document.getElementById('terms');
            if (terms && !terms.checked) {
                e.preventDefault();
                alert('You must accept the terms and conditions to proceed.');
            }
        });
    }

    // Character counter for text inputs
    document.querySelectorAll('input[maxlength]').forEach(input => {
        const counter = document.createElement('small');
        counter.className = 'character-count';
        counter.textContent = `0/${input.getAttribute('maxlength')}`;
        input.parentNode.appendChild(counter);
        
        input.addEventListener('input', function() {
            counter.textContent = `${this.value.length}/${this.getAttribute('maxlength')}`;
        });
    });

    // Enhanced faculty-department relationship
    const facultyDepartments = {
        'ICT': ['Computer Science', 'Information Technology', 'Data Science'],
        'Business Administration': ['Commerce', 'Tourism Management', 'Business Management'],
        'Education': ['Early Childhood', 'Primary Education', 'Secondary Education'],
        'Engineering and Science': ['Mechanical', 'Electrical Engineering', 'Chemical Engineering'],
        'Building and Civil Engineering': ['Civil Engineering', 'Building Studies', 'Construction Management']
    };
    
    const facultySelect = document.getElementById('faculty');
    if (facultySelect) {
        facultySelect.addEventListener('change', function() {
            const departmentSelect = document.getElementById('department');
            if (departmentSelect) {
                departmentSelect.innerHTML = '<option value="">Select Department</option>';
                
                if (this.value && facultyDepartments[this.value]) {
                    facultyDepartments[this.value].forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept;
                        option.textContent = dept;
                        if (dept === '<?= $fields['department'] ?>') {
                            option.selected = true;
                        }
                        departmentSelect.appendChild(option);
                    });
                }
            }
        });
    }
</script>
</body>
</html>