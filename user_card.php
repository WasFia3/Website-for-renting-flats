<?php
require_once("database.php");

$type = $_GET['type'] ?? ''; // 'owner' أو 'customer'
$id = $_GET['id'] ?? 0;

// دالة لجلب بيانات المستخدم
function getUserCardData($type, $id) {
    global $pdo;
    
    if ($type === 'owner') {
        $sql = "SELECT o.name, o.city, o.mobile, o.telephone, u.email 
                FROM owners o 
                JOIN users u ON o.user_id = u.user_id 
                WHERE o.owner_id = ?";
    } else {
        $sql = "SELECT c.name, c.city, c.mobile, c.telephone, u.email 
                FROM customers c 
                JOIN users u ON c.user_id = u.user_id 
                WHERE c.customer_id = ?";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user card: " . $e->getMessage());
        return null;
    }
}

$user = getUserCardData($type, $id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Card</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php if ($user): ?>
        <div class="user-card">
            <h2><?= htmlspecialchars($user['name']) ?></h2>
            <p><?= htmlspecialchars($user['city']) ?></p>
            
            <div class="contact-info">
                <p>
                    <span class="icon">📱</span>
                    <?= htmlspecialchars($user['mobile']) ?>
                </p>
                <p>
                    <span class="icon">✉️</span>
                    <a href="mailto:<?= htmlspecialchars($user['email']) ?>">
                        <?= htmlspecialchars($user['email']) ?>
                    </a>
                </p>
            </div>
        </div>
    <?php else: ?>
        <p>User not found</p>
    <?php endif; ?>
</body>
</html>