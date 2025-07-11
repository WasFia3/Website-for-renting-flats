<?php
session_start();

// التحقق من أن المستخدم مسجل دخوله كملاك
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'owner') {
    header("Location: login.php");
    exit();
}

require_once 'database.php';

$owner_id = $_SESSION['owner_id'];

// جلب جميع الرسائل الموجهة لهذا المالك مع تفاصيل إضافية للطلبات
$stmt = $pdo->prepare("
    SELECT m.*, f.ref_number as flat_ref, c.name as customer_name, 
           c.customer_id, ar.request_id, ar.request_status
    FROM messages m
    JOIN flats f ON m.flat_id = f.flat_id
    LEFT JOIN customers c ON m.sender_id = c.customer_id AND m.sender_type = 'customer'
    LEFT JOIN appointment_requests ar 
      ON m.related_request = ar.request_id 
      AND ar.customer_id = m.sender_id
    WHERE m.receiver_id = :owner_id AND m.receiver_type = 'owner'
    ORDER BY m.created_at DESC
");

$stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تحديث حالة الرسائل إلى مقروءة
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = :owner_id AND receiver_type = 'owner'")
    ->execute([':owner_id' => $owner_id]);

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Messages</title>
    <link rel="stylesheet" href="styles.css">
       
    <!-- رابط لأيقونات Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <main class="messages-container">
        <h1>My Messages</h1>
        
        <?php if (count($messages) > 0): ?>
            <?php foreach ($messages as $message): ?>
                <div class="message-card <?= $message['is_read'] ? '' : 'unread' ?>">
                    <div class="message-header">
                        <div>
                            <span class="message-subject">
                                <?= htmlspecialchars($message['subject']) ?>
                                <span class="message-type type-<?= str_replace('_', '-', $message['message_type']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $message['message_type'])) ?>
                                </span>
                                <?php if (isset($message['request_status'])): ?>
                                    <span class="request-status status-<?= $message['request_status'] ?>">
                                        <?= ucfirst($message['request_status']) ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                            <div class="message-meta">
                                From: 
                                <?= $message['sender_type'] == 'customer' && isset($message['customer_name']) 
                                    ? htmlspecialchars($message['customer_name']) 
                                    : 'System' ?>
                                | 
                                Flat: <?= htmlspecialchars($message['flat_ref']) ?> | 
                                Date: <?= date('Y-m-d H:i', strtotime($message['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="message-body">
                        <?= nl2br(htmlspecialchars($message['message_body'])) ?>
                    </div>
                    
<div class="message-actions">
    <?php if ($message['message_type'] == 'appointment_request'): ?>
        <?php if (!isset($message['request_status']) || $message['request_status'] == 'pending'): ?>
            <a href="approve.php?request_id=<?= $message['related_request'] ?>" class="btn btn-approve">
                <i class="fas fa-check"></i> Approve
            </a>
            <a href="reject.php?request_id=<?= $message['related_request'] ?>" class="btn btn-reject">
                <i class="fas fa-times"></i> Reject
            </a>
        <?php else: ?>
            <span class="btn btn-disabled">
                <?= ucfirst($message['request_status']) ?>
            </span>
        <?php endif; ?>
    <?php endif; ?>
</div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-messages">
                <p>You have no messages yet.</p>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>