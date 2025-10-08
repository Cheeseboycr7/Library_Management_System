<!DOCTYPE html>
 <html lang="en">
 <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
     <!-- Link to CSS -->
     <link rel="stylesheet" href="../css/style.css">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Link to Swiper CSS for Slider -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">

    <style>

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: #1e3a8a; /* Royal Blue */
            color: #fff;
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: 100%;
            margin-top: 1px;
        }

        .sidebar .logo {
            text-align: center;
            margin-bottom: 20px;
            
        }

        .sidebar img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
        }

        .sidebar h2 {
            text-align: center;
            margin-top: 10px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: #fff;
            text-decoration: none;
            margin: 10px 0;
            font-size: 16px;
        }

        .sidebar a i {
            margin-right: 10px;
        }

        .sidebar a:hover {
            background-color: #3b82f6;
            border-radius: 5px;
            padding: 8px;
        }
    </style>
 </head>
 <body>
  <!-- Header Section -->
    <header>
        <div class="logo-container">
           
            <h1>Welcome to Our College Library</h1>
        </div>
        <nav>
            <ul>
                <li><a href="#about"     class="fas fa-house">About</a></li>
                <li><a href="#services"  class="fas fa-assist">Services</a></li>
                <li><a href="#contact"   class="fas fa-phone">Contact</a></li>
            </ul>
        </nav>
    </header>
     <!-- Sidebar -->
     <div class="sidebar">
        <div class="logo">
            <img style="width: 50%; height:50%;border-radius: 5px;" src="../include/ECOT.jpg" alt="College Logo">
            <h2>Admin Panel</h2>
        </div>
        <a href="#"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="../admin/add_book.php"><i class="fas fa-plus"></i> Add Book</a>
        <a href="../admin/authors.php"><i class="fa fa-person"></i> Add Author</a>
        <a href="../admin/publishers.php"><i class="fa fa-house"></i> Add Publisher</a>
        <a href="../admin/shelves.php"><i class="fa fa-box"></i> Add Book_Shelf_NO</a>
        <a href="../admin/delete_book.php"><i class="fa fa-trash"></i>Delete Book</a>
        <a href="../admin/Display_book.php"><i class="fa fa-book"></i>Display Book</a>
    </div>
 </body>
 </html>
