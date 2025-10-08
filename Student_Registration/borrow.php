<?php
// Security and error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session management
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: Student_login.php');
    exit();
}

// Set timezone
date_default_timezone_set("Africa/Johannesburg");
$currentDateTime = date("F j, Y, g:i a");

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ecot_library2';

try {
    $conn = new mysqli($host, $user, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $userName = $_SESSION['username'];

    // Retrieve user information including status
    $app_query = $conn->prepare("SELECT Application_ID, Name, Surname, Status FROM application WHERE Username = ?");
    $app_query->bind_param("s", $userName);
    $app_query->execute();
    $app_result = $app_query->get_result();
    $app_data = $app_result->fetch_assoc();
    $application_id = $app_data['Application_ID'] ?? null;
    $full_name = htmlspecialchars($app_data['Name'] . ' ' . $app_data['Surname']);
    $user_status = $app_data['Status'] ?? null;

    if (!$application_id) {
        throw new Exception("Error: Application ID not found for user.");
    }

    // Check if user is deactivated
    if ($user_status === 'Inactive') {
        echo "<script>alert('Your account is deactivated. Please contact the library administrator.'); window.location.href = 'My_Issued_Books.php';</script>";
        exit();
    }

    // Remove expired reservations (books not fetched within 30 minutes)
    $expiration_query = "
        SELECT b.ISBN_NO FROM borrow b
        WHERE b.Status = 'Reserved'
        AND TIMESTAMPDIFF(MINUTE, b.Reserved_At, NOW()) >= 30
    ";
    $expired_books = $conn->query($expiration_query);

    while ($row = $expired_books->fetch_assoc()) {
        $isbn_no = $row['ISBN_NO'];

        // Restore book quantity
        $update_book = $conn->prepare("UPDATE book SET Quantity = Quantity + 1 WHERE ISBN_NO = ?");
        $update_book->bind_param("s", $isbn_no);
        $update_book->execute();

        // Remove reservation
        $delete_reservation = $conn->prepare("DELETE FROM borrow WHERE ISBN_NO = ? AND Status = 'Reserved'");
        $delete_reservation->bind_param("s", $isbn_no);
        $delete_reservation->execute();
    }

    // Handle form submission for borrowing a book
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // First, check if the user has already borrowed 3 books
        $count_sql = "SELECT COUNT(*) as count FROM borrow WHERE Application_ID = ? AND Status = 'Borrowed'";
        $stmt = $conn->prepare($count_sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $count_result = $stmt->get_result();
        $count_data = $count_result->fetch_assoc();
        $borrow_count = $count_data['count'];

        if ($borrow_count >= 3) {
            echo "<script>alert('You have reached your borrowing limit (3 books). Please return a book to borrow another.'); window.location.href = 'My_Issued_Books.php';</script>";
            exit();
        }

        $isbn_no = $_POST['isbn_no'] ?? '';
        $borrow_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+2 days'));

        // Validate ISBN
        if (empty($isbn_no)) {
            echo "<script>alert('Please select a book to borrow.'); window.location.href = 'borrow.php';</script>";
            exit();
        }

        // Check if book exists, has available copies, and is not reference-only
        $check_book = $conn->prepare("SELECT Title, Quantity, reference_only FROM book WHERE ISBN_NO = ? AND Quantity > 0");
        $check_book->bind_param("s", $isbn_no);
        $check_book->execute();
        $book_result = $check_book->get_result();

        if ($book_result->num_rows > 0) {
            $book_data = $book_result->fetch_assoc();
            $book_title = $book_data['Title'];
            
            // Check if book is reference-only
            if ($book_data['reference_only'] == 1) {
                echo "<script>alert('This book is for library use only and cannot be borrowed.'); window.location.href = 'borrow.php';</script>";
                exit();
            }

            // Insert reservation record
            $borrow_sql = $conn->prepare("INSERT INTO borrow (ISBN_NO, Borrow_Date, Due_Date, Status, Application_ID, Reserved_At) VALUES (?, ?, ?, 'Reserved', ?, NOW())");
            $borrow_sql->bind_param("sssi", $isbn_no, $borrow_date, $due_date, $application_id);

            if ($borrow_sql->execute()) {
                // Reduce book quantity
                $update_sql = $conn->prepare("UPDATE book SET Quantity = Quantity - 1 WHERE ISBN_NO = ?");
                $update_sql->bind_param("s", $isbn_no);
                $update_sql->execute();

                echo "<script>alert('Book \"$book_title\" reserved successfully! You have 30 minutes to fetch it.'); window.location.href = 'My_Issued_Books.php';</script>";
                exit();
            } else {
                echo "<script>alert('Failed to reserve the book. Please try again.'); window.location.href = 'borrow.php';</script>";
                exit();
            }
        } else {
            echo "<script>alert('This book is not available for borrowing.'); window.location.href = 'borrow.php';</script>";
            exit();
        }
    }

    // Search books - exclude reference-only books
    $search_query = '';
    $books = [];

    $sql = "SELECT b.ISBN_NO, b.Title, b.Quantity, b.cover_image, b.reference_only,
                   GROUP_CONCAT(CONCAT(a.Name, ' ', a.Surname) SEPARATOR ', ') AS authors,
                   s.Category
            FROM book b
            LEFT JOIN book_author ba ON b.ISBN_NO = ba.ISBN_NO
            LEFT JOIN author a ON ba.Author_ID = a.Author_ID
            LEFT JOIN book_shelf bs ON b.ISBN_NO = bs.ISBN_NO
            LEFT JOIN shelf s ON bs.Shelf_ID = s.Shelf_ID
            WHERE b.Quantity > 0 AND b.reference_only = 0";

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search_query = trim($_GET['search']);
        $sql .= " AND (b.Title LIKE ? OR b.ISBN_NO LIKE ? OR CONCAT(a.Name, ' ', a.Surname) LIKE ?)";
    }

    $sql .= " GROUP BY b.ISBN_NO, b.Title, b.Quantity, b.cover_image, s.Category";

    $stmt = $conn->prepare($sql);

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $like_query = "%" . $search_query . "%";
        $stmt->bind_param("sss", $like_query, $like_query, $like_query);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $books = $result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Book - ECOT Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
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
            background-color: var(--light-color);
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
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

        .content {
            margin-left: 280px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--primary-color);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .book-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .book-cover {
            height: 200px;
            object-fit: cover;
            object-position: center;
            background-color: #f0f0f0;
        }

        .book-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--secondary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.8rem;
        }

        .book-badge.library-use {
            background-color: #dc3545;
            left: 10px;
            right: auto;
        }

        .form-check-input:disabled + .form-check-label {
            color: #6c757d;
            cursor: not-allowed;
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

            .content {
                margin-left: 80px;
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

        a {
            text-decoration: none;
        }
    </style>
</head>
<body>
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
            <a href="../Student_Registration/Home.php" class="text-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="content">
        <div class="navbar">
            <h3 class="m-0">ECOT Library Management System</h3>
            <div>
                <span class="me-3"><i class="fas fa-clock me-1"></i> <?= $currentDateTime ?></span>
                <a href="profile.php" class="text-white me-3">
                    <i class="fas fa-user-circle me-1"></i>
                    <?= htmlspecialchars($userName) ?>
                </a>
                <a href="../Student_Registration/Home.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Logout
                </a>
            </div>
        </div>

        <h4 class="mb-4">Borrow Book</h4>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Search by title, ISBN or author"
                                   value="<?= htmlspecialchars($search_query) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="application_id" value="<?= htmlspecialchars($application_id) ?>">

                    <?php if (!empty($books)): ?>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
                            <?php foreach ($books as $book): ?>
                                <div class="col">
                                    <div class="card book-card h-100">
                                        <div class="position-relative">
                                            <img src="<?= !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : '../assets/images/default-book-cover.jpg' ?>"
                                                 alt="<?= htmlspecialchars($book['Title']) ?>"
                                                 class="card-img-top book-cover">
                                            <span class="book-badge">Available: <?= (int)$book['Quantity'] ?></span>
                                        </div>
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title"><?= htmlspecialchars($book['Title']) ?></h5>
                                            <?php if (!empty($book['authors'])): ?>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-user-pen me-1"></i>
                                                    <?= htmlspecialchars($book['authors']) ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if (!empty($book['Category'])): ?>
                                                <span class="badge bg-secondary mb-2">
                                                    <i class="fas fa-tag me-1"></i>
                                                    <?= htmlspecialchars($book['Category']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <div class="mt-auto">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="isbn_no"
                                                           id="isbn_<?= htmlspecialchars($book['ISBN_NO']) ?>"
                                                           value="<?= htmlspecialchars($book['ISBN_NO']) ?>" required>
                                                    <label class="form-check-label" for="isbn_<?= htmlspecialchars($book['ISBN_NO']) ?>">
                                                        Select this book
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus-square me-1"></i> Borrow Selected Book
                        </button>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php if (!empty($search_query)): ?>
                                No books match your search criteria.
                            <?php else: ?>
                                There are currently no books available for borrowing.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        $(document).ready(function() {
            // Add active class to current menu item
            var current = location.pathname.split('/').pop();
            $('.sidebar-nav a').each(function() {
                var $this = $(this);
                if ($this.attr('href') === current) {
                    $this.addClass('active');
                }
            });

            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>
