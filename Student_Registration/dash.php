<?php
// Security and error handling
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

// Database configuration (consider moving to a separate config file)
//require_once 'config.php'; // Suggested for better security

// Database connection with error handling


// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ecot_library2';

try {
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $userName = $_SESSION['username'];
    
    // Fetch Application_ID using prepared statement
    $app_query = $conn->prepare("SELECT Application_ID FROM application WHERE Username = ?");
    if (!$app_query) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $app_query->bind_param("s", $userName);
    $app_query->execute();
    $app_result = $app_query->get_result();
    $app_data = $app_result->fetch_assoc();
    $application_id = $app_data['Application_ID'] ?? null;
    
    if (!$application_id) {
        throw new Exception("Error: Application ID not found for user.");
    }
    
    // Fetch borrowed books with proper error handling
    $sql = $conn->prepare("SELECT b.Borrow_ID, b.ISBN_NO, b.Borrow_Date, b.Due_Date, bo.Title 
                         FROM borrow b
                         JOIN book bo ON b.ISBN_NO = bo.ISBN_NO
                         WHERE b.Application_ID = ? AND b.Status = 'Borrowed' 
                         AND TIMESTAMPDIFF(MINUTE, b.Reserved_At, b.Borrow_Date) <= 30");
    if (!$sql) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $sql->bind_param("i", $application_id);
    $sql->execute();
    $result = $sql->get_result();
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>My Issued Books - ECOT Library</title>
    
    <!-- Favicon -->
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            background-color: var(--primary-color);
            color: white;
            padding: 20px 15px;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
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
            flex-grow: 1;
        }
        
        .sidebar a {
            display: flex;
            align-items: center;
            text-decoration: none;
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
        
        .sidebar-footer {
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
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
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .user-menu .badge {
            margin-right: 15px;
            background-color: var(--secondary-color);
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .dashboard-title {
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
        }
        
        .table-container {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .table th {
            background-color: var(--primary-color) !important;
            color: white;
            font-weight: 500;
        }
        
        .table td, .table th {
            vertical-align: middle;
            padding: 12px 15px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-btn {
            padding: 5px 10px;
            font-size: 0.85rem;
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
            
            .user-menu {
                margin-top: 10px;
                width: 100%;
                justify-content: space-between;
            }
        }

        a{
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <img src="../Student_Registration/include/ECOT.jpg" alt="ECOT Library Logo">
            <h5 class="mb-0">ECOT Library</h5>
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

    <div class="content">
        <div class="navbar">
            <h3 class="m-0">ECOT Library Management System</h3>
            
            <div class="user-menu">
                <span class="badge">
                    <i class="fas fa-clock me-1"></i>
                    <?php echo htmlspecialchars($currentDateTime); ?>
                </span>
                
                <a href="profile.php" class="text-white me-3">
                    <i class="fas fa-user-circle me-1"></i>
                    <?php echo htmlspecialchars($userName); ?>
                </a>
                
                <a href="Home.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Logout
                </a>
            </div>
        </div>

        <div class="dashboard-header">
            <h4 class="dashboard-title">My Issued Books</h4>
            <div>
                <a href="borrow.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i>
                    Borrow New Book
                </a>
            </div>
        </div>
        
        <div class="table-container">
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Borrow ID</th>
                                <th>ISBN No</th>
                                <th>Title</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                $dueDate = new DateTime($row['Due_Date']);
                                $today = new DateTime();
                                $interval = $today->diff($dueDate);
                                $daysLeft = $interval->format('%r%a');
                                
                                if ($daysLeft < 0) {
                                    $statusClass = 'badge-danger';
                                    $statusText = 'Overdue';
                                } elseif ($daysLeft <= 3) {
                                    $statusClass = 'badge-warning';
                                    $statusText = 'Due Soon';
                                } else {
                                    $statusClass = 'badge-success';
                                    $statusText = 'Active';
                                }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['Borrow_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ISBN_NO']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Title']); ?></td>
                                    <td><?php echo date("M j, Y", strtotime($row['Borrow_Date'])); ?></td>
                                    <td><?php echo date("M j, Y", strtotime($row['Due_Date'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="book_details.php?isbn=<?php echo $row['ISBN_NO']; ?>" 
                                           class="btn btn-sm btn-outline-primary action-btn" 
                                           title="View Book Details">
                                            <i class="fas fa-info-circle"></i>
                                        </a>
                                        <a href="renew_book.php?borrow_id=<?php echo $row['Borrow_ID']; ?>" 
                                           class="btn btn-sm btn-outline-success action-btn" 
                                           title="Renew Book">
                                            <i class="fas fa-sync-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    You currently have no issued books. 
                    <a href="Student_Books.php" class="alert-link">Browse our collection</a> to borrow books.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Add active class to current menu item
        $(document).ready(function() {
            var current = location.pathname.split('/').pop();
            $('.sidebar-nav a').each(function() {
                var $this = $(this);
                if ($this.attr('href') === current) {
                    $this.addClass('active');
                }
            });
            
            // Toggle sidebar on mobile
            $('#sidebarToggle').click(function() {
                $('.sidebar').toggleClass('active');
            });
        });
    </script>
</body>
</html>

<?php
// Close database connections
$app_query->close();
$sql->close();
$conn->close();
?>