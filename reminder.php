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
$connection = new mysqli($servername, $username, $password, $dbname);

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$user_id = $_SESSION['user_id'];

// Handle form submission for adding a reminder
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_reminder'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];
    $reminder_date = $_POST['reminder_date'];

    $insert_query = $connection->prepare("INSERT INTO reminders (user_id, title, description, amount, due_date, reminder_date) VALUES (?, ?, ?, ?, ?, ?)");
    $insert_query->bind_param("issdss", $user_id, $title, $description, $amount, $due_date, $reminder_date);

    if ($insert_query->execute()) {
        $success = "Reminder added successfully!";
    } else {
        $error = "Error adding reminder.";
    }
}

// Handle marking reminder as complete
if (isset($_POST['complete_reminder'])) {
    $reminder_id = $_POST['reminder_id'];
    $update_query = $connection->prepare("UPDATE reminders SET status = 'completed' WHERE id = ? AND user_id = ?");
    $update_query->bind_param("ii", $reminder_id, $user_id);
    $update_query->execute();
}

// Handle deleting reminder
if (isset($_POST['delete_reminder'])) {
    $reminder_id = $_POST['reminder_id'];
    $delete_query = $connection->prepare("DELETE FROM reminders WHERE id = ? AND user_id = ?");
    $delete_query->bind_param("ii", $reminder_id, $user_id);
    $delete_query->execute();
}

// Fetch reminders
$reminders_query = $connection->prepare("
    SELECT * FROM reminders 
    WHERE user_id = ? 
    ORDER BY 
        CASE status 
            WHEN 'pending' THEN 1 
            WHEN 'overdue' THEN 2 
            WHEN 'completed' THEN 3 
        END, 
        due_date ASC
");
$reminders_query->bind_param("i", $user_id);
$reminders_query->execute();
$reminders = $reminders_query->get_result();

// Update overdue reminders
$current_date = date('Y-m-d');
$connection->query("UPDATE reminders SET status = 'overdue' WHERE due_date < '$current_date' AND status = 'pending'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminder Settings - No Broke</title>
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

        /* Main Content */
        .main-content {
            background-color: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
     
        .reminder-card {
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }

        .reminder-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .reminder-title {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
        }

        .reminder-amount {
            font-size: 16px;
            color: #ef4444;
        }

        .reminder-dates {
            display: flex;
            gap: 20px;
            margin: 10px 0;
            color: #64748b;
        }

        .reminder-description {
            color: #64748b;
            margin: 10px 0;
        }

        .reminder-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-complete {
            background-color: #22c55e;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-delete {
            background-color: #ef4444;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-completed {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .status-overdue {
            background-color: #fee2e2;
            color: #dc2626;
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
                    <a href="category.php" class="nav-item">Category</a>
                    <a href="reminder.php" class="nav-item active">Reminders</a>
                    <a href="budget.php" class="nav-item">Budget</a>
                    <a href="validation.php" class="nav-item">Logout</a>
                </nav>
            </aside>


            <main class="main-content">
                <h2>Reminder Settings</h2>
                  <!--a Reminder form for user-->
                <div class="reminder-card">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="title">Reminder Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="2"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="amount">Amount (RM)</label>
                            <input type="number" id="amount" name="amount" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="due_date">Due Date</label>
                            <input type="date" id="due_date" name="due_date" required>
                        </div>

                        <div class="form-group">
                            <label for="reminder_date">Reminder Date</label>
                            <input type="date" id="reminder_date" name="reminder_date" required>
                        </div>

                        <button type="submit" name="add_reminder" class="submit-btn">Add Reminder</button>
                    </form>
                </div>
                <!--The Reminder form-->
                <div class="reminders-list">
                    <h3>Your Reminders</h3>
                    <?php while ($reminder = $reminders->fetch_assoc()) { ?>
                        <div class="reminder-card">
                            <div class="reminder-header">
                                <span class="reminder-title"><?php echo htmlspecialchars($reminder['title']); ?></span>
                                <span class="reminder-amount">RM <?php echo number_format($reminder['amount'], 2); ?></span>
                            </div>
                            
                            <div class="reminder-description">
                                <?php echo htmlspecialchars($reminder['description']); ?>
                            </div>
                             <!---To show the time of the expense with time and remind-->
                            <div class="reminder-dates">
                                <span>Due: <?php echo date('d M Y', strtotime($reminder['due_date'])); ?></span>
                                <span>Reminder: <?php echo date('d M Y', strtotime($reminder['reminder_date'])); ?></span>
                                <span class="status-badge status-<?php echo $reminder['status']; ?>">
                                    <?php echo ucfirst($reminder['status']); ?>
                                </span>
                            </div>
                            <!--Creating status for the user to see the reminder-->
                            <div class="reminder-actions">
                                <?php if ($reminder['status'] == 'pending' || $reminder['status'] == 'overdue') { ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="reminder_id" value="<?php echo $reminder['id']; ?>">
                                        <button type="submit" name="complete_reminder" class="btn-complete">Mark Complete</button>
                                    </form>
                                <?php } ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="reminder_id" value="<?php echo $reminder['id']; ?>">
                                    <button type="submit" name="delete_reminder" class="btn-delete">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php } ?>
                </div>


                
            </main>
        </div>
    </div>
</body>
<div class="footer">
    <p>Disclaimer: This business is fictitious and part of a university course.</p>
</html>