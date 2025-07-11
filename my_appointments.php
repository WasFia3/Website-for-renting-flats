<?php
session_start();
require_once 'database.php';

// التحقق من تسجيل الدخول كعميل
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}

// الحصول على customer_id من user_id
try {
    $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        die("Customer profile not found");
    }
    
    $customerId = $customer['customer_id'];
} catch (PDOException $e) {
    die("Error fetching customer data: " . $e->getMessage());
}

// استعلام لجميع مواعيد العميل
try {
    $stmt = $pdo->prepare("
        SELECT 
            ar.request_id,
            ar.request_status,
            ar.requested_at,
            ar.owner_response_at,
            asl.slot_date,
            asl.start_time,
            asl.end_time,
            asl.contact_number,
            f.flat_id,
            f.ref_number AS flat_ref,
            f.location,
            f.rent_cost,
            o.name AS owner_name,
            o.mobile,
            o.email AS owner_email
        FROM 
            appointment_requests ar
        JOIN 
            appointment_slots asl ON ar.slot_id = asl.slot_id
        JOIN 
            flats f ON asl.flat_id = f.flat_id
        JOIN 
            owners o ON asl.owner_id = o.owner_id
        WHERE 
            ar.customer_id = :customer_id
        ORDER BY 
            asl.slot_date DESC, asl.start_time DESC
    ");
    $stmt->execute([':customer_id' => $customerId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching appointments: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Appointments</title>
    <link rel="stylesheet" href="styles.css">
   
</head>
<body>
<?php include 'navbar.php'; ?>

<main class="container">
    <h1>My Appointments</h1>
    
    <?php if (empty($appointments)): ?>
        <div class="alert alert-info">You have no appointments yet.</div>
    <?php else: ?>
        <div class="appointments-list">
            <?php foreach ($appointments as $appt): ?>
                <div class="appointment-card">
                    <div class="appointment-header">
                        <h3>
                            Appointment for Flat <?= htmlspecialchars($appt['flat_ref'] ?? $appt['flat_id']) ?> 
                            <span class="status-<?= htmlspecialchars(strtolower($appt['request_status'])) ?>">
                                (<?= htmlspecialchars($appt['request_status']) ?>)
                            </span>
                        </h3>
                        <p>Location: <?= htmlspecialchars($appt['location']) ?></p>
                        <p>Monthly Rent: $<?= number_format($appt['rent_cost'], 2) ?></p>
                    </div>
                    
                    <div class="appointment-details">
                        <p><strong>Date:</strong> <?= htmlspecialchars($appt['slot_date']) ?></p>
                        <p><strong>Time:</strong> <?= substr($appt['start_time'], 0, 5) ?> - <?= substr($appt['end_time'], 0, 5) ?></p>
                        <p><strong>Owner:</strong> <?= htmlspecialchars($appt['owner_name']) ?></p>
                        <p><strong>Contact:</strong> <?= htmlspecialchars($appt['mobile']) ?> 
                            (<?= htmlspecialchars($appt['owner_email']) ?>)
                        </p>
                        <p><strong>Requested At:</strong> <?= $appt['requested_at'] ?></p>
                        <?php if ($appt['owner_response_at']): ?>
                            <p><strong>Owner Response:</strong> <?= $appt['owner_response_at'] ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="appointment-actions">
                        <?php if ($appt['request_status'] === 'pending'): ?>
                            <a href="cancel_appointment.php?request_id=<?= $appt['request_id'] ?>" class="btn btn-warning">Cancel Request</a>
                        <?php elseif ($appt['request_status'] === 'approved'): ?>
                            <?php if (strtotime($appt['slot_date']) >= strtotime('today')): ?>
                                <a href="flat_details.php?id=<?= $appt['flat_id'] ?>" class="btn btn-primary">View Flat Details</a>
                                <span class="upcoming-badge">Upcoming Appointment</span>
                            <?php else: ?>
                                <span class="completed-badge">Completed</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
</body>
</html>