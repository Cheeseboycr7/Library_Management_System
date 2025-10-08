<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ECOT Library - Splash Screen</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: white;
            flex-direction: column;
        }
        .splash-container {
            text-align: center;
            color: white;
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 15px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.2);
        }
        .logo {
            width: 120px;
            margin-bottom: 20px;
            border-radius: 10px;
        }
        h2 {
            margin-bottom: 15px;
            font-size: 24px;
            font-weight: 700;
        }
        .loading-bar {
            width: 100%;
            max-width: 350px;
            height: 20px;
            background: #ddd;
            border-radius: 1px;
            margin: 20px auto;
            overflow: hidden;
            position: relative;
        }
        .progress {
            height: 100%;
            width: 0;
            background: #00264d;
            border-radius: 1px;
            transition: width 1s linear;
        }
        .loading-text {
            margin-top: 15px;
            font-size: 18px;
            font-weight: bold;
        }
        #percent {
            color: #00264d;
        }
    </style>
</head>
<body>

    <div class="splash-container">
        <img src="../Student_Registration/include/ECOT.jpg" alt="ECOT Logo" class="logo">
        <h2>Welcome to ECOT Library Management System</h2>
        <div class="loading-bar">
            <div class="progress"></div>
        </div>
        <p class="loading-text">Loading... <span id="percent">0%</span></p>
    </div>

    <script>
        let progress = document.querySelector(".progress");
        let percentText = document.getElementById("percent");
        let width = 0;

        function updateProgress() {
            if (width < 100) {
                width++;
                progress.style.width = width + "%";
                percentText.innerText = width + "%";
                setTimeout(updateProgress, 50); /* Faster progress update */
            } else {
                setTimeout(() => {
                    window.location.href = "dash.php";  // Change to your main page
                }, 500);
            }
        }
        updateProgress();
    </script>

</body>
</html>
