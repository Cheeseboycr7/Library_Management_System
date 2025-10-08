<?php
session_start();
include '../includes/db_connect.php';

// Check if a search query is submitted
$search = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($conn->real_escape_string($_GET['search']));
}

// Get the selected status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Base query with improved search functionality
$query = "
    SELECT 
        b.Borrow_ID,
        a.Application_ID,
        a.Name,
        a.Surname,
        a.Faculty,
        a.Department,
        b.ISBN_NO,
        b.Borrow_Date,
        b.Due_Date,
        b.Status,
        CASE 
            WHEN b.Status = 'Borrowed' AND b.Due_Date < CURDATE() THEN 'Overdue'
            ELSE b.Status
        END AS Display_Status
    FROM borrow b
    INNER JOIN application a ON b.Application_ID = a.Application_ID
";

// Add conditions based on search and filters
$conditions = [];
if (!empty($search)) {
    $conditions[] = "(a.Name LIKE '%$search%' 
                    OR a.Surname LIKE '%$search%' 
                    OR CONCAT(a.Name, ' ', a.Surname) LIKE '%$search%'
                    OR a.Application_ID LIKE '%$search%')";
}

if ($status_filter !== 'all') {
    if ($status_filter === 'Overdue') {
        $conditions[] = "b.Status = 'Borrowed' AND b.Due_Date < CURDATE()";
    } else {
        $conditions[] = "b.Status = '$status_filter'";
    }
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Default sorting
$query .= " ORDER BY ";

// Add sorting based on status filter
switch ($status_filter) {
    case 'Overdue':
        $query .= " b.Due_Date ASC, b.Borrow_Date DESC";
        break;
    case 'Borrowed':
        $query .= " b.Due_Date ASC, b.Borrow_Date DESC";
        break;
    default:
        $query .= " b.Borrow_Date DESC";
        break;
}

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowed Books | Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <style>

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Poppins", serif;
        }
        /* Improved search container */
        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .search-container input[type="text"] {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            min-width: 250px;
        }
        
        .search-container button {
            padding: 10px 20px;
            background-color: #00264d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .search-container button:hover {
            background-color: #004080;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }

        .main-content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1a365d;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title i {
            color: #2b6cb0;
        }

        .filter-section {
            background: white;
            padding: 1.25rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: flex-end;
        }

        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }

        .search-box {
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.625rem 1rem 0.625rem 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 0.9375rem;
            transition: all 0.2s;
            background-color: white;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
            background-color: white;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
        }

        .filter-controls {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        @media (max-width: 640px) {
            .filter-controls {
                flex-direction: column;
                width: 100%;
            }
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 0.875rem;
            color: #4a5568;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .filter-select {
            padding: 0.625rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 0.9375rem;
            background-color: #f8fafc;
            min-width: 180px;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
        }

        .search-btn {
            padding: 0.625rem 1.5rem;
            background-color: #2b6cb0;
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.9375rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            height: 42px;
        }

        .search-btn:hover {
            background-color: #2c5282;
        }

        .table-container {
            background: white;
            padding: 1.25rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.9375rem;
        }

        th {
            background-color: #2b6cb0;
            color: white;
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #edf2f7;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        tr:hover {
            background-color: #ebf8ff;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .status-badge.borrowed {
            background-color: #bee3f8;
            color: #2b6cb0;
        }

        .status-badge.overdue {
            background-color: #fed7d7;
            color: #c53030;
        }

        .status-badge.returned {
            background-color: #c6f6d5;
            color: #276749;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            padding: 0.5rem 1rem;
            background-color: #2b6cb0;
            color: white;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .back-link:hover {
            background-color: #2c5282;
            color: white;
        }

        .no-results {
            text-align: center;
            padding: 2rem;
            color: #718096;
        }

          /* Improved search container */
          .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .search-container input[type="text"] {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            min-width: 250px;
        }
        
        .search-container button {
            padding: 10px 20px;
            background-color: #00264d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .search-container button:hover {
            background-color: #004080;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <!-- Page Title -->
        <h1 class="page-title">Borrowed Books</h1>

        <!-- Improved Search Bar -->
        <div class="search-container">
            <form method="GET" action="" style="display: flex; width: 100%; gap: 10px;">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search by name, surname, full name, or Application ID" 
                    value="<?php echo htmlspecialchars($search); ?>" 
                    aria-label="Search borrowed books"
                />
                <button type="submit">Search</button>
                
                <!-- Status filter dropdown -->
                <select name="status" onchange="this.form.submit()" style="padding: 10px; border-radius: 4px;">
                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="Borrowed" <?= $status_filter === 'Borrowed' ? 'selected' : '' ?>>Borrowed</option>
                    <option value="Overdue" <?= $status_filter === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                    <option value="Returned" <?= $status_filter === 'Returned' ? 'selected' : '' ?>>Returned</option>
                </select>
            </form>
        </div>

      <!-- Results Table -->
      <div class="table-container">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th style="background-color: #00264d;">Borrow ID</th>
                            <th style="background-color: #00264d;">Member Name</th>
                            <th style="background-color: #00264d;">Faculty</th>
                            <th style="background-color: #00264d;">Department</th>
                            <th style="background-color: #00264d;">ISBN</th>
                            <th style="background-color: #00264d;">Borrow Date</th>
                            <th style="background-color: #00264d;">Due Date</th>
                            <th style="background-color: #00264d;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): 
                            $isOverdue = strtotime($row['Due_Date']) < time() && $row['Status'] !== 'Returned';
                            $statusClass = '';
                            $statusText = $row['Status'];
                            
                            if ($isOverdue) {
                                $statusClass = 'overdue';
                                $statusText = 'Overdue';
                            } elseif ($row['Status'] === 'Borrowed') {
                                $statusClass = 'borrowed';
                            } elseif ($row['Status'] === 'Returned') {
                                $statusClass = 'returned';
                            }
                        ?>
                            <tr class="<?php echo $isOverdue ? 'bg-red-50' : ''; ?>">
                                <td><?php echo $row['Borrow_ID']; ?></td>
                                <td><?php echo htmlspecialchars($row['Name'] . ' ' . $row['Surname']); ?></td>
                                <td><?php echo htmlspecialchars($row['Faculty']); ?></td>
                                <td><?php echo htmlspecialchars($row['Department']); ?></td>
                                <td><?php echo $row['ISBN_NO']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['Borrow_Date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['Due_Date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-book-open fa-2x mb-3" style="color: #cbd5e0;"></i>
                    <h3 class="text-lg font-medium">No borrowed books found</h3>
                    <p class="text-sm">Try adjusting your search or filter criteria</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

     <!-- Back Link -->
     <div class="text-center">
            <a style="background-color: #00264d;" href="try.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
</body>
</html>