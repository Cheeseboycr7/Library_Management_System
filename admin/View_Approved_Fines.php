<?php
session_start();
date_default_timezone_set("Africa/Johannesburg");
$currentDateTime = date("F j, Y, g:i a");

// Ensure admin is logged in (adjust session check as needed)


$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ecot_library2';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query approved fines along with book details and payment info.
// This query joins the returns, borrow, book, payment, and admin tables.
$query = "SELECT 
            r.Return_ID, 
            r.Borrow_ID, 
            r.Fine_Amount, 
            r.Return_Date, 
            b.ISBN_NO, 
            b.Title,
            p.Date AS PaymentDate, 
            p.Approval_Time, 
            a.Username AS Admin_Username
          FROM returns r
          JOIN borrow bo ON r.Borrow_ID = bo.Borrow_ID
          JOIN book b ON bo.ISBN_NO = b.ISBN_NO
          JOIN payment p ON r.Return_ID = p.Return_ID
          LEFT JOIN admin a ON p.Employee_ID = a.Admin_ID
          WHERE r.Fine_Status = 'Approved'
          ORDER BY p.Approval_Time DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Approved Fines - ECOT Library</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #ffffff;
      font-family: Arial, sans-serif;
    }
    .navbar {
      background-color: #00264d;
      color: white;
      padding: 10px 20px;
    }
    .navbar span {
      font-size: 1rem;
    }
    h2 {
      color: #00264d;
      text-align: center;
      margin-top: 30px;
      margin-bottom: 20px;
    }
    .table-container {
      margin: auto;
      max-width: 1100px;
      padding: 20px;
    }
    .table thead {
      background-color: #00264d;
      color: white;
    }
    .table tbody tr:hover {
      background-color: #f2f2f2;
    }
  </style>
</head>
<body>
  
  <div class="container table-container">
    <h2>Approved Fines</h2>
    <?php if ($result->num_rows > 0) { ?>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Return ID</th>
          <th>Borrow ID</th>
          <th>Book Title</th>
          <th>ISBN No</th>
          <th>Fine Amount</th>
          <th>Return Date</th>
          <th>Payment Date</th>
          <th>Approval Time</th>
          <th>Approved By</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()) {
            $approvedBy = isset($row['Admin_Username']) ? $row['Admin_Username'] : 'N/A';
        ?>
        <tr>
          <td><?php echo $row['Return_ID']; ?></td>
          <td><?php echo $row['Borrow_ID']; ?></td>
          <td><?php echo htmlspecialchars($row['Title']); ?></td>
          <td><?php echo htmlspecialchars($row['ISBN_NO']); ?></td>
          <td><?php echo number_format($row['Fine_Amount'], 2); ?></td>
          <td><?php echo date("F j, Y", strtotime($row['Return_Date'])); ?></td>
          <td><?php echo date("F j, Y", strtotime($row['PaymentDate'])); ?></td>
          <td><?php echo date("g:i a", strtotime($row['Approval_Time'])); ?></td>
          <td><?php echo htmlspecialchars($approvedBy); ?></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
    <div style="text-align: center; margin-top: 20px;">
                <i  class="fas fa-dashboard"></i>
                <a href="try.php" >Back to Dashboard</a>
            </div>
    <?php } else { ?>
      <p class="text-center">No approved fines found.</p>
    <?php } ?>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
