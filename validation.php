<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "expense_tracker";
$connection = new mysqli($servername, $username, $password, $dbname);

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $reg_username = $_POST['username'];
    $reg_email = $_POST['email'];
    $reg_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if the username already exists
    $check_user = $connection->prepare("SELECT * FROM users WHERE username = ?");
    $check_user->bind_param("s", $reg_username);
    $check_user->execute();
    $result = $check_user->get_result();

    // Check if the email already exists
    $check_email = $connection->prepare("SELECT * FROM users WHERE email = ?");
    $check_email->bind_param("s", $reg_email);
    $check_email->execute();
    $email_result = $check_email->get_result();

    if ($result->num_rows > 0) {
        $error = "Username already exists. Please choose a different one.";
    } elseif ($email_result->num_rows > 0) {
        $error = "Email address already registered. Please use a different email or login to your existing account.";
    } else {
        // Insert the new user into the database
        $insert_query = $connection->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $insert_query->bind_param("sss", $reg_username, $reg_email, $reg_password);
        if ($insert_query->execute()) {
            $success = "Registration successful. You can now log in.";
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}

// Handle user login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $login_username = $_POST['username'];
    $login_password = $_POST['password'];

    $login_query = $connection->prepare("SELECT * FROM users WHERE username = ?");
    $login_query->bind_param("s", $login_username);
    $login_query->execute();
    $user = $login_query->get_result()->fetch_assoc();

    if ($user && password_verify($login_password, $user['password'])) {
        $_SESSION['user_id'] = $user['id']; // ensure the user_id is id
        header("Location: Dashboard.php"); // Redirect to the dashboard after login
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Register Form</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Times New Roman', sans-serif;
        }

        body {
            background-color: #f0f9ff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            max-width: 400px;
            width: 90%;
            padding: 20px;
        }

        .form-container {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: #0f172a;
            margin-bottom: 10px;
        }

        .toggle-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .toggle-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background-color: #e2e8f0;
            color: #64748b;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-btn.active {
            background-color: #0ea5e9;
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #0f172a;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #0ea5e9;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #0ea5e9;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #0284c7;
        }

        #registerForm {
            display: none;
        }

        .form {
            transition: opacity 0.3s ease;
        }

        .error-message {
            color: #ef4444;
            margin-top: 5px;
            font-size: 14px;
        }

        .error-message {
            color: #ef4444;
            margin-top: 5px;
            font-size: 14px;
            text-align: center;
            padding: 10px;
            background-color: #fef2f2;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .success-message {
            color: #059669;
            margin-top: 5px;
            font-size: 14px;
            text-align: center;
            padding: 10px;
            background-color: #ecfdf5;
            border-radius: 4px;
            margin-bottom: 15px;
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
</head>
<body>
 <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h2>Welcome to No Broke</h2>
                <div class="toggle-buttons">
                    <button class="toggle-btn active" onclick="toggleForm('login')">Login</button> <!--Create toggle button when click on Login or Register-->
                    <button class="toggle-btn" onclick="toggleForm('register')">Register</button> <!--Letting the form to swtich to login/register-->
                </div>
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div> <!--Either login successfully/ register sucessful or not successful-->
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="form" method="POST" action="">
                <div class="form-group">
                    <label for="loginUsername">Username</label>
                    <input type="text" id="loginUsername" name="username" required>
                </div>
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" name="password" required>
                </div>
                <button type="submit" name="login" class="submit-btn">Login</button>
            </form>

            <!-- Register Form -->
            <form id="registerForm" class="form" method="POST" action="">
                <div class="form-group">
                    <label for="registerUsername">Username</label>
                    <input type="text" id="registerUsername" name="username" required>
                </div>
                <div class="form-group">
                    <label for="registerEmail">Email</label>
                    <input type="email" id="registerEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="registerPassword">Password</label>
                    <input type="password" id="registerPassword" name="password" required>
                </div>
                <button type="submit" name="register" class="submit-btn">Register</button>
            </form>
        </div>
    </div>

    <script>
        //Creating function to handle the button Login or Register 
        // So the button will gain the input and send it to the database.
        function toggleForm(formType) {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const loginBtn = document.querySelector('.toggle-btn:nth-child(1)');
            const registerBtn = document.querySelector('.toggle-btn:nth-child(2)');
            //Onclick on login/ register will switch the form, how the form can be switch if user want to do register or login
            if (formType === 'login') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                loginBtn.classList.add('active');
                registerBtn.classList.remove('active');
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                loginBtn.classList.remove('active');
                registerBtn.classList.add('active');
            }
        }
    </script>
</body>
</html>
</body>
<div class="footer">
    <p>Disclaimer: This business is fictitious and part of a university course.</p>
</html>