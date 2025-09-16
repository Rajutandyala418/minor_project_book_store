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


// ✅ Get username from session or URL
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
} elseif (isset($_GET['username'])) {
    $username = $_GET['username'];
    $_SESSION['username'] = $username; // fallback set session
} else {
    header("Location: login.php");
    exit();
}

$message = "";

/* ---------------------------
   Handle Update Submission
----------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');

    $sql = "UPDATE users 
            SET first_name = ?, last_name = ?, email = ?, phone = ?
            WHERE username = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $message = "Database error (prepare): " . htmlspecialchars($conn->error);
    } else {
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $phone, $username);

if ($stmt->execute()) {
    $message = "✅ Profile updated successfully!";
    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name']  = $last_name;
    $_SESSION['email']      = $email;
    $_SESSION['phone']      = $phone;

    // -----------------------
    // Send email notification
    // -----------------------
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
        $mail->Subject = "Profile Updated Successfully";
        $mail->Body = "
            <p>Dear {$first_name} {$last_name},</p>
            <p>Your profile details have been updated successfully. Here are your updated details:</p>
            <ul>
                <li>First Name: {$first_name}</li>
                <li>Last Name: {$last_name}</li>
                <li>Email: {$email}</li>
                <li>Phone: {$phone}</li>
            </ul>
            <p>If you did not make this change, please contact support immediately.</p>
        ";
        $mail->AltBody = "Dear {$first_name} {$last_name}, Your profile has been updated. First Name: {$first_name}, Last Name: {$last_name}, Email: {$email}, Phone: {$phone}";

        $mail->send();
    } catch (Exception $e) {
        error_log("Profile Update Mailer Error: " . $mail->ErrorInfo);
        // Optionally show a message to user:
        echo "<p style='color:red;'>Email notification failed: {$mail->ErrorInfo}</p>";
    }
}
 else {
            $message = "❌ Update failed! " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}

/* ---------------------------
   Fetch Current User Details
----------------------------*/
$sql = "SELECT id, username, first_name, last_name, email, phone 
        FROM users 
        WHERE username = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error (prepare): " . htmlspecialchars($conn->error));
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$user) {
    die("User not found.");
}

$username   = htmlspecialchars($user['username'] ?? '');
$first_name = htmlspecialchars($user['first_name'] ?? '');
$last_name  = htmlspecialchars($user['last_name'] ?? '');
$email      = htmlspecialchars($user['email'] ?? '');
$phone      = htmlspecialchars($user['phone'] ?? '');

$display_name    = !empty($first_name) ? $first_name : $username;
$profile_initial = strtoupper(substr($display_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Profile</title>
    <style>
        html, body {
    margin: 0;
    padding: 0;
    min-height: 100%;   /* use min-height instead of fixed height */
    font-family: 'Poppins', sans-serif;
    overflow-x: hidden; /* only hide horizontal scrollbars */
    overflow-y: auto;   /* allow vertical scrolling */
    color: #fff;
    background: linear-gradient(
        135deg,
        #ff0000, #ff7f00, #ffff00, #7fff00, #00ff00,
        #00ff7f, #00ffff, #007fff, #0000ff, #7f00ff,
        #ff00ff, #ff007f, #ff6666, #ff9966, #ffcc66,
        #ccff66, #66ff66, #66ffcc, #66ccff, #6699ff,
        #6666ff, #9966ff, #cc66ff, #ff66ff, #ff66cc
    );
    background-size: 400% 400%;
    animation: gradientAnimation 20s ease infinite;
}

        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .top-nav {
            position: absolute;
            top: 20px; right: 30px;
            display: flex; align-items: center;
            gap: 15px; color: #00bfff; font-weight: 600;
        }
        .profile-menu { position: relative; }
        .profile-circle {
            width: 45px; height: 45px;
            background: #00bfff; border-radius: 50%;
            cursor: pointer; border: 2px solid #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.2rem; color: white;
            user-select: none;
        }
        .dropdown-content {
            display: none; position: absolute; top: 55px; right: 0;
            background: rgba(0,0,0,0.85); border-radius: 6px;
            min-width: 150px; z-index: 10;
            box-shadow: 0 4px 8px rgba(0,0,0,0.5);
        }
        .dropdown-content a {
            display: block; padding: 10px 12px;
            color: white; text-decoration: none; font-weight: 600;
            transition: background 0.2s ease;
        }
        .dropdown-content a:hover { background: rgba(255,255,255,0.1); }
        .container {
            max-width: 700px; margin: 100px auto 50px;
            background: rgba(0, 0, 0, 0.7); border-radius: 10px;
            padding: 30px; box-sizing: border-box;
        }
        h1 {
            color: #00bfff; margin-bottom: 25px;
            text-align: center; font-weight: 700;
        }
        form table { width: 100%; border-collapse: collapse; color: white; }
        th, td {
            padding: 12px 10px; text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        th { width: 30%; color: #00bfff; }
        input[type="text"], input[type="email"] {
            width: 100%; padding: 10px; border-radius: 6px;
            border: none; font-size: 1rem; box-sizing: border-box;
        }
        input[readonly] { background: rgba(255,255,255,0.2); cursor: default; color: #ccc; }
        button {
            margin-top: 20px; width: 100%; padding: 14px;
            font-weight: 700; font-size: 1.1rem; border-radius: 8px;
            border: none; background: linear-gradient(90deg, #00bfff, #1e90ff);
            color: white; cursor: pointer; transition: background 0.3s ease;
            user-select: none;
        }
        button:hover { background: linear-gradient(90deg, #1e90ff, #00bfff); }
        .message {
            margin-top: 10px; text-align: center;
            font-weight: 600; color: #0ff; text-shadow: 0 0 5px #0ff;
        }
        .back-btn {
            display: inline-block; margin-top: 25px; padding: 10px 20px;
            background: #007bff; color: white; border-radius: 6px;
            font-weight: bold; text-decoration: none;
            transition: background 0.3s ease;
        }
        .back-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
<div class="top-nav">
    <span>Welcome, <?php echo htmlspecialchars($display_name); ?></span>
    <div class="profile-menu">
        <div class="profile-circle" id="profileBtn"><?php echo $profile_initial; ?></div>
        <div class="dropdown-content" id="dropdownMenu">
            <a href="settings.php?username=<?php echo urlencode($username); ?>">Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <h1>Your Profile Details</h1>
    <?php if (!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <table>
            <tr>
                <th>Username</th>
                <td><input type="text" value="<?php echo $username; ?>" readonly></td>
            </tr>
            <tr>
                <th>First Name</th>
                <td><input type="text" name="first_name" value="<?php echo $first_name; ?>" required></td>
            </tr>
            <tr>
                <th>Last Name</th>
                <td><input type="text" name="last_name" value="<?php echo $last_name; ?>" required></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><input type="email" name="email" value="<?php echo $email; ?>" required></td>
            </tr>
            <tr>
                <th>Phone</th>
                <td><input type="text" name="phone" value="<?php echo $phone; ?>" required pattern="[0-9]{10}" title="Enter 10-digit phone number"></td>
            </tr>
        </table>
        <button type="submit">Update Profile</button>
    </form>

    <div style="text-align:center;">
        <!-- ✅ Back to Dashboard with username -->
        <a href="dashboard.html?username=<?php echo urlencode($username); ?>" class="back-btn">Back to Dashboard</a>
    </div>
</div>

<script>
    // Profile dropdown toggle
    const profileBtn = document.getElementById('profileBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');

    profileBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', function() {
        dropdownMenu.style.display = 'none';
    });
</script>
</body>
</html>
