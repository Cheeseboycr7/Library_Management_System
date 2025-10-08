<?php
session_start();
date_default_timezone_set("Africa/Johannesburg");

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ecot_library2';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Approve reserved book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    $borrow_id = $_POST['approve_id'];
    $call_number = trim($_POST['call_number']);
    
    // Validate call number
    if (empty($call_number)) {
        echo "<script>alert('Please enter a call number before approving.'); window.location.href = 'approved_reserseved.php';</script>";
        exit();
    }
    
    // Update status to 'Borrowed' and set call number
    $update_query = $conn->prepare("UPDATE borrow SET Status = 'Borrowed', Call_Number = ? WHERE Borrow_ID = ? AND TIMESTAMPDIFF(MINUTE, Reserved_At, NOW()) <= 30");
    $update_query->bind_param("si", $call_number, $borrow_id);
    
    if ($update_query->execute()) {
        echo "<script>alert('Book successfully approved as borrowed with call number: $call_number'); window.location.href = 'approved_reserseved.php';</script>";
    } else {
        echo "<script>alert('Approval failed. Either time exceeded or an error occurred.');</script>";
    }
}

// Fetch all reservations within 30 minutes
$query = "SELECT b.Borrow_ID, b.ISBN_NO, bk.Title, b.Application_ID, a.Name, a.Surname, b.Reserved_At 
          FROM borrow b 
          JOIN book bk ON b.ISBN_NO = bk.ISBN_NO 
          JOIN application a ON b.Application_ID = a.Application_ID 
          WHERE b.Status = 'Reserved' AND TIMESTAMPDIFF(MINUTE, b.Reserved_At, NOW()) <= 30";
$reservations = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Approve Borrowed Books</title>
    
    <!-- Bootstrap & Custom Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #f4f4f4;
            font-family: "Poppins", serif;
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
        .container {
            background: white;
            margin-top: 30px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }
        .table {
            margin-top: 15px;
            border-radius: 8px;
            overflow: hidden;
        }
        .table th {
            background-color: #00264d;
            color: white;
            text-align: center;
        }
        .table tbody tr:hover {
            background-color: #e6f2ff;
        }
        .approve-btn {
            background-color: #00264d;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: 0.3s;
        }
        .approve-btn:hover {
            background-color: #004080;
        }
        .call-number-input {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 150px;
            margin-right: 10px;
        }
    </style>
</head>
<body>

    <div class="header">Approve Book Borrow Requests</div>

    <div class="container">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Borrow ID</th>
                    <th>Book Title</th>
                    <th>ISBN</th>
                    <th>Name</th>
                    <th>Reserved At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $reservations->fetch_assoc()): ?>
                    <tr>
                        <td class="text-center"><?= htmlspecialchars($row['Borrow_ID']) ?></td>
                        <td><?= htmlspecialchars($row['Title']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['ISBN_NO']) ?></td>
                        <td><?= htmlspecialchars($row['Name']) ?> <?= htmlspecialchars($row['Surname']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['Reserved_At']) ?></td>
                        <td class="text-center">
                            <form method="POST" style="display:flex; align-items:center; justify-content:center;">
                                <input type="hidden" name="approve_id" value="<?= $row['Borrow_ID'] ?>">
                                <input type="text" name="call_number" class="call-number-input" placeholder="Call Number" required>
                                <button type="submit" class="approve-btn"><i class="fas fa-check"></i> Approve</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div style="text-align: center; margin-top: 20px;">
                <i class="fas fa-dashboard"></i>
                <a href="try.php">Back to Dashboard</a>
            </div>
    </div>

</body>
</html>