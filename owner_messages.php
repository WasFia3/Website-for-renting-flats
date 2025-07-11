<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['owner_id'])) {
    die("Error: Owner not logged in");
}

// Check for cookies first, then URL parameters
if (isset($_COOKIE['message_sort_prefs'])) {
    $cookiePrefs = explode(':', $_COOKIE['message_sort_prefs']);
    $defaultSort = $cookiePrefs[0] ?? 'created_at';
    $defaultOrder = $cookiePrefs[1] ?? 'DESC';
} else {
    $defaultSort = 'created_at';
    $defaultOrder = 'DESC';
}

// Get sorting parameters from request (overrides cookies if present)
$sort = $_GET['sort'] ?? $defaultSort;
$order = $_GET['order'] ?? $defaultOrder;

// Validate sort column and order
$validSortColumns = ['created_at', 'subject', 'sender_name', 'flat_id'];
if (!in_array($sort, $validSortColumns)) {
    $sort = 'created_at';
}
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Update cookie with current sorting preferences (30 days expiration)
setcookie('message_sort_prefs', "$sort:$order", time() + (86400 * 30), "/");

try {
    if (!isset($_GET['keep_unread'])) {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = :owner_id AND receiver_type = 'owner'");
        $stmt->execute(['owner_id' => $_SESSION['owner_id']]);
    }
    
    // Base query
    $query = "
        SELECT m.*, 
               CASE 
                   WHEN m.sender_type = 'customer' THEN c.name
                   WHEN m.sender_type = 'manager' THEN 'Management Team'
                   WHEN m.sender_type = 'system' THEN 'System Notification'
                   ELSE 'Unknown Sender'
               END AS sender_name,
               f.flat_id AS flat_id,
               f.ref_number AS flat_ref
        FROM messages m
        LEFT JOIN customers c ON m.sender_type = 'customer' AND m.sender_id = c.customer_id
        LEFT JOIN flats f ON m.flat_id = f.flat_id
        WHERE m.receiver_id = :owner_id 
          AND m.receiver_type = 'owner'
    ";
    
    // Add filter conditions for all message types
    $filter = $_GET['filter'] ?? 'all';
    switch ($filter) {
        case 'appointments':
            $query .= " AND (m.message_type = 'appointment_request' OR m.message_type = 'appointment_response')";
            break;
        case 'rent_requests':
            $query .= " AND m.message_type = 'rent_confirmation'";
            break;
        case 'approvals':
            $query .= " AND m.message_type = 'flat_approval'";
            break;
        case 'general':
            $query .= " AND m.message_type = 'general'";
            break;
        case 'system':
            $query .= " AND m.sender_type = 'system'";
            break;
        // 'all' case shows all messages
    }
    
    // Add sorting
    $query .= " ORDER BY $sort $order";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['owner_id' => $_SESSION['owner_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to generate sort link
function sortLink($column, $label) {
    global $sort, $order, $filter;
    
    $newOrder = ($sort === $column && $order === 'DESC') ? 'ASC' : 'DESC';
    $arrow = '';
    
    if ($sort === $column) {
        $arrow = $order === 'ASC' ? ' ▲' : ' ▼';
    }
    
    $url = "?filter=$filter&sort=$column&order=$newOrder";
    
    return "<a href=\"$url\" class=\"sort-link\">$label$arrow</a>";
}

// Function to get appropriate action button based on message type
function getActionButton($message) {
    switch ($message['message_type']) {
        case 'appointment_request':
            return '<a href="owner_respond_msg.php?message_id='.$message['message_id'].'" class="msg-btn msg-btn-respond">Respond</a>';
        case 'flat_approval':
            return '<a href="approve_flat.php?flat_id='.$message['flat_id'].'" class="msg-btn msg-btn-approve">Review</a>';
        case 'rent_confirmation':
            return '<a href="rent_details.php?rental_id='.$message['related_request'].'" class="msg-btn msg-btn-details">Details</a>';
        default:
            return '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Messages</title>
    <link rel="stylesheet" href="styles.css">
   
</head>
<body>
<?php include 'navbar.php'; ?>
    
    <main class="content">
        <h1 class="messages-title">My Messages</h1>
        
        <div class="messages-wrapper">
            <?php if (isset($_GET['sort']) || isset($_GET['order'])): ?>
                <div class="cookie-notice">
                    Your sorting preferences have been saved and will be remembered for future visits.
                </div>
            <?php endif; ?>
            
            <div class="message-filters">
                <a href="?filter=all&sort=<?= $sort ?>&order=<?= $order ?>" class="filter-btn <?= $filter === 'all' ? 'active-filter' : '' ?>">All</a>
                <a href="?filter=appointments&sort=<?= $sort ?>&order=<?= $order ?>" class="filter-btn <?= $filter === 'appointments' ? 'active-filter' : '' ?>">Appointments</a>
                <a href="?filter=rent_requests&sort=<?= $sort ?>&order=<?= $order ?>" class="filter-btn <?= $filter === 'rent_requests' ? 'active-filter' : '' ?>">Rent Requests</a>
                <a href="?filter=approvals&sort=<?= $sort ?>&order=<?= $order ?>" class="filter-btn <?= $filter === 'approvals' ? 'active-filter' : '' ?>">Approvals</a>
                <a href="?filter=general&sort=<?= $sort ?>&order=<?= $order ?>" class="filter-btn <?= $filter === 'general' ? 'active-filter' : '' ?>">General</a>
                <a href="?filter=system&sort=<?= $sort ?>&order=<?= $order ?>" class="filter-btn <?= $filter === 'system' ? 'active-filter' : '' ?>">System</a>
            </div>

            <table class="messages-table">
                <thead>
                    <tr>
                        <th class="msg-date"><?= sortLink('created_at', 'Date') ?></th>
                        <th class="msg-sender"><?= sortLink('sender_name', 'From') ?></th>
                        <th class="msg-subject"><?= sortLink('subject', 'Subject') ?></th>
                        <th class="msg-flat"><?= sortLink('flat_id', 'Flat ID') ?></th>
                        <th class="msg-request">Related Request</th>
                        <th class="msg-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="6" class="no-messages">No messages found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                        <tr class="message-row <?= $message['is_read'] ? 'read' : 'unread' ?>">
                            <td class="msg-date"><?= date('Y-m-d H:i', strtotime($message['created_at'])) ?></td>
                            <td class="msg-sender"><?= htmlspecialchars($message['sender_name']) ?></td>
                            <td class="msg-subject">
                                <?php if (!$message['is_read']): ?>
                                    <span class="msg-icon-unread">✉️</span>
                                <?php endif; ?>
                                <?= htmlspecialchars($message['subject']) ?>
                                <span class="message-type type-<?= $message['message_type'] ?>">
                                    <?= str_replace('_', ' ', $message['message_type']) ?>
                                </span>
                                <?php if ($message['is_important']): ?>
                                    <span class="msg-icon-important">❗</span>
                                <?php endif; ?>
                            </td>
                            <td class="msg-flat">
                                <?php if ($message['flat_id']): ?>
                                    <a href="flat_details.php?id=<?= $message['flat_id'] ?>" class="msg-link" target="_blank">
                                        <?= $message['flat_ref'] ?? $message['flat_id'] ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="msg-request">
                                <?php if ($message['related_request']): ?>
                                    <a href="view_request.php?id=<?= $message['related_request'] ?>" class="msg-link" target="_blank">
                                        Request #<?= $message['related_request'] ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="msg-actions">
                                <a href="owner_view_msg.php?id=<?= $message['message_id'] ?>" class="msg-btn msg-btn-view">View</a>
                                <?= getActionButton($message) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <?php include 'footer.php'; ?>

</body>
</html>