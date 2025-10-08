<?php
session_start();

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ecot_library2';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get book details from ISBN parameter
$isbn = $_GET['isbn'] ?? null;
if (!$isbn) {
    header('Location: Student_Books.php');
    exit();
}

// Get book details with author and publisher information
$book_query = "SELECT b.*, 
               a.Name AS Author_Name, a.Surname AS Author_Surname,
               p.Name_of_Publisher, p.Publication_Date,
               s.BookShelf_NO, s.Category
               FROM book b
               LEFT JOIN book_author ba ON b.ISBN_NO = ba.ISBN_NO
               LEFT JOIN author a ON ba.Author_ID = a.Author_ID
               LEFT JOIN publisher p ON b.Publisher_ID = p.Publisher_ID
               LEFT JOIN book_shelf bs ON b.ISBN_NO = bs.ISBN_NO
               LEFT JOIN shelf s ON bs.Shelf_ID = s.Shelf_ID
               WHERE b.ISBN_NO = ?";
$stmt = $conn->prepare($book_query);
$stmt->bind_param("s", $isbn);
$stmt->execute();
$book_result = $stmt->get_result();

if ($book_result->num_rows === 0) {
    header('Location: Student_Books.php');
    exit();
}

$book = $book_result->fetch_assoc();

// Get related books (same category)
$related_query = "SELECT b.ISBN_NO, b.Title, b.cover_image, 
                 a.Name AS Author_Name, a.Surname AS Author_Surname
                 FROM book b
                 LEFT JOIN book_author ba ON b.ISBN_NO = ba.ISBN_NO
                 LEFT JOIN author a ON ba.Author_ID = a.Author_ID
                 LEFT JOIN book_shelf bs ON b.ISBN_NO = bs.ISBN_NO
                 LEFT JOIN shelf s ON bs.Shelf_ID = s.Shelf_ID
                 WHERE s.Category = ? AND b.ISBN_NO != ?
                 LIMIT 4";
$stmt = $conn->prepare($related_query);
$stmt->bind_param("ss", $book['Category'], $isbn);
$stmt->execute();
$related_books = $stmt->get_result();

// Get current user's application ID
$userName = $_SESSION['username'];
$app_query = "SELECT Application_ID FROM application WHERE Username = ?";
$stmt = $conn->prepare($app_query);
$stmt->bind_param("s", $userName);
$stmt->execute();
$app_result = $stmt->get_result();
$app_row = $app_result->fetch_assoc();
$applicationID = $app_row['Application_ID'] ?? null;

// Check if user already borrowed this book
$borrowed_query = "SELECT Borrow_ID FROM borrow 
                   WHERE Application_ID = ? AND ISBN_NO = ? AND Status = 'Borrowed'";
