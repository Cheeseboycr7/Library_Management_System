<?php
session_start();
include '../includes/db_connect.php';

// Initialize search term
$searchTerm = '';

// Update the query to include search functionality
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $query = "SELECT Application_ID, Name, Surname, Faculty, Department, Cellphone_No, Parent_Cellphone_No, Course, ID_Number, Status, Deactivation_Reason 
              FROM application 
              WHERE Name LIKE ? OR Surname LIKE ?";
    $stmt = $conn->prepare($query);
    $searchParam = "%" . $searchTerm . "%";
    $stmt->bind_param('ss', $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
}
else {
    // Fetch Registered Members
    $query = "SELECT Application_ID, Name, Surname, Faculty, Department, Cellphone_No, Parent_Cellphone_No, Course, ID_Number, Status, Deactivation_Reason FROM application";
    $result = $conn->query($query);
}

// Handle Deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate'])) {
    $applicationId = $_POST['application_id'];
    $reason = $_POST['reason'];
    
    // First, check if the user has any active borrowings
    $checkBorrowings = $conn->prepare("SELECT COUNT(*) as active_borrowings FROM borrow WHERE Application_ID = ? AND (Status = 'Borrowed' OR Status = 'Reserved')");
    $checkBorrowings->bind_param('i', $applicationId);
    $checkBorrowings->execute();
    $borrowingsResult = $checkBorrowings->get_result();
    $borrowingsData = $borrowingsResult->fetch_assoc();
    
    if ($borrowingsData['active_borrowings'] > 0) {
        echo "<script>alert('Cannot deactivate user. They have active book borrowings/reservations.'); window.location.href='Members.php';</script>";
        exit();
    }
    
    $updateQuery = "UPDATE application SET Status = 'Inactive', Deactivation_Reason = ? WHERE Application_ID = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('si', $reason, $applicationId);
    if ($stmt->execute()) {
        echo "<script>alert('User deactivated successfully. They can no longer borrow books.'); window.location.href='Members.php';</script>";
    } else {
        echo "<script>alert('Error deactivating user');</script>";
    }
}

// Handle Reactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reactivate'])) {
    $applicationId = $_POST['application_id'];
    $updateQuery = "UPDATE application SET Status = 'Active', Deactivation_Reason = NULL WHERE Application_ID = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('i', $applicationId);
    if ($stmt->execute()) {
        echo "<script>alert('User reactivated successfully. They can now borrow books again.'); window.location.href='Members.php';</script>";
    } else {
        echo "<script>alert('Error reactivating user');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Members</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <style>
        /* Previous styles remain the same... */

        
 body {
            font-family: "Poppins", serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .main-content {
            padding: 20px;
        }

        .table-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #1f2937;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .btn {
            padding: 6px 12px;
            font-size: 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-activate {
            background-color: #16a34a;
            color: white;
        }

        .btn-activate:hover {
            background-color: #15803d;
        }

        .btn-deactivate {
            background-color: #dc2626;
            color: white;
        }

        .btn-deactivate:hover {
            background-color: #b91c1c;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 24px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .modal-content {
                width: 90%;
            }
        }
        
        .status-active {
            color: #16a34a;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #dc2626;
            font-weight: bold;
        }
        
        .disabled-row {
            background-color: #fee2e2 !important;
        }
        
        .disabled-row:hover {
            background-color: #fecaca !important;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <h1>Registered Members</h1>
        
        <!-- Search Bar -->
        <div class="search-container">
            <form method="GET" action="">
                <input style="margin-bottom: 5px;" type="text" name="search" placeholder="Search by Name or Surname" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button style="background-color: #00264d;color:#fff" type="submit" class="search-btn">Search</button>
            </form>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="background-color: #00264d;">ID</th>
                        <th style="background-color: #00264d;">Name</th>
                        <th style="background-color: #00264d;">Surname</th>
                        <th style="background-color: #00264d;">Faculty</th>
                        <th style="background-color: #00264d;">Department</th>
                        <th style="background-color: #00264d;">Cellphone</th>
                        <th style="background-color: #00264d;">Parent Cellphone</th>
                        <th style="background-color: #00264d;">Course</th>
                        <th style="background-color: #00264d;">ID Number</th>
                        <th style="background-color: #00264d;">Status</th>
                        <th style="background-color: #00264d;">Reason</th>
                        <th style="background-color: #00264d;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="<?php echo $row['Status'] === 'Inactive' ? 'disabled-row' : ''; ?>">
                                <td><?php echo $row['Application_ID']; ?></td>
                                <td><?php echo $row['Name']; ?></td>
                                <td><?php echo $row['Surname']; ?></td>
                                <td><?php echo $row['Faculty']; ?></td>
                                <td><?php echo $row['Department']; ?></td>
                                <td><?php echo $row['Cellphone_No']; ?></td>
                                <td><?php echo $row['Parent_Cellphone_No']; ?></td>
                                <td><?php echo $row['Course']; ?></td>
                                <td><?php echo $row['ID_Number']; ?></td>
                                <td id="status-cell-<?php echo $row['Application_ID']; ?>" class="<?php echo $row['Status'] === 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $row['Status']; ?>
                                    <?php if ($row['Status'] === 'Inactive'): ?>
                                        <br><small>(Cannot borrow books)</small>
                                    <?php endif; ?>
                                </td>
                                <td id="reason-cell-<?php echo $row['Application_ID']; ?>"><?php echo $row['Deactivation_Reason'] ?? 'N/A'; ?></td>
                                <td>
                                    <?php if ($row['Status'] === 'Active'): ?>
                                        <button id="action-btn-<?php echo $row['Application_ID']; ?>" class="btn btn-deactivate" onclick="openModal(<?php echo $row['Application_ID']; ?>)">Deactivate</button>
                                    <?php else: ?>
                                        <button id="action-btn-<?php echo $row['Application_ID']; ?>" class="btn btn-activate" onclick="activateUser(<?php echo $row['Application_ID']; ?>)">Activate</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12">No members found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <i class="fas fa-dashboard"></i>
            <a href="try.php">Back to Dashboard</a>
        </div>
    </div>

    <!-- Deactivation Modal -->
    <div id="deactivationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Deactivate User</h2>
            <p style="text-align: left; margin-bottom: 15px; color: #dc2626;">
                <i class="fas fa-exclamation-circle"></i> Note: Deactivated users cannot borrow books.
            </p>
            <form method="POST" action="">
                <input type="hidden" id="application_id" name="application_id">
                <div class="form-group">
                    <label for="reason">Reason for Deactivation:</label>
                    <textarea name="reason" id="reason" rows="4" required></textarea>
                </div>
                <button type="submit" name="deactivate" class="btn btn-deactivate">Deactivate</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(applicationId) {
            document.getElementById('deactivationModal').style.display = 'flex';
            document.getElementById('application_id').value = applicationId;
        }

        function closeModal() {
            document.getElementById('deactivationModal').style.display = 'none';
        }

        function activateUser(applicationId) {
            if (confirm("Are you sure you want to activate this user?\nThey will be able to borrow books again.")) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        alert("User activated successfully. They can now borrow books.");
                        location.reload();
                    }
                };
                xhr.send("reactivate=1&application_id=" + applicationId);
            }
        }
    </script>
</body>
</html>