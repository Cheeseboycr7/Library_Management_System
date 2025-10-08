<?php

function getBorrowingHistory($application_id, $filters = []) {
    global $conn;
    
    // Base query
    $sql = "SELECT bh.*, b.cover_image 
            FROM borrowing_history bh
            JOIN book b ON bh.ISBN_NO = b.ISBN_NO
            WHERE bh.Application_ID = ?";
    
    // Initialize parameters
    $params = [$application_id];
    $types = "i";
    
    // Apply filters
    if (!empty($filters['status'])) {
        $sql .= " AND bh.Status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if (!empty($filters['from_date'])) {
        $sql .= " AND bh.Borrow_Date >= ?";
        $params[] = $filters['from_date'];
        $types .= "s";
    }
    
    if (!empty($filters['to_date'])) {
        $sql .= " AND bh.Borrow_Date <= ?";
        $params[] = $filters['to_date'];
        $types .= "s";
    }
    
    if (!empty($filters['title'])) {
        $sql .= " AND bh.Title LIKE ?";
        $params[] = '%' . $filters['title'] . '%';
        $types .= "s";
    }
    
    // Order by most recent first
    $sql .= " ORDER BY bh.Borrow_Date DESC";
    
    // Prepare and execute
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}