$stmt = $conn->prepare($borrowed_query);
$stmt->bind_param("is", $applicationID, $isbn);
$stmt->execute();
$borrowed_result = $stmt->get_result();
$already_borrowed = $borrowed_result->num_rows > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['Title']); ?> - ECOT Library</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>


        :root {
                    --primary-color: #00264d;
                    --secondary-color: #004080;
                    --accent-color: #007bff;
                    --light-color: #f8f9fa;
                    --dark-color: #343a40;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .book-cover {
            max-height: 400px;
            width: auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .book-details {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .badge-available {
            background-color: #28a745;
        }
        .badge-unavailable {
            background-color: #dc3545;
        }
        .related-book {
            transition: transform 0.3s;
        }
        .related-book:hover {
            transform: translateY(-5px);
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background-color: #00264d;
            color: white;
            padding: 20px;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .navbar {
            background-color: #00264d;
            color: white;
        }

        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            background-color: var(--primary-color);
            color: white;
            padding: 20px 15px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .sidebar-brand {
            text-align: center;
            padding: 15px 0 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-brand img {
            border-radius: 5px;
            width: 150px;
            margin-bottom: 10px;
        }

        
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            height: calc(100% - 180px);
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 15px;
            margin-bottom: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .sidebar a:hover, .sidebar a.active {
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
        }
        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

         /* Responsive adjustments */
         @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                overflow: hidden;
            }
            
            .sidebar-brand span, .sidebar a span {
                display: none;
            }
            
            .sidebar a i {
                margin-right: 0;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .content {
                margin-left: 0;
            }
            
            .navbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        a{
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <img src="../Student_Registration/include/ECOT.jpg" alt="ECOT Library Logo">
            <h5 class="mb-0">Welcome, <?= htmlspecialchars($userName); ?></h5>
        </div>
        
        <div class="sidebar-nav">
            <a href="My_Issued_Books.php" class="active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="profile.php">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
            <a href="My_Issued_Books.php">
                <i class="fas fa-book"></i>
                <span>My Issued Books</span>
            </a>
            <a href="Student_Books.php">
                <i class="fas fa-book-open"></i>
                <span>Books Catalog</span>
            </a>
            <a href="borrow.php">
                <i class="fas fa-plus-square"></i>
                <span>Borrow Book</span>
            </a>
            <a href="return_book.php">
                <i class="fas fa-exchange-alt"></i>
                <span>Return Book</span>
            </a>

            <a href="History.php">
                <i class="fas fa-exchange-alt"></i>
                <span>History</span>
            </a>
        </div>
        
        <div class="sidebar-footer">
            <a href="Home.php" class="text-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Content -->
    <div  class="content">
        <!-- Navbar -->
        <nav style="margin-left: 50px;" class="navbar navbar-expand-lg mb-4">
            <div class="container-fluid">
                <a class="navbar-brand text-white" href="#">ECOT Library</a>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3"><?php echo date("F j, Y, g:i a"); ?></span>
                    <a href="profile.php" class="text-white me-3">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <a href="logout.php" class="btn btn-danger btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </nav>

        <!-- Breadcrumb -->
        <nav style="margin-left: 50px;" aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="Student_Books.php">Books</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($book['Title']); ?></li>
            </ol>
        </nav>

        <!-- Book Details Section -->
        <div style="margin-left: 50px;" class="row book-details p-4 mb-4">
            <div class="col-md-4 text-center">
                <img src="../uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                     alt="Book Cover" 
                     class="book-cover img-fluid mb-3">
                
                <div class="availability mb-3">
                    <span class="badge <?php echo $book['Quantity'] > 0 ? 'badge-available' : 'badge-unavailable'; ?> p-2">
                        <?php echo $book['Quantity'] > 0 ? 'Available' : 'Not Available'; ?>
                        <?php if ($book['Quantity'] > 0): ?>
                            (<?php echo $book['Quantity']; ?> copies)
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if ($book['Quantity'] > 0 && !$already_borrowed && $applicationID): ?>
                    <a href="borrow.php?isbn=<?php echo $isbn; ?>" class="btn btn-primary btn-lg w-100 mb-3">
                        <i class="fas fa-plus-square"></i> Borrow This Book
                    </a>
                <?php elseif ($already_borrowed): ?>
                    <button class="btn btn-secondary btn-lg w-100 mb-3" disabled>
                        <i class="fas fa-check-circle"></i> Already Borrowed
                    </button>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Book Details</h5>
                        <ul class="list-unstyled">
                            <li><strong>ISBN:</strong> <?php echo htmlspecialchars($book['ISBN_NO']); ?></li>
                           
                            <li><strong>Shelf:</strong> <?php echo htmlspecialchars($book['BookShelf_NO']); ?></li>
                            <li><strong>Category:</strong> <?php echo htmlspecialchars($book['Category']); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <h1 class="display-4"><?php echo htmlspecialchars($book['Title']); ?></h1>
                <?php if (!empty($book['Sub_Title'])): ?>
                    <h3 class="text-muted"><?php echo htmlspecialchars($book['Sub_Title']); ?></h3>
                <?php endif; ?>
                <p class="lead">by <?php echo htmlspecialchars($book['Author_Name'] . ' ' . $book['Author_Surname']); ?></p>
                
                <?php if (!empty($book['Edition_Statement'])): ?>
                    <div class="mb-4">
                        <span class="badge bg-info text-dark"><?php echo htmlspecialchars($book['Edition_Statement']); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="description mb-4">
                    <h3>Description</h3>
                    <?php if (!empty($book['Notes'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($book['Notes'])); ?></p>
                    <?php else: ?>
                        <p class="text-muted">No description available for this book.</p>
                    <?php endif; ?>
                </div>
                
                <div class="additional-info">
                    <div class="row">
                        <div class="col-md-6">
                            <h4><i class="fas fa-info-circle"></i> Additional Information</h4>
                            <ul>
                                <?php if (!empty($book['Series'])): ?>
                                    <li><strong>Series:</strong> <?php echo htmlspecialchars($book['Series']); ?></li>
                                <?php endif; ?>
                                <li><strong>Language:</strong> <?php echo htmlspecialchars($book['Language']); ?></li>
                                <?php if (!empty($book['Collation'])): ?>
                                    <li><strong>Collation:</strong> <?php echo htmlspecialchars($book['Collation']); ?></li>
                                <?php endif; ?>
                                <?php if (!empty($book['Price'])): ?>
                                    <li><strong>Price:</strong> E <?php echo number_format($book['Price'], 2); ?></li>
                                <?php endif; ?>
                                <li><strong>Accession No:</strong> <?php echo htmlspecialchars($book['AccNo']); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Related Books Section -->
        <?php if ($related_books->num_rows > 0): ?>
        <div style="margin-left: 50px;"  class="related-books mb-4">
            <h3 class="mb-4">You May Also Like</h3>
            <div class="row">
                <?php while ($related = $related_books->fetch_assoc()): ?>
                <div class="col-md-3 mb-4">
                    <div class="card related-book h-100">
                        <a href="book_details.php?isbn=<?php echo $related['ISBN_NO']; ?>">
                            <img src="../uploads/<?php echo htmlspecialchars($related['cover_image']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($related['Title']); ?>"
                                 style="height: 200px; object-fit: contain;">
                        </a>
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="book_details.php?isbn=<?php echo $related['ISBN_NO']; ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($related['Title']); ?>
                                </a>
                            </h5>
                            <p class="card-text text-muted">by <?php echo htmlspecialchars($related['Author_Name'] . ' ' . $related['Author_Surname']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>