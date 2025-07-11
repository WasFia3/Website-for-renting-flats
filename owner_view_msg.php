<?php
session_start();
require_once 'database.php';

// التحقق من تسجيل دخول المالك
if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}

// التحقق من وجود معرّف الرسالة
if (!isset($_GET['id'])) {
    die("Error: Message ID not specified");
}

$message_id = $_GET['id'];

try {
    // جلب بيانات الرسالة
    $stmt = $pdo->prepare("
        SELECT m.*, 
               CASE 
                   WHEN m.sender_type = 'customer' THEN c.name
                   WHEN m.sender_type = 'manager' THEN 'Management Team'
                   WHEN m.sender_type = 'system' THEN 'System Notification'
                   ELSE 'Unknown Sender'
               END AS sender_name,
               f.ref_number AS flat_ref,
               f.location AS flat_location,
               f.address AS flat_address
        FROM messages m
        LEFT JOIN customers c ON m.sender_type = 'customer' AND m.sender_id = c.customer_id
        LEFT JOIN flats f ON m.flat_id = f.flat_id
        WHERE m.message_id = :message_id
        AND m.receiver_id = :owner_id
        AND m.receiver_type = 'owner'
    ");
    $stmt->execute(['message_id' => $message_id, 'owner_id' => $_SESSION['owner_id']]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        die("Error: Message not found or you don't have permission to view it");
    }

    // تحديث حالة الرسالة كمقروءة
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE message_id = :message_id");
    $stmt->execute(['message_id' => $message_id]);

    // جلب المواعيد المتاحة إذا كانت الرسالة طلب معاينة
    if ($message['message_type'] == 'appointment_request') {
        $stmt = $pdo->prepare("
            SELECT * FROM appointment_slots
            WHERE flat_id = :flat_id
            AND owner_id = :owner_id
            AND status = 'available'
            AND slot_date >= CURDATE()
            ORDER BY slot_date, start_time
        ");
        $stmt->execute(['flat_id' => $message['flat_id'], 'owner_id' => $_SESSION['owner_id']]);
        $available_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Message</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
   <main id="view-message-page" class="content">
    <h1 class="page-title">Message Details</h1>
    
    <div id="message-header" class="message-header">
        <h2><?= htmlspecialchars($message['subject']) ?></h2>
        <p class="message-meta">
            <strong>From:</strong> <?= htmlspecialchars($message['sender_name']) ?> |
            <strong>Date:</strong> <?= date('Y-m-d H:i', strtotime($message['created_at'])) ?>
        </p>
        
        <?php if ($message['flat_id']): ?>
            <p class="flat-info">
                <strong>Flat:</strong> 
                <a href="flat_details.php?id=<?= $message['flat_id'] ?>">
                    <?= htmlspecialchars($message['flat_ref']) ?> - <?= htmlspecialchars($message['flat_location']) ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
    
    <div id="message-body" class="message-body">
        <p><?= nl2br(htmlspecialchars($message['message_body'])) ?></p>
    </div>
    
    <?php if ($message['message_type'] == 'appointment_request'): ?>
        <div id="message-actions" class="message-actions">
            <h3 class="actions-title">Available Actions:</h3>
            
            <div class="action-buttons">
                <a href="respond_appointment.php?message_id=<?= $message_id ?>" class="btn btn-primary">Respond to Request</a>
                
                <?php if (!empty($available_slots)): ?>
                    <a href="appointment_slots.php?flat_id=<?= $message['flat_id'] ?>" class="btn btn-secondary">Manage Available Slots</a>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($message['message_type'] == 'rent_confirmation'): ?>
        <div id="message-actions" class="message-actions">
            <h3 class="actions-title">Available Actions:</h3>
            
            <div class="action-buttons">
                <a href="rent_details.php?rental_id=<?= $message['related_request'] ?>" class="btn btn-primary">View Rental Details</a>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="back-link">
        <a href="owner_messages.php" class="btn btn-secondary">&larr; Back to Messages</a>
    </div>
</main>

    <?php include 'footer.php'; ?>
</body>
</html>