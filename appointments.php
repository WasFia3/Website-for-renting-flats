<?php
session_start();
require_once 'database.php'; 

$flatId = $_SESSION['flat_id'] ?? null;

if (!$flatId) {
    echo "Missing flat ID.";
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// get dates for this avalible flat with this id 
try {
    $stmt = $pdo->prepare("
        SELECT * FROM appointment_slots 
        WHERE flat_id = :flat_id 
          AND slot_date >= CURDATE() 
          AND status = 'available'
        ORDER BY slot_date, start_time
    ");
    $stmt->execute(['flat_id' => $flatId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching appointments: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Appointment - Flat <?= htmlspecialchars($flatId) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<main>
    <h1>Available Appointments for Flat <?= htmlspecialchars($flatId) ?></h1>

    <?php if (empty($appointments)): ?>
        <p>No upcoming available appointments found.</p>
    <?php else: ?>
        <table class="appointment-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Contact</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td><?= htmlspecialchars($appt['slot_date']) ?></td>
                        <td><?= htmlspecialchars(substr($appt['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr($appt['end_time'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars($appt['contact_number']) ?></td>
                        <td>
                            <form action="book_appointment.php" method="POST">
                                <input type="hidden" name="slot_id" value="<?= $appt['slot_id'] ?>">
                                <input type="hidden" name="flat_id" value="<?= $flatId ?>">
                                <button type="submit" class="btn-book">Book</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

</body>
</html>
