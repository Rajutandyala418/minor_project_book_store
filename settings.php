<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/include/db_connect.php";
require __DIR__ . '/include/php_mailer/Exception.php';
require __DIR__ . '/include/php_mailer/PHPMailer.php';
require __DIR__ . '/include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// ✅ Get username from URL (instead of session)
if (!isset($_GET['username']) || empty($_GET['username'])) {
    die("Unauthorized access. Please <a href='login.php'>login</a> first.");
}

$username = $_GET['username']; // ✅ Taking username from URL

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $hashedPassword, $username);

if ($stmt->execute()) {
    // -----------------------
    // Send password update email
    // -----------------------
 

    // Fetch user's name and email
    $stmt_user = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE username = ?");
    $stmt_user->bind_param("s", $username);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user = $result_user->fetch_assoc();
    $stmt_user->close();

    if ($user) {
        $first_name = $user['first_name'];
        $last_name  = $user['last_name'];
        $email      = $user['email'];

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'rvrbookstore.minor@gmail.com';
            $mail->Password   = 'tmmt wlfb mvtx usvt'; // remove spaces
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('rvrbookstore.minor@gmail.com', 'Rvr Book Store');
            $mail->addReplyTo('rvrbookstore.minor@gmail.com', 'Support');
            $mail->addAddress($email, $first_name . " " . $last_name);

            $mail->isHTML(true);
            $mail->Subject = "Your Password Has Been Updated";
            $mail->Body = "
                <p>Dear {$first_name} {$last_name},</p>
                <p>Your password has been successfully updated for your account <b>{$username}</b>.</p>
                <p>If you did not make this change, please contact support immediately.</p>
                <p>Thank you, <br>Rvr Book Store Team</p>
            ";
            $mail->AltBody = "Dear {$first_name} {$last_name}, Your password for username {$username} has been updated successfully.";

            $mail->send();
        } catch (Exception $e) {
            error_log("Password Update Mailer Error: " . $mail->ErrorInfo);
        }
    }

    echo "
    <div id='popup'>
        <h2>Password Updated Successfully!</h2>
        <p>Redirecting to login page in <span id='countdown'>5</span> seconds...</p>
    </div>
    <script>
        let seconds = 5;
        const countdown = document.getElementById('countdown');
        const popup = document.getElementById('popup');
        popup.style.display = 'block';

        const timer = setInterval(() => {
            seconds--;
            countdown.textContent = seconds;
            if(seconds <= 0){
                clearInterval(timer);
                window.location.href = 'login.php'; // ✅ redirect to login page
            }
        }, 1000);
    </script>
    <style>
        #popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(
                135deg,
                #ff0000, #ff7f00, #ffff00, #7fff00, #00ff00,
                #00ff7f, #00ffff, #007fff, #0000ff, #7f00ff,
                #ff00ff, #ff007f, #ff6666, #ff9966, #ffcc66,
                #ccff66, #66ff66, #66ffcc, #66ccff, #6699ff,
                #6666ff, #9966ff, #cc66ff, #ff66ff, #ff66cc
            );
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            text-align: center;
            z-index: 9999;
            color: #fff;
        }
    </style>
    ";
    exit();
} else {
    $error = "Error updating password!";
}

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Settings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(
                135deg,
                #ff0000, #ff7f00, #ffff00, #7fff00, #00ff00,
                #00ff7f, #00ffff, #007fff, #0000ff, #7f00ff,
                #ff00ff, #ff007f, #ff6666, #ff9966, #ffcc66,
                #ccff66, #66ff66, #66ffcc, #66ccff, #6699ff,
                #6666ff, #9966ff, #cc66ff, #ff66ff, #ff66cc
            );
            animation: gradientAnimation 20s ease infinite;
            margin: 0;
            padding: 0;
            height: 100vh;
        }
        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .container {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            max-width: 450px;
            margin: 80px auto;
            text-align: center;
            color: #fff;
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            justify-content: space-between;
        }
        label {
            flex: 1;
            margin-right: 10px;
            font-weight: bold;
            text-align: right;
        }
        input {
            flex: 2;
            padding: 10px;
            border: none;
            border-radius: 5px;
        }
        button {
            background: #007BFF;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
        .error-msg {
            color: yellow;
            margin-bottom: 10px;
        }
        .back-to-main {
            position: absolute;
            top: 20px;
            right: 30px;
            padding: 10px 20px;
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<a href="dashboard.html?username=<?php echo urlencode($username); ?>" class="back-to-main">Back to dashboard Page</a>

<div class="container">
    <h2>Account Settings</h2>
    <?php if(isset($error)) echo "<div class='error-msg'>$error</div>"; ?>
    <form method="POST">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" value="<?php echo htmlspecialchars($username); ?>" readonly>
        </div>
        <div class="form-group">
            <label>New Password:</label>
            <input type="password" name="password" required placeholder="Enter new password">
        </div>
        <div class="form-group">
            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" required placeholder="Re-enter password">
        </div>
        <button type="submit">Update Password</button>
    </form>
</div>
</body>
</html>
