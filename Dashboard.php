<?php
session_start();

// Validation for the user
if (!isset($_SESSION['user_id'])) {
    header("Location: validation.php");
    exit();
}

// Database connection configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "expense_tracker";

// Initialize variables
$connection = null;
$mysql_running = true;
$connection_error = '';
$total_expenses = 0;
$total_income = 0;
$net_balance = 0;
$result = null;
$budget_result = null;

try {
    // Create connection with error handling
    $connection = new mysqli($servername, $username, $password, $dbname);

    // Set charset to ensure proper encoding
    $connection->set_charset("utf8mb4");

    // Check connection
    if ($connection->connect_error) {
        throw new Exception("Connection failed: " . $connection->connect_error);
    }

    // Get user ID from session
    $user_id = $_SESSION['user_id'];

    // Set default filters
    $month_filter = isset($_POST['month_filter']) ? $_POST['month_filter'] : date('m');
    $year_filter = isset($_POST['year_filter']) ? $_POST['year_filter'] : date('Y');

    // Build date condition
    $date_condition = "";
    if (isset($_POST['apply_date_filter'])) {
        $date_condition = " AND MONTH(date) = ? AND YEAR(date) = ?";
    }

    // Prepare budget query
    $budget_query = "SELECT amount, date FROM budgets WHERE user_id = ?";
    if (isset($_POST['apply_date_filter'])) {
        $budget_query .= $date_condition;
    }
    $budget_query .= " ORDER BY date DESC";

    $budget_stmt = $connection->prepare($budget_query);
    if (!$budget_stmt) {
        throw new Exception("Failed to prepare budget statement: " . $connection->error);
    }

    // Bind parameters and execute budget query
    if (isset($_POST['apply_date_filter'])) {
        $budget_stmt->bind_param("iss", $user_id, $month_filter, $year_filter);
    } else {
        $budget_stmt->bind_param("i", $user_id);
    }

    $budget_stmt->execute();
    $budget_result = $budget_stmt->get_result();

    // Prepare expenses query with proper parameter binding
    $expenses_query = "SELECT category, amount, date FROM expenses WHERE user_id = ?";
    if (isset($_POST['apply_filter'])) {
        $category_filter = $_POST['category_filter'] ?? '';
        $sort_filter = $_POST['sort_filter'] ?? '';

        if (!empty($category_filter)) {
            $expenses_query .= " AND category = ?";
        }

        // Add sorting
        switch ($sort_filter) {
            case 'newest':
                $expenses_query .= " ORDER BY date DESC";
                break;
            case 'oldest':
                $expenses_query .= " ORDER BY date ASC";
                break;
            case 'a-z':
                $expenses_query .= " ORDER BY category ASC";
                break;
            case 'z-a':
                $expenses_query .= " ORDER BY category DESC";
                break;
            default:
                $expenses_query .= " ORDER BY date DESC";
        }
    }

    $expenses_stmt = $connection->prepare($expenses_query);
    if (!$expenses_stmt) {
        throw new Exception("Failed to prepare expenses statement: " . $connection->error);
    }

    // Bind parameters and execute expenses query
    if (!empty($category_filter)) {
        $expenses_stmt->bind_param("is", $user_id, $category_filter);
    } else {
        $expenses_stmt->bind_param("i", $user_id);
    }

    $expenses_stmt->execute();
    $result = $expenses_stmt->get_result();

    // Calculate totals with prepared statements
    $totals_query = "SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ?" . $date_condition . ") as total_expenses,
        (SELECT COALESCE(SUM(amount), 0) FROM budgets WHERE user_id = ?" . $date_condition . ") as total_income";

    $totals_stmt = $connection->prepare($totals_query);
    if (!$totals_stmt) {
        throw new Exception("Failed to prepare totals statement: " . $connection->error);
    }
    // Capture the parameter and set the value of the variable
    if (isset($_POST['apply_date_filter'])) {
        $totals_stmt->bind_param("iiiiii", $user_id, $month_filter, $year_filter, $user_id, $month_filter, $year_filter);
    } else {
        $totals_stmt->bind_param("ii", $user_id, $user_id);
    }

    $totals_stmt->execute();
    $totals_result = $totals_stmt->get_result();
    $totals = $totals_result->fetch_assoc();

    $total_expenses = $totals['total_expenses'];
    $total_income = $totals['total_income'];
    $net_balance = $total_income - $total_expenses;

} catch (Exception $exception) {
    $mysql_running = false;
    $connection_error = $exception->getMessage();
    error_log("Database error: " . $exception->getMessage());
} finally {
    // Close all statements
    if (isset($budget_stmt))
        $budget_stmt->close();
    if (isset($expenses_stmt))
        $expenses_stmt->close();
    if (isset($totals_stmt))
        $totals_stmt->close();

    // Close connection
    if ($connection)
        $connection->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Broke Dashboard</title>
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
        .stat-card.income {
            background-color: #dcfce7;
            transition: transform 0.2s;
        }

        .stat-card.income:hover {
            transform: translateY(-5px);
        }

        .stat-card.expenses {
            background-color: #fee2e2;
            transition: transform 0.2s;
        }

        .stat-card.expenses:hover {
            transform: translateY(-5px);
        }

        .stats-summary {
            margin-top: 10px;
            font-size: 14px;
            color: #64748b;
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

        .addnewbutton {
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

        .cat_result {
            color :red;
            font-weight : 600;
        }
        
        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-btn:hover,
         .close-btn:focus {
             color: black;
             text-decoration: none;
             cursor: pointer;
         }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 20px;
            font-weight: bold;
            color: #64748b;
            cursor: pointer;
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

        .filter-select {
            padding: 4px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background-color: #f0f9ff;
            color: #64748b;
        }
        .section-divider {
            margin: 40px 0;
            border-top: 1px solid #e2e8f0;
        }

        .budget-section {
            margin-top: 40px;
        }
      
        .budget-amount {
            color: #047857;
            font-weight: 600;
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
    <?php if (!$mysql_running || !$connection): ?>
            <div style="background-color: #fee2e2; color: #dc2626; padding: 1rem; margin: 1rem; border-radius: 0.5rem;">
                <p><strong>Database Connection Error:</strong> <?php echo htmlspecialchars($connection_error); ?></p>
                <p>Please check if MySQL is running and try again.</p>
            </div>
    <?php endif; ?>
    
    <!--Settings for the header-->
    <div class="container">
        <header class="header"
            <span><h1><b>No Broke</b></h1></span>
            </div>
        </header>
       <!--Settings for the dashboard-->
        <div class="dashboard-layout">
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <a href="Dashboard.php" class="nav-item active">Dashboard</a>
                    <a href="category.php" class="nav-item">Category</a>
                    <a href="reminder.php" class="nav-item">Reminders</a>
                    <a href="budget.php" class="nav-item">Budget</a>
                    <a href="validation.php" class="nav-item">Logout</a>
                </nav>
            </aside>
               <!--Settings for the main contents-->
             <main class="main-content">
                    <!--Settings for the filter button based on the Month-->
                <div class="date-filter" style="margin-bottom: 20px;">
                    <form method="POST" action="" style="display: flex; gap: 10px; align-items: center; background: none; box-shadow: none; padding: 0; margin: 0; max-width: none;">
                        <select name="month_filter" class="filter-select">
                            <?php
                            //opt for user to select the month
                            $months = [
                                '01' => 'January',
                                '02' => 'February',
                                '03' => 'March',
                                '04' => 'April',
                                '05' => 'May',
                                '06' => 'June',
                                '07' => 'July',
                                '08' => 'August',
                                '09' => 'September',
                                '10' => 'October',
                                '11' => 'November',
                                '12' => 'December'
                            ];
                            foreach ($months as $value => $label) {
                                $selected = ($value == $month_filter) ? 'selected' : '';
                                echo "<option value='$value' $selected>$label</option>";
                            }
                            ?>
                        </select>
                        <select name="year_filter" class="filter-select">
                            <?php
                            // If no years available, show current year
                            if (empty($available_years)) {
                                $available_years[] = date('Y');
                            }
                            foreach ($available_years as $year) {
                                $selected = ($year == $year_filter) ? 'selected' : '';
                                echo "<option value='$year' $selected>$year</option>";
                            }
                            ?>
                        </select>
                        <button type="submit" name="apply_date_filter" class="add-new-btn" style="white-space: nowrap;">
                            Filter Date
                        </button>
                    </form>
                </div>
                 <!--Display the total income-->
                <div class="stats-grid">
                    <div class="stat-card income">
                        <h3>Total Income</h3>
                        <div class="amount">RM <?php echo number_format($total_income, 2); ?></div>
                        <div class="stats-summary">
                            <?php
                            if (isset($_POST['apply_date_filter'])) {
                                echo $months[$month_filter] . " " . $year_filter;
                            } else {
                                echo "All Time";
                            }
                            ?>
                        </div>
                    </div>
                        <!--Display for total expenses-->
                    <div class="stat-card expenses">
                        <h3>Total Expenses</h3>
                        <div class="amount">RM <?php echo number_format($total_expenses, 2); ?></div>
                        <div class="stats-summary">
                            <?php
                            if (isset($_POST['apply_date_filter'])) {
                                echo $months[$month_filter] . " " . $year_filter;
                            } else {
                                echo "All Time";
                            }
                            ?>
                        </div>
                    </div>
                       <!--Doing calculation and show how much this month/total have left-->
                    <div class="stat-card">
                        <h3>Net Balance</h3>
                        <div class="amount">RM <?php echo number_format($net_balance, 2); ?></div>
                        <div class="stats-summary">
                            <?php echo $net_balance >= 0 ? 'Available Balance' : 'Negative Balance'; ?>
                        </div>
                    </div>
                </div>



                    <!--Filter function button html to let user to interact-->
   <form method="POST" action="" style="display: flex; gap: 10px; align-items: center;">
            <select name="sort_filter" class="filter-select">
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
                <option value="a-z">A-Z (Category)</option>
                <option value="z-a">Z-A (Category)</option>
            </select>
            <button type="submit" name="apply_filter" class="add-new-btn">Apply Filters</button>
        </form>

                <div class="transactions">
    <div class="transactions-header">
        <h2>Your Expenses History</h2>
    </div>

                    <div class="chart-section" style="margin-top: 20px; text-align: right;">
    <form action="chart.php" method="post" style="display: inline;">
        <button type="submit" class="add-new-btn">
            Generate Expense Chart
        </button>
    </form>
</div>
                    </div>
                    <table class="transaction-list">
        <thead>
            <tr>
                <th>Category</th>
                <th>Date</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                 <tr>
                   <td><?php echo htmlspecialchars($row['category']); ?></td>
                   <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                   <td class="cat_result" ><?php echo "RM " . number_format($row['amount'], 2); ?></td>
                 </tr>
            <?php
            } ?>

        </tbody>      
        </table>
        <div class="section-divider"></div>
        <div class="budget-section">
        <div class="transactions-header">
        <h2>Your Budget History</h2>
        </div>
        
        <table class="transaction-list">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Budget Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (isset($budget_result) && $budget_result->num_rows > 0)
                    while ($budget_row = $budget_result->fetch_assoc()) {
                        ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($budget_row['date'])); ?></td>
                                <td class="budget-amount">RM <?php echo number_format($budget_row['amount'], 2); ?></td>
                            </tr>
                        <?php
                    }
                ?>
            </tbody>
    </div>
    </table>                            
   </div>
</body>
   <!--footer section-->
<div class="footer">
    <p>Disclaimer: This business is fictitious and part of a university course.</p>
</html>