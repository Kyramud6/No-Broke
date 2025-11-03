<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: validation.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "expense_tracker";

// Create connection
$connection = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$message = ""; // Initialize message variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];
    
    // Determine the category
    if ($_POST['category'] === 'other') {
        $category = $_POST['other_category'];
    } else {
        $category = $_POST['category'];
    }

    // Prepare the SQL statement
    $sql = "INSERT INTO expenses (user_id, category, amount, date) VALUES (?, ?, ?, ?)";
    $stmt = $connection->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("isds", $user_id, $category, $amount, $date);
        
        if ($stmt->execute()) {
            $message = "Expense added successfully!";
            
            // Redirect after successful insertion
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Error: " . $connection->error;
    }
}
?>


<!doctype html>
<html lang="en">
<head>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Times New Roman', sans-serif;
        }

        body {
            background-color: #f0f9ff;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            background-color: white;
            border-radius: 15px;
            padding: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-links a {
            color: #64748b;
            text-decoration: none;
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 24px;
        }

        .sidebar {
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            color: #64748b;
            text-decoration: none;
            border-radius: 6px;
        }

        .nav-item.active {
            background-color: #e0f2fe;
            color: #0ea5e9;
        }

        .main-content {
            background-color: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .stat-card .amount {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
        }

        .transactions {
            margin-top: 24px;
        }

        .transactions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .add-new-btn {
            background-color: #0ea5e9;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        .transaction-list {
            border-spacing: 0;
            width: 100%;
        }

        .transaction-list th {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
        }

        .transaction-list td {
            padding: 12px;
        }

        form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 20px auto;
        }

        form label {
            display: block;
            margin-bottom: 10px;
            color: #0f172a;
        }

        form input,
        form select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
        }

        form button {
            background-color: #0ea5e9;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        form button:hover {
            background-color: #0284c7;
        }

        .footer {
            bottom: 0;
            text-align: center;
            padding: 20px;
            color: #64748b;
            background-color: white;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }

    </style>
</head>
    <div class="container">
        <header class="header"
            <span><h1><b>No Broke</b></h1></span>
            </div>
        </header>
<body>

             <div class="dashboard-layout">
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <a href="Dashboard.php" class="nav-item ">Dashboard</a>
                    <a href="category.php" class="nav-item active">Category</a>
                    <a href="reminder.php" class="nav-item">Reminders</a>
                    <a href="budget.php" class="nav-item">Budget</a>
                    <a href="validation.php" class="nav-item">Logout</a>
                </nav>
            </aside>

<main class="main-content">
    <h1>Manage Your Categories</h1>
    <p>Here you can add, update, or remove categories for your expenses.</p>

    <div id="message-container" style="margin-bottom: 20px;"></div>

    <!--- User Input for Categories -->
    <form id="expenses" action="" method="POST">
        <label for="category">Select Category : </label>
        <select name="category" id="category" onchange="handleCategoryChange()" required>
            <option value="">-- Select Category --</option>

            <!--- Category for Tuition Fee -->
           <optgroup label="Tuition Fee">
               <option value="tuition">Tuition</option>
               <option value="books">Books</option>
               <option value="stationary">Stationary</option>
               <option value="online courses">Online Courses</option>
           </optgroup>

            <!--- Category for Housing -->
            <optgroup label="Housing">
               <option value="rent">Rent</option>
               <option value="utilities">Utilities</option>
               <option value="household">Household</option>
           </optgroup>

            <!--- Category for Entertainment -->
            <optgroup label="Entertainment">
               <option value="subscription">Subscription</option>
               <option value="hobbies">Hobbies</option>
               <option value="social">Social</option>
           </optgroup>

            <!--- Category for Personal Care -->
            <optgroup label="Personal Care">
               <option value="grooming">Grooming</option>
               <option value="clothes">Clothes</option>
               <option value="laundry">Laundry</option>
               <option value="cosmetic">Cosmetic</option>
           </optgroup>

            <!--- Category for Miscellaneous -->
            <optgroup label="Miscellaneous">
               <option value="gift">Gift</option>
               <option value="emergency">Emergency</option>
               <option value="transportation">Transportation</option>
           </optgroup>

            <!--- Custom Category -->
            <option value="other">Other</option>
        </select>

        <!--- Category for Custom Category -->
        <div id="othercategoryfield" style="display:none;">
            <label for="othercategory">Other Category</label>
            <input type="text" id="othercategory" name="other_category" placeholder="Enter Custom Category" />
        </div>

        <!-- Giving user to input the amount -->
        <label for="amount">Amount (RM) :</label>
        <input type="number" id="amount" name="amount" required />

        <!-- Giving user to input the date -->
        <label for="date">Date</label>
        <input type="date" id="date" name="date" required />

        <!-- Button to get the user input -->
        <button type="submit">Add Expense</button>
    </form>

</body>
<div class="footer">
    <p>Disclaimer: This business is fictitious and part of a university course.</p>
    <!--Create the javascript to handle the button event-->
    <!--To call the function and update the value into the page and PHP will handle the rest-->
<script>
    function handleCategoryChange() {
        const category = document.getElementById('category').value;
        const othercategoryfield = document.getElementById('othercategoryfield');
        if (category === 'other') {
            othercategoryfield.style.display = 'block';
        } else {
            othercategoryfield.style.display = 'none';
        }
    }
    //To handle error when submitting the message
    <?php if ($message && strpos($message, 'Error') === 0): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const messageContainer = document.getElementById('message-container');
            messageContainer.innerHTML = `
                <div style="padding: 10px; margin-bottom: 15px; border-radius: 5px; 
                    background-color: #fee2e2; color: #dc2626; border: 1px solid #dc2626;">
                    <?php echo addslashes($message); ?>
                </div>`;
            
            // Automatically hide the error message after 5 seconds
            setTimeout(function() {
                messageContainer.innerHTML = '';
            }, 5000);
        });
    <?php endif; ?>
</script>
</html>


