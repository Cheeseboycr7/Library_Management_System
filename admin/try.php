<?php

session_start();
date_default_timezone_set("Africa/Johannesburg"); // Set the desired timezone
$currentDateTime = date("F j, Y, g:i a"); // Initial time value

include '../includes/db_connect.php';
include('../includes/db_connect1.php');


if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php'); // Redirect to login if not authenticated
    exit();
}

// Fetch the admin's name from the session
$adminName = $_SESSION['admin_name'];
// Fetch actual statistics from the database
$borrowedBooksQuery = "SELECT MONTH(Borrow_Date) as month, COUNT(*) as borrowedBooks FROM borrow WHERE Status = 'Borrowed' GROUP BY month";
$borrowedBooksResult = $conn->query($borrowedBooksQuery);

$monthlyData = [];
while ($row = $borrowedBooksResult->fetch_assoc()) {
    $monthlyData[$row['month']] = $row['borrowedBooks'];
}

// Prepare data for the chart
$chartData = [];
for ($i = 1; $i <= 12; $i++) {
    $chartData[] = isset($monthlyData[$i]) ? $monthlyData[$i] : 0; // Default to 0 if no data for the month
}

$overdueBooksQuery = "SELECT COUNT(*) as overdueBooks FROM borrow WHERE Due_Date < NOW() AND Status = 'Borrowed'";
$overdueBooksResult = $conn->query($overdueBooksQuery);
$overdueBooks = $overdueBooksResult->fetch_assoc()['overdueBooks'];

$visitors = 532; // This could be fetched from a visitors table if available
$newMembersQuery = "SELECT COUNT(*) as newMembers FROM application"; // Updated to use the application table
$newMembersResult = $conn->query($newMembersQuery);
$newMembers = $newMembersResult->fetch_assoc()['newMembers'];

$booksQuery = "SELECT COUNT(*) as totalBooks FROM book";
$booksResult = $conn->query($booksQuery);
$totalBooks = $booksResult->fetch_assoc()['totalBooks'];

// Fetch users and books for the lists
$usersQuery = "SELECT * FROM application"; // Assuming this table holds user data
$usersResult = $conn->query($usersQuery);

$booksListQuery = "SELECT * FROM book LIMIT 5"; // Fetching top 5 books
$booksListResult = $conn->query($booksListQuery);

$overdueBooksListQuery = "SELECT * FROM borrow WHERE Due_Date < NOW() AND Status = 'Borrowed'"; // Fetch overdue books
$overdueBooksListResult = $conn->query($overdueBooksListQuery);

$issuedBooksQuery = "SELECT * FROM borrow"; // Fetch issued books
$issuedBooksResult = $conn->query($issuedBooksQuery);


$borrowedBooksQuery = "SELECT COUNT(*) as borrowedBooks FROM borrow WHERE Status = 'Borrowed'";
$borrowedBooksResult = $conn->query($borrowedBooksQuery);
$borrowedBooks = $borrowedBooksResult->fetch_assoc()['borrowedBooks'];

$sql = "SELECT 
            SUM(CASE WHEN Fine_Status = 'Approved' THEN 1 ELSE 0 END) AS approved_count,
            SUM(CASE WHEN Fine_Status != 'Approved' THEN 1 ELSE 0 END) AS not_approved_count
        FROM returns";

$result = $conn->query($sql);
$data = $result->fetch_assoc();

