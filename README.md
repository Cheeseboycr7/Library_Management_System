ECOT Library Management System
Full-stack Library Management System (PHP + MySQL backend, web admin/student UI, optional Android app integration)
Features

- Admin dashboard (manage books, users, fines, and reports)
- Student application and login
- Borrow and return books with automatic fine tracking
- Book catalog with ISBN, Accession Number, and Author details
- Reports for low stock, borrowed books, and commission
- REST APIs for Android app integration

Tech Stack

- Backend: PHP (PDO)
- Database: MySQL
- Frontend: HTML, TailwindCSS, Bootstrap
- Optional: Android (React Native or Java)

Installation Steps

1. Install XAMPP or Laragon.
2. Copy the project folder into `htdocs/` or `www/`.
3. Import the `ecot_library2.sql` file into phpMyAdmin.
4. Update database credentials in `includes/db_connect.php`.
5. Start Apache and MySQL from XAMPP.
6. Open `http://localhost/ecot_library_system` in your browser.

Database Structure

Main Tables:
- book (ISBN_NO, Title, AccNo, Publisher_ID, Quantity, etc.)
- borrow (Borrow_ID, Application_ID, ISBN_NO, Borrow_Date, Due_Date, Status)
- returns (Return_ID, Borrow_ID, Fine_Status)
- application (Application_ID, Name, Username, Password, etc.)
- author, publisher, shelf, book_author, book_shelf

Troubleshooting

1. Check that Apache and MySQL are running.
2. Ensure `uploads/` folder is writable.
3. Use your local IP instead of 'localhost' when connecting from a mobile device.
4. Verify Chart.js and scripts are properly linked.

License
This project is provided under the MIT License. You may modify and distribute it freely.
