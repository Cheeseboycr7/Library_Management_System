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

// Define renewal settings
$max_renews = 2; // Maximum allowed renewals
$renew_days = 14; // Days to extend for each renewal

// Get current user's application ID
$userName = $_SESSION['username'];
$app_query = "SELECT Application_ID FROM application WHERE Username = ?";
$stmt = $conn->prepare($app_query);
$stmt->bind_param("s", $userName);
$stmt->execute();
$app_result = $stmt->get_result();
$app_row = $app_result->fetch_assoc();
$applicationID = $app_row['Application_ID'] ?? null;

// Handle renew request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_id'])) {
    $borrow_id = $_POST['borrow_id'];
    
    // Check if book is already renewed
    $check_renew_query = "SELECT Renew_Count FROM borrow WHERE Borrow_ID = ?";
    $stmt = $conn->prepare($check_renew_query);
    $stmt->bind_param("i", $borrow_id);
    $stmt->execute();
    $renew_result = $stmt->get_result();
    $renew_data = $renew_result->fetch_assoc();
    
  
    
    if ($renew_data['Renew_Count'] >= $max_renews) {
        $error = "You have already renewed this book the maximum number of times ($max_renews).";
    } else {
        // Calculate new due date
        $new_due_date = date('Y-m-d', strtotime("+$renew_days days"));
        
        // Update borrow record
        $renew_query = "UPDATE borrow 
                        SET Due_Date = ?, 
                            Renew_Count = Renew_Count + 1,
                            Renewed_Date = CURDATE()
                        WHERE Borrow_ID = ?";
        $stmt = $conn->prepare($renew_query);
        $stmt->bind_param("si", $new_due_date, $borrow_id);
        
        if ($stmt->execute()) {
            $success = "Book renewed successfully. New due date: " . date("F j, Y", strtotime($new_due_date));
            
            // Get book details for email
            $book_query = "SELECT b.Title, a.Email 
                           FROM borrow br
                           JOIN book b ON br.ISBN_NO = b.ISBN_NO
                           JOIN application a ON br.Application_ID = a.Application_ID
                           WHERE br.Borrow_ID = ?";
            $stmt = $conn->prepare($book_query);
            $stmt->bind_param("i", $borrow_id);
            $stmt->execute();
            $book_result = $stmt->get_result();
            $book_data = $book_result->fetch_assoc();
            
            // Send confirmation email
            if ($book_data) {
                $to = $book_data['Email'];
                $subject = "Book Renewal Confirmation";
                $message = "Dear $userName,\n\n";
                $message .= "You have successfully renewed the book '{$book_data['Title']}'.\n";
                $message .= "New due date: " . date("F j, Y", strtotime($new_due_date)) . "\n\n";
                $message .= "Thank you for using ECOT Library.\n";
                
                $headers = "From: ecot-library@example.com";
                mail($to, $subject, $message, $headers);
            }
        } else {
            $error = "Failed to renew book. Please try again.";
        }
    }
}

// Get borrowed books eligible for renewal
$borrowed_books_query = "SELECT b.Borrow_ID, bk.Title, bk.ISBN_NO, b.Borrow_Date, b.Due_Date, b.Renew_Count
                         FROM borrow b
                         JOIN book bk ON b.ISBN_NO = bk.ISBN_NO
                         WHERE b.Application_ID = ?
                         AND b.Status = 'Borrowed'
                         AND b.Due_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                         ORDER BY b.Due_Date ASC";
$stmt = $conn->prepare($borrowed_books_query);
$stmt->bind_param("i", $applicationID);
$stmt->execute();
$borrowed_books = $stmt->get_result();

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
    <title>Renew Books - ECOT Library</title>
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
        .book-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .due-soon {
            border-left: 4px solid #ffc107;
        }
        .due-today {
            border-left: 4px solid #dc3545;
        }
        .badge-renewed {
            background-color: #6c757d;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background-color: #00264d;
            color: white;
            padding: 20px;
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
                    <a href="logout.php" class="btn btn-danger btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </nav>

        <h2 style="margin-left: 50px;" class="mb-4">Renew Books</h2>
        
        <?php if(isset($success)): ?>
            <div style="margin-left: 50px;" class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div style="margin-left: 50px;" class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div style="margin-left: 50px;" class="card">
            <div class="card-body">
                <h5 class="card-title">Books Eligible for Renewal</h5>
                <p class="card-text">
                    You can renew books up to <?php echo $max_renews; ?> times, for <?php echo $renew_days; ?> days each renewal.
                    Only books due within the next 7 days are shown.
                </p>
                
                <?php if($borrowed_books->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th  style="  background-color: #00264d;">Book Title</th>
                                    <th  style="  background-color: #00264d;">Borrow Date</th>
                                    <th  style="  background-color: #00264d;">Due Date</th>
                                    <th  style="  background-color: #00264d;">Renewals</th>
                                    <th  style="  background-color: #00264d;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($book = $borrowed_books->fetch_assoc()): 
                                    $due_class = '';
                                    $due_date = strtotime($book['Due_Date']);
                                    $today = strtotime(date('Y-m-d'));
                                    
                                    if ($due_date == $today) {
                                        $due_class = 'due-today';
                                    } elseif (($due_date - $today) <= (2 * 24 * 60 * 60)) {
                                        $due_class = 'due-soon';
                                    }
                                ?>
                                <tr class="<?php echo $due_class; ?>">
                                    <td>
                                        <a href="book_details.php?isbn=<?php echo $book['ISBN_NO']; ?>">
                                            <?php echo htmlspecialchars($book['Title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo date("M j, Y", strtotime($book['Borrow_Date'])); ?></td>
                                    <td><?php echo date("M j, Y", $due_date); ?></td>
                                    <td>
                                        <?php if($book['Renew_Count'] > 0): ?>
                                            <span class="badge badge-renewed"><?php echo $book['Renew_Count']; ?> time(s)</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Never renewed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to renew this book?');">
                                            <input type="hidden" name="borrow_id" value="<?php echo $book['Borrow_ID']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-sync-alt"></i> Renew
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        You currently have no books eligible for renewal. Only books due within the next 7 days can be renewed.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>