<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['step1']) || !isset($_SESSION['step2'])) {
    header("Location: step1.php");
    exit();
}

$step1 = $_SESSION['step1'];
$step2 = $_SESSION['step2'];
$errorMsg = '';
$confirmationMessage = '';

require_once 'database.php';

function generateUniqueID($pdo, $table, $idColumn) {
    do {
        $newID = mt_rand(100000000, 999999999);
        $stmt = $pdo->prepare("SELECT $idColumn FROM $table WHERE $idColumn = ?");
        $stmt->execute([$newID]);
    } while ($stmt->rowCount() > 0);
    
    return $newID;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        $role = $_SESSION['role'] ?? null;

        $hashedPassword = $step2['password'];

        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $step2['username'],
            $step1['email'],
            $hashedPassword,
            $role
        ]);

        $userId = $pdo->lastInsertId();

        $address = $step1['flat_house_number'] . ', ' . $step1['street_name'] . ', ' . $step1['city'];

        if ($role === 'customer') {
            $customerId = generateUniqueID($pdo, 'customers', 'customer_id');

            $stmt = $pdo->prepare("
                INSERT INTO customers (
                    customer_id, user_id, national_id, name, 
                    flat_house_number, address, postal_code, date_of_birth, 
                    email, mobile, telephone, street_name, city
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $customerId,
                $userId,
                $step1['idNumber'],
                $step1['name'],
                $step1['flat_house_number'],
                $address,
                $step1['postal_code'],
                $step1['date_of_birth'],
                $step1['email'],
                $step1['mobile'],
                $step1['telephone'] ?? null,
                $step1['street_name'],
                $step1['city']
            ]);
        } elseif ($role === 'owner') {
            $ownerId = generateUniqueID($pdo, 'owners', 'owner_id');

            $stmt = $pdo->prepare("
                INSERT INTO owners (
                    owner_id, user_id, national_id, name, 
                    flat_house_number, address, postal_code, date_of_birth, 
                    email, mobile, telephone, street_name, city,
                    bank_name, bank_branch, account_number
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $ownerId,
                $userId,
                $step1['idNumber'],
                $step1['name'],
                $step1['flat_house_number'],
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
        }

        $pdo->commit();

        unset($_SESSION['step1']);
        unset($_SESSION['step2']);
        unset($_SESSION['role']);

        if ($role === 'customer') {
            $confirmationMessage = "Registration Successful! Thank you {$step1['name']}. Your Customer ID is: $customerId. Save it.";
        } elseif ($role === 'owner') {
            $confirmationMessage = "Registration Successful! Thank you {$step1['name']}. Your Owner ID is: $ownerId. Save it.";
        } else {
            $confirmationMessage = "Registration Successful! Thank you {$step1['name']}.";
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errorMsg = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $errorMsg = "Error processing registration: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Confirmation</title>
    <link rel="stylesheet" href="styles.css">
    
     <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

       
        form {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
        }


        button:hover {
            background-color: #45a049;
        }
    </style>

</head>
<body>
    
    <section>
   
   <div>
        <form method="post" action="">
                <?php include 'navbar.php'; ?>

            
             <h2>Review and Confirm Registration</h2>
    <?php if (!empty($errorMsg)): ?>
        <div><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <?php if (!empty($confirmationMessage)): ?>
        <p><?= $confirmationMessage ?></p>
        <p><a href="homepage.php">Return to Home</a></p>
    <?php else: ?>
            <h3>Personal Information</h3>
            <p><strong>Full Name:</strong> <?= htmlspecialchars($step1['name']) ?></p>
            <p><strong>National ID:</strong> <?= htmlspecialchars($step1['idNumber']) ?></p>
            <p><strong>Address:</strong> 
                <?= htmlspecialchars($step1['flat_house_number']) ?>, 
                <?= htmlspecialchars($step1['street_name']) ?>, 
                <?= htmlspecialchars($step1['city']) ?>, 
                <?= htmlspecialchars($step1['postal_code']) ?>
            </p>
            <p><strong>Date of Birth:</strong> <?= htmlspecialchars($step1['date_of_birth']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($step1['email']) ?></p>
            <p><strong>Mobile Number:</strong> <?= htmlspecialchars($step1['mobile']) ?></p>
            <p><strong>Telephone:</strong> <?= htmlspecialchars($step1['telephone'] ?? 'N/A') ?></p>

            <h3>Account Information</h3>
            <p><strong>Username:</strong> <?= htmlspecialchars($step2['username']) ?></p>
            <p><strong>Password:</strong> •••••••• (hidden)</p>

            <button type="submit">Confirm Registration</button>
        </form>
        </div>
        </section>
    <?php endif; ?>

    <?php include 'footer.php'; ?>
</body>
</html>
