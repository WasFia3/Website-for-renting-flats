<?php
session_start();

// Redirect to step 1 if step1 data is missing
if (!isset($_SESSION['step1'])) {
    header("Location: owner_registration.php");
    exit();
}

$errorMsg = '';

// Check email uniqueness function
function isEmailUnique($email) {
    $filename = 'registered_emails.txt';
    if (!file_exists($filename)) return true;

    $emails = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return !in_array($email, $emails);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Sanitize username for output only (not for storage)
        $safe_username = htmlspecialchars($username);

        // Validate email format
        if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Username must be a valid email address.");
        }

        // Check email uniqueness
        if (!isEmailUnique($username)) {
            throw new Exception("This email is already registered. Please use a different email.");
        }

        // Validate password length
        if (strlen($password) < 6 || strlen($password) > 15) {
            throw new Exception("Password must be between 6-15 characters.");
        }

        // Validate password starts with digit
        if (!ctype_digit($password[0])) {
            throw new Exception("Password must start with a digit.");
        }

        // Validate password ends with lowercase letter
        if (!ctype_lower(substr($password, -1))) {
            throw new Exception("Password must end with a lowercase letter.");
        }

        // Confirm password match
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }

        // Store in session
        $_SESSION['step2'] = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];

        // Redirect to confirmation page
        header("Location: owner_confirmation.php");
        exit();

    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create E-Account</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="registration-page">
        <section class="registration-container">
            <h2>Create E-Account</h2>

            <?php if (!empty($errorMsg)): ?>
                <div class="error-message" style="color: red; margin-bottom: 15px;">
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="" class="registration-form">
                <div class="form-group">
                    <label for="username">Username (Email)<span>*</span>:</label>
                    <input type="email" name="username" id="username" 
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="password">Password<span>*</span>:</label>
                    <input type="password" name="password" id="password" 
                           pattern="^\d.*[a-z]$" 
                           title="Must start with digit and end with lowercase letter (6-15 characters)" 
                           required>
                    <small>Must be 6-15 characters, start with a digit, and end with a lowercase letter</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password<span>*</span>:</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>

                <button type="submit">Create Account</button>
            </form>
        </section>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        // Client-side password validation
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            if (password.length > 0) {
                if (password.length < 6 || password.length > 15) {
                    this.setCustomValidity("Password must be between 6-15 characters.");
                } else if (!/^\d/.test(password)) {
                    this.setCustomValidity("Password must start with a digit.");
                } else if (!/[a-z]$/.test(password)) {
                    this.setCustomValidity("Password must end with a lowercase letter.");
                } else {
                    this.setCustomValidity("");
                }
            } else {
                this.setCustomValidity("");
            }
        });

        // Confirm password validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const confirmPassword = this.value;
            const password = document.getElementById('password').value;
            if (confirmPassword !== password) {
                this.setCustomValidity("Passwords do not match.");
            } else {
                this.setCustomValidity("");
            }
        });
    </script>
</body>
</html>
