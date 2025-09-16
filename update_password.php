<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/include/db_connect.php');

// ✅ PHPMailer includes
require __DIR__ . '/include/php_mailer/Exception.php';
require __DIR__ . '/include/php_mailer/PHPMailer.php';
require __DIR__ . '/include/php_mailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = ''; 
$username = ''; 

if (isset($_GET['username']) && !empty($_GET['username'])) { 
    $username = trim($_GET['username']); 
} elseif (isset($_SESSION['username'])) { 
    $username = $_SESSION['username']; 
} else { 
    die("No username provided."); 
}

$registration_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $username = trim($_POST['username']); // hidden or readonly field

    $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (!preg_match($password_pattern, $password)) {
        $message = "Password must be at least 8 characters long, include upper and lower case letters, a number, and a special character.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Get user_id from username
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($user_id, $first_name, $last_name, $email);
        if ($stmt->fetch()) {
            $stmt->close();

            // Update password
            $stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt2->bind_param("si", $hashed_password, $user_id);
            if ($stmt2->execute() && $stmt2->affected_rows > 0) {
                $message = "Password updated successfully!";

                // -----------------------
                // Send Email using PHPMailer
                // -----------------------
                $first_name = isset($first_name) ? trim($first_name) : '';
                $last_name  = isset($last_name)  ? trim($last_name) : '';
                $fullName = trim($first_name . ' ' . $last_name);

                if ($fullName === '') {
                    $fullName = $username;
                }

                try {
                    $mail = new PHPMailer(true);
                    $mail->SMTPDebug = 0; // disable debug in production
                    $mail->Debugoutput = 'html';

                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'rvrbookstore.minor@gmail.com';
                    $mail->Password   = 'tmmt wlfb mvtx usvt'; // ✅ App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                    $mail->Port       = 587;

                    // ✅ Optional SSL options for Windows/XAMPP certs
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer'       => true,
                            'verify_peer_name'  => true,
                            'allow_self_signed' => false,
                            'cafile'            => 'C:\xampp\php\extras\ssl\cacert.pem'
                        ]
                    ];

                    $mail->setFrom('rvrbookstore.minor@gmail.com', 'Rvr Book Store');
                    $mail->addAddress($email, $fullName);
                    $mail->addReplyTo('rvrbookstore.minor@gmail.com', 'Support');

                    $mail->isHTML(true);
                    $mail->Subject = "Your Password Has Been Updated";
                    $mail->Body    = "
                        <p>Hi <b>{$fullName}</b>,</p>
                        <p>Your password for username <b>{$username}</b> has been <b>successfully updated</b>.</p>
                        <p>You can now log in using your new password.</p>
                        <p>If this was <b>not you</b>, please log in immediately and update your details for security.</p>
                        <p>Thank you,<br><b>Rvr Book Store Team</b></p>
                    ";
                    $mail->AltBody = "Hi {$fullName}, your password for username {$username} has been successfully updated. 
                    You can now log in with your new password. If this wasn’t you, please log in and update your details immediately. 
                    Thank you - Rvr Book Store Team.";

             
                } catch (Exception $e) {
                    echo "❌ Mailer Error: " . $mail->ErrorInfo;
                }

            } else {
                $message = "Failed to update password.";
            }
            $stmt2->close();
        } else {
            $message = "User not found.";
            $stmt->close();
        }
    }
}

// Handle AJAX check
if (isset($_GET['check_field']) && isset($_GET['value'])) {
    $field = $_GET['check_field'];
    $value = trim($_GET['value']);
    if (!in_array($field, ['username', 'email', 'phone'])) {
        echo json_encode(["status" => "invalid"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT $field FROM users WHERE $field = ?");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode(["status" => "exists"]);
    } else {
        echo json_encode(["status" => "available"]);
    }
    $stmt->close();
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Password</title>
<style>
        /* Your existing CSS */
        body {
            font-family: 'Poppins', sans-serif;
            background: #111;
            color: white;
            margin: 0;
        }
  .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -2;
        }
label {
    display: block;
    text-align: left;
    font-size: 1.2rem;
    margin-top: 12px;
    margin-bottom: 4px;
    color: blue;
    text-shadow: 0 0 4px #ffde59;
}

    .update-box {
    background: linear-gradient(
        135deg,
        #ff0000, #ff7f00, #ffff00, #7fff00, #00ff00
           );
    border-radius: 10px;
    width: 400px;
    margin: 100px auto;
    padding: 30px;
    text-align: center;
    box-shadow: 0 0 10px #0ff, 0 0 20px #0ff, 0 0 30px #0ff;
    color: white;
}

        h2 {
            margin-bottom: 20px;
            color: #ffde59;
            text-shadow: 0 0 5px #ffde59, 0 0 10px #ffde59;
        }
        input, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 6px;
            border: none;
            font-size: 1rem;
        }
        input {
            background: #fff;
            color: #333;
        }
        button {
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white;
            cursor: pointer;
            box-shadow: 0 0 10px #ff512f, 0 0 20px #dd2476;
            transition: 0.3s;
        }
        button:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
            box-shadow: 0 0 20px #dd2476, 0 0 40px #ff512f;
        }
        .message {
            font-size: 0.9rem;
            margin-top: 10px;
            color: #0ff;
            text-shadow: 0 0 5px #0ff, 0 0 10px #0ff;
        }/* Neon rectangular button */
