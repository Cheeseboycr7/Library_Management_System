<?php
//session_start();
require_once '../includes/db_connect1.php';
require_once '../includes/function1.php';

// Check if user is logged in and is an admin
/*if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
*/
$error_message = $success_message = '';

// Handle fine payment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'pay_fine') {
        $borrow_id = $_POST['borrow_id'];
        $amount_paid = $_POST['amount_paid'];
        
        $result = pay_fine($borrow_id, $amount_paid);
        if ($result === true) {
            $success_message = "Fine paid successfully.";
        } else {
            $error_message = $result;
        }
    }
}

// Fetch overdue books with fines
//$overdue_books = get_overdue_books();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fines - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Manage Fines</h1>
        
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>

        <table class="w-full bg-white shadow-md rounded mb-4">
            <thead>
                <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">Book Title</th>
                    <th class="py-3 px-6 text-left">Member Name</th>
                    <th class="py-3 px-6 text-left">Due Date</th>
                    <th class="py-3 px-6 text-left">Days Overdue</th>
                    <th class="py-3 px-6 text-left">Fine Amount</th>
                    <th class="py-3 px-6 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm font-light">
                <?php foreach ($overdue_books as $book): ?>
                <tr class="border-b border-gray-200 hover:bg-gray-100">
                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($book['Title']); ?></td>
                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($book['Member_Name']); ?></td>
                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($book['Due_Date']); ?></td>
                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($book['Days_Overdue']); ?></td>
                    <td class="py-3 px-6 text-left">$<?php echo htmlspecialchars(number_format($book['Fine_Amount'], 2)); ?></td>
                    <td class="py-3 px-6 text-center">
                        <button onclick="showPayFineForm('<?php echo $book['Borrow_ID']; ?>', '<?php echo $book['Fine_Amount']; ?>')" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-2 rounded">
                            Pay Fine
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pay Fine Form (hidden by default) -->
    <div id="payFineForm" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-bold mb-4">Pay Fine</h3>
            <form method="POST">
                <input type="hidden" name="action" value="pay_fine">
                <input type="hidden" id="borrow_id" name="borrow_id">
                <div class="mb-4">
                    <label for="amount_paid" class="block text-gray-700 text-sm font-bold mb-2">Amount to Pay</label>
                    <input type="number" id="amount_paid" name="amount_paid" step="0.01" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Pay Fine
                    </button>
                    <button type="button" onclick="hidePayFineForm()" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showPayFineForm(borrowId, fineAmount) {
            document.getElementById('payFineForm').classList.remove('hidden');
            document.getElementById('borrow_id').value = borrowId;
            document.getElementById('amount_paid').value = fineAmount;
            document.getElementById('amount_paid').max = fineAmount;
        }

        function hidePayFineForm() {
            document.getElementById('payFineForm').classList.add('hidden');
        }
    </script>
</body>
</html>

