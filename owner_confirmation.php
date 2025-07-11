<?php
session_start();

// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect with session check
if (!isset($_SESSION['step1']) || !isset($_SESSION['step2'])) {
    $_SESSION['redirect_reason'] = "Session data expired or missing";
    header("Location: owner_eaccount.php");
    exit();
}

$step1 = $_SESSION['step1'];
$step2 = $_SESSION['step2'];
$errorMsg = '';
$confirmationMessage = '';

require_once 'database.php'; // Ensure this file exists

function generateOwnerID($pdo) {
    do {
        $newID = mt_rand(100000000, 999999999);
        $stmt = $pdo->prepare("SELECT owner_id FROM owners WHERE owner_id = ?");
        $stmt->execute([$newID]);
    } while ($stmt->rowCount() > 0);
    return $newID;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role)
            VALUES (?, ?, ?, 'owner')
        ");
        $stmt->execute([
            $step2['username'],
            $step1['email'],
            $step2['password'] // Should be pre-hashed
            
        ]);
        $userId = $pdo->lastInsertId();

        // Handle owner registration
            $ownerId = generateOwnerID($pdo);
            $address = $step1['street_name'] . ', ' . $step1['city'];

            $stmt = $pdo->prepare("
                INSERT INTO owners (
                    owner_id, user_id, national_id, name, address, 
                    postal_code, date_of_birth, email, mobile, telephone,
                    street_name, city, bank_name, bank_branch, account_number
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $ownerId,
                $userId,
                $step1['national_id'],
                $step1['name'],
                $address,
                $step1['postal_code'],
                $step1['date_of_birth'],
                $step1['email'],
                $step1['mobile'],
                $step1['telephone'] ?? null,
                $step1['street_name'],
                $step1['city'],
                $step1['bank_name'],
                $step1['bank_branch'],
                $step1['account_number']
            ]);

            $confirmationMessage = "Registration Successful!<br><br>
                Thank you {$step1['name']}, your registration is complete.<br>
                Your Owner ID is: <strong>$ownerId</strong>";
        

        $pdo->commit();
        unset($_SESSION['step1'], $_SESSION['step2']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        $errorMsg = "Database Error: " . $e->getMessage();
    } catch (Exception $e) {
        $errorMsg = "System Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Confirmation</title>
           <link rel="stylesheet" href="styles.css">

</head>
<body>
    <div class="container">

        <?php if ($errorMsg): ?>
            <div class="error"><?= $errorMsg ?></div>
        <?php endif; ?>

        <?php if ($confirmationMessage): ?>
            <div class="success"><?= $confirmationMessage ?></div>
            <p>Save this ID for future transactions.</p>
            <a href="login.php"><button>Proceed to Login</button></a>
        <?php else: ?>
            <div>
                <h1>Registration Confirmation</h1>
                <h2>Review Your Information</h2>
                <h3>Personal Details:</h3>
                <p><strong>Name:</strong> <?= htmlspecialchars($step1['name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($step1['email']) ?></p>
                <p><strong>ID Number:</strong> <?= htmlspecialchars($step1['national_id']) ?></p>
                <p><strong>Address:</strong> <?= htmlspecialchars($step1['street_name'] . ', ' . $step1['city']) ?></p>
                
                <h3>Account Details:</h3>
                <p><strong>Username:</strong> <?= htmlspecialchars($step2['username']) ?></p>
                
                <form method="POST">
                <button type="submit">Confirm Registration</button>
            </form>
            
            </div>

            
        <?php endif; ?>
    </div>
</body>
</html>