<?php
session_start();

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ecot_library2';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: Student_login.php");
    exit();
}

$userName = $_SESSION['username'];

// Get user details from the application table using the username
$app_query = "SELECT Application_ID, Cellphone_No, Parent_Cellphone_No, Course FROM application WHERE Username = ?";
$stmt = $conn->prepare($app_query);
$stmt->bind_param("s", $userName);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$app_id = $user_data['Application_ID'];
$cellphone = $user_data['Cellphone_No'];
$parent_cellphone = $user_data['Parent_Cellphone_No'];
$course = $user_data['Course'];

// Handle form submission for profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the posted values
    $cellphone_new = trim($_POST['cellphone']);
    $parent_cell_new = trim($_POST['parent_cell']);
    $course_new = trim($_POST['course']);
    
    // (Optional) Add validation here if needed

    $update_sql = "UPDATE application SET Cellphone_No = ?, Parent_Cellphone_No = ?, Course = ? WHERE Application_ID = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sssi", $cellphone_new, $parent_cell_new, $course_new, $app_id);
    
    if ($stmt->execute()) {
        $success = "Profile updated successfully.";
        // Update local variables so the new values show immediately
        $cellphone = $cellphone_new;
        $parent_cellphone = $parent_cell_new;
        $course = $course_new;
    } else {
        $error = "Failed to update profile. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - ECOT Library</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <style>
    body {
      background-color: #ffffff; /* White background */
      font-family: 'Poppins', sans-serif;
    }
    .container {
      max-width: 600px;
      margin-top: 50px;
    }
    .card {
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    .card-header {
      background-color: #00264d;
      color: white;
      text-align: center;
      font-size: 1.5rem;
    }
    .btn-primary {
      background-color: #00264d;
      border-color: #00264d;
    }
    .btn-primary:hover {
      background-color: #001a33;
      border-color: #001a33;
    }
    a{
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="container">
     <div class="card">
       <div class="card-header">
         My Profile
       </div>
       <div class="card-body">
         <?php if(isset($success)) { echo "<div class='alert alert-success'>$success</div>"; } ?>
         <?php if(isset($error)) { echo "<div class='alert alert-danger'>$error</div>"; } ?>
         <form method="POST" action="">
            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text" class="form-control" value="<?php echo htmlspecialchars($userName); ?>" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Cellphone Number</label>
              <input type="text" name="cellphone" class="form-control" value="<?php echo htmlspecialchars($cellphone); ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Parent Cellphone Number</label>
              <input type="text" name="parent_cell" class="form-control" value="<?php echo htmlspecialchars($parent_cellphone); ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Course</label>
              <input type="text" name="course" class="form-control" value="<?php echo htmlspecialchars($course); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Update Profile</button>
         </form>
       </div>
     </div>
  </div>
  <div style="text-align: center; margin-top: 20px;">
                <i  class="fas fa-dashboard"></i>
                <a href="dash.php" >Back to Dashboard</a>
            </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
