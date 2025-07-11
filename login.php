<?php
require_once "database.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute([':username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    // فقط إذا كان الدور "customer"، نجيب customer_id
    if ($user['role'] === 'customer') {
        $stmt2 = $pdo->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
        $stmt2->execute([$user['user_id']]);
        $customer = $stmt2->fetch(PDO::FETCH_ASSOC);
        $_SESSION['customer_id'] = $customer['customer_id'] ?? null;
    } 
    
      if ($user['role'] === 'owner') {
        $stmt2 = $pdo->prepare("SELECT owner_id FROM owners WHERE user_id = ?");
        $stmt2->execute([$user['user_id']]);
        $owner = $stmt2->fetch(PDO::FETCH_ASSOC);
        $_SESSION['owner_id'] = $owner['owner_id'] ?? null;

    } 
    
    
    header("Location: homepage.php");
    exit;


        } else {
            echo "Invalid username or password.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="login-container">
    <h2>Login to your account</h2>

    <?php if (isset($error)) : ?>
        <p class="error">Error: <?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required />
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required />
        </div>
        <input type="submit" value="Login" />
    </form>

    <p>New user? <a href="register.php">Register</a></p>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
