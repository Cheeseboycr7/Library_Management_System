<?php
// student_navbar.php
session_start();
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="student_dashboard.php">
            <i class="bi bi-book me-2"></i>ECOT Library
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="student_dashboard.php">
                        <i class="bi bi-house-door me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="browse_books.php">
                        <i class="bi bi-search me-1"></i> Browse Books
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="student_history.php">
                        <i class="bi bi-clock-history me-1"></i> My History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="current_loans.php">
                        <i class="bi bi-bookmark-check me-1"></i> Current Loans
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['student_name'] ?? 'Student') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="student_profile.php">
                            <i class="bi bi-person me-2"></i> My Profile
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>