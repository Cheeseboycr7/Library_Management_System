<?php
session_start();
include '../includes/db_connect1.php';

// Initialize variables
$error = '';
$success = '';
$currentSection = $_SESSION['current_section'] ?? 1;

if (!function_exists('validate_input')) {
    function validate_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

// Enhanced ISBN Validation Functions
function validateISBN($isbn) {
    // Remove all non-alphanumeric characters
    $isbn = preg_replace('/[^0-9X]/i', '', $isbn);
    $length = strlen($isbn);
    
    // Validate length
    if ($length !== 10 && $length !== 13) {
        return ['valid' => false, 'message' => 'ISBN must be 10 or 13 digits long'];
    }
    
    // Validate characters
    if ($length === 10 && !preg_match('/^[0-9]{9}[0-9X]$/i', $isbn)) {
        return ['valid' => false, 'message' => 'Invalid ISBN-10 format'];
    }
    
    if ($length === 13 && !preg_match('/^[0-9]{13}$/', $isbn)) {
        return ['valid' => false, 'message' => 'Invalid ISBN-13 format'];
    }
    
    // Perform checksum validation
    if ($length === 10) {
        return validateISBN10($isbn);
    } else {
        return validateISBN13($isbn);
    }
}

function validateISBN10($isbn) {
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += (10 - $i) * intval($isbn[$i]);
    }
    
    $checkDigit = strtoupper($isbn[9]);
    $checkValue = ($checkDigit === 'X') ? 10 : intval($checkDigit);
    $calculatedCheck = (11 - ($sum % 11)) % 11;
    
    $isValid = ($calculatedCheck === $checkValue);
    
    return [
        'valid' => $isValid,
        'message' => $isValid ? 'Valid ISBN-10' : 'Invalid ISBN-10 checksum'
    ];
}

function validateISBN13($isbn) {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $weight = ($i % 2 === 0) ? 1 : 3;
        $sum += $weight * intval($isbn[$i]);
    }
    
    $checkDigit = intval($isbn[12]);
    $calculatedCheck = (10 - ($sum % 10)) % 10;
    
    $isValid = ($calculatedCheck === $checkDigit);
    
    return [
        'valid' => $isValid,
        'message' => $isValid ? 'Valid ISBN-13' : 'Invalid ISBN-13 checksum'
    ];
}

// Navigation handling
if(isset($_POST['go_back'])) {
    $_SESSION['current_section'] = max(1, $currentSection - 1);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Book Details Section
        if (isset($_POST['save_book'])) {
            $isbn = validate_input($_POST['isbn']);
            $title = validate_input($_POST['title']);
            $edition = validate_input($_POST['edition']);
            $price = validate_input($_POST['price']);
            $quantity = validate_input($_POST['quantity']);
            $language = validate_input($_POST['language']);
            
            // Validate ISBN
            $isbnValidation = validateISBN($isbn);
            if (!$isbnValidation['valid']) {
                throw new Exception($isbnValidation['message']);
            }
            
            // Handle file upload
            $cover_image = '';
            if(isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/books/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileExt = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array(strtolower($fileExt), $allowedExts)) {
                    throw new Exception('Only JPG, JPEG, PNG & GIF files are allowed');
                }
                
                $fileName = uniqid() . '_' . basename($_FILES['cover_image']['name']);
                $filePath = $uploadDir . $fileName;
                
                if(move_uploaded_file($_FILES['cover_image']['tmp_name'], $filePath)) {
                    $cover_image = $filePath;
                } else {
                    throw new Exception('Failed to upload cover image');
                }
            }

            $stmt = $conn->prepare("INSERT INTO book (ISBN_NO, Title, Edition_Statement, Price, Quantity, Language, cover_image, Publisher_ID) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdsssi", $isbn, $title, $edition, $price, $quantity, $language, $cover_image, $publisher_id);
            $stmt->execute();
            
            $_SESSION['current_isbn'] = $isbn;
            $currentSection = 2;
            $success = "Book details saved successfully!";
        }

        // Author Details Section