$approvedCount = $data['approved_count'];
$notApprovedCount = $data['not_approved_count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - ECOT Library</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link rel="icon" href="../ECOT.jpg" type="image/jpeg">


  <style>
    /* General Styling */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", serif;
    }
    body {
      background-color: #f4f4f9;
      font-family: "Poppins", serif;
    }
    .wrapper {
      display: flex;
    }

    /* Sidebar Styling */
    .sidebar {
      width: 250px;
      background-color: #00264d;
      color: #fff;
      height: 100vh;
      position: fixed;
    }
    .sidebar .logo {
      text-align: center;
      padding: 20px;
      border-bottom: 1px solid #1a2732;
    }
    .sidebar .logo img {
      width: 60px;
    }
    .sidebar ul {
      padding: 20px 0;
      list-style: none;
    }
    .sidebar ul li {
      padding: 10px 20px;
    }
    .sidebar ul li a {
      color: #fff;
      text-decoration: none;
      display: flex;
      align-items: center;
      font-size: 16px;
    }
    .sidebar ul li a i {
      margin-right: 10px;
    }
    .sidebar ul li a:hover {
      background-color: #1a2732;
      border-radius: 4px;
    }
    .sidebar ul li .logout {
      color: #00264d;
    }

    /* Main Content Styling */
    .main-content {
      margin-left: 250px;
      width: calc(100% - 250px);
      padding: 20px;
    }
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #fff;
      padding: 10px 20px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      border-radius: 5px;
    }
    header .admin-info {
      display: flex;
      align-items: center;
    }
    header .admin-info span {
      margin-right: 20px;
    }
    header .admin-info .btn-logout {
      background-color:  #00264d;
      color: #fff;
      padding: 5px 15px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      text-decoration: none;
    }

    /* Statistics Cards */
    .statistics {
      display: flex;
      justify-content: space-between;
      margin: 20px 0;
    }
    .stat-card {
      width: 22%;
      padding: 20px;
      background-color: #fff;
      border-radius: 8px;
      text-align: center;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .stat-card h3 {
      font-size: 18px;
      color: #333;
      margin-bottom: 10px;
    }
    .stat-card p {
      font-size: 24px;
      color: #2a3f54;
    }

    /* Charts Section */
    .charts {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
    }
    .charts .chart-container {
      width: 48%;
      padding: 20px;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .charts .chart-container h3 {
      margin-bottom: 10px;
    }

    .logout{
      color: #f4f4f9;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <!-- Sidebar -->
    <nav class="sidebar">
      <div class="logo">
      <img  style="border-radius: 5px;width: 130px; margin-left:8px;" src="./ECOT.jpg" alt="User Profile">
        <h3>ECOT Admin Panel</h3>
      </div>
      <ul>
        <li><a href="#dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="Book_registration.php"><i class="fas fa-book"></i>Book Registration</a></li>
        <li><a href="update_book.php"><i class="fas fa-edit"></i>Update Book Details</a></li>
        <li><a href="Display_book.php"><i class="fas fa-book-open"></i>View Registered Books</a></li>
        <li><a href="Members.php"><i class="fas fa-user-cog"></i>Manage Users</a></li>
        <li><a href="payment.php"><i class="fas fa-credit-card"></i>Approve Payment</a></li>
        <li><a href="approved_reserseved.php"><i class="fas fa-thumbs-up"></i>Approve Reserved Book</a></li>
        <li><a href="Admin_Return_Book.php"><i class="fas fa-check-circle"></i>Approve Returned Book</a></li>
        <li><a href="Reading_Mode.php"><i class="fas fa-learn"></i>Reading Mode</a></li>
        <li><a href="Report.php">  <i class="bi bi-printer"></i>Print Report</li>
        <li><a href="admin_login.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
    <header>
  <h2>Admin Dashboard</h2>
  <div class="admin-info">
    <span>Welcome, <?php echo htmlspecialchars($adminName); ?>!</span> <!-- Display the admin's name -->
    <span id="realTimeClock"><?php echo $currentDateTime; ?></span>
    <a style="color: #fff;" href="admin_login.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i>Logout</a>
  </div>
</header>


      <!-- Statistics Section -->
      <section class="statistics">
        <div class="stat-card">
          <h3><?php echo $totalBooks; ?></h3>
          <h3><a style="text-decoration:none;color:black" href="Display_book.php"><i class="fas fa-book"></i>View Total Books</a></h3>
        </div>
        <div class="stat-card">
        <h3><?php echo $newMembers; ?></h3>
        <h3><a style="text-decoration:none;color:black" href="Members.php"><i class="fas fa-user"></i>View Total Members</a></h3>
        </div>
        <div class="stat-card">
           <h3><?php echo $borrowedBooks; ?></h3>
           <h3><a style="text-decoration:none;color:black" href="view_Borrowed_books.php"><i class="fas fa-book"></i>View Borrowed Books</a></h3>
      
        </div>
        <div class="stat-card">
          <h3><a style="text-decoration:none;color:black" href="View_Approved_Fines.php"><i class="fas fa-pen"></i>Fines Approved</a></h3>
          
        </div>
      </section>

      <!-- Charts Section -->
      <section class="charts">
        <div class="chart-container">
          <h3>Books Borrowed (Monthly)</h3>
          <canvas id="bar-chart"></canvas>
        </div>
        <div class="chart-container">
          <h3>Fine Approved</h3>
          <canvas id="pie-chart"></canvas>
        </div>
      </section>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"> </script>
  <script>
    // Bar Chart
    const barCtx = document.getElementById('bar-chart').getContext('2d');
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
          label: 'Books Borrowed',
          data: <?php echo json_encode($chartData); ?>, // Use dynamic data from PHP
          backgroundColor: '#2a3f54',
        }]
      },
    });

    // Pie Chart
      // PHP variables passed to JavaScript
      
      const approvedCount = <?php echo $approvedCount; ?>;
        const notApprovedCount = <?php echo $notApprovedCount; ?>;

        // Pie Chart
        const pieCtx = document.getElementById('pie-chart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: ['Approved', 'Not Approved'],
                datasets: [{
                    label: 'Fine Status',
                    data: [approvedCount, notApprovedCount],
                    backgroundColor: ['#2a3f54', '#e74c3c'],
                }]
            },
        });

        // Function to update the clock
function updateRealTimeClock() {
    const now = new Date();
    
    // Format options for date and time
    const options = {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    };
    
    // Format the date and time according to Johannesburg timezone
    const johannesburgTime = now.toLocaleString('en-US', {
        ...options,
        timeZone: 'Africa/Johannesburg'
    });
    
    // Update the clock display
    document.getElementById('realTimeClock').textContent = johannesburgTime;
}

// Update immediately and then every second
updateRealTimeClock();
setInterval(updateRealTimeClock, 1000);
  </script>
</body>
</html>