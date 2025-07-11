<?php
session_start();
require_once 'database.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}

// Get customer ID from users table
try {
    $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) die("Customer not found");
    $customerId = $customer['customer_id'];
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$flat_id = isset($_GET['flat_id']) ? (int)$_GET['flat_id'] : 0;

// Fetch flat details
try {
    $stmt = $pdo->prepare("
        SELECT f.*, o.name AS owner_name 
        FROM flats f
        JOIN owners o ON f.owner_id = o.owner_id
        WHERE f.flat_id = :flat_id AND f.is_approved = 1
    ");
    $stmt->execute(['flat_id' => $flat_id]);
    $flat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$flat) die("Flat not found or not approved");
} catch (PDOException $e) {
    die("Error fetching flat: " . $e->getMessage());
}

// Fetch available slots (not expired or taken)
$currentDate = date('Y-m-d');
try {
    $stmt = $pdo->prepare("
        SELECT * FROM appointment_slots 
        WHERE flat_id = :flat_id 
          AND slot_date >= :current_date
          AND status = 'available'
        ORDER BY slot_date, start_time
    ");
    $stmt->execute([
        'flat_id' => $flat_id,
        'current_date' => $currentDate
    ]);
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching slots: " . $e->getMessage());
}

// Handle booking request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slot_id'])) {
    $slot_id = (int)$_POST['slot_id'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Create appointment request
        $stmt = $pdo->prepare("
            INSERT INTO appointment_requests 
            (slot_id, customer_id, request_status) 
            VALUES (:slot_id, :customer_id, 'pending')
        ");
        $stmt->execute([
            'slot_id' => $slot_id,
            'customer_id' => $customerId
        ]);
        $request_id = $pdo->lastInsertId();
        
        // Update slot status
        $stmt = $pdo->prepare("
            UPDATE appointment_slots 
            SET status = 'booked' 
            WHERE slot_id = :slot_id
        ");
        $stmt->execute(['slot_id' => $slot_id]);
        
        // Create notification message
        $messageSubject = "New Appointment Request";
        $messageBody = "A customer has requested to view your flat (Ref: {$flat['ref_number']})";
        
        $stmt = $pdo->prepare("
            INSERT INTO messages 
            (sender_id, sender_type, receiver_id, receiver_type, subject, message_body, message_type, flat_id, related_request) 
            VALUES (:sender_id, 'customer', :receiver_id, 'owner', :subject, :message_body, 'appointment_request', :flat_id, :request_id)
        ");
        $stmt->execute([
            'sender_id' => $customerId,
            'receiver_id' => $flat['owner_id'],
            'subject' => $messageSubject,
            'message_body' => $messageBody,
            'flat_id' => $flat_id,
            'request_id' => $request_id
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success'] = "Appointment request sent! Waiting for owner's confirmation.";
        header("Location: view_messages.php");
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Booking failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Appointment - <?= htmlspecialchars($flat['ref_number']) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <main class="container">
        <h1>Request Viewing Appointment</h1>
        <h2>Flat: <?= htmlspecialchars($flat['ref_number']) ?> - <?= htmlspecialchars($flat['location']) ?></h2>
        
        <?php if (isset($error)): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (empty($slots)): ?>
            <div class="alert info">No available time slots for this flat</div>
        <?php else: ?>
            <table class="appointment-slots">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Contact</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slots as $slot): ?>
                        <tr>
                            <td><?= htmlspecialchars($slot['slot_date']) ?></td>
                            <td>
                                <?= substr($slot['start_time'], 0, 5) ?> - 
                                <?= substr($slot['end_time'], 0, 5) ?>
                            </td>
                            <td><?= htmlspecialchars($slot['contact_number']) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="slot_id" value="<?= $slot['slot_id'] ?>">
                                    <button type="submit" class="btn-book">Book Slot</button>
                                </form>
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