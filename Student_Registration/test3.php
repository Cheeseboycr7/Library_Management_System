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

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set timezone
date_default_timezone_set("Africa/Johannesburg");
$currentDateTime = date("F j, Y, g:i a");

// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ecot_library2';

// Pagination settings
$perPage = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

try {
    // Database connection
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $userName = $_SESSION['username'];
    
    // Get user information from application table
    $user_query = $conn->prepare("SELECT Application_ID, Name, Surname, Email FROM application WHERE Username = ?");
    $user_query->bind_param("s", $userName);
    $user_query->execute();
    $user_result = $user_query->get_result();
    $user_data = $user_result->fetch_assoc();
    
    if (!$user_data) {
        throw new Exception("User data not found.");
    }
    
    $application_id = $user_data['Application_ID'];
    $full_name = $user_data['Name'] . ' ' . $user_data['Surname'];
    $user_email = $user_data['Email'];
    
    // Search and filter functionality
    $search_query = isset($_GET['search']) ? trim($conn->real_escape_string($_GET['search'])) : '';
    $books = [];
    $category_filter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
    $sort_by = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : 'title_asc';
    
    // Base SQL query
    $sql = "SELECT SQL_CALC_FOUND_ROWS b.ISBN_NO, b.Title, b.Quantity, b.cover_image, 
                   GROUP_CONCAT(DISTINCT CONCAT(a.Name, ' ', a.Surname) SEPARATOR ', ') AS authors,
                   s.Category, p.Name_of_Publisher, s.BookShelf_NO, b.Publication_Date
            FROM book b
            LEFT JOIN book_author ba ON b.ISBN_NO = ba.ISBN_NO
            LEFT JOIN author a ON ba.Author_ID = a.Author_ID
            LEFT JOIN book_shelf bs ON b.ISBN_NO = bs.ISBN_NO
            LEFT JOIN shelf s ON bs.Shelf_ID = s.Shelf_ID
            LEFT JOIN publisher p ON b.Publisher_ID = p.Publisher_ID
            WHERE b.Quantity > 0";
    
    // Add search conditions if provided
    if (!empty($search_query)) {
        $sql .= " AND (b.Title LIKE ? OR b.ISBN_NO LIKE ? OR CONCAT(a.Name, ' ', a.Surname) LIKE ?)";
    }
    
    // Add category filter if provided
    if (!empty($category_filter)) {
        $sql .= " AND s.Category = ?";
    }
    
    $sql .= " GROUP BY b.ISBN_NO, b.Title, b.Quantity, b.cover_image, s.Category, p.Name_of_Publisher, s.BookShelf_NO, b.Publication_Date";
    
    // Add sorting
    switch ($sort_by) {
        case 'title_desc':
            $sql .= " ORDER BY b.Title DESC";
            break;
        case 'author_asc':
            $sql .= " ORDER BY authors ASC";
            break;
        case 'author_desc':
            $sql .= " ORDER BY authors DESC";
            break;
        case 'date_newest':
            $sql .= " ORDER BY b.Publication_Date DESC";
            break;
        case 'date_oldest':
            $sql .= " ORDER BY b.Publication_Date ASC";
            break;
        default: // title_asc
            $sql .= " ORDER BY b.Title ASC";
    }
    
    // Add pagination
    $sql .= " LIMIT ? OFFSET ?";
    
    // Prepare statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters based on search and filter
    $param_types = '';
    $param_values = [];
    
    if (!empty($search_query)) {
        $search_param = "%$search_query%";
        $param_types .= 'sss';
        array_push($param_values, $search_param, $search_param, $search_param);
    }
    
    if (!empty($category_filter)) {
        $param_types .= 's';
        array_push($param_values, $category_filter);
    }
    
    $param_types .= 'ii';
    array_push($param_values, $perPage, $offset);
    
    $stmt->bind_param($param_types, ...$param_values);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
    
    // Get total count for pagination
    $total_result = $conn->query("SELECT FOUND_ROWS() AS total");
    $total_row = $total_result->fetch_assoc();
    $total_books = $total_row['total'];
    $total_pages = ceil($total_books / $perPage);
    
    // Get unique categories from shelf table for filter dropdown
    $categories = [];
    $category_query = $conn->query("SELECT DISTINCT Category FROM shelf WHERE Category IS NOT NULL AND Category != '' ORDER BY Category");
    while ($cat_row = $category_query->fetch_assoc()) {
        $categories[] = $cat_row['Category'];
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Catalog - ECOT Library</title>
    
    <!-- Favicon -->
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #00264d;
            --secondary-color: #004080;
            --accent-color: #007bff;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --danger-color: #dc3545;
            --success-color: #28a745;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-color);
            color: #333;
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
        
        .search-container {
            position: relative;
            margin-bottom: 30px;
        }
        
        .book-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .book-cover {
            height: 250px;
            object-fit: cover;
            object-position: center;
            background-color: #f0f0f0;
        }
        
        .book-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--success-color);
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        
        .book-title {
            font-weight: 600;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 3em;
        }
        
        .book-meta {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .book-meta-container {
            margin: 10px 0;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
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
            
            .sidebar.active {
                transform: translateX(0);
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
        
        .sort-dropdown {
            width: 200px;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .pagination .page-link {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <img src="../Student_Registration/include/ECOT.jpg" alt="ECOT Library Logo">
            <h5>Welcome, <?= htmlspecialchars($userName); ?></h5>
        </div>
        
        <div class="sidebar-nav">
            <a href="My_Issued_Books.php">
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
            <a href="Student_Books.php" class="active">
                <i class="fas fa-book-open"></i>
                <span>Book Catalog</span>
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
                <i class="fas fa-history"></i>
                <span>History</span>
            </a>
            <div class="mt-auto">
                <a href="Home.php" class="text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="navbar">
            <h3 class="m-0">ECOT Library Management System</h3>
            <div>
                <span class="me-3"><i class="fas fa-clock me-1"></i> <?= htmlspecialchars($currentDateTime); ?></span>
                <a href="profile.php" class="text-white me-3">
                    <i class="fas fa-user-circle me-1"></i>
                    <?= htmlspecialchars($userName); ?>
                </a>
                <a href="Home.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Logout
                </a>
            </div>
        </div>

        <h4 class="mb-4">Book Catalog</h4>
        
        <!-- Search and Filter Section -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                    
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by title, ISBN or author" 
                                   value="<?= htmlspecialchars($search_query); ?>">
                            <button class="btn btn-primary" type="submit" id="search-btn">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category); ?>" 
                                    <?= ($category_filter === $category) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <select class="form-select sort-dropdown" name="sort">
                            <option value="title_asc" <?= $sort_by === 'title_asc' ? 'selected' : '' ?>>Title (A-Z)</option>
                            <option value="title_desc" <?= $sort_by === 'title_desc' ? 'selected' : '' ?>>Title (Z-A)</option>
                            <option value="author_asc" <?= $sort_by === 'author_asc' ? 'selected' : '' ?>>Author (A-Z)</option>
                            <option value="author_desc" <?= $sort_by === 'author_desc' ? 'selected' : '' ?>>Author (Z-A)</option>
                            <option value="date_newest" <?= $sort_by === 'date_newest' ? 'selected' : '' ?>>Publication Date (Newest)</option>
                            <option value="date_oldest" <?= $sort_by === 'date_oldest' ? 'selected' : '' ?>>Publication Date (Oldest)</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading books...</p>
        </div>
        
        <!-- Books Grid -->
        <?php if (!empty($books)): ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="books-grid">
                <?php foreach ($books as $book): ?>
                    <div class="col">
                        <div class="card book-card h-100">
                            <div class="position-relative">
                                <img src="<?= !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : '../Student_Registration/uploads/books/default_book_cover.jpg'; ?>" 
                                     alt="<?= htmlspecialchars($book['Title']); ?>" 
                                     class="card-img-top book-cover">
                                <?php if ($book['Quantity'] > 0): ?>
                                    <span class="book-badge">Available: <?= (int)$book['Quantity']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title book-title"><?= htmlspecialchars($book['Title']); ?></h5>
                                <p class="card-text book-meta mb-1">
                                    <i class="fas fa-user-pen me-1"></i>
                                    <?= !empty($book['authors']) ? htmlspecialchars($book['authors']) : 'Unknown Author'; ?>
                                </p>
                                <div class="book-meta-container">
                                    <?php if (!empty($book['Category'])): ?>
                                        <span class="badge bg-secondary mb-1">
                                            <i class="fas fa-tag me-1"></i>
                                            <?= htmlspecialchars($book['Category']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($book['BookShelf_NO'])): ?>
                                        <span class="badge bg-info mb-1">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            Shelf: <?= htmlspecialchars($book['BookShelf_NO']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($book['Publication_Date'])): ?>
                                        <span class="badge bg-light text-dark mb-1">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('Y', strtotime($book['Publication_Date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            ISBN: <?= htmlspecialchars($book['ISBN_NO']); ?>
                                        </small>
                                        <a href="book_details.php?isbn=<?= htmlspecialchars($book['ISBN_NO']); ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h4>No Books Found</h4>
                <p class="text-muted">
                    <?php if (!empty($search_query)): ?>
                        No books match your search criteria. Try a different search term.
                    <?php else: ?>
                        There are currently no books available in the catalog.
                    <?php endif; ?>
                </p>
                <a href="Student_Books.php" class="btn btn-primary mt-3">
                    <i class="fas fa-sync me-1"></i> Reset Search
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <!-- Custom JavaScript -->
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
            
            // Show loading spinner during form submission
            $('form').on('submit', function() {
                $('#loading-spinner').show();
                $('#books-grid').hide();
            });
            
            // Auto-submit form when sort dropdown changes
            $('.sort-dropdown').change(function() {
                $(this).closest('form').submit();
            });
            
            // Hide loading spinner after page load
            $('#loading-spinner').hide();
        });
    </script>
</body>
</html>