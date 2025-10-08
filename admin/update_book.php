<?php
$host = 'localhost';
$dbname = 'ecot_library2';
$username = 'root';
$password = '';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$error_message = '';
$success_message = '';
$existing_book = null;

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

// Rest of your code remains the same...

// Check if ISBN is submitted via GET (for checking existing books)
if (isset($_GET['isbn_check'])) {
    $isbn_check = validate_input($_GET['isbn_check']);
    if (!empty($isbn_check)) {
        try {
            // Check if book exists
            $stmt = $pdo->prepare("SELECT * FROM Book WHERE ISBN_NO = ?");
            $stmt->execute([$isbn_check]);
            $existing_book = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_book) {
                // Get authors for the book
                $stmt = $pdo->prepare("SELECT a.Name, a.Surname FROM Author a 
                                      JOIN Book_Author ba ON a.Author_ID = ba.Author_ID 
                                      WHERE ba.ISBN_NO = ?");
                $stmt->execute([$isbn_check]);
                $existing_book['authors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get publisher
                if ($existing_book['Publisher_ID']) {
                    $stmt = $pdo->prepare("SELECT Name_of_Publisher FROM Publisher WHERE Publisher_ID = ?");
                    $stmt->execute([$existing_book['Publisher_ID']]);
                    $publisher = $stmt->fetch(PDO::FETCH_ASSOC);
                    $existing_book['publisher_name'] = $publisher['Name_of_Publisher'] ?? '';
                }
                
                // Get shelf
                $stmt = $pdo->prepare("SELECT s.BookShelf_NO FROM shelf s 
                                      JOIN Book_Shelf bs ON s.Shelf_ID = bs.Shelf_ID 
                                      WHERE bs.ISBN_NO = ?");
                $stmt->execute([$isbn_check]);
                $shelf = $stmt->fetch(PDO::FETCH_ASSOC);
                $existing_book['shelf_number'] = $shelf['BookShelf_NO'] ?? '';
            }
        } catch (Exception $e) {
            $error_message = "Error checking ISBN: " . $e->getMessage();
        }
    }
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
   
    // Handle authors
    $authors = $_POST['author'] ?? [];
    $image_path = null;

    // Validate inputs
    if (validateISBN($isbn)) {
        $error_message = "Invalid ISBN format. Please enter a valid ISBN-10 or ISBN-13.";
    } elseif (!is_numeric($quantity) || $quantity < 0) {
        $error_message = "Invalid quantity.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error_message = "Invalid price.";
    } else {
        try {
            $pdo->beginTransaction();

            // Check if book already exists to determine if we're updating or inserting
            $stmt = $pdo->prepare("SELECT ISBN_NO FROM Book WHERE ISBN_NO = ?");
            $stmt->execute([$isbn]);
            $book_exists = $stmt->fetch();

            // Add/update publisher if provided
            $publisher_id = null;
            if (!empty($publisher_name)) {
                // Check if publisher exists
                $stmt = $pdo->prepare("SELECT Publisher_ID FROM Publisher WHERE Name_of_Publisher = ?");
                $stmt->execute([$publisher_name]);
                $existing_publisher = $stmt->fetch();
                
                if ($existing_publisher) {
                    $publisher_id = $existing_publisher['Publisher_ID'];
                } else {
                    $stmt = $pdo->prepare("INSERT INTO Publisher (Name_of_Publisher) VALUES (?)");
                    $stmt->execute([$publisher_name]);
                    $publisher_id = $pdo->lastInsertId();
                }
            }

            // Add/update shelf if provided
            $shelf_id = null;
            if (!empty($shelf_number)) {
                // Check if shelf exists
                $stmt = $pdo->prepare("SELECT Shelf_ID FROM shelf WHERE BookShelf_NO = ?");
                $stmt->execute([$shelf_number]);
                $existing_shelf = $stmt->fetch();
                
                if ($existing_shelf) {
                    $shelf_id = $existing_shelf['Shelf_ID'];
                } else {
                    $stmt = $pdo->prepare("INSERT INTO shelf (BookShelf_NO) VALUES (?)");
                    $stmt->execute([$shelf_number]);
                    $shelf_id = $pdo->lastInsertId();
                }
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

            if ($book_exists) {
                // Update existing book
                $update_fields = [
                    'Title' => $title,
                    'Edition_Statement' => $edition,
                    'Language' => $language,
                    'Price' => $price,
                    'Series' => $series,
                    'Notes' => $notes,
                    'Call_NO' => $callno,
                    'Quantity' => $quantity,
                    'Publisher_ID' => $publisher_id
                ];
                
                if ($image_path) {
                    $update_fields['cover_image'] = $image_path;
                }
                
                $set_clause = implode(', ', array_map(function($field) {
                    return "$field = :$field";
                }, array_keys($update_fields)));
                
                $stmt = $pdo->prepare("UPDATE Book SET $set_clause WHERE ISBN_NO = :isbn");
                $update_fields['isbn'] = $isbn;
                $stmt->execute($update_fields);
                
                // Delete existing authors for this book
                $stmt = $pdo->prepare("DELETE FROM Book_Author WHERE ISBN_NO = ?");
                $stmt->execute([$isbn]);
                
                $success_message = "Book updated successfully!";
            } else {
                // Insert new book
                $stmt = $pdo->prepare("INSERT INTO Book (ISBN_NO, Title, Edition_Statement, Language, Price, Series, Notes, Call_NO, Quantity, cover_image, Publisher_ID) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$isbn, $title, $edition, $language, $price, $series, $notes, $callno, $quantity, $image_path, $publisher_id]);
                
                $success_message = "Book added successfully!";
            }

            // Process authors
            foreach ($authors as $author) {
                if (!empty($author['name']) && !empty($author['surname'])) {
                    // Check if author exists
                    $stmt = $pdo->prepare("SELECT Author_ID FROM Author WHERE Name = ? AND Surname = ?");
                    $stmt->execute([$author['name'], $author['surname']]);
                    $existingAuthor = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($existingAuthor) {
                        $author_id = $existingAuthor['Author_ID'];
                    } else {
                        // Insert new author
                        $stmt = $pdo->prepare("INSERT INTO Author (Name, Surname) VALUES (?, ?)");
                        $stmt->execute([$author['name'], $author['surname']]);
                        $author_id = $pdo->lastInsertId();
                    }

                    // Link author to book
                    $stmt = $pdo->prepare("INSERT INTO Book_Author (ISBN_NO, Author_ID, Role) VALUES (?, ?, 'Primary')");
                    $stmt->execute([$isbn, $author_id]);
                }
            }

            // Update book-shelf relationship
            if ($shelf_id) {
                // Remove existing shelf assignment
                $stmt = $pdo->prepare("DELETE FROM Book_Shelf WHERE ISBN_NO = ?");
                $stmt->execute([$isbn]);
                
                // Add new shelf assignment
                $stmt = $pdo->prepare("INSERT INTO Book_Shelf (Shelf_ID, ISBN_NO) VALUES (?, ?)");
                $stmt->execute([$shelf_id, $isbn]);
            }

            $pdo->commit();
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
    <title><?php echo isset($existing_book) ? 'Update' : 'Add'; ?> Book</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script>
        let authorIndex = <?php echo isset($existing_book['authors']) ? count($existing_book['authors']) : 1; ?>;
        
        function addAuthorField() {
            const authorsContainer = document.getElementById('authors-container');
            const authorField = document.createElement('div');
            authorField.className = 'grid grid-cols-2 gap-4 mb-2';
            
            authorField.innerHTML = `
                <div>
                    <input type="text" name="author[${authorIndex}][name]" placeholder="Author Name" class="border p-2 w-full" required>
                </div>
                <div>
                    <input type="text" name="author[${authorIndex}][surname]" placeholder="Author Surname" class="border p-2 w-full" required>
                </div>
            `;
            
            authorsContainer.appendChild(authorField);
            authorIndex++;
        }
        
        function checkExistingBook() {
            const isbn = document.getElementById('isbn').value;
            if (isbn) {
                window.location.href = `?isbn_check=${encodeURIComponent(isbn)}`;
            }
        }

            // Add to your script section
function validateISBNInput(isbn) {
    // Simple client-side validation pattern
    return /^(?:\d{9}[\dXx]|\d{13})$/.test(isbn.replace(/[-\s]/g, ''));
}
    </script>
    <style>
        body {
            font-family: "Poppins", serif;
        }
        .i a:hover {
            background-color: #3b82f6;
            border-radius: 5px;
            padding: 8px;
        }
        .existing-book {
            background-color: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .btn-primary {
            background-color: #00264d;
            color: white;
        }
        .btn-primary:hover {
            background-color: #003366;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h2 style="color: #00264d;" class="text-2xl font-semibold mb-4"><?php echo isset($existing_book) ? 'Update' : 'Add'; ?> Book</h2>
        
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <!-- Book Details -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="isbn" class="block text-sm font-medium text-gray-700">ISBN*</label>
                        <div class="flex">
                            <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($existing_book['ISBN_NO'] ?? ''); ?>" required 
                                   class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <button type="button" onclick="checkExistingBook()" 
                                    class="ml-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Check
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Title*</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($existing_book['Title'] ?? ''); ?>" required 
                               class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <?php if (isset($existing_book)): ?>
                    <div class="existing-book rounded-md">
                        <h3 class="font-semibold text-blue-600">Existing Book Found</h3>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <div>
                                <p class="text-sm"><strong>Title:</strong> <?php echo htmlspecialchars($existing_book['Title']); ?></p>
                                <p class="text-sm"><strong>Edition:</strong> <?php echo htmlspecialchars($existing_book['Edition_Statement'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm"><strong>Language:</strong> <?php echo htmlspecialchars($existing_book['Language'] ?? 'N/A'); ?></p>
                                <p class="text-sm"><strong>Price:</strong> <?php echo htmlspecialchars($existing_book['Price'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <p class="text-sm mt-2"><strong>Authors:</strong> 
                            <?php 
                            if (!empty($existing_book['authors'])) {
                                $author_names = [];
                                foreach ($existing_book['authors'] as $author) {
                                    $author_names[] = htmlspecialchars($author['Name'] . ' ' . $author['Surname']);
                                }
                                echo implode(', ', $author_names);
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </p>
                        <p class="mt-2 text-sm text-gray-600">The form has been pre-filled with existing book details. You can modify them if needed.</p>
                    </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edition" class="block text-sm font-medium text-gray-700">Edition</label>
                        <input type="text" id="edition" name="edition" value="<?php echo htmlspecialchars($existing_book['Edition_Statement'] ?? ''); ?>" 
                               class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="language" class="block text-sm font-medium text-gray-700">Language</label>
                        <input type="text" id="language" name="language" value="<?php echo htmlspecialchars($existing_book['Language'] ?? ''); ?>" 
                               class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">Price*</label>
                        <input type="number" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars($existing_book['Price'] ?? ''); ?>" required 
                               class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="series" class="block text-sm font-medium text-gray-700">Series</label>
                        <input type="text" id="series" name="series" value="<?php echo htmlspecialchars($existing_book['Series'] ?? ''); ?>" 
                               class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea id="notes" name="notes" rows="3" 
                              class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($existing_book['Notes'] ?? ''); ?></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="callno" class="block text-sm font-medium text-gray-700">Call Number*</label>
                        <input type="text" id="callno" name="callno" value="<?php echo htmlspecialchars($existing_book['Call_NO'] ?? ''); ?>" required 
                               class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity*</label>
                        <input type="number" id="quantity" name="quantity" value="<?php echo htmlspecialchars($existing_book['Quantity'] ?? '1'); ?>" required 
                               class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Authors -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Authors*</label>
                    <div id="authors-container" class="space-y-2 mt-2">
                        <?php if (isset($existing_book['authors'])): ?>
                            <?php foreach ($existing_book['authors'] as $index => $author): ?>
                                <div class="grid grid-cols-2 gap-4 mb-2">
                                    <div>
                                        <input type="text" name="author[<?php echo $index; ?>][name]" 
                                               value="<?php echo htmlspecialchars($author['Name']); ?>" 
                                               placeholder="Author Name" 
                                               class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                    </div>
                                    <div>
                                        <input type="text" name="author[<?php echo $index; ?>][surname]" 
                                               value="<?php echo htmlspecialchars($author['Surname']); ?>" 
                                               placeholder="Author Surname" 
                                               class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="grid grid-cols-2 gap-4 mb-2">
                                <div>
                                    <input type="text" name="author[0][name]" placeholder="Author Name" 
                                           class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                </div>
                                <div>
                                    <input type="text" name="author[0][surname]" placeholder="Author Surname" 
                                           class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" onclick="addAuthorField()" 
                            class="mt-2 inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        <i class="fas fa-plus mr-1"></i> Add Another Author
                    </button>
                </div>

                <!-- Additional Fields -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="publisher_name" class="block text-sm font-medium text-gray-700">Publisher</label>
                        <input type="text" id="publisher_name" name="publisher_name" 
                               value="<?php echo htmlspecialchars($existing_book['publisher_name'] ?? ''); ?>" 
                               class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="shelf_number" class="block text-sm font-medium text-gray-700">Shelf Number</label>
                        <input type="text" id="shelf_number" name="shelf_number" 
                               value="<?php echo htmlspecialchars($existing_book['shelf_number'] ?? ''); ?>" 
                               class="border p-2 w-full rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">Cover Image</label>
                    <input type="file" id="image" name="image" accept="image/*" 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <?php if (isset($existing_book['cover_image']) && !empty($existing_book['cover_image'])): ?>
                        <p class="mt-1 text-sm text-gray-500">Current cover image: <?php echo basename($existing_book['cover_image']); ?></p>
                        <?php if (file_exists($existing_book['cover_image'])): ?>
                            <img src="<?php echo $existing_book['cover_image']; ?>" alt="Book cover" class="mt-2 h-32 object-contain">
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="pt-4">
                    <button type="submit" 
                            class="btn-primary inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <?php echo isset($existing_book) ? 'Update Book' : 'Add Book'; ?>
                    </button>
                    <a href="try.php" 
                       class="ml-2 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>


</body>
</html>