<?php
// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ecot_library2'; // Replace with your database name

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $return_id = $_POST['return_id'];
    $admin_id = $_POST['admin_id'];
    $fine_amount = $_POST['fine_amount'];
    $isbn_no = $_POST['isbn_no'];
    $member_id = 1; // Replace with logged-in student's Member ID
    $payment_date = date('Y-m-d');

    // Insert payment record
    $payment_sql = "INSERT INTO payment (Return_ID, Fine_Amount, Date, ISBN_NO, Employee_ID) 
                    VALUES ('$return_id', '$fine_amount', '$payment_date', '$isbn_no', '$admin_id')";
    if ($conn->query($payment_sql)) {
        // Generate receipt
        $receipt_sql = "INSERT INTO receipt (Member_ID, Date, Amount_Paid, ISBN_NO) 
                        VALUES ('$member_id', '$payment_date', '$fine_amount', '$isbn_no')";
        $conn->query($receipt_sql);

        echo "<script>alert('Payment processed successfully and receipt generated!');</script>";
    } else {
        echo "<script>alert('Failed to process payment. Please try again.');</script>";
    }
}

// Retrieve return records for payment
$return_records_sql = "SELECT r.Return_ID, bk.ISBN_NO, bk.Title, r.Fine_Amount 
                       FROM returns r
                       JOIN borrow b ON r.Borrow_ID = b.Borrow_ID
                       JOIN book bk ON b.ISBN_NO = bk.ISBN_NO
                       WHERE r.Fine_Amount > 0";

$return_records = $conn->query($return_records_sql);

// Retrieve admin details
$admin_sql = "SELECT Admin_ID, Username FROM admin";
$admins = $conn->query($admin_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - ECOT Library</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
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
            background-color: #007bff;
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
        }

        .table thead {
            background-color: #007bff;
            color: white;
        }

        .table th, .table td {
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>ELMS</h2>
        <a href="My_Issued_Books.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="borrow.php"><i class="fas fa-plus-square"></i> Borrow Book</a>
        <a href="return_book.php"><i class="fas fa-plus-square"></i> Return Book</a>
        <a href="payment.php"><i class="fas fa-money-bill"></i> Payment</a>
    </div>
    <!-- Content -->
    <div class="content">
        <div class="navbar">
            <h3>ECOT Library Management System</h3>
        </div>

        <h2 class="dashboard-title">Process Payment</h2>

        <div class="table-container">
            <form method="POST">
                <div class="form-group mb-3">
                    <label for="return_id" class="form-label">Select Return Record</label>
                    <select name="return_id" id="return_id" class="form-control" required>
                        <option value="">-- Select a Return Record --</option>
                        <?php
                        if ($return_records->num_rows > 0) {
                            while ($row = $return_records->fetch_assoc()) {
                                echo "<option value='{$row['Return_ID']}'>
                                      {$row['Title']} (ISBN: {$row['ISBN_NO']} - Fine: {$row['Fine_Amount']})
                                      </option>";
                            }
                        } else {
                            echo "<option value=''>No return records with pending fines</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label for="admin_id" class="form-label">Processed By (Admin)</label>
                    <select name="admin_id" id="admin_id" class="form-control" required>
                        <option value="">-- Select Admin --</option>
                        <?php
                        if ($admins->num_rows > 0) {
                            while ($row = $admins->fetch_assoc()) {
                                echo "<option value='{$row['Admin_ID']}'>
                                      {$row['Username']}
                                      </option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label for="fine_amount" class="form-label">Fine Amount</label>
                    <input type="number" name="fine_amount" id="fine_amount" class="form-control" step="0.01" min="0" placeholder="0.00" required>
                </div>

                <div class="form-group mb-3">
                    <label for="isbn_no" class="form-label">Book ISBN</label>
                    <input type="text" name="isbn_no" id="isbn_no" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary">Process Payment</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
