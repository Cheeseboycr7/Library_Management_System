<?php  
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

date_default_timezone_set("Africa/Johannesburg"); 
$currentDateTime = date("F j, Y, g:i a");

session_start();

$host = 'localhost'; 
$user = 'root'; 
$password = ''; 
$database = 'ecot_library2';  

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['username'])) {
    header('Location: Student_login.php'); 
    exit();
}

$userName = $_SESSION['username'];

// Get Application_ID and Email using Username
$app_query = "SELECT Application_ID, Email FROM application WHERE Username = ?";
$stmt = $conn->prepare($app_query);
$stmt->bind_param("s", $userName);
$stmt->execute();
$app_result = $stmt->get_result();
$app_row = $app_result->fetch_assoc();
$applicationID = $app_row['Application_ID'] ?? null;
$userEmail = $app_row['Email'] ?? null;

// Function to send email reminders using PHPMailer
function sendReminderEmail($toEmail, $bookTitle, $dueDate) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'shezimandla20@gmail.com';
        $mail->Password = 'ujns wrhc hljz mppp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('shezimandla20@gmail.com', 'ECOT Library');
        $mail->addAddress($toEmail);
        $mail->Subject = "Library Book Return Reminder";
        $mail->Body = "Dear User,\n\nThis is a reminder to return the book '{$bookTitle}' by {$dueDate}. Please return it on time to avoid fines.\n\nThank you,\nECOT Library";
        $mail->send();
        
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

// Check for books nearing due date and send reminders
if ($applicationID && $userEmail) {
    $reminder_sql = "SELECT b.Borrow_ID, bk.Title, b.Due_Date 
                     FROM borrow b 
                     JOIN book bk ON b.ISBN_NO = bk.ISBN_NO 
                     WHERE b.Application_ID = ? 
                     AND b.Status = 'Borrowed' 
                     AND b.Due_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)";

    $stmt = $conn->prepare($reminder_sql);
    $stmt->bind_param("i", $applicationID);
    $stmt->execute();
    $reminder_result = $stmt->get_result();

    while ($reminder = $reminder_result->fetch_assoc()) {
        sendReminderEmail($userEmail, $reminder['Title'], $reminder['Due_Date']);
    }
}

// Handle form submission for returning a book
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $borrow_id = $_POST['borrow_id'];
    $return_type = $_POST['return_type'];
    $return_date = date('Y-m-d');

    // Retrieve the due date and book details
    $due_date_sql = "SELECT b.Due_Date, bk.Title, bk.ISBN_NO 
                     FROM borrow b
                     JOIN book bk ON b.ISBN_NO = bk.ISBN_NO
                     WHERE b.Borrow_ID = ?";
    $stmt = $conn->prepare($due_date_sql);
    $stmt->bind_param("i", $borrow_id);
    $stmt->execute();
    $due_date_result = $stmt->get_result();
    $due_date_row = $due_date_result->fetch_assoc();
    $due_date = $due_date_row['Due_Date'];
    $bookTitle = $due_date_row['Title'];
    $isbn = $due_date_row['ISBN_NO'];

    // Calculate fine based on return type and overdue days
    $fine_amount = 0;
    $fine_reason = '';
    
    if ($return_date > $due_date) {
        $date1 = new DateTime($due_date);
        $date2 = new DateTime($return_date);
        $interval = $date1->diff($date2);
        $days_overdue = $interval->days;
        
        // Base fine for overdue
        $fine_amount = $days_overdue * 1; // E1 per day
        
        // Additional penalties for damaged or lost books
        if ($return_type === 'Damaged') {
            $fine_amount += 10; // E10 additional for damaged books
            $fine_reason = 'Damaged book + overdue fine';
        } elseif ($return_type === 'Lost') {
            $fine_amount += 50; // E50 additional for lost books
            $fine_reason = 'Lost book replacement + overdue fine';
        } else {
            $fine_reason = 'Overdue fine';
        }
    } elseif ($return_type === 'Damaged') {
        $fine_amount = 10; // E10 for damaged books returned on time
        $fine_reason = 'Damaged book';
    } elseif ($return_type === 'Lost') {
        $fine_amount = 50; // E50 for lost books
        $fine_reason = 'Lost book replacement';
    }

    // Format fine amount as Emalangeni currency
    $fine_amount_formatted = "E " . number_format($fine_amount, 2);

    // Insert return record with fine reason
    $return_sql = "INSERT INTO returns (Borrow_ID, Return_Date, Fine_Amount, Return_Type, Fine_Reason) 
                   VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($return_sql);
    $stmt->bind_param("isiss", $borrow_id, $return_date, $fine_amount, $return_type, $fine_reason);
    
    if ($stmt->execute()) {
        // Update borrow status
        $update_borrow_sql = "UPDATE borrow SET Status = 'Returned' WHERE Borrow_ID = ?";
        $stmt = $conn->prepare($update_borrow_sql);
        $stmt->bind_param("i", $borrow_id);
        $stmt->execute();

        // Update book quantity (only if not lost)
        if ($return_type !== 'Lost') {
            $update_book_sql = "UPDATE book SET Quantity = Quantity + 1 
                                WHERE ISBN_NO = ?";
            $stmt = $conn->prepare($update_book_sql);
            $stmt->bind_param("s", $isbn);
            $stmt->execute();
        }

        // Send return confirmation email
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'shezimandla20@gmail.com';
            $mail->Password = 'ujns wrhc hljz mppp';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('shezimandla20@gmail.com', 'ECOT Library');
            $mail->addAddress($userEmail);
            $mail->Subject = "Book Return Confirmation";
            
            $emailBody = "Dear $userName,\n\n";
            $emailBody .= "You have successfully returned the book '$bookTitle'.\n";
            $emailBody .= "Return Date: $return_date\n";
            $emailBody .= "Return Type: $return_type\n";
            
            if ($fine_amount > 0) {
                $emailBody .= "Fine Amount: $fine_amount_formatted\n";
                $emailBody .= "Reason: $fine_reason\n";
            }
            
            $emailBody .= "\nThank you for using ECOT Library.\n";
            
            $mail->Body = $emailBody;
            $mail->send();
            
        } catch (Exception $e) {
            error_log("Confirmation email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }

        echo "<script>
                alert('Book returned successfully! Fine charged: $fine_amount_formatted');
                window.location.href = 'return_book.php';
              </script>";
    } else {
        echo "<script>alert('Failed to return the book. Please try again.');</script>";
    }
}

