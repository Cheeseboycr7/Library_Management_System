<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: Student_login.php');
    exit();
}

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ecot_library2';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current user's application ID
$userName = $_SESSION['username'];
$app_query = "SELECT Application_ID FROM application WHERE Username = ?";
$stmt = $conn->prepare($app_query);
$stmt->bind_param("s", $userName);
$stmt->execute();
$app_result = $stmt->get_result();
$app_row = $app_result->fetch_assoc();
$applicationID = $app_row['Application_ID'] ?? null;

// Get sort parameter from URL
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'recent';
$sort_clause = "ORDER BY br.Borrow_Date DESC"; // Default sort

switch ($sort) {
    case 'status':
        $sort_clause = "ORDER BY 
                        CASE 
                            WHEN r.Return_ID IS NULL AND br.Due_Date < CURDATE() THEN 1
                            WHEN r.Return_ID IS NULL THEN 2
                            WHEN r.Fine_Amount > 0 THEN 3
                            ELSE 4
                        END, br.Borrow_Date DESC";
        break;
    case 'title':
        $sort_clause = "ORDER BY b.Title ASC";
        break;
    case 'recent':
    default:
        $sort_clause = "ORDER BY br.Borrow_Date DESC";
        break;
}

// Get borrowing history with sorting
$history_query = "SELECT 
                    b.Title, 
                    b.ISBN_NO, 
                    b.cover_image,
                    br.Borrow_Date, 
                    br.Due_Date,
                    r.Return_Date,
                    r.Fine_Amount,
                    r.Return_Type,
                    CASE 
                        WHEN r.Return_ID IS NULL AND br.Due_Date < CURDATE() THEN 'Overdue'
                        WHEN r.Return_ID IS NULL THEN 'Borrowed'
                        WHEN r.Fine_Amount > 0 THEN 'Returned (Fine Paid)'
                        ELSE 'Returned'
                    END AS Status
                 FROM borrow br
                 JOIN book b ON br.ISBN_NO = b.ISBN_NO
                 LEFT JOIN returns r ON br.Borrow_ID = r.Borrow_ID
                 WHERE br.Application_ID = ?
                 $sort_clause";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $applicationID);
$stmt->execute();
$history_result = $stmt->get_result();

// Get count of books due soon for notifications
$notif_query = "SELECT COUNT(*) as count 
                FROM borrow 
                WHERE Application_ID = ? 
                AND Status = 'Borrowed'
                AND Due_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)";
$stmt = $conn->prepare($notif_query);
$stmt->bind_param("i", $applicationID);
$stmt->execute();
$notif_result = $stmt->get_result();
$notif_count = $notif_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My History - ECOT Library</title>
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
        .history-item {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .history-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .book-cover-sm {
            width: 60px;
            height: 90px;
            object-fit: cover;
            border-radius: 4px;
        }
        .badge-borrowed {
            background-color: #17a2b8;
        }
        .badge-returned {
            background-color: #28a745;
        }
        .badge-overdue {
            background-color: #dc3545;
        }
        .badge-fine {
            background-color: #ffc107;
            color: #212529;
        }
        .fine-amount {
            font-weight: bold;
            color: #dc3545;
        }
        .sort-option {
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            margin-right: 5px;
            display: inline-block;
        }
        .sort-option:hover, .sort-option.active {
            background-color: #00264d;
            color: white;
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
        </div>
        
        <div class="sidebar-footer">
            <a href="Home.php" class="text-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Content -->
    <div class="content">
        <!-- Navbar -->
        <nav style="margin-left: 50px;" class="navbar navbar-expand-lg mb-4">
            <div class="container-fluid">
                <a class="navbar-brand text-white" href="#">ECOT Library</a>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3"><?php echo date("F j, Y, g:i a"); ?></span>
                    
                    <!-- Notifications -->
                    <div class="dropdown me-3">
                        <a class="text-white dropdown-toggle" href="#" role="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php if($notif_count > 0): ?>
                                <span class="badge bg-danger"><?php echo $notif_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="notifDropdown">
                            <?php if($notif_count > 0): ?>
                                <?php
                                $notif_books_query = "SELECT b.Title, br.Due_Date 
                                                     FROM borrow br
                                                     JOIN book b ON br.ISBN_NO = b.ISBN_NO
                                                     WHERE br.Application_ID = ?
                                                     AND br.Status = 'Borrowed'
                                                     AND br.Due_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)";
                                $stmt = $conn->prepare($notif_books_query);
                                $stmt->bind_param("i", $applicationID);
                                $stmt->execute();
                                $notif_books = $stmt->get_result();
                                while($notif = $notif_books->fetch_assoc()):
                                ?>
                                    <li><a class="dropdown-item" href="#">
                                        <?php echo htmlspecialchars($notif['Title']); ?> due on <?php echo date("M j", strtotime($notif['Due_Date'])); ?>
                                    </a></li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="#">No notifications</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <a href="profile.php" class="text-white me-3">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($userName); ?>
                    </a>
                    <a href="Home.php" class="btn btn-danger btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </nav>

        <h2 style="margin-left: 50px;" class="mb-4">My Borrowing History</h2>

        <!-- Sorting Options -->
        <div style="margin-left: 50px;" class="mb-4">
            <span class="me-2">Sort by:</span>
            <a href="?sort=recent" class="sort-option <?php echo $sort == 'recent' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Most Recent
            </a>
            <a href="?sort=status" class="sort-option <?php echo $sort == 'status' ? 'active' : ''; ?>">
                <i class="fas fa-sort"></i> Status
            </a>
            <a href="?sort=title" class="sort-option <?php echo $sort == 'title' ? 'active' : ''; ?>">
                <i class="fas fa-sort-alpha-down"></i> Title
            </a>
        </div>

        <?php if($history_result->num_rows > 0): ?>
            <div style="margin-left: 50px;" class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th  style="  background-color: #00264d;">Book</th>
                                    <th  style="  background-color: #00264d;">Borrowed</th>
                                    <th  style="  background-color: #00264d;">Due</th>
                                    <th  style="  background-color: #00264d;">Returned</th>
                                    <th  style="  background-color: #00264d;">Status</th>
                                    <th  style="  background-color: #00264d;">Fine</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($item = $history_result->fetch_assoc()): ?>
                                <tr class="history-item">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../uploads/<?php echo htmlspecialchars($item['cover_image']); ?>" 
                                                 alt="Book Cover" 
                                                 class="book-cover-sm me-3">
                                            <div>
                                                <strong><?php echo htmlspecialchars($item['Title']); ?></strong><br>
                                                <small class="text-muted">ISBN: <?php echo htmlspecialchars($item['ISBN_NO']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo date("M j, Y", strtotime($item['Borrow_Date'])); ?></td>
                                    <td><?php echo date("M j, Y", strtotime($item['Due_Date'])); ?></td>
                                    <td>
                                        <?php if($item['Return_Date']): ?>
                                            <?php echo date("M j, Y", strtotime($item['Return_Date'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not returned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $badge_class = '';
                                        switch($item['Status']) {
                                            case 'Borrowed': $badge_class = 'badge-borrowed'; break;
                                            case 'Returned': $badge_class = 'badge-returned'; break;
                                            case 'Overdue': $badge_class = 'badge-overdue'; break;
                                            case 'Returned (Fine Paid)': $badge_class = 'badge-fine'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $item['Status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($item['Fine_Amount'] > 0): ?>
                                            <span class="fine-amount">E <?php echo number_format($item['Fine_Amount'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                You haven't borrowed any books yet. Visit our <a href="Student_Books.php">book catalog</a> to get started!
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>