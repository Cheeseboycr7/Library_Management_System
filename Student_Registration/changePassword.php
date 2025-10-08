<?php 
    // session_start();
  //  if (!isset($_SESSION["student"])) {
       
   // }
   include '../Book-Registration/includes/db_connect1.php';
   include('../Book-Registration/includes/db_connect.php');
   include('../Student_Registration/include/sidebar.php');
 ?>

 <!DOCTYPE html>
 <html lang="en">
 <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../Book-Registration/css/style.css">
    <link rel="stylesheet" href="../Book-Registration/css/pro1.css">

    
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
            background-color: #f4f4f4;
            margin-top: 0;
            margin-bottom: 0;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: #1e3a8a; /* Royal Blue */
            color: #fff;
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: 100%;
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

        /* Main Content */
        .main-content {
            flex-grow: 1;
            padding: 20px;
            background-color: #fff;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }

        .stat-box {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 200px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-box h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #1e3a8a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #1e3a8a;
            color: white;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 8px 12px;
            margin: 5px;
            color: #fff;
            background-color: #1e3a8a;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #3b82f6;
        }



        /*font awesome */

        
    /* Author Icon */
    .fa-author {
      color: #4CAF50;
      font-size: 40px;
      padding: 10px;
      border: 2px solid #4CAF50;
      border-radius: 50%;
    }

    /* Publisher Icon */
    .fa-publisher {
      color: #FF5722;
      font-size: 40px;
      margin-right: 5px;
    }

    .fa-publisher-group {
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    /* Book Shelf No Icon */
    .fa-book_shelf-no {
      color: #2196F3;
      font-size: 40px;
      padding: 8px;
      background: #E3F2FD;
      border-radius: 5px;
    }

    /* Display Book Icon */
    .fa-display-book {
      color: #FFC107;
      font-size: 40px;
      padding: 10px;
      background: #FFF9C4;
      border-radius: 10px;
      box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
    }
  
    </style>
 </head>
 <body>

 
    <!--dashboard area-->

	                <div style="padding:50px 50px 50px 50px;margin-top:150px;display:inline-block;margin-left: 300px;" class=""> 

                    <form  method="post">
						
							<b>Current Password:</b>
							<input type="password" class="form-control mt-10" name="cpassword" placeholder="Current password">
							<br>
							<b>New Password:</b>
							<input type="password" class="form-control mt-10" name="npassword" placeholder="New password">
							<br>
							<b>Conform Password:</b>
							<input type="password" class="form-control mt-10" name="conpass" placeholder="Conform password">
							<br>
							<input type="submit" name="submit" class="btn" value="Change Password">
						</form>
                    </div>
						
						  <?php
							if (isset($_POST["submit"])){
							
								$cpass    = $_POST['cpassword'];
								$npass    = $_POST['npassword'];
								$conpass  = $_POST['conpass'];
								$stmt = $pdo->query( "SELECT password FROM application where name ='$_POST[name]'");								
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $pass   = $row['PASSWORD'];
								}
								if($cpass != $pass){
									?>
										<div class="alert alert-warning">
											<strong style="color:#333">Invalid!</strong> <span style="color: red;font-weight: bold; ">You entered wrong password</span>
										</div>
									<?php
								}else{
									if($npass == $conpass){
									$pdo->query("update application set password='$npass' where name='$_POST[name]'");
									
									 ?>
										<div class="alert alert-success">
											<strong style="color:#333">Success!</strong> <span style="color: green;font-weight: bold; ">Your password is changed.</span>
										</div>
									<?php
									}else{
									?>
										<div class="alert alert-warning">
											<strong style="color:#333">Not match!</strong> <span style="color: red;font-weight: bold; ">Your password</span>
										</div>
									<?php
									}			
								}								
							}
						?>
	<?php 
		//include 'inc/footer.php';
	 ?>
 </body>
 </html>
	