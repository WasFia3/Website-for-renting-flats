<?php
session_start();
require_once 'database.php';

// التحقق من تسجيل دخول المالك
if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}

// التحقق من وجود معرّف الرسالة
if (!isset($_GET['message_id'])) {
    die("Error: Message ID not specified");
}

$message_id = $_GET['message_id'];

try {
    // جلب بيانات الرسالة
    $stmt = $pdo->prepare("
        SELECT m.*, c.name AS customer_name, c.mobile AS customer_mobile,
               f.ref_number AS flat_ref, f.location AS flat_location, f.address AS flat_address
        FROM messages m
        LEFT JOIN customers c ON m.sender_id = c.customer_id
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

    // جلب المواعيد المتاحة للشقة
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

    // معالجة إرسال النموذج
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $response_type = $_POST['response_type'];
        $response_note = $_POST['response_note'] ?? '';
        
        if ($response_type === 'approve') {
            // الموافقة على الموعد المطلوب
            $slot_id = $_POST['slot_id'];
            
            // تحديث حالة الطلب
            $stmt = $pdo->prepare("
                UPDATE appointment_requests
                SET request_status = 'approved',
                    owner_response_at = NOW()
                WHERE request_id = :request_id
            ");
            $stmt->execute(['request_id' => $message['related_request']]);
            
            // تحديث حالة الموعد
            $stmt = $pdo->prepare("
                UPDATE appointment_slots
                SET status = 'booked',
                    contact_number = :contact_number
                WHERE slot_id = :slot_id
            ");
            $stmt->execute([
                'slot_id' => $slot_id,
                'contact_number' => $_POST['contact_number']
            ]);
            
            // إرسال رسالة تأكيد للعميل
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, 
                                    subject, message_body, message_type, flat_id, related_request)
                VALUES (:sender_id, 'owner', :receiver_id, 'customer',
                        'Appointment Approved', :message_body, 'appointment_response', 
                        :flat_id, :related_request)
            ");
            $stmt->execute([
                'sender_id' => $_SESSION['owner_id'],
                'receiver_id' => $message['sender_id'],
                'message_body' => "Your appointment request has been approved. " . $response_note,
                'flat_id' => $message['flat_id'],
                'related_request' => $message['related_request']
            ]);
            
            $success_message = "Appointment approved successfully! Notification sent to the customer.";
            
        } elseif ($response_type === 'reject') {
            // رفض طلب المعاينة
            $stmt = $pdo->prepare("
                UPDATE appointment_requests
                SET request_status = 'rejected',
                    owner_response_at = NOW()
                WHERE request_id = :request_id
            ");
            $stmt->execute(['request_id' => $message['related_request']]);
            
            // إرسال رسالة رفض للعميل
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, 
                                    subject, message_body, message_type, flat_id, related_request)
                VALUES (:sender_id, 'owner', :receiver_id, 'customer',
                        'Appointment Rejected', :message_body, 'appointment_response', 
                        :flat_id, :related_request)
            ");
            $stmt->execute([
                'sender_id' => $_SESSION['owner_id'],
                'receiver_id' => $message['sender_id'],
                'message_body' => "Your appointment request has been rejected. Reason: " . $response_note,
                'flat_id' => $message['flat_id'],
                'related_request' => $message['related_request']
            ]);
            
            $success_message = "Appointment rejected. Notification sent to the customer.";
            
        } elseif ($response_type === 'reschedule') {
            // اقتراح مواعيد بديلة
            $new_slot_date = $_POST['new_slot_date'];
            $new_start_time = $_POST['new_start_time'];
            $new_end_time = $_POST['new_end_time'];
            $contact_number = $_POST['contact_number'];
            
            // إنشاء موعد جديد
            $stmt = $pdo->prepare("
                INSERT INTO appointment_slots 
                (flat_id, owner_id, slot_date, start_time, end_time, contact_number, status)
                VALUES (:flat_id, :owner_id, :slot_date, :start_time, :end_time, :contact_number, 'available')
            ");
            $stmt->execute([
                'flat_id' => $message['flat_id'],
                'owner_id' => $_SESSION['owner_id'],
                'slot_date' => $new_slot_date,
                'start_time' => $new_start_time,
                'end_time' => $new_end_time,
                'contact_number' => $contact_number
            ]);
            $new_slot_id = $pdo->lastInsertId();
            
            // إرسال رسالة اقتراح موعد جديد للعميل
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, 
                                    subject, message_body, message_type, flat_id, related_request)
                VALUES (:sender_id, 'owner', :receiver_id, 'customer',
                        'New Appointment Proposed', :message_body, 'appointment_response', 
                        :flat_id, :related_request)
            ");
            $stmt->execute([
                'sender_id' => $_SESSION['owner_id'],
                'receiver_id' => $message['sender_id'],
                'message_body' => "The owner has proposed a new appointment time for your viewing. " . 
                                 "Date: $new_slot_date, Time: $new_start_time to $new_end_time. " . 
                                 "Contact Number: $contact_number. " . $response_note,
                'flat_id' => $message['flat_id'],
                'related_request' => $message['related_request']
            ]);
            
            $success_message = "New appointment time proposed! Notification sent to the customer.";
        }
        
        // تحديث حالة الرسالة الأصلية كمقروءة
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE message_id = :message_id");
        $stmt->execute(['message_id' => $message_id]);
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
    <title>Respond to Appointment Request</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body id="respond-appointment-page">
    <?php include 'navbar.php'; ?>
    
    <main class="content" id="respond-main">
        <h1>Respond to Appointment Request</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert success">
                <?= htmlspecialchars($success_message) ?>
                <a href="messages.php" class="btn">Back to Messages</a>
            </div>
        <?php else: ?>
        
        <div class="message-details" id="message-details">
            <h2>Request Details</h2>
            <p><strong>From:</strong> <?= htmlspecialchars($message['customer_name']) ?></p>
            <p><strong>Flat:</strong> <?= htmlspecialchars($message['flat_ref']) ?> - <?= htmlspecialchars($message['flat_location']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($message['flat_address']) ?></p>
            <p><strong>Message:</strong> <?= htmlspecialchars($message['message_body']) ?></p>
            <p><strong>Received:</strong> <?= date('Y-m-d H:i', strtotime($message['created_at'])) ?></p>
        </div>

        <form method="post" class="response-form" id="response-form">
            <input type="hidden" name="message_id" value="<?= $message_id ?>">
            
            <div class="form-section" id="response-options">
                <h2>Response Options</h2>
                
                <div class="response-option">
                    <input type="radio" name="response_type" id="approve" value="approve" checked>
                    <label for="approve">Approve Request</label>
                    
                    <div class="option-details" id="approve-details">
                        <label for="slot_id">Select Available Slot:</label>
                        <select name="slot_id" id="slot_id" required>
                            <?php foreach ($available_slots as $slot): ?>
                                <option value="<?= $slot['slot_id'] ?>">
                                    <?= date('Y-m-d', strtotime($slot['slot_date'])) ?> 
                                    (<?= substr($slot['start_time'], 0, 5) ?> - <?= substr($slot['end_time'], 0, 5) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label for="contact_number">Contact Number:</label>
                        <input type="text" name="contact_number" id="contact_number" 
                               value="<?= htmlspecialchars($_SESSION['owner_phone'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="response-option">
                    <input type="radio" name="response_type" id="reject" value="reject">
                    <label for="reject">Reject Request</label>
                    
                    <div class="option-details" id="reject-details">
                        <label for="reject_reason">Reason for Rejection:</label>
                        <textarea name="response_note" id="reject_reason" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="response-option">
                    <input type="radio" name="response_type" id="reschedule" value="reschedule">
                    <label for="reschedule">Propose New Time</label>
                    
                    <div class="option-details" id="reschedule-details">
                        <label for="new_slot_date">New Date:</label>
                        <input type="date" name="new_slot_date" id="new_slot_date" 
                               min="<?= date('Y-m-d') ?>">
                        
                        <label for="new_start_time">Start Time:</label>
                        <input type="time" name="new_start_time" id="new_start_time">
                        
                        <label for="new_end_time">End Time:</label>
                        <input type="time" name="new_end_time" id="new_end_time">
                        
                        <label for="reschedule_contact">Contact Number:</label>
                        <input type="text" name="contact_number" id="reschedule_contact" 
                               value="<?= htmlspecialchars($_SESSION['owner_phone'] ?? '') ?>" required>
                        
                        <label for="reschedule_note">Additional Notes:</label>
                        <textarea name="response_note" id="reschedule_note" rows="3"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit Response</button>
                <a href="messages.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        
        <?php endif; ?>
    </main>
    
    <?php include 'footer.php'; ?>

    <script>
        // إظهار/إخفاء تفاصيل الخيارات حسب الاختيار
        document.querySelectorAll('input[name="response_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.option-details').forEach(detail => {
                    detail.style.display = 'none';
                });
                document.getElementById(this.value + '-details').style.display = 'block';
            });
        });
        
        // إظهار تفاصيل الخيار الافتراضي عند التحميل
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('approve-details').style.display = 'block';
        });
    </script>
</body>
</html>
