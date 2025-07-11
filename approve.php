<?php
// approve.php
session_start();

// التحقق من أن المستخدم مسجل دخوله كملاك
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

require_once 'database.php';

// التحقق من وجود معرف الطلب في الرابط
if (!isset($_GET['request_id'])) {
    die("Request ID is missing");
}

$request_id = (int)$_GET['request_id'];
$owner_id = $_SESSION['owner_id'];

// التحقق من أن الطلب يخص هذا المالك
$check_request = $pdo->prepare("
    SELECT ar.request_id 
    FROM appointment_requests ar
    JOIN messages m ON ar.request_id = m.related_request
    WHERE ar.request_id = :request_id 
    AND m.receiver_id = :owner_id
    AND m.receiver_type = 'owner'
    AND ar.request_status = 'pending'
");
$check_request->execute([':request_id' => $request_id, ':owner_id' => $owner_id]);

if ($check_request->rowCount() == 0) {
    // طباعة معلومات التصحيح
    echo "<h3>Debug Information:</h3>";
    echo "Request ID: " . $request_id . "<br>";
    echo "Owner ID: " . $owner_id . "<br>";

    $debug_query = $pdo->prepare("
        SELECT ar.request_status, aps.owner_id 
        FROM appointment_requests ar
        JOIN appointment_slots aps ON ar.slot_id = aps.slot_id
        WHERE ar.request_id = ?
    ");
    $debug_query->execute([$request_id]);
    $debug_data = $debug_query->fetch();

    echo "Request Status: " . ($debug_data['request_status'] ?? 'Not Found') . "<br>";
    echo "Slot Owner ID: " . ($debug_data['owner_id'] ?? 'Not Found') . "<br>";
    echo "Session Owner ID: " . $owner_id . "<br>";

    die("Invalid request or already processed");
}

try {
    // بدء المعاملة
    $pdo->beginTransaction();

    // 1. تحديث حالة الطلب إلى 'approved'
    $update_request = $pdo->prepare("
        UPDATE appointment_requests 
        SET request_status = 'approved', 
            owner_response_at = NOW() 
        WHERE request_id = :request_id
    ");
    $update_request->execute([':request_id' => $request_id]);

    // 2. تحديث حالة الفتحة إلى 'booked'
    $update_slot = $pdo->prepare("
        UPDATE appointment_slots 
        SET status = 'booked' 
        WHERE slot_id = (
            SELECT slot_id FROM appointment_requests 
            WHERE request_id = :request_id
        )
    ");
    $update_slot->execute([':request_id' => $request_id]);

    // 3. الحصول على تفاصيل الطلب لإرسال الإشعار
    $request_details = $pdo->prepare("
        SELECT ar.customer_id, asl.slot_date, asl.start_time, asl.end_time, 
               f.ref_number, f.address, f.flat_id
        FROM appointment_requests ar
        JOIN appointment_slots asl ON ar.slot_id = asl.slot_id
        JOIN flats f ON asl.flat_id = f.flat_id
        WHERE ar.request_id = :request_id
    ");
    $request_details->execute([':request_id' => $request_id]);
    $details = $request_details->fetch(PDO::FETCH_ASSOC);

    // 4. إرسال رسالة تأكيد للعميل
    $subject = "Appointment Approved";
    $message_body = "Your appointment for flat " . $details['ref_number'] . " at " . 
                    $details['address'] . " has been approved.\n\n" .
                    "Date: " . $details['slot_date'] . "\n" .
                    "Time: " . $details['start_time'] . " - " . $details['end_time'];

    $insert_message = $pdo->prepare("
        INSERT INTO messages (
            sender_id, sender_type, receiver_id, receiver_type, 
            subject, message_body, message_type, flat_id, 
            related_request, is_important, created_at
        ) VALUES (
            :owner_id, 'owner', :customer_id, 'customer',
            :subject, :message_body, 'appointment_response', 
            :flat_id, :request_id, 1, NOW()
        )
    ");
    $insert_message->execute([
        ':owner_id' => $owner_id,
        ':customer_id' => $details['customer_id'],
        ':subject' => $subject,
        ':message_body' => $message_body,
        ':flat_id' => $details['flat_id'],
        ':request_id' => $request_id
    ]);

    // 5. تحديث الرسالة الأصلية لتكون مقروءة ومهمة
    $update_original_message = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1, is_important = 1 
        WHERE related_request = :request_id
    ");
    $update_original_message->execute([':request_id' => $request_id]);

    // تأكيد المعاملة
    $pdo->commit();

    $_SESSION['success'] = "Appointment request approved successfully!";
    header("Location: messages.php");
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error approving request: " . $e->getMessage());
}
?>