.neon-btn {
  display: inline-block;
  padding: 10px 18px;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 700;
  letter-spacing: 0.2px;
  color: #0f0f0f;
  background: linear-gradient(90deg, #ff512f 0%, #dd2476 100%);
  border: 1px solid rgba(255,255,255,0.12);

  /* neon glow */
  box-shadow:
    0 0 6px rgba(221,36,118,0.45),
    0 0 18px rgba(255,81,47,0.20),
    0 6px 18px rgba(0,0,0,0.6);

  transition: transform .12s ease, box-shadow .12s ease;
  -webkit-tap-highlight-color: transparent;
}

/* Hover and focus styles (stronger glow and slight lift) */
.neon-btn:hover,
.neon-btn:focus {
  transform: translateY(-3px);
  box-shadow:
    0 0 12px rgba(221,36,118,0.65),
    0 0 32px rgba(255,81,47,0.35),
    0 8px 26px rgba(0,0,0,0.65);
  outline: none;
}

/* Keyboard-visible focus ring for accessibility */
.neon-btn:focus-visible {
  box-shadow:
    0 0 14px rgba(221,36,118,0.8),
    0 0 36px rgba(255,81,47,0.45),
    0 10px 30px rgba(0,0,0,0.7);
  border: 1px solid rgba(255,255,255,0.25);
}

/* Smaller devices: keep it readable */
@media (max-width:420px) {
  .neon-btn { padding: 8px 12px; font-size: 0.95rem; }
}

        a {
            display: inline-block;
            margin-top: 10px;
            text-decoration: none;
            color: #0ff;
                        transition: 0.3s;
        }
        a:hover {
            color: #fff;
                    }
        .password-rules {
            text-align: left;
            font-size: 0.8rem;
            color: yellow;
            margin-top: -5px;
            margin-bottom: 10px;
        }
        .password-rules li {
            list-style: none;
        }
        .valid { color: blue; }
        .invalid { color: red; }
    </style>
</head>
<body>
<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<!-- Neon Back to Login button (replace your old small link with this) -->
<div style="text-align:right; padding: 12px 18px;">
  <a href="login.php" class="neon-btn" aria-label="Back to Login">⬅ Back to Login</a>
</div>

<div class="update-box">
    <?php if ($message): ?>
        <p class="message"><?php echo $message; ?></p>
    <?php endif; ?>
   <form method="post" onsubmit="return validateForm()">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" 
           value="<?php echo htmlspecialchars($username); ?>" readonly>

    <label for="password">New Password</label>
    <input type="password" id="password" name="password" 
           placeholder="Enter New Password" required>
    <ul class="password-rules" id="passwordRules">
        <li id="length" class="invalid">• At least 8 characters</li>
        <li id="lowercase" class="invalid">• At least one lowercase letter</li>
        <li id="uppercase" class="invalid">• At least one uppercase letter</li>
        <li id="number" class="invalid">• At least one number</li>
        <li id="special" class="invalid">• At least one special character</li>
    </ul>

    <label for="confirm_password">Confirm New Password</label>
    <input type="password" id="confirm_password" name="confirm_password" 
           placeholder="Confirm New Password" required>

    <button type="submit">Update Password</button>
</form>

</div>

<script>
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const rules = {
        length: document.getElementById('length'),
        lowercase: document.getElementById('lowercase'),
        uppercase: document.getElementById('uppercase'),
        number: document.getElementById('number'),
        special: document.getElementById('special')
    };

    passwordInput.addEventListener('input', function () {
        const value = passwordInput.value;
        rules.length.className = value.length >= 8 ? 'valid' : 'invalid';
        rules.lowercase.className = /[a-z]/.test(value) ? 'valid' : 'invalid';
        rules.uppercase.className = /[A-Z]/.test(value) ? 'valid' : 'invalid';
        rules.number.className = /\d/.test(value) ? 'valid' : 'invalid';
        rules.special.className = /[\W_]/.test(value) ? 'valid' : 'invalid';
    });

    function validateForm() {
        if (passwordInput.value !== confirmPasswordInput.value) {
            alert("Passwords do not match!");
            return false;
        }
        const allValid = Object.values(rules).every(rule => rule.classList.contains('valid'));
        if (!allValid) {
            alert("Password does not meet all the requirements!");
            return false;
        }
        return true;
    }
</script>
</body>
</html>
