<?php
include '../includes/db_connect1.php';

$error_message = '';
$success_message = '';

if (!function_exists('validate_input')) {
    function validate_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

function validateISBN($isbn) {
    $isbn = str_replace(['-', ' '], '', $isbn);
    if (strlen($isbn) == 10) {
        return validateISBN10($isbn);
    } elseif (strlen($isbn) == 13) {
        return validateISBN13($isbn);
    }
    return false;
}

function validateISBN10($isbn) {
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += (10 - $i) * intval($isbn[$i]);
    }
    $check = (11 - ($sum % 11)) % 11;
    $lastChar = strtolower($isbn[9]);
    return ($check == 10 && $lastChar == 'x') || ($check == intval($lastChar));
}

function validateISBN13($isbn) {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += ($i % 2 == 0 ? 1 : 3) * intval($isbn[$i]);
    }
    $check = (10 - ($sum % 10)) % 10;
    return $check == intval($isbn[12]);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Gather inputs
    $isbn = validate_input($_POST['isbn'] ?? '');
    $title = validate_input($_POST['title'] ?? '');
    $edition = validate_input($_POST['edition'] ?? '');
    $language = validate_input($_POST['language'] ?? '');
    $price = validate_input($_POST['price'] ?? 0);
    $series = validate_input($_POST['series'] ?? '');
    $notes = validate_input($_POST['notes'] ?? '');
    $callno = validate_input($_POST['callno'] ?? '');
    $quantity = validate_input($_POST['quantity'] ?? 0);
    $publisher_name = validate_input($_POST['publisher_name'] ?? '');
    $shelf_number = validate_input($_POST['shelf_number'] ?? '');
   
    // Add the account number
    $authors = $_POST['author'] ?? []; // Array of authors
    $image_path = null;

    // Validate inputs
    if (!validateISBN($isbn)) {
        $error_message = "Invalid ISBN format. Please enter a valid ISBN-10 or ISBN-13.";
    } elseif (!is_numeric($quantity) || $quantity < 0) {
        $error_message = "Invalid quantity.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error_message = "Invalid price.";
    }  else {
        try {
            $pdo->beginTransaction();

            // Add publisher if provided
            $publisher_id = null;
            if (!empty($publisher_name)) {
                $stmt = $pdo->prepare("INSERT INTO Publisher (Name_of_Publisher) VALUES (?)");
                $stmt->execute([$publisher_name]);
                $publisher_id = $pdo->lastInsertId();
            }

            // Add shelf if provided
            $shelf_id = null;
            if (!empty($shelf_number)) {
                $stmt = $pdo->prepare("INSERT INTO shelf (BookShelf_NO) VALUES (?)");
                $stmt->execute([$shelf_number]);
                $shelf_id = $pdo->lastInsertId();
            }

            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/books/';
                $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
                $filePath = $uploadDir . $fileName;
                $fileType = pathinfo($filePath, PATHINFO_EXTENSION);

                $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array(strtolower($fileType), $allowedTypes)) {
                    $error_message = "Invalid file type. Only JPG, JPEG, PNG, and WEBP files are allowed.";
                } else {
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                        $image_path = $filePath;
                    } else {
                        $error_message = "Failed to upload the image.";
                    }
                }
            }

            // Insert book details
            $stmt = $pdo->prepare("INSERT INTO Book (ISBN_NO, Title, Edition_Statement, Language, Price, Series, Notes, Call_NO, Quantity, cover_image, Publisher_ID) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$isbn, $title, $edition, $language, $price, $series, $notes, $callno, $quantity, $image_path, $publisher_id]);

   // Ensure authors array is set and contains the necessary fields
$authors = $_POST['author'] ?? []; // Array of authors

// Debugging: Display the authors array to verify structure
echo "<pre>";
var_dump($authors);  // Check the structure of the authors array
echo "</pre>";

foreach ($authors as $author) {
    // Ensure both name and surname are not empty
    if (!empty($author['name']) && !empty($author['surname'])) {
        // Check if author already exists to avoid duplicates
        $stmt = $pdo->prepare("SELECT Author_ID FROM Author WHERE Name = ? AND Surname = ?");
        $stmt->execute([$author['name'], $author['surname']]);
        $existingAuthor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingAuthor) {
            $author_id = $existingAuthor['Author_ID']; // Use existing author ID
        } else {
            // Insert new author
            $stmt = $pdo->prepare("INSERT INTO Author (Name, Surname) VALUES (?, ?)");
            $stmt->execute([$author['name'], $author['surname']]);
            $author_id = $pdo->lastInsertId();
        }

        // Ensure book exists before linking
        if (!empty($isbn)) {
            $stmt = $pdo->prepare("SELECT ISBN_NO FROM Book WHERE ISBN_NO = ?");
            $stmt->execute([$isbn]);
            if ($stmt->fetch()) {
                // Insert Book-Author Relationship
                $stmt = $pdo->prepare("INSERT INTO Book_Author (ISBN_NO, Author_ID, Role) VALUES (?, ?, 'Primary')");
                $stmt->execute([$isbn, $author_id]);
            } else {
               // echo "Book with ISBN $isbn does not exist!";
            }
        } else {
            //echo "ISBN is missing!";
        }
    } else {
        //echo "Author name or surname is empty!";
    }
}


            // Insert book-shelf relationship
            if ($shelf_id) {
                $stmt = $pdo->prepare("INSERT INTO Book_Shelf (Shelf_ID, ISBN_NO) VALUES (?, ?)");
                $stmt->execute([$shelf_id, $isbn]);
            }

            $pdo->commit();
            $success_message = "Book added successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        let authorIndex = 1; // Start at 1 since we have one author field by default

        function addAuthorField() {
            const authorsContainer = document.getElementById('authors-container');
            const authorField = document.createElement('div');
            authorField.className = 'grid grid-cols-2 gap-4 mb-2 items-end';
            
            authorField.innerHTML = `
                <div>
                    <input type="text" name="author[${authorIndex}][name]" placeholder="Author Name" class="w-full px-3 py-2 border rounded-md">
                </div>
                <div>
                    <input type="text" name="author[${authorIndex}][surname]" placeholder="Author Surname" class="w-full px-3 py-2 border rounded-md">
                </div>
            `;
            
            authorsContainer.appendChild(authorField);
            authorIndex++;
        }
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
        }
        .form-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .form-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        .form-title {
            color: #00264d;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #00264d;
            padding-bottom: 0.5rem;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        .required-field::after {
            content: "*";
            color: #ef4444;
            margin-left: 0.25rem;
        }
        input, textarea, select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            transition: border-color 0.2s;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #00264d;
            box-shadow: 0 0 0 3px rgba(0, 38, 77, 0.1);
        }
        .btn-primary {
            background-color: #00264d;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #001a33;
        }
        .btn-secondary {
            background-color: #6b7280;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .btn-secondary:hover {
            background-color: #4b5563;
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }
        .full-width {
            grid-column: span 2;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #00264d;
            margin-top: 1rem;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-card">
            <h1 class="form-title">Add New Book</h1>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="form-grid">
                    <!-- Book Details -->
                    <div class="form-group">
                        <label for="isbn" class="required-field">ISBN</label>
                        <input type="text" id="isbn" name="isbn" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="title" class="required-field">Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edition">Edition</label>
                        <input type="text" id="edition" name="edition">
                    </div>
                    
                    <div class="form-group">
                        <label for="language">Language</label>
                        <input type="text" id="language" name="language">
                    </div>
                    
                    <div class="form-group">
                        <label for="price" class="required-field">Price</label>
                        <input type="number" id="price" name="price" required step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="series">Series</label>
                        <input type="text" id="series" name="series">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="callno" class="required-field">Call Number</label>
                        <input type="text" id="callno" name="callno" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity" class="required-field">Quantity</label>
                        <input type="number" id="quantity" name="quantity" required min="1">
                    </div>
                    
                    <!-- Authors Section -->
                    <div class="form-group full-width">
                        <label class="required-field">Authors</label>
                        <div id="authors-container" class="space-y-3">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <input type="text" name="author[0][name]" placeholder="Author Name" required>
                                </div>
                                <div>
                                    <input type="text" name="author[0][surname]" placeholder="Author Surname" required>
                                </div>
                            </div>
                        </div>
                        <button type="button" onclick="addAuthorField()" class="btn-secondary mt-2">
                            <i class="fas fa-plus mr-1"></i> Add Another Author
                        </button>
                    </div>
                    
                    <!-- Additional Fields -->
                    <div class="form-group">
                        <label for="publisher_name" class="required-field">Publisher</label>
                        <input type="text" id="publisher_name" name="publisher_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="shelf_number" class="required-field">Shelf Number</label>
                        <input type="text" id="shelf_number" name="shelf_number" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="image">Cover Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                    </div>
                </div>
                
                <div class="flex justify-between items-center mt-8">
                    <a href="try.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <button type="submit" class="btn-primary px-6 py-2">
                        <i class="fas fa-plus-circle mr-1"></i> Add Book
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>