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

$connection = null;
$message = '';
//Database connection so the budget can connect to the database.
try {
    $connection = new mysqli($servername, $username, $password, $dbname);

    if ($connection->connect_error) {
        throw new Exception("Connection failed: " . $connection->connect_error);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['amount'])) {
        $amount = floatval($_POST['amount']);
        $date = $_POST['date'];
        $user_id = $_SESSION['user_id'];

        // Preparing the  statement
        $stmt = $connection->prepare("INSERT INTO budgets (user_id, amount, date) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connection->error);
        }

        // Bind parameters and execute
        $stmt->bind_param("ids", $user_id, $amount, $date);

        if ($stmt->execute()) {
            $message = "Budget successfully added!";
            // Redirect after successful insertion
            // So after click on Add budget it will direct user to dashboard/main menu
            header("Location: dashboard.php");
            exit();
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    }
} catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
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
            margin-bottom: 24px;
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
            height: fit-content;
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
                font-weight: 500;
            }

            form input {
                width: 100%;
                padding: 10px;
                margin-bottom: 20px;
                border: 1px solid #cbd5e1;
                border-radius: 5px;
                transition: border-color 0.3s ease;
            }

                form input:focus {
                    outline: none;
                    border-color: #0ea5e9;
                }

            form button {
                background-color: #0ea5e9;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                width: 100%;
                font-size: 16px;
                transition: background-color 0.3s ease;
            }

                form button:hover {
                    background-color: #0284c7;
                }

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #059669;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #dc2626;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #64748b;
            background-color: white;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
            margin-top: 24px;
        }

        h1 {
            color: #0f172a;
            margin-bottom: 16px;
        }

        .description {
            color: #64748b;
            margin-bottom: 24px;
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
<body>
    <div class="container">
        <header class="header">
            <h1><b>Budget Planner</b></h1>
        </header>

           <div class="dashboard-layout">
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <a href="Dashboard.php" class="nav-item">Dashboard</a>
                    <a href="category.php" class="nav-item">Category</a>
                    <a href="reminder.php" class="nav-item">Reminders</a>
                    <a href="budget.php" class="nav-item active">Budget</a>
                    <a href="validation.php" class="nav-item">Logout</a>
                </nav>
            </aside>
                <main class="main-content">
                <h1>Set Your Budget</h1>
                <p class="description">Plan your monthly expenses by setting a budget limit.</p>

                <?php if (!empty($message)): ?>
                    <div class="alert <?php echo strpos($message, 'Error') === 0 ? 'alert-error' : 'alert-success'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                    <!--To get input of the user-->
                <form method="POST" action="">
                    <label for="amount">Budget Amount (RM)</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" required 
                           placeholder="Enter your budget amount"/>

                    <label for="date">Start Date</label>
                    <input type="date" id="date" name="date" required />

                    <button type="submit">Set Budget</button>
                </form>
            </main>
        </div>
<div class="footer">
<p>Disclaimer: This business is fictitious and part of a university course.</p>
</html>
