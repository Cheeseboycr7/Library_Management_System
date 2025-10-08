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

// Ensure admin is logged in
if (!isset($_SESSION['admin_name'])) {
    header("Location: login.php");
    exit();
}

// Handle ending a session
if (isset($_GET['end_session'])) {
    $session_id = intval($_GET['end_session']);
    
    $update_sql = "UPDATE reading_sessions SET Status = 'ended', End_Time = NOW() 
                   WHERE Session_ID = ? AND Status = 'active'";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $session_id);
    
    if ($stmt->execute()) {
        $message = "Session ended successfully.";
    } else {
        $error = "Error ending session.";
    }
}

// Fetch all readers
$readers_sql = "SELECT u.User_ID, u.Username, u.Full_Name, u.Email, u.Phone_Number, u.Gender,
                COUNT(rs.Session_ID) as total_sessions
                FROM users u
                LEFT JOIN reading_sessions rs ON u.User_ID = rs.User_ID
                GROUP BY u.User_ID
                ORDER BY u.Full_Name";
$readers = $conn->query($readers_sql);

// Fetch all reading sessions for filter
$all_sessions_sql = "SELECT rs.Session_ID, u.Full_Name, rs.Start_Time, rs.End_Time, rs.Status
                    FROM reading_sessions rs
                    JOIN users u ON rs.User_ID = u.User_ID
                    ORDER BY rs.Start_Time DESC";
$all_sessions = $conn->query($all_sessions_sql);

// Filter by user if requested
$filtered_sessions = [];
if (isset($_GET['filter_user'])) {
    $user_id = intval($_GET['filter_user']);
    $filter_sql = "SELECT rs.Session_ID, u.Full_Name, rs.Start_Time, rs.End_Time, rs.Status
                  FROM reading_sessions rs
                  JOIN users u ON rs.User_ID = u.User_ID
                  WHERE u.User_ID = ?
                  ORDER BY rs.Start_Time DESC";
    $filter_stmt = $conn->prepare($filter_sql);
    $filter_stmt->bind_param("i", $user_id);
    $filter_stmt->execute();
    $filtered_sessions = $filter_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Readers - Library System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            color: #333;
        }
        h2 {
            text-align: center;
            color: #00264d;
            margin-bottom: 30px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #00264d;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .btn {
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-block;
            margin: 2px;
        }
        .btn-primary {
            background-color: #00264d;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        .status-ended {
            color: #6c757d;
        }
        .status-expired {
            color: #dc3545;
        }
        .nav-links {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .section-title {
            color: #00264d;
            margin-bottom: 15px;
            font-size: 20px;
            border-bottom: 2px solid #00264d;
            padding-bottom: 5px;
        }
        .filter-form {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .filter-form select, .filter-form button {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .filter-form button {
            background-color: #00264d;
            color: white;
            border: none;
            cursor: pointer;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Library Readers Management</h2>
        
        <div class="nav-links">
            <a href="Reading_Mode.php" class="btn btn-primary"><i class="fas fa-book-reader"></i> Reading Sessions</a>
            <a href="try.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if (isset($message)): ?>
            <div class="message success"><?= $message ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="message error"><?= $error ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 class="section-title">All Registered Readers</h3>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Total Sessions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($reader = $readers->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($reader['User_ID']) ?></td>
                            <td><?= htmlspecialchars($reader['Username']) ?></td>
                            <td><?= htmlspecialchars($reader['Full_Name']) ?></td>
                            <td><?= htmlspecialchars($reader['Email']) ?></td>
                            <td><?= htmlspecialchars($reader['Phone_Number']) ?></td>
                            <td><?= htmlspecialchars($reader['Gender']) ?></td>
                            <td><?= htmlspecialchars($reader['total_sessions']) ?></td>
                            <td>
                                <a href="?filter_user=<?= $reader['User_ID'] ?>" class="btn btn-primary">
                                    <i class="fas fa-history"></i> View Sessions
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3 class="section-title">Reading Sessions</h3>
            
            <?php if (isset($_GET['filter_user'])): ?>
                <div class="filter-form">
                    <span>Showing sessions for: 
                        <strong><?= htmlspecialchars($filtered_sessions->fetch_assoc()['Full_Name']) ?></strong>
                    </span>
                    <a href="view_readers.php" class="btn btn-danger">Clear Filter</a>
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Session ID</th>
                        <th>Reader Name</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sessions = isset($_GET['filter_user']) ? $filtered_sessions : $all_sessions;
                    if ($sessions->num_rows > 0): 
                        while ($session = $sessions->fetch_assoc()): 
                            $status_class = '';
                            if ($session['Status'] == 'active') {
                                $status_class = strtotime($session['End_Time']) > time() ? 'status-active' : 'status-expired';
                            } else {
                                $status_class = 'status-ended';
                            }
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($session['Session_ID']) ?></td>
                            <td><?= htmlspecialchars($session['Full_Name']) ?></td>
                            <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($session['Start_Time']))) ?></td>
                            <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($session['End_Time']))) ?></td>
                            <td class="<?= $status_class ?>">
                                <?= htmlspecialchars($session['Status']) ?>
                                <?php if ($session['Status'] == 'active' && strtotime($session['End_Time']) < time()): ?>
                                    (Expired)
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($session['Status'] == 'active'): ?>
                                    <a href="?end_session=<?= $session['Session_ID'] ?>" class="btn btn-danger">
                                        <i class="fas fa-stop"></i> End Session
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No reading sessions found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Confirm ending a session
        document.querySelectorAll('[href*="end_session"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to end this reading session?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>