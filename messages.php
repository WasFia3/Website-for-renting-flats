<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Determine receiver ID based on role
$receiver_id = null;
$receiver_type = '';

switch ($_SESSION['role']) {
    case 'customer':
        $receiver_type = 'customer';
        $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $result = $stmt->fetch();
        $receiver_id = $result['customer_id'];
        break;
        
    case 'owner':
        $receiver_type = 'owner';
        $stmt = $pdo->prepare("SELECT owner_id FROM owners WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $result = $stmt->fetch();
        $receiver_id = $result['owner_id'];
        break;
        
    case 'manager':
        $receiver_type = 'manager';
        $receiver_id = $_SESSION['user_id']; // Using user_id for manager
        break;
}

// Fetch messages with filter support
try {
    // Base query
    $query = "
        SELECT m.*, 
               CASE 
                 WHEN m.sender_type = 'customer' THEN c.name
                 WHEN m.sender_type = 'owner' THEN o.name
                 WHEN m.sender_type = 'manager' THEN 'Management Team'
                 ELSE 'System Notification'
               END AS sender_name,
               f.flat_id AS flat_id
        FROM messages m
        LEFT JOIN customers c ON m.sender_id = c.customer_id AND m.sender_type = 'customer'
        LEFT JOIN owners o ON m.sender_id = o.owner_id AND m.sender_type = 'owner'
        LEFT JOIN flats f ON m.flat_id = f.flat_id
        WHERE m.receiver_id = :receiver_id AND m.receiver_type = :receiver_type
    ";
    
    // Add filter conditions based on the selected filter
    $filter = $_GET['filter'] ?? 'all';
    switch ($filter) {
        case 'appointments':
            $query .= " AND (m.message_type = 'appointment_request' OR m.message_type = 'appointment_response')";
            break;
        case 'rent_requests':
            $query .= " AND m.message_type = 'rent_confirmation'";
            break;
        // 'all' case shows all messages
    }
    
    $query .= " ORDER BY m.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'receiver_id' => $receiver_id,
        'receiver_type' => $receiver_type
    ]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching messages: " . $e->getMessage());
}

// Mark messages as read
if (!empty($messages) && !isset($_GET['keep_unread'])) {
    $message_ids = implode(',', array_column($messages, 'message_id'));
    $pdo->exec("UPDATE messages SET is_read = 1 WHERE message_id IN ($message_ids)");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages</title>
    <link rel="stylesheet" href="styles.css">
       <link rel="stylesheet" href="styles.css">

</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <main class="container">
        <h1>My Messages</h1>
        
        <div class="message-filters">
            <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active-filter' : ''; ?>">All</a>
            <a href="?filter=appointments" class="filter-btn <?php echo $filter === 'appointments' ? 'active-filter' : ''; ?>">Appointments</a>
            <a href="?filter=rent_requests" class="filter-btn <?php echo $filter === 'rent_requests' ? 'active-filter' : ''; ?>">Rent Requests</a>
        </div>
        
        <?php if (empty($messages)): ?>
            <div class="alert info">No messages found</div>
        <?php else: ?>
            <table class="messages-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Flat Reference</th>
                        <th>Related Request</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                        <tr class="<?= $msg['is_read'] ? 'read' : 'unread' ?>">
                            <td><?= date('Y-m-d H:i', strtotime($msg['created_at'])) ?></td>
                            <td><?= htmlspecialchars($msg['sender_name']) ?></td>
                            <td>
                                <?php if (!$msg['is_read']): ?>
                                    <span class="msg-icon-unread">✉️</span>
                                <?php endif; ?>
                                <?= htmlspecialchars($msg['subject']) ?>
                                <?php if ($msg['is_important']): ?>
                                    <span class="msg-icon-important">❗</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($msg['flat_id'])): ?>
                                    <a href="flat_details.php?id=<?= $msg['flat_id'] ?>" class="msg-link" target="_blank">
                                        <?= $msg['flat_id'] ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($msg['related_request'])): ?>
                                    <a href="view_request.php?id=<?= $msg['related_request'] ?>" class="msg-link" target="_blank">
                                        Request #<?= $msg['related_request'] ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view_message.php?id=<?= $msg['message_id'] ?>" class="msg-btn msg-btn-view">View</a>
                                <?php if ($msg['message_type'] == 'appointment_request'): ?>
                                    <a href="respond_appointment.php?message_id=<?= $msg['message_id'] ?>" class="msg-btn msg-btn-respond">Respond</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
    
    <?php include 'footer.php'; ?>
</body>
</html>