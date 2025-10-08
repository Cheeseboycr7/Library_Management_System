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

$whereClause = "";
$report_date = date("Y-m-d"); 
if (isset($_POST['generate'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $report_type = $_POST['report_type'];

    if (!empty($start_date) && !empty($end_date)) {
        $whereClause = "WHERE DATE(b.Borrow_Date) BETWEEN '$start_date' AND '$end_date'";
    }

    if ($report_type == "borrowed_books") {
        $query = "SELECT b.Borrow_ID, a.Application_ID, a.Name, a.Surname, bk.Title AS Book_Title, b.Borrow_Date, b.Due_Date 
                  FROM borrow b 
                  JOIN book bk ON b.ISBN_NO = bk.ISBN_NO
                  JOIN application a ON b.Application_ID = a.Application_ID 
                  $whereClause";
    } elseif ($report_type == "returned_books") {
        $query = "SELECT r.Return_ID, a.Application_ID, a.Name, a.Surname, bk.Title AS Book_Title, r.Return_Date, r.Fine_Status, r.Fine_Amount 
                  FROM returns r 
                  JOIN borrow b ON r.Borrow_ID = b.Borrow_ID
                  JOIN book bk ON b.ISBN_NO = bk.ISBN_NO
                  JOIN application a ON b.Application_ID = a.Application_ID
                  $whereClause";
    } elseif ($report_type == "fines_collected") {
        $query = "SELECT p.Payment_ID, a.Application_ID, a.Name, a.Surname, r.Fine_Amount, p.Date AS Payment_Date, p.Approval_Time 
                  FROM payment p 
                  JOIN returns r ON p.Return_ID = r.Return_ID
                  JOIN borrow b ON r.Borrow_ID = b.Borrow_ID
                  JOIN application a ON b.Application_ID = a.Application_ID
                  $whereClause";
    } elseif ($report_type == "overdue_books") {
        $query = "SELECT b.Borrow_ID, a.Application_ID, a.Name, a.Surname, bk.Title AS Book_Title, b.Due_Date, 
                         DATEDIFF(CURDATE(), b.Due_Date) AS Days_Overdue
                  FROM borrow b
                  JOIN book bk ON b.ISBN_NO = bk.ISBN_NO
                  JOIN application a ON b.Application_ID = a.Application_ID
                  WHERE b.Due_Date < CURDATE() AND NOT EXISTS 
                        (SELECT 1 FROM returns r WHERE r.Borrow_ID = b.Borrow_ID)";
    }
    
    $result = mysqli_query($conn, $query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Reports</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href=https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
        .btn-primary:hover {
            background-color: royalblue;
        }

        a {
            text-decoration: none;
        }
        
        /* Print-specific styles */
        @media print {
            .no-print, #reportTable_filter, #reportTable_length, #reportTable_info, #reportTable_paginate {
                display: none !important;
            }
            
            body {
                padding: 20px;
                font-size: 12px;
            }
            
            .print-logo {
                display: block !important;
                text-align: center;
                margin-bottom: 15px;
            }
            
            .print-logo img {
                width: 80px;
                height: auto;
            }
            
            .table {
                width: 100%;
                margin-bottom: 1rem;
                color: #212529;
                border-collapse: collapse;
            }
            
            .table th, .table td {
                padding: 0.3rem;
                vertical-align: top;
                border: 1px solid #dee2e6;
            }
            
            .table thead th {
                vertical-align: bottom;
                border-bottom: 2px solid #dee2e6;
                background-color: #f8f9fa !important;
                color: #000 !important;
            }
            
            h2, h4 {
                page-break-after: avoid;
            }
            
            .table-responsive {
                overflow-x: visible;
            }
            
            .container-fluid {
                width: 100%;
                padding-right: 15px;
                padding-left: 15px;
                margin-right: auto;
                margin-left: auto;
            }
            
            .signature-area {
                margin-top: 50px;
            }
        }
        
        /* Screen-only styles */
        @media screen {
            .print-logo {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <!-- Logo for printing only -->
    <div class="print-logo">
        <img src="ECOT.jpg" alt="ECOT Library Logo">
    </div>
    
    <!-- Logo for screen only -->
    <div style="margin-left:580px" class="div no-print">
        <img style="width: 95px;" src="ECOT.jpg" alt="">
    </div>
    
    <h2 class="text-center mb-3">ECOT Library Management</h2>
   <h2 class="text-center"><?php //echo ucwords(str_replace('_', ' ', $report_type)) ?> Report</h2>
    <p class="text-center">Report Date: <?php echo $report_date; ?></p>
    <?php if (isset($_POST['generate'])): ?>
        <p class="text-center">Period: <?php echo $start_date . ' to ' . $end_date; ?></p>
    <?php endif; ?>
    
    <form method="POST" class="row g-3 mb-4 no-print">
        <div class="col-md-3">
            <label class="form-label">Start Date:</label>
            <input type="date" name="start_date" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">End Date:</label>
            <input type="date" name="end_date" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Report Type:</label>
            <select name="report_type" class="form-select" required>
                <option value="borrowed_books">Borrowed Books</option>
                <option value="returned_books">Returned Books</option>
                <option value="fines_collected">Fines Collected</option>
                <option value="overdue_books">Overdue Books</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button style="background-color: #00264d;" type="submit" name="generate" class="btn btn-primary w-100">Generate Report</button>
        </div>
    </form>

    <?php if (isset($_POST['generate'])): ?>
    <div class="table-responsive">
        <table id="reportTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <?php if ($report_type == "borrowed_books" || $report_type == "overdue_books"): ?>
                        <th>Borrow ID</th><th>Patron ID</th><th>Name</th><th>Book Title</th><th>Borrowed Date</th><th>Due Date</th>
                        <?php if ($report_type == "overdue_books"): ?>
                            <th>Days Overdue</th>
                        <?php endif; ?>
                    <?php elseif ($report_type == "returned_books"): ?>
                        <th>Return ID</th><th>Patron ID</th><th>Name</th><th>Book Title</th><th>Return Date</th><th>Fine Amount</th>
                    <?php elseif ($report_type == "fines_collected"): ?>
                        <th>Payment ID</th><th>Patron ID</th><th>Name</th><th>Amount</th><th>Payment Date</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <?php 
                        if ($report_type == "borrowed_books"): ?>
                            <td><?php echo $row['Borrow_ID']; ?></td>
                            <td><?php echo $row['Application_ID']; ?></td>
                            <td><?php echo $row['Name'] . ' ' . $row['Surname']; ?></td>
                            <td><?php echo $row['Book_Title']; ?></td>
                            <td><?php echo $row['Borrow_Date']; ?></td>
                            <td><?php echo $row['Due_Date']; ?></td>
                        <?php elseif ($report_type == "returned_books"): ?>
                            <td><?php echo $row['Return_ID']; ?></td>
                            <td><?php echo $row['Application_ID']; ?></td>
                            <td><?php echo $row['Name'] . ' ' . $row['Surname']; ?></td>
                            <td><?php echo $row['Book_Title']; ?></td>
                            <td><?php echo $row['Return_Date']; ?></td>
                            <td><?php echo $row['Fine_Amount']; ?></td>
                        <?php elseif ($report_type == "fines_collected"): ?>
                            <td><?php echo $row['Payment_ID']; ?></td>
                            <td><?php echo $row['Application_ID']; ?></td>
                            <td><?php echo $row['Name'] . ' ' . $row['Surname']; ?></td>
                            <td><?php echo $row['Fine_Amount']; ?></td>
                            <td><?php echo $row['Payment_Date']; ?></td>
                        <?php elseif ($report_type == "overdue_books"): ?>
                            <td><?php echo $row['Borrow_ID']; ?></td>
                            <td><?php echo $row['Application_ID']; ?></td>
                            <td><?php echo $row['Name'] . ' ' . $row['Surname']; ?></td>
                            <td><?php echo $row['Book_Title']; ?></td>
                            <td><?php echo $row['Due_Date']; ?></td>
                            <td><?php echo $row['Days_Overdue']; ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="d-flex justify-content-end mt-3 no-print">
            <button class="btn btn-success" onclick="printReport()">Print Report</button>
        </div>

        <script>
            function printReport() {
                window.print();
            }
        </script>
    </div>
    <?php endif; ?>

    <div class="mt-4 signature-area">
        <h4>Admin Signature</h4>
        <p>______________________________________</p>
        <p>Signature & Date</p>
    </div>
</div>
<div style="text-align: center; margin-top: 20px;" class="no-print">
    <i class="fas fa-dashboard"></i>
    <a href="try.php">Back to Dashboard</a>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function () {
        $('#reportTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'btn btn-success',
                    autoPrint: true,
                    customize: function (win) {
                        $(win.document.body).find('h2').css('text-align', 'center');
                        $(win.document.body).find('table').addClass('compact').css('font-size', 'inherit');
                    }
                }
            ]
        });
    });
</script>
</body>
</html>