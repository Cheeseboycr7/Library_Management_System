<?php
session_start();
$host = 'localhost'; 
$user = 'root'; 
$password = ''; 
$database = 'ecot_library2';  
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure admin is logged in (adjust your session check as needed)
if (!isset($_SESSION['admin_name'])) {
    header("Location: login.php"); // Adjust the login page path as needed
    exit();
}

$adminName = $_SESSION['admin_name'];

// Fetch Pending Fines with Book Details
$fines_sql = "SELECT r.Return_ID, r.Borrow_ID, r.Fine_Amount, r.Return_Date, b.Title, b.ISBN_NO
              FROM returns r
              JOIN borrow bo ON r.Borrow_ID = bo.Borrow_ID
              JOIN book b ON bo.ISBN_NO = b.ISBN_NO
              WHERE r.Fine_Status = 'Pending'";
$fines_result = $conn->query($fines_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $return_id = $_POST['return_id'];
    $fine_amount = $_POST['fine_amount'];
    $admin_id = $_POST['admin_id']; // This will be the logged-in admin's ID

    // Update Fine_Status to Approved
    $update_fine_sql = "UPDATE returns SET Fine_Status = 'Approved' WHERE Return_ID = ?";
    $stmt = $conn->prepare($update_fine_sql);
    $stmt->bind_param("i", $return_id);
    $stmt->execute();

    // Insert record into payment table with current date and approval time
    $insert_payment_sql = "INSERT INTO payment (Return_ID, Fine_Amount, Date, Employee_ID, Approval_Time) 
                            VALUES (?, ?, CURDATE(), ?, NOW())";
    $stmt = $conn->prepare($insert_payment_sql);
    $stmt->bind_param("idi", $return_id, $fine_amount, $admin_id);
    $stmt->execute();

    echo "<script>alert('Fine approved and recorded in payments.'); window.location.href = 'payment.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <title>Admin - Approve Fines</title>
    <style>
        body {
            font-family: "Poppins", serif;
            background-color: #ffffff;
            margin: 0;
            padding: 20px;
        }
        h2 {
            color: #00264d;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-container {
            max-width: 500px;
            margin: auto;
            background: #ffffff;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .form-label {
            font-weight: bold;
            color: #00264d;
            margin-bottom: 5px;
            display: block;
        }
        select, input, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background-color: #00264d;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background-color: #001a33;
        }
    </style>
</head>
<body>
    <h2>Approve Pending Fines</h2>
    <div class="form-container">
        <form method="POST">
            <label for="return_id" class="form-label">Select Return Record:</label>
            <select name="return_id" id="return_id" required>
                <option value="">-- Select Return --</option>
                <?php while ($row = $fines_result->fetch_assoc()) { ?>
                    <option value="<?= $row['Return_ID'] ?>"
                            data-fine="<?= $row['Fine_Amount'] ?>"
                            data-isbn="<?= $row['ISBN_NO'] ?>"
                            data-title="<?= $row['Title'] ?>">
                        Borrow ID: <?= $row['Borrow_ID'] ?> | Book: <?= $row['Title'] ?> | ISBN: <?= $row['ISBN_NO'] ?> | Fine: <?= $row['Fine_Amount'] ?>
                    </option>
                <?php } ?>
            </select>
            
            <!-- Admin Name displayed as readonly -->
            <label for="admin_name" class="form-label">Logged in Admin:</label>
            <input type="text" name="admin_name" id="admin_name" value="<?= $adminName ?>" readonly>
            
            <input type="hidden" name="admin_id" id="admin_id" value="<?= $_SESSION['admin_id'] ?>">
            <input type="hidden" name="fine_amount" id="fine_amount" value="">

            <button type="submit">Approve Fine</button>
        </form>
    </div>
    <div style="text-align: center; margin-top: 20px;">
        <i class="fas fa-dashboard"></i>
        <a href="try.php">Back to Dashboard</a>
    </div>
    
    <script>
        document.getElementById("return_id").addEventListener("change", function () {
            let selectedOption = this.options[this.selectedIndex];
            let fine = selectedOption.getAttribute("data-fine") || "0";
            document.getElementById("fine_amount").value = fine;
        });
    </script>
</body>
</html>
