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

// Fetch available books for dropdown
$books_sql = "SELECT ISBN_NO, Title FROM book WHERE Quantity > 0 ORDER BY Title";
$books_result = $conn->query($books_sql);

// Handle form submission for reading session registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if all required form fields are set
    if (isset($_POST['username'], $_POST['full_name'], $_POST['email'], $_POST['phone_number'], $_POST['gender'], $_POST['book_isbn'])) {
        $username = $conn->real_escape_string($_POST['username']);
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone_number = $conn->real_escape_string($_POST['phone_number']);
        $gender = $conn->real_escape_string($_POST['gender']);
        $book_isbn = $conn->real_escape_string($_POST['book_isbn']);

        // Get book title
        $book_title = '';
        $book_stmt = $conn->prepare("SELECT Title FROM book WHERE ISBN_NO = ?");
        $book_stmt->bind_param("s", $book_isbn);
        $book_stmt->execute();
        $book_result = $book_stmt->get_result();
        if ($book_result->num_rows > 0) {
            $book_row = $book_result->fetch_assoc();
            $book_title = $book_row['Title'];
        }

        // Check if user already exists
        $check_sql = "SELECT User_ID FROM users WHERE Username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // User exists, get their ID
            $user_row = $check_result->fetch_assoc();
            $user_id = $user_row['User_ID'];
        } else {
            // Insert new user
            $insert_sql = "INSERT INTO users (Username, Full_Name, Email, Phone_Number, Gender) 
                           VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("sssss", $username, $full_name, $email, $phone_number, $gender);
            $stmt->execute();
            $user_id = $stmt->insert_id;
        }

        // Set start time as current time and end time as 1 hour later
        $start_time = date('Y-m-d H:i:s');
        $end_time = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Check if user has active session
        $active_session_sql = "SELECT Session_ID FROM reading_sessions 
                              WHERE User_ID = ? AND End_Time > NOW()";
        $active_stmt = $conn->prepare($active_session_sql);
        $active_stmt->bind_param("i", $user_id);
        $active_stmt->execute();
        $active_result = $active_stmt->get_result();

        if ($active_result->num_rows > 0) {
            echo "<script>alert('User already has an active reading session.');</script>";
        } else {
            // Insert new reading session with book information
            $insert_session_sql = "INSERT INTO reading_sessions 
                                  (User_ID, User_Name, Start_Time, End_Time, Status, Book_ISBN, Book_Title) 
                                  VALUES (?, ?, ?, ?, 'active', ?, ?)";
            $stmt_session = $conn->prepare($insert_session_sql);
            $stmt_session->bind_param("isssss", $user_id, $full_name, $start_time, $end_time, $book_isbn, $book_title);
            
            if ($stmt_session->execute()) {
                echo "<script>alert('Reading session registered successfully.');</script>";
            } else {
                echo "<script>alert('Error registering reading session.');</script>";
            }
        }
    } else {
        echo "<script>alert('Please fill in all the required fields.');</script>";
    }
}

// Handle session renewal (admin action)
if (isset($_GET['renew'])) {
    $session_id = intval($_GET['renew']);
    
    // Get current end time
    $get_sql = "SELECT End_Time FROM reading_sessions WHERE Session_ID = ?";
    $get_stmt = $conn->prepare($get_sql);
    $get_stmt->bind_param("i", $session_id);
    $get_stmt->execute();
    $get_result = $get_stmt->get_result();
    
    if ($get_result->num_rows > 0) {
        $session = $get_result->fetch_assoc();
        $new_end_time = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($session['End_Time'])));
        
        // Update session
        $update_sql = "UPDATE reading_sessions SET End_Time = ? WHERE Session_ID = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_end_time, $session_id);
        
        if ($update_stmt->execute()) {
            echo "<script>alert('Session renewed successfully for 1 more hour.');</script>";
        } else {
            echo "<script>alert('Error renewing session.');</script>";
        }
    }
}