// Retrieve borrowed books for the logged-in student
$borrowed_books_sql = "SELECT b.Borrow_ID, bk.ISBN_NO, bk.Title, b.Borrow_Date, b.Due_Date
                       FROM borrow b
                       JOIN book bk ON b.ISBN_NO = bk.ISBN_NO
                       WHERE b.Status = 'Borrowed' 
                       AND b.Application_ID = ?";

$stmt = $conn->prepare($borrowed_books_sql);
$stmt->bind_param("i", $applicationID);
$stmt->execute();
$borrowed_books = $stmt->get_result();

// Query notifications
$notif_sql = "SELECT b.Borrow_ID, bk.Title, b.Due_Date
              FROM borrow b
              JOIN book bk ON b.ISBN_NO = bk.ISBN_NO
              WHERE b.Application_ID = ? 
                AND b.Status = 'Borrowed'
                AND b.Due_Date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)";
$stmt = $conn->prepare($notif_sql);
$stmt->bind_param("i", $applicationID);
$stmt->execute();
$notif_result = $stmt->get_result();
$notif_count = $notif_result->num_rows;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1, maximum-scale=1">
    <title>Return Book - ECOT Library</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            width: 100%;
            max-width: 100%;
            overflow-x:hidden ;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background-color: #00264d;
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .sidebar h2 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .sidebar a {
            text-decoration: none;
            color: white;
            font-size: 16px;
            padding: 10px;
            display: block;
            border-radius: 5px;
            margin-bottom: 10px;
            transition: background-color 0.3s ease;
        }

        .sidebar a:hover {
            background-color: #004080;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color:  #00264d;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .dashboard-title {
            margin: 20px 0;
        }

        .table-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .table thead {
            background-color: #00264d;
            color: white;
        }

        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }

        a {
            text-decoration: none;
            color: white;
        }

        .logout {
            background-color:  #00264d;
            color: #fff;
            padding: 5px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .fine-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            border-left: 4px solid #00264d;
        }

        .fine-details h5 {
            color: #00264d;
            margin-bottom: 10px;
        }

        .badge-overdue {
            background-color: #dc3545;
        }

        .badge-due-soon {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-returned {
            background-color: #28a745;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            height: calc(100% - 180px);
        }

        

        
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div  class="sidebar" style="width: 270px;">
        <div style="margin-bottom: 25px;margin-top: 5px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 15px 0 25px;" class="sidebar-brand">
            <img style="margin-bottom:10px; width:150px; margin-left:30px; border-radius:5px;" src="../Student_Registration/include/ECOT.jpg" alt="ECOT Library Logo">
            <h5 style="margin-left: 20px;" class="mb-0">Welcome, <?= htmlspecialchars($userName); ?></h5>
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
        <div style="margin-left: 10px; padding-bottom:20px" class="navbar d-flex justify-content-between align-items-center px-4">
            <h3 class="m-0">ECOT Library Management System</h3>
            <div class="d-flex align-items-center">
                <span class="me-3"><?php echo $currentDateTime; ?> |</span>
                
                <!-- Notifications Dropdown -->
                <div class="dropdown me-3">
                    <a class="text-white dropdown-toggle" href="#" role="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php if($notif_count > 0){ ?>
                            <span class="badge bg-danger"><?php echo $notif_count; ?></span>
                        <?php } ?>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="notifDropdown">
                        <?php if($notif_count > 0){ ?>
                            <?php while($notif = $notif_result->fetch_assoc()) { ?>
                                <li>
                                    <a class="dropdown-item" href="#">
                                        Book <strong><?php echo htmlspecialchars($notif['Title']); ?></strong>
                                        is due on <?php echo date("F j, Y", strtotime($notif['Due_Date'])); ?>
                                    </a>
                                </li>
                            <?php } ?>
                        <?php } else { ?>
                            <li><a class="dropdown-item" href="#">No notifications</a></li>
                        <?php } ?>
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

        <h2   style="margin-left: 10px;" style="margin-right: 25px;" class="dashboard-title">Return Book</h2>

        <div class="table-container">
            <h4>Borrowed Books</h4>
            <form method="POST">
                <div class="form-group mb-3">
                    <label for="borrow_id" class="form-label">Select Borrowed Book</label>
                    <select name="borrow_id" id="borrow_id" class="form-control" required>
                        <option value="">-- Select a Borrowed Book --</option>
                        <?php
                        if ($borrowed_books->num_rows > 0) {
                            while ($row = $borrowed_books->fetch_assoc()) {
                                $due_date = strtotime($row['Due_Date']);
                                $today = strtotime(date('Y-m-d'));
                                $status = '';
                                
                                if ($today > $due_date) {
                                    $status = '<span class="badge badge-overdue ms-2">Overdue</span>';
                                } elseif (($due_date - $today) <= (2 * 24 * 60 * 60)) {
                                    $status = '<span class="badge badge-due-soon ms-2">Due Soon</span>';
                                }
                                
                                echo "<option value='{$row['Borrow_ID']}'>
                                      {$row['Title']} (Due: " . date("M j, Y", $due_date) . ") $status
                                      </option>";
                            }
                        } else {
                            echo "<option value=''>No borrowed books found</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label for="return_type" class="form-label">Return Type</label>
                    <select name="return_type" id="return_type" class="form-control" required>
                        <option value="Normal">Normal (Good Condition)</option>
                        <option value="Damaged">Damaged</option>
                        <option value="Lost">Lost</option>
                    </select>
                </div>

                <div class="fine-details">
                    <h5>Fine Information</h5>
                    <ul>
                        <li><strong>Normal Return:</strong> No fine if returned on time</li>
                        <li><strong>Overdue:</strong> E1 per day late</li>
                        <li><strong>Damaged Book:</strong> E10 additional fine</li>
                        <li><strong>Lost Book:</strong> E50 replacement fee</li>
                    </ul>
                </div>
                <button style="background-color: #00264d; margin-top:8px;" type="submit" class="btn btn-primary">
                    <i class="fas fa-exchange-alt"></i> Return Book
                </button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Show confirmation dialog before submitting
        document.querySelector('form').addEventListener('submit', function(e) {
            const returnType = document.getElementById('return_type').value;
            const borrowId = document.getElementById('borrow_id').value;
            
            if (!borrowId) {
                e.preventDefault();
                alert('Please select a book to return');
                return;
            }
            
            if (returnType === 'Damaged') {
                if (!confirm('Are you sure this book is damaged? A E10 fine will be applied.')) {
                    e.preventDefault();
                }
            } else if (returnType === 'Lost') {
                if (!confirm('Are you reporting this book as lost? A E50 replacement fee will be charged.')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>