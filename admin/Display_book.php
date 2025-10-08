<?php
session_start();
// Security check - only allow admin access

$servername = "127.0.0.1";
$username = "root";
$password = "";
$database = "ecot_library2";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle book deletion
if (isset($_POST['delete_book'])) {
    $isbn = $_POST['isbn'];
    
    try {
        // Disable foreign key checks
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Begin transaction
        $conn->beginTransaction();
        
        // First delete from book_author table
        $stmt = $conn->prepare("DELETE FROM book_author WHERE ISBN_NO = ?");
        $stmt->execute([$isbn]);
        
        // Then delete from book_shelf table
        $stmt = $conn->prepare("DELETE FROM book_shelf WHERE ISBN_NO = ?");
        $stmt->execute([$isbn]);
        
        // Finally delete from book table
        $stmt = $conn->prepare("DELETE FROM book WHERE ISBN_NO = ?");
        $stmt->execute([$isbn]);
        
        // Re-enable foreign key checks
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to refresh and avoid form resubmission
        header("Location: Display_book.php");
        exit();
    } catch (PDOException $e) {
        // Ensure foreign key checks are re-enabled even if error occurs
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
        $conn->rollBack();
        die("Error deleting book: " . $e->getMessage());
    }
}

$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Get distinct BookShelf_NO and Category for filters
$shelfOptions = $conn->query("SELECT DISTINCT BookShelf_NO FROM shelf WHERE BookShelf_NO IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$categoryOptions = $conn->query("SELECT DISTINCT Category FROM shelf WHERE Category IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

$selectedShelf = $_GET['shelf'] ?? '';
$selectedCategory = $_GET['category'] ?? '';

try {
    // JOIN book -> book_shelf -> shelf
    $sql = "
    SELECT 
        b.ISBN_NO, b.Title, b.Price, b.Language, b.Quantity, b.cover_image,
        bs.Shelf_ID,
        s.BookShelf_NO, s.Category
    FROM book b
    LEFT JOIN book_shelf bs ON b.ISBN_NO = bs.ISBN_NO
    LEFT JOIN shelf s ON bs.Shelf_ID = s.Shelf_ID
    WHERE (b.Title LIKE :search OR b.ISBN_NO LIKE :search OR b.Language LIKE :search)
    ";
    $params = ['search' => "%$search%"];

    if (!empty($selectedShelf)) {
        $sql .= " AND s.BookShelf_NO = :shelf";
        $params['shelf'] = $selectedShelf;
    }
    if (!empty($selectedCategory)) {
        $sql .= " AND s.Category = :category";
        $params['category'] = $selectedCategory;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching books: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Display</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: "Poppins", serif;
            margin: 20px;
            background-color: #f8f9fa;
            color: #333;
        }
        h1 {
            text-align: center;
            font-size: 28px;
            color:  #00264d;
            margin-bottom: 20px;
        }
        .search-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .search-container input {
            padding: 10px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .search-container button {
            padding: 10px 15px;
            background-color: #00264d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .book-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .book {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        .book:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }
        .book img {
            width: 100%;
            height: 250px;
            object-fit: contain;
            background-color: #f1f1f1;
        }
        .book h3 {
            font-size: 20px;
            color: #00264d;
            margin: 15px 0;
        }
        .book p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }
        .book .price {
            font-size: 16px;
            font-weight: bold;
            color: #28a745;
        }
        .search-container {
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .search-container form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
        }
        .search-container input,
        .search-container select,
        .search-container button {
            padding: 10px;
            font-size: 16px;
            flex: 1;
            min-width: 200px;
        }
        .search-container button {
            background-color: #001a33;
            color: white;
            border: none;
        }
        .search-container {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-weight: 500;
            color: #00264d;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .filter-group input,
        .filter-group select {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #00264d;
            box-shadow: 0 0 0 3px rgba(0, 38, 77, 0.1);
        }
        .search-btn {
            background-color: #00264d;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            height: 100%;
        }
        .search-btn:hover {
            background-color: #007bff;
            transform: translateY(-1px);
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.2s;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .modal-buttons button {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .cancel-btn {
            background-color: #6c757d;
            color: white;
            border: none;
        }
        .confirm-btn {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            .search-btn {
                height: auto;
            }
        }
    </style>
</head>
<body>
    <h1>Registered Books</h1>
    <div class="container mt-3">
        <form method="GET" action="">
            <div class="search-container">
                <div class="filter-grid">
                    <!-- Search Input -->
                    <div class="filter-group">
                        <label for="search">Search Books</label>
                        <input type="text" id="search" name="search" 
                               placeholder="Title, ISBN, or Language" 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <!-- Shelf Dropdown -->
                    <div style="margin-left: 15px;" class="filter-group">
                        <label for="shelf">Shelf Location</label>
                        <select name="shelf" id="shelf">
                            <option value="">All Shelves</option>
                            <?php foreach ($shelfOptions as $shelf): ?>
                                <option value="<?= htmlspecialchars($shelf) ?>" 
                                    <?= $shelf == $selectedShelf ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($shelf) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Category Dropdown -->
                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select name="category" id="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categoryOptions as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>" 
                                    <?= $category == $selectedCategory ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="filter-group">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div style="text-align: center; margin-top: 20px;">
        <i class="fas fa-dashboard"></i>
        <a href="try.php">Back to Dashboard</a>
    </div>

    <div class="book-container">
        <?php foreach ($books as $book): ?>
            <div class="book">
                <?php 
                    $imagePath = !empty($book['cover_image']) 
                        ? htmlspecialchars($book['cover_image']) 
                        : 'placeholder.png';
                ?>
                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($book['Title']) ?> Cover Image">
                <h3><?= htmlspecialchars($book['Title']) ?></h3>
                <p class="price">Price: E<?= htmlspecialchars($book['Price']) ?></p>
                <p>Language: <?= htmlspecialchars($book['Language']) ?></p>
                <p>Quantity: <?= htmlspecialchars($book['Quantity']) ?></p>
                <p>ISBN: <?= htmlspecialchars($book['ISBN_NO']) ?></p>
                <p style="color: #0066cc;"><strong>Bookshelf:</strong> <?= htmlspecialchars($book['BookShelf_NO'] ?? 'N/A') ?></p>
                <p style="color: #993300;"><strong>Category:</strong> <?= htmlspecialchars($book['Category'] ?? 'N/A') ?></p>
                
                <!-- Delete Button -->
                <form method="POST" action="" onsubmit="return confirmDelete(event, '<?= htmlspecialchars($book['Title']) ?>')">
                    <input type="hidden" name="isbn" value="<?= htmlspecialchars($book['ISBN_NO']) ?>">
                    <button type="submit" name="delete_book" class="delete-btn">
                        <i class="fas fa-trash"></i> Remove Book
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Confirmation Modal -->
    <div style="margin-top: 0px;" id="confirmationModal" class="confirmation-modal">
        <div class="modal-content">
            <h3 id="modalMessage">Are you sure you want to delete this book?</h3>
            <div style="margin-top: 0px;" class="modal-buttons">
                <button class="cancel-btn" onclick="closeModal()">Cancel</button>
                <form id="deleteForm" method="POST" action="">
                    <input type="hidden" name="isbn" id="modalIsbn">
                    <button type="submit" name="delete_book" class="confirm-btn">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Confirmation modal functions
        function confirmDelete(event, bookTitle) {
            event.preventDefault();
            const form = event.target;
            const isbn = form.querySelector('input[name="isbn"]').value;
            
            document.getElementById('modalMessage').textContent = 
                `Are you sure you want to delete "${bookTitle}" (ISBN: ${isbn})?`;
            document.getElementById('modalIsbn').value = isbn;
            
            document.getElementById('confirmationModal').style.display = 'flex';
            return false;
        }
        
        function closeModal() {
            document.getElementById('confirmationModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('confirmationModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>