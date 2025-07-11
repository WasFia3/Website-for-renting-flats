<?php
session_start();
require_once 'database.php';

// Debug helper — log to PHP error log
function debugLog($msg) {
    error_log("[DEBUG] " . $msg);
}

// Check login & role - gotta be customer
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    debugLog("User not logged in or not customer. user_id: " . ($_SESSION['user_id'] ?? 'none') . ", role: " . ($_SESSION['role'] ?? 'none'));
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Check required POST data, and catch if flat_id missing for redirect fallback
if (!isset($_POST['slot_id']) || !isset($_POST['flat_id'])) {
    $flatIdFallback = $_POST['flat_id'] ?? 'unknown';
    debugLog("Missing POST data. slot_id: " . ($_POST['slot_id'] ?? 'missing') . ", flat_id: " . $flatIdFallback);
    $_SESSION['error'] = "Missing required data";
    header("Location: flat_details.php?id=" . $flatIdFallback);
    exit;
}

$slotId = $_POST['slot_id'];
$flatId = $_POST['flat_id'];
$customerId = $_SESSION['customer_id'] ?? null;

if (!$customerId) {
    debugLog("No customer_id found in session");
    $_SESSION['error'] = "Session expired or invalid";
    header("Location: login.php");
    exit;
}

try {
    debugLog("Starting transaction for slot_id=$slotId, flat_id=$flatId, customer_id=$customerId");
    $pdo->beginTransaction();

    // Update slot status only if available
    $stmt = $pdo->prepare("
        UPDATE appointment_slots 
        SET status = 'booked' 
        WHERE slot_id = :slot_id AND status = 'available'
    ");
    $stmt->execute(['slot_id' => $slotId]);

    if ($stmt->rowCount() === 0) {
        debugLog("Slot no longer available or already booked: slot_id=$slotId");
        throw new Exception("Slot no longer available");
    }
    debugLog("Slot updated to booked successfully");

    // Insert appointment request
    $stmt = $pdo->prepare("
        INSERT INTO appointment_requests 
        (slot_id, customer_id, request_status) 
        VALUES (:slot_id, :customer_id, 'pending')
    ");
    $stmt->execute([
        'slot_id' => $slotId,
        'customer_id' => $customerId
    ]);
    $requestId = $pdo->lastInsertId();
    if (!$requestId) {
        debugLog("Failed to get lastInsertId after appointment_requests insert");
        throw new Exception("Failed to create appointment request");
    }
    debugLog("Inserted appointment request with request_id=$requestId");

    // Insert notification message for owner
    $stmt = $pdo->prepare("
        INSERT INTO messages 
        (sender_id, sender_type, receiver_id, receiver_type, subject, message_body, message_type, flat_id, related_request) 
        VALUES (:sender_id, 'customer', 
                (SELECT owner_id FROM flats WHERE flat_id = :flat_id), 'owner',
                'New Appointment Request', 
                'A customer has requested to view the flat.', 
                'appointment_request', 
                :flat_id, :request_id)
    ");
    $stmt->execute([
        'sender_id' => $customerId,
        'flat_id' => $flatId,
        'request_id' => $requestId
    ]);
    if ($stmt->rowCount() === 0) {
        debugLog("Message insertion failed, possible flat_id invalid: flat_id=$flatId");
        throw new Exception("Failed to notify owner");
    }
    debugLog("Notification message sent to owner");

    // Commit all changes
    $pdo->commit();
    debugLog("Transaction committed successfully");

    $_SESSION['success'] = "Your appointment request has been submitted successfully";
    header("Location: appointment_confirmation.php?request_id=" . $requestId);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $errorMsg = $e->getMessage();
    debugLog("Transaction rolled back due to error: $errorMsg");
    // For debugging, comment out the redirect and show error directly:
    // $_SESSION['error'] = "Error booking appointment: " . $errorMsg;
    // header("Location: appointments.php?flat_id=" . $flatId);
    // exit;

    // Show error in browser:
    echo "<h2>Error booking appointment:</h2>";
    echo "<pre>" . htmlspecialchars($errorMsg) . "</pre>";
    echo "<pre>Debug info: slot_id=$slotId, flat_id=$flatId, customer_id=$customerId</pre>";
    exit;
}
