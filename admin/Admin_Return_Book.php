<?php
session_start();


if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php'); // Redirect to login if not authenticated
    exit();
}

$adminName = $_SESSION['admin_name'];


// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ecot_library2';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle return approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_id'])) {
    $return_id = $_POST['return_id'];
    $action = $_POST['action'];
   // $admin_id = $_SESSION['admin_id'];
    
    if ($action === 'approve') {
        // Get return details
        $return_query = "SELECT r.*, b.ISBN_NO, br.Application_ID 
                         FROM returns r
                         JOIN borrow br ON r.Borrow_ID = br.Borrow_ID
                         JOIN book b ON br.ISBN_NO = b.ISBN_NO
                         WHERE r.Return_ID = ?";
        $stmt = $conn->prepare($return_query);
        $stmt->bind_param("i", $return_id);
        $stmt->execute();
        $return_result = $stmt->get_result();
        $return_data = $return_result->fetch_assoc();
        
        if ($return_data) {
            // Update return status
            $update_return = "UPDATE returns SET Fine_Status = 'Approved' WHERE Return_ID = ?";
            $stmt = $conn->prepare($update_return);
            $stmt->bind_param("i", $return_id);
            $stmt->execute();
            
            // Record payment if there's a fine
            if ($return_data['Fine_Amount'] > 0) {
                $payment_query = "INSERT INTO payment (Return_ID, Fine_Amount, Date, ISBN_NO, Employee_ID, Approval_Time)
                                  VALUES (?, ?, CURDATE(), ?, ?, NOW())";
                $stmt = $conn->prepare($payment_query);
                $stmt->bind_param("idsi", $return_id, $return_data['Fine_Amount'], $return_data['ISBN_NO'], $admin_id);
                $stmt->execute();
            }
            
            // Update book quantity
            $update_book = "UPDATE book SET Quantity = Quantity + 1 WHERE ISBN_NO = ?";
            $stmt = $conn->prepare($update_book);
            $stmt->bind_param("s", $return_data['ISBN_NO']);
            $stmt->execute();
            
            // Update borrow status
            $update_borrow = "UPDATE borrow SET Status = 'Returned' WHERE Borrow_ID = ?";
            $stmt = $conn->prepare($update_borrow);
            $stmt->bind_param("i", $return_data['Borrow_ID']);
            $stmt->execute();
            
            $success = "Return approved successfully!";
        }
    } elseif ($action === 'reject') {
        // Delete the return record if rejected
        $delete_return = "DELETE FROM returns WHERE Return_ID = ?";
        $stmt = $conn->prepare($delete_return);
        $stmt->bind_param("i", $return_id);
        $stmt->execute();
        
        $success = "Return rejected and removed from the system!";
    }
}

// Get pending returns
$pending_returns_query = "SELECT r.Return_ID, r.Return_Date, r.Fine_Amount, r.Return_Type,
                         b.Title, b.ISBN_NO,
                         a.Name, a.Surname, a.Email,
                         br.Borrow_Date, br.Due_Date
                         FROM returns r
                         JOIN borrow br ON r.Borrow_ID = br.Borrow_ID
                         JOIN book b ON br.ISBN_NO = b.ISBN_NO
                         JOIN application a ON br.Application_ID = a.Application_ID
                         WHERE r.Fine_Status = 'Pending'
                         ORDER BY r.Return_Date ASC";
$pending_returns = $conn->query($pending_returns_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Book Returns - ECOT Library</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #00264d;
        }
        .card {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .return-item {
            border-left: 4px solid #00264d;
            transition: transform 0.2s;
        }
        .return-item:hover {
            transform: translateY(-3px);
        }
        .fine-amount {
            font-weight: bold;
            color: #dc3545;
        }
        .header {
            background-color: #00264d;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
<div class="header">Approve Book Return Requests</div>

    <div style="margin-top: 5px;" class="container">
        <h2 class="mb-4">Approve Book Returns</h2>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if($pending_returns->num_rows > 0): ?>
            <div class="row">
                <?php while($return = $pending_returns->fetch_assoc()): 
                    $is_overdue = strtotime($return['Due_Date']) < strtotime($return['Return_Date']);
                ?>
                <div class="col-md-6">
                    <div class="card return-item">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($return['Title']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                Returned by: <?php echo htmlspecialchars($return['Name'] . ' ' . $return['Surname']); ?>
                            </h6>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>ISBN:</strong> <?php echo htmlspecialchars($return['ISBN_NO']); ?></p>
                                    <p class="mb-1"><strong>Borrowed:</strong> <?php echo date("M j, Y", strtotime($return['Borrow_Date'])); ?></p>
                                    <p class="mb-1"><strong>Due:</strong> <?php echo date("M j, Y", strtotime($return['Due_Date'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Returned:</strong> <?php echo date("M j, Y", strtotime($return['Return_Date'])); ?></p>
                                    <p class="mb-1"><strong>Status:</strong> <span class="badge badge-pending">Pending Approval</span></p>
                                    <?php if($return['Fine_Amount'] > 0): ?>
                                        <p class="mb-1"><strong>Fine:</strong> <span class="fine-amount">E <?php echo number_format($return['Fine_Amount'], 2); ?></span></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="return_id" value="<?php echo $return['Return_ID']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="return_id" value="<?php echo $return['Return_ID']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this return?');">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                            
                            <a href="mailto:<?php echo htmlspecialchars($return['Email']); ?>" class="btn btn-info btn-sm float-end">
                                <i class="fas fa-envelope"></i> Contact Student
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                There are currently no pending returns to approve.
            </div>
        <?php endif; ?>
        <div style="text-align: center; margin-top: 20px;">
                <i  class="fas fa-dashboard"></i>
                <a href="try.php" >Back to Dashboard</a>
            </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>