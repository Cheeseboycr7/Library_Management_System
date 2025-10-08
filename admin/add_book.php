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
    <link rel="stylesheet" href=https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
<script>
    let authorIndex = 0; // Initialize the index starting at 0

    function addAuthorField() {
        const authorsContainer = document.getElementById('authors-container');
        const authorField = document.createElement('div');
        authorField.className = 'grid grid-cols-2 gap-4 mb-2';

        // Dynamically add index to the name and surname fields
        authorField.innerHTML = `
            <div>
                <input type="text" name="author[${authorIndex}][name]" placeholder="Author Name" class="border p-2 w-full" required>
            </div>
            <div>
                <input type="text" name="author[${authorIndex}][surname]" placeholder="Author Surname" class="border p-2 w-full" required>
            </div>
        `;

        // Append the new author fields to the container
        authorsContainer.appendChild(authorField);

        // Increment the index for the next author
        authorIndex++;
    }
</script>

    <style>

        body{
            font-family: "Poppins", serif;
        }
        .i a:hover {
            background-color: #3b82f6;
            border-radius: 5px;
            padding: 8px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h2 style="color: #00264d;" class="text-2xl font-semibold mb-4">Add New Book</h2>
        <?php if ($error_message): ?>
            <div class="text-red-600"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="text-green-600"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <!-- Book Details -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="isbn">ISBN*</label>
                        <input type="text" id="isbn" name="isbn" required class="border p-2 w-full">
                    </div>
                    <div>
                        <label for="title">Title*</label>
                        <input type="text" id="title" name="title" required class="border p-2 w-full">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edition">Edition</label>
                        <input type="text" id="edition" name="edition" class="border p-2 w-full">
                    </div>
                    <div>
                        <label for="language">Language</label>
                        <input type="text" id="language" name="language" class="border p-2 w-full">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="price">Price*</label>
                        <input type="number" id="price" name="price" required class="border p-2 w-full">
                    </div>
                    <div>
                        <label for="series">Series</label>
                        <input type="text" id="series" name="series" class="border p-2 w-full">
                    </div>
                </div>
                <div>
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="border p-2 w-full"></textarea>
                </div>
                <div>
                    <label for="callno">Call Number*</label>
                    <input type="text" id="callno" name="callno" required class="border p-2 w-full">
                </div>
                <div>
                    <label for="quantity">Quantity*</label>
                    <input type="number" id="quantity" name="quantity" required class="border p-2 w-full">
                </div>

                <!-- Authors -->
                <div id="authors-container" class="space-y-2">
                    <label>Authors*</label>
                    <div class="grid grid-cols-2 gap-4 mb-2">
                        <div>
                            <input  type="text" name="author[0][name]" placeholder="Author Name" class="border p-2 w-full" required>
                        </div>
                        <div>
                            <input type="text" name="author[0][surname]" placeholder="Author Surname" class="border p-2 w-full" required>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="addAuthorField()" class="bg-gray-500 text-white px-2 py-1 rounded">Add Another Author</button>

                <!-- Additional Fields -->
                <div>
                    <label for="publisher_name">Publisher*</label>
                    <input type="text" id="publisher_name" name="publisher_name" class="border p-2 w-full">
                </div>
                <div>
                    <label for="shelf_number">Shelf Number*</label>
                    <input type="text" id="shelf_number" name="shelf_number" class="border p-2 w-full">
                </div>
                <div>
                    <label for="image">Cover Image</label>
                    <input type="file" id="image" name="image" accept="image/*" class="border p-2 w-full">
                </div>

                <div>
                    <button style="background-color: #00264d;;" type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Add Book</button>
                </div>
            </form>
            <div style="text-align: center; margin-top: 20px;">
                <i  class="fas fa-dashboard"></i>
                <a href="try.php" >Back to Dashboard</a>
            </div>
        </div>
    </div>
   
</body>
</html>