if (isset($_POST['save_author'])) {
    $authorCount = isset($_POST['author_count']) ? intval($_POST['author_count']) : 1;
    $authorNames = $_POST['author_name'] ?? [];
    $authorSurnames = $_POST['author_surname'] ?? [];
    
    if (count($authorNames) !== $authorCount || count($authorSurnames) !== $authorCount) {
        throw new Exception('Invalid number of authors submitted');
    }
    
    $authorIds = [];
    for ($i = 0; $i < $authorCount; $i++) {
        $name = validate_input($authorNames[$i]);
        $surname = validate_input($authorSurnames[$i]);

        if (empty($name) || empty($surname)) {
            throw new Exception('All author names and surnames are required');
        }

        $stmt = $conn->prepare("INSERT INTO author (Name, Surname) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $surname);
        $stmt->execute();
        
        $authorIds[] = $stmt->insert_id;
    }
    
    $_SESSION['current_authors'] = $authorIds; // Store all author IDs in session
    $currentSection = 3;
    $success = "Author details saved successfully!";
}

        // Publisher Details Section
        if (isset($_POST['save_publisher'])) {
            $name = validate_input($_POST['publisher_name']);
            $date = validate_input($_POST['Date_of_Publisher']);

            if (empty($name) || empty($date)) {
                throw new Exception('Publisher name is required');
            }

            $stmt = $conn->prepare("INSERT INTO publisher (Name_of_Publisher) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            
            $_SESSION['current_publisher'] = $stmt->insert_id;
            $currentSection = 4;
            $success = "Publisher details saved successfully!";
        }

            // Book-Author Relationship Section
        if (isset($_POST['save_book_author'])) {
            $isbn = $_SESSION['current_isbn'] ?? '';
            $authorIds = $_POST['author_ids'] ?? [];
            $employee_id = intval($_POST['employee_id']);

            if (empty($isbn) || empty($authorIds) || $employee_id <= 0) {
                throw new Exception('Invalid book, author or employee selection');
            }

            foreach ($authorIds as $authorId) {
                $stmt = $conn->prepare("INSERT INTO book_author (ISBN_NO, Author_ID, Employee_ID) VALUES (?, ?, ?)");
                $stmt->bind_param("sii", $isbn, $authorId, $employee_id);
                $stmt->execute();
            }
            
            $currentSection = 5;
            $success = "Book-Author relationships saved!";
        }
        // Author-Publisher Relationship Section
        if (isset($_POST['save_author_publisher'])) {
            $author_id = intval($_POST['author_id']);
            $publisher_id = intval($_POST['publisher_id']);

            if ($author_id <= 0 || $publisher_id <= 0) {
                throw new Exception('Invalid author or publisher selection');
            }

            $stmt = $conn->prepare("INSERT INTO author_publisher (Author_ID, Publisher_ID) VALUES (?, ?)");
            $stmt->bind_param("ii", $author_id, $publisher_id);
            $stmt->execute();
            
            $currentSection = 6;
            $success = "Author-Publisher relationship saved!";
        }

        // Shelf Details Section
        if (isset($_POST['save_shelf'])) {
            $shelf_no = validate_input($_POST['shelf_no']);
            $category = validate_input($_POST['category']);

            if (empty($shelf_no)) {
                throw new Exception('Shelf number is required');
            }

            $stmt = $conn->prepare("INSERT INTO shelf (BookShelf_NO, Category) VALUES (?, ?)");
            $stmt->bind_param("ss", $shelf_no, $category);
            $stmt->execute();
            
            $_SESSION['current_shelf'] = $stmt->insert_id;
            $currentSection = 7;
            $success = "Shelf details saved successfully!";
        }

        // Book-Shelf Relationship Section
        if (isset($_POST['save_book_shelf'])) {
            $isbn = $_SESSION['current_isbn'] ?? '';
            $shelf_id = intval($_POST['shelf_id']);
            $employee_id = intval($_POST['employee_id']);

            if (empty($isbn) || $shelf_id <= 0 || $employee_id <= 0) {
                throw new Exception('Invalid book, shelf or employee selection');
            }

            $stmt = $conn->prepare("INSERT INTO book_shelf (ISBN_NO, Shelf_ID, Employee_ID) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $isbn, $shelf_id, $employee_id);
            $stmt->execute();
            
            $currentSection = 8;
            $success = "Book registration completed successfully!";
            
            // Clear session data after completion
            unset($_SESSION['current_isbn']);
            unset($_SESSION['current_author']);
            unset($_SESSION['current_publisher']);
            unset($_SESSION['current_shelf']);
        }

        // Only increment section if successful
        if(empty($error)) {
            $_SESSION['current_section'] = min(7, $currentSection + 1);
        }
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
        // Stay on current section when error occurs
        $_SESSION['current_section'] = $currentSection;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>ECOT Library Book Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #00264d;
            --secondary-color: #1a365d;
            --accent-color: #2b6cb0;
            --light-bg: #f8fafc;
        }

        /* Tooltip styles */
.tooltip {
  position: relative;
  display: inline-block;
}

.tooltip .tooltiptext {
  visibility: hidden;
  width: 200px;
  background-color: #333;
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
  font-size: 14px;
  font-weight: normal;
}

.tooltip .tooltiptext::after {
  content: "";
  position: absolute;
  top: 100%;
  left: 50%;
  margin-left: -5px;
  border-width: 5px;
  border-style: solid;
  border-color: #333 transparent transparent transparent;
}

.tooltip:hover .tooltiptext {
  visibility: visible;
  opacity: 1;
}

/* For form fields */
.form-group label {
  position: relative;
}

.form-group label .tooltip-icon {
  display: inline-block;
  width: 16px;
  height: 16px;
  background-color: #2b6cb0;
  color: white;
  border-radius: 50%;
  text-align: center;
  line-height: 16px;
  font-size: 12px;
  margin-left: 5px;
  cursor: help;
}

        * {
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--light-bg);
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 38, 77, 0.1);
            padding: 2rem;
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding: 1rem 0;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .step.active .step-circle {
            background: var(--primary-color);
            color: white;
        }

        .step-line {
            position: absolute;
            top: 20px;
            left: 50%;
            right: -50%;
            height: 2px;
            background: #e2e8f0;
            z-index: -1;
        }

        .section {
            display: none;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
            font-weight: 500;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.1);
        }

        button {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .file-upload {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-upload input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }

        .preview-image {
            max-width: 200px;
            margin-top: 1rem;
            border-radius: 4px;
            display: none;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .navigation-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 2rem;
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: white;
            padding: 0.75rem 2rem;
        }

        .btn-primary:hover, .btn-secondary:hover {
            filter: brightness(110%);
            transform: translateY(-1px);
        }

        .step-label {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }

        .step.active .step-label {
            color: var(--primary-color);
            font-weight: 500;
        }

        .form-text {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .isbn-validation {
            margin-top: 0.25rem;
            font-size: 0.875rem;
        }

        .isbn-valid {
            color: #166534;
        }

        .isbn-invalid {
            color: #b91c1c;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .progress-steps {
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .step {
                flex: none;
                width: 33%;
            }
        }

        .author-group {
        margin-bottom: 2rem;
        padding: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
    }
    
    .author-group h3 {
        color: var(--primary-color);
        margin-bottom: 1rem;
    }
    </style>
</head>
<body>
    <div class="container">
        <h1>ECOT Library Book Registration</h1>
        
        <div class="progress-steps">
            <?php 
            $steps = [
                1 => 'Book Details',
                2 => 'Author Info',
                3 => 'Publisher Info',
                4 => 'Relationships',
                5 => 'More Relations',
                6 => 'Shelf Info',
                7 => 'Final Step'
            ];
            
            foreach($steps as $num => $label): ?>
                <div class="step <?= $currentSection >= $num ? 'active' : '' ?>">
                    <div class="step-circle"><?= $num ?></div>
                    <div class="step-label"><?= $label ?></div>
                    <?php if($num < 7): ?><div class="step-line"></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if($error): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Book Details Section -->
        <div class="section <?= $currentSection == 1 ? 'active' : '' ?>">
            <h2>Book Information</h2>
            <form method="POST" enctype="multipart/form-data" id="bookForm">
                <div class="form-group">
                    <label for="isbn">ISBN Number
                    <span class="tooltip-icon" title="International Standard Book Number (10 or 13 digits)">i</span>
                    </label>
                    <input type="text" id="isbn" name="isbn" placeholder="Enter ISBN" required 
                    class="input-tooltip" title="Format: 10 or 13 digits (last digit can be X for ISBN-10)">
                    
                    <small class="form-text">Example: 978-3-16-148410-0 (13 digits) or 0-306-40615-2 (10 digits)</small>
                    <div id="isbnValidation" class="isbn-validation"></div>
                </div>
                
                <div class="form-group">
                    <label>Book Title
                    <span class="tooltip-icon" title="The complete title as it appears on the book cover">i</span>
                    </label>
                    <input type="text" name="title" placeholder="Enter Book Title" required
                    class="input-tooltip" title="Include subtitle if present (after colon or dash)">
                </div>
                
                <div class="form-group">
                    <label>Edition
                    <span class="tooltip-icon" title="Edition number or statement">i</span>
                    </label>
                    <input type="text" name="edition" placeholder="Enter Edition"
                    class="input-tooltip" title="Examples: 'First edition', '2nd edition', 'Revised edition'">
                </div>
                
                <div class="form-group">
                    <label>Price
                    <span class="tooltip-icon" title="Current price of the book">i</span>
                    </label>
                    <input type="number" step="0.01" name="price" placeholder="Enter Price" min="0"
                    class="input-tooltip" title="Enter price in local currency (e.g., 19.99)">
                </div>

                <div class="form-group">
                    <label>Book Quantity
                    <span class="tooltip-icon" title="Number of copies being registered">i</span>
                    </label>
                    <input type="number" step="1" name="quantity" placeholder="Enter Quantity" min="1" required
                    class="input-tooltip" title="Minimum 1, enter total number of copies">
                </div>

                <div class="form-group">
                    <label>Book Language
                    <span class="tooltip-icon" title="Primary language of the book content">i</span>
                    </label>
                    <input type="text" name="language" placeholder="Enter Language" required
                    class="input-tooltip" title="Examples: English, French, Spanish, etc.">
                </div>
                
                <div class="form-group">
                    <label>Cover Image
                    <span class="tooltip-icon" title="Upload an image of the book cover">i</span>
                    </label>
                    <div class="file-upload">
                        <input type="file" name="cover_image" accept="image/*" id="coverImage"
                        class="input-tooltip" title="Supported formats: JPG, PNG, GIF (Max 2MB)">
                        <button type="button">Choose File</button>
                    </div>
                    <img src="#" class="preview-image" alt="Cover Preview">
                    <small class="form-text">Allowed formats: JPG, JPEG, PNG, GIF (Max 2MB)</small>
                </div>
                
                <div class="navigation-buttons">
                    <button type="submit" name="save_book" class="btn-primary">Save & Continue →</button>
                </div>
            </form>
        </div>

       <!-- Author Details Section -->
<div class="section <?= $currentSection == 2 ? 'active' : '' ?>">
    <h2>Author Information</h2>
    <form method="POST" id="authorForm">
        <div class="form-group">
            <label>Number of Authors
                <span class="tooltip-icon" title="How many authors contributed to this book?">i</span>
            </label>
            <input type="number" id="authorCount" name="author_count" min="1" max="10" value="1" required
                   class="input-tooltip" title="Enter the number of authors (1-10)">
        </div>
        
        <div id="authorFieldsContainer">
            <!-- Author fields will be dynamically added here -->
            <div class="author-group">
                <h3>Author 1</h3>
                <div class="form-group">
                    <label>Author First Name
                        <span class="tooltip-icon" title="Author's legal first name">i</span>
                    </label>
                    <input type="text" name="author_name[]" placeholder="Enter First Name" required
                           class="input-tooltip" title="Enter the author's given name">
                </div>
                
                <div class="form-group">
                    <label>Author Last Name
                        <span class="tooltip-icon" title="Author's family name/surname">i</span>
                    </label>
                    <input type="text" name="author_surname[]" placeholder="Enter Last Name" required
                           class="input-tooltip" title="Enter the author's surname/family name">
                </div>
            </div>
        </div>
        
        <div class="navigation-buttons">
            <?php if($currentSection > 1): ?>
                <button type="button" class="btn-secondary" onclick="goBack()">← Back</button>
            <?php endif; ?>
            <button type="submit" name="save_author" class="btn-primary">Save & Continue →</button>
        </div>
    </form>
</div>

         <!-- Publisher Details Section -->
         <div class="section <?= $currentSection == 3 ? 'active' : '' ?>">
            <h2>Publisher Information</h2>
            <form method="POST" id="publisherForm">
                <div class="form-group">
                    <label>Publisher Name
                        <span class="tooltip-icon" title="Name of the publishing company">i</span>
                    </label>
                    <input type="text" name="publisher_name" placeholder="Enter Publisher Name" required
                           class="input-tooltip" title="Official name of the publisher (e.g., Penguin Random House)">
                </div>
                
                <div class="form-group">
                    <label>Publication Date
                        <span class="tooltip-icon" title="Date when the book was published">i</span>
                    </label>
                    <input type="date" name="Date_of_Publisher" placeholder="Enter Publication Date" required
                           class="input-tooltip" title="Select the official publication date">
                </div>
                
                <div class="navigation-buttons">
                    <?php if($currentSection > 1): ?>
                        <button type="button" class="btn-secondary" onclick="goBack()">← Back</button>
                    <?php endif; ?>
                    <button type="submit" name="save_publisher" class="btn-primary">Save & Continue →</button>
                </div>
            </form>
        </div>

        <!-- Book-Author Relationship Section -->
<div class="section <?= $currentSection == 4 ? 'active' : '' ?>">
    <h2>Link Book to Authors</h2>
    <form method="POST" id="bookAuthorForm">
        <?php 
        $authors = isset($_SESSION['current_authors']) ? $_SESSION['current_authors'] : [];
        foreach ($authors as $authorId): 
            $author = $conn->query("SELECT * FROM author WHERE Author_ID = $authorId")->fetch_assoc();
        ?>
            <div class="form-group">
                <label>Author: <?= $author['Name'] ?> <?= $author['Surname'] ?></label>
                <input type="hidden" name="author_ids[]" value="<?= $authorId ?>">
            </div>
        <?php endforeach; ?>
        
        <div class="form-group">
            <label>Responsible Employee
                <span class="tooltip-icon" title="Staff member handling this registration">i</span>
            </label>
            <select name="employee_id" required
                    class="input-tooltip" title="Select the employee processing this registration">
                <?php $employees = $conn->query("SELECT * FROM employee ORDER BY Emp_Surname, Emp_Name"); ?>
                <?php while($emp = $employees->fetch_assoc()): ?>
                    <option value="<?= $emp['Employee_ID'] ?>">
                        <?= $emp['Emp_Name'] ?> <?= $emp['Emp_Surname'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="navigation-buttons">
            <?php if($currentSection > 1): ?>
                <button type="button" class="btn-secondary" onclick="goBack()">← Back</button>
            <?php endif; ?>
            <button type="submit" name="save_book_author" class="btn-primary">Save & Continue →</button>
        </div>
    </form>
</div>
        <!-- Author-Publisher Relationship Section -->
        <div class="section <?= $currentSection == 5 ? 'active' : '' ?>">
            <h2>Link Author to Publisher</h2>
            <form method="POST" id="authorPublisherForm">
                <div class="form-group">
                    <label>Select Author
                    <span class="tooltip-icon" title="Choose the author from existing records">i</span>
                    </label>
                    <select name="author_id" required
                    class="input-tooltip" title="Select the author to link with publisher">
                        <?php $authors = $conn->query("SELECT * FROM author ORDER BY Surname, Name"); ?>
                        <?php while($author = $authors->fetch_assoc()): ?>
                            <option value="<?= $author['Author_ID'] ?>">
                                <?= $author['Name'] ?> <?= $author['Surname'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Select Publisher
                    <span class="tooltip-icon" title="Choose the publisher from existing records">i</span>
                    </label>
                    <select name="publisher_id" required
                    class="input-tooltip" title="Select the publisher for this author">
                        <?php $publishers = $conn->query("SELECT * FROM publisher ORDER BY Name_of_Publisher"); ?>
                        <?php while($pub = $publishers->fetch_assoc()): ?>
                            <option value="<?= $pub['Publisher_ID'] ?>"><?= $pub['Name_of_Publisher'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="navigation-buttons">
                    <?php if($currentSection > 1): ?>
                        <button type="button" class="btn-secondary" onclick="goBack()">← Back</button>
                    <?php endif; ?>
                    <button type="submit" name="save_author_publisher" class="btn-primary">Save & Continue →</button>
                </div>
            </form>
        </div>

        <!-- Shelf Details Section -->
        <div class="section <?= $currentSection == 6 ? 'active' : '' ?>">
            <h2>Shelf Information</h2>
            <form method="POST" id="shelfForm">
                <div class="form-group">
                    <label>Shelf Number
                    <span class="tooltip-icon" title="Physical shelf location identifier">i</span>
                    </label>
                    <input type="text" name="shelf_no" placeholder="Enter Shelf Number" required
                    class="input-tooltip" title="Example: A12, B5, C23 (alphanumeric)">
                </div>
                
                <div class="form-group">
                    <label>Category
                    <span class="tooltip-icon" title="General category for this shelf">i</span>
                    </label>
                    <input type="text" name="category" placeholder="Enter Category"
                     class="input-tooltip" title="Examples: Fiction, Science, History, Reference">
                </div>
                
                <div class="navigation-buttons">
                    <?php if($currentSection > 1): ?>
                        <button type="button" class="btn-secondary" onclick="goBack()">← Back</button>
                    <?php endif; ?>
                    <button type="submit" name="save_shelf" class="btn-primary">Save & Continue →</button>
                </div>
            </form>
        </div>

        <!-- Book-Shelf Relationship Section -->
        <div class="section <?= $currentSection == 7 ? 'active' : '' ?>">
            <h2>Assign Book to Shelf</h2>
            <form method="POST" id="bookShelfForm">
                <div class="form-group">
                    <label>Select Shelf
                    <span class="tooltip-icon" title="Choose the shelf location for this book">i</span>
                    </label>
                    <select name="shelf_id" required
                    class="input-tooltip" title="Select where this book will be physically stored">
                        <?php $shelves = $conn->query("SELECT * FROM shelf ORDER BY BookShelf_NO"); ?>
                        <?php while($shelf = $shelves->fetch_assoc()): ?>
                            <option value="<?= $shelf['Shelf_ID'] ?>"><?= $shelf['BookShelf_NO'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Responsible Employee
                    <span class="tooltip-icon" title="Staff member handling this assignment">i</span>
                    </label>
                    <select name="employee_id" required
                    class="input-tooltip" title="Select the employee processing this shelf assignment">
                        <?php $employees = $conn->query("SELECT * FROM employee ORDER BY Emp_Surname, Emp_Name"); ?>
                        <?php while($emp = $employees->fetch_assoc()): ?>
                            <option value="<?= $emp['Employee_ID'] ?>">
                                <?= $emp['Emp_Name'] ?> <?= $emp['Emp_Surname'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="navigation-buttons">
                    <?php if($currentSection > 1): ?>
                        <button type="button" class="btn-secondary" onclick="goBack()">← Back</button>
                    <?php endif; ?>
                    <button type="submit" name="save_book_shelf" class="btn-primary">Complete Registration</button>
                </div>
            </form>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <i class="fas fa-dashboard"></i>
        <a href="try.php">Back to Dashboard</a>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('coverImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('File size must be less than 2MB');
                e.target.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function() {
                const preview = document.querySelector('.preview-image');
                preview.src = reader.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        });

        // ISBN validation
        document.getElementById('isbn').addEventListener('blur', function() {
            const isbn = this.value.replace(/[-\s]/g, '');
            const validationDiv = document.getElementById('isbnValidation');
            
            if (isbn.length === 0) {
                validationDiv.textContent = '';
                return;
            }
            
            // Client-side validation
            if (isbn.length !== 10 && isbn.length !== 13) {
                validationDiv.textContent = 'ISBN must be 10 or 13 digits long';
                validationDiv.className = 'isbn-validation isbn-invalid';
                return;
            }
            
            if (isbn.length === 10 && !/^\d{9}[\dXx]$/i.test(isbn)) {
                validationDiv.textContent = 'Invalid ISBN-10 format';
                validationDiv.className = 'isbn-validation isbn-invalid';
                return;
            }
            
            if (isbn.length === 13 && !/^\d{13}$/.test(isbn)) {
                validationDiv.textContent = 'Invalid ISBN-13 format';
                validationDiv.className = 'isbn-validation isbn-invalid';
                return;
            }
            
            // If basic format is valid, show checking message
            validationDiv.textContent = 'Checking ISBN...';
            validationDiv.className = 'isbn-validation';
            
            // You could add AJAX validation here to check against database
            // For now we'll just show that the format is valid
            setTimeout(() => {
                validationDiv.textContent = 'ISBN format is valid';
                validationDiv.className = 'isbn-validation isbn-valid';
            }, 500);
        });

        // Navigation functions
        function goBack() {
            <?php $_SESSION['current_section'] = max(1, $currentSection - 1); ?>
            window.location.reload();
        }

        // Form validation before submission
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    alert('Please fill in all required fields correctly');
                    // Focus on first invalid field
                    const invalidField = form.querySelector(':invalid');
                    if (invalidField) {
                        invalidField.focus();
                    }
                }
            });
        });


         // Dynamic author fields
    document.getElementById('authorCount').addEventListener('change', function() {
        const count = parseInt(this.value);
        const container = document.getElementById('authorFieldsContainer');
        container.innerHTML = '';
        
        for (let i = 0; i < count; i++) {
            const authorGroup = document.createElement('div');
            authorGroup.className = 'author-group';
            authorGroup.innerHTML = `
                <h3>Author ${i+1}</h3>
                <div class="form-group">
                    <label>Author First Name
                        <span class="tooltip-icon" title="Author's legal first name">i</span>
                    </label>
                    <input type="text" name="author_name[]" placeholder="Enter First Name" required
                           class="input-tooltip" title="Enter the author's given name">
                </div>
                
                <div class="form-group">
                    <label>Author Last Name
                        <span class="tooltip-icon" title="Author's family name/surname">i</span>
                    </label>
                    <input type="text" name="author_surname[]" placeholder="Enter Last Name" required
                           class="input-tooltip" title="Enter the author's surname/family name">
                </div>
            `;
            container.appendChild(authorGroup);
        }
    });
    </script>
</body>
</html>