// Fetch active reading sessions for display
$active_sessions_sql = "SELECT rs.Session_ID, u.Username, u.Full_Name, rs.Start_Time, rs.End_Time, 
                       rs.Book_ISBN, rs.Book_Title
                       FROM reading_sessions rs
                       JOIN users u ON rs.User_ID = u.User_ID
                       WHERE rs.End_Time > NOW() AND rs.Status = 'active'
                       ORDER BY rs.End_Time ASC";
$active_sessions = $conn->query($active_sessions_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library Reading Session Management</title>
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
        .form-container, .sessions-container {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #00264d;
        }
        input, select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #00264d;
            box-shadow: 0 0 0 2px rgba(0, 38, 77, 0.1);
        }
        button, .btn {
            background-color: #00264d;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, .btn:hover {
            background-color: #001a33;
            transform: translateY(-2px);
        }
        .btn-renew {
            background-color: #28a745;
        }
        .btn-renew:hover {
            background-color: #218838;
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
        .time-remaining {
            font-weight: bold;
            color: #28a745;
        }
        .expired {
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Library Reading Session Management</h2>
        
        <div class="nav-links">
            <a href="try.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="view_readers.php" class="btn"><i class="fas fa-users"></i> View All Readers</a>
        </div>

        <div class="form-container">
            <h3 class="section-title">Register New Reading Session</h3>
            <form method="POST">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" required>

                <label for="full_name">Full Name:</label>
                <input type="text" name="full_name" id="full_name" required>

                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>

                <label for="phone_number">Phone Number:</label>
                <input type="text" name="phone_number" id="phone_number">

                <label for="gender">Gender:</label>
                <select name="gender" id="gender" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>

                <label for="book_isbn">Book:</label>
                <select name="book_isbn" id="book_isbn" required>
                    <option value="">Select a book</option>
                    <?php while ($book = $books_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($book['ISBN_NO']) ?>">
                            <?= htmlspecialchars($book['Title']) ?> (ISBN: <?= htmlspecialchars($book['ISBN_NO']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>

                <button type="submit"><i class="fas fa-user-plus"></i> Register Session</button>
            </form>
        </div>

        <div class="sessions-container">
            <h3 class="section-title">Active Reading Sessions</h3>
            <?php if ($active_sessions->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Book Title</th>
                            <th>ISBN</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Time Remaining</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($session = $active_sessions->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($session['Session_ID']) ?></td>
                                <td><?= htmlspecialchars($session['Username']) ?></td>
                                <td><?= htmlspecialchars($session['Full_Name']) ?></td>
                                <td><?= htmlspecialchars($session['Book_Title']) ?></td>
                                <td><?= htmlspecialchars($session['Book_ISBN']) ?></td>
                                <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($session['Start_Time']))) ?></td>
                                <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($session['End_Time']))) ?></td>
                                <td class="time-remaining" data-end="<?= htmlspecialchars($session['End_Time']) ?>">
                                    Calculating...
                                </td>
                                <td>
                                    <a href="?renew=<?= $session['Session_ID'] ?>" class="btn btn-renew">
                                        <i class="fas fa-sync-alt"></i> Renew
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No active reading sessions at the moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Calculate and display time remaining for each session
        function updateTimeRemaining() {
            document.querySelectorAll('.time-remaining').forEach(element => {
                const endTime = new Date(element.dataset.end);
                const now = new Date();
                const diff = endTime - now;
                
                if (diff <= 0) {
                    element.textContent = "Expired";
                    element.classList.add('expired');
                    element.classList.remove('time-remaining');
                } else {
                    const hours = Math.floor(diff / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    element.textContent = `${hours}h ${minutes}m remaining`;
                }
            });
        }

        // Update time remaining immediately and then every minute
        updateTimeRemaining();
        setInterval(updateTimeRemaining, 60000);

        // Confirm session renewal
        document.querySelectorAll('.btn-renew').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Renew this reading session for 1 more hour?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>