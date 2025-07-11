<?php
session_start();

// Check if logged in as owner
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'owner') {
    header("Location: login.php");
    exit();
}

// Connect to the database
require_once 'database.php';

// Get owner ID
$owner_id = $_SESSION['owner_id'];

// Handle accept or reject appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $appointment_id = $_POST['appointment_id'];
    
    try {
        if ($_POST['action'] == 'accept') {
            $stmt = $pdo->prepare("UPDATE appointment_slots SET status = 'accepted' WHERE slot_id = :id AND owner_id = :owner_id");
            $message = "Inspection appointment accepted successfully";
        } elseif ($_POST['action'] == 'reject') {
            $stmt = $pdo->prepare("UPDATE appointment_slots SET status = 'rejected' WHERE slot_id = :id AND owner_id = :owner_id");
            $message = "Inspection appointment rejected successfully";
        }
        
        $stmt->bindParam(':slot_id', $slot_id);
        $stmt->bindParam(':owner_id', $owner_id);
        $stmt->execute();
        
        $_SESSION['success_message'] = $message;
        header("Location: owner_appointments.php");
        exit();
    } catch (PDOException $e) {
        $error_message = "Database error occurred: " . $e->getMessage();
    }
}

// Query to fetch inspection appointments
try {
    $sql = "SELECT a.*, f.ref_number, f.location, f.address, 
                   c.name as customer_name, c.mobile as customer_mobile,
                   u.email as customer_email
            FROM appointments a
            JOIN flats f ON a.flat_id = f.flat_id
            JOIN customers c ON a.customer_id = c.customer_id
            JOIN users u ON c.user_id = u.user_id
            WHERE a.owner_id = :owner_id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':owner_id', $owner_id);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error retrieving data: " . $e->getMessage();
}

// Include page header
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
<!-- Main page content -->


<main>
        <?php include 'navbar.php'; ?>

    <h1>Manage Inspection Appointments</h1>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <!-- Appointment filters -->
    <div class="filter-buttons">
        <a href="owner_appointments.php?filter=all" class="<?php echo (!isset($_GET['filter']) || $_GET['filter'] == 'all') ? 'active' : ''; ?>">All</a>
        <a href="owner_appointments.php?filter=pending" class="<?php echo (isset($_GET['filter']) && $_GET['filter'] == 'pending') ? 'active' : ''; ?>">Pending Approval</a>
        <a href="owner_appointments.php?filter=accepted" class="<?php echo (isset($_GET['filter']) && $_GET['filter'] == 'accepted') ? 'active' : ''; ?>">Accepted</a>
        <a href="owner_appointments.php?filter=rejected" class="<?php echo (isset($_GET['filter']) && $_GET['filter'] == 'rejected') ? 'active' : ''; ?>">Rejected</a>
        <a href="owner_appointments.php?filter=completed" class="<?php echo (isset($_GET['filter']) && $_GET['filter'] == 'completed') ? 'active' : ''; ?>">Completed</a>
    </div>
    
    <!-- Appointments table -->
    <div class="appointments-table-container">
        <?php if (count($appointments) > 0): ?>
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th>Flat Number</th>
                        <th>Location</th>
                        <th>Customer Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): 
                        // Apply filter if exists
                        if (isset($_GET['filter']) && $_GET['filter'] != 'all' && $appt['status'] != $_GET['filter']) {
                            continue;
                        }
                        
                        // Determine status color class
                        $status_class = '';
                        if ($appt['status'] == 'accepted') {
                            $status_class = 'accepted';
                        } elseif ($appt['status'] == 'rejected') {
                            $status_class = 'rejected';
                        } elseif ($appt['status'] == 'completed') {
                            $status_class = 'completed';
                        }
                        
                        // Check if appointment is in the past
                        $current_datetime = strtotime(date('Y-m-d H:i:s'));
                        $appointment_datetime = strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']);
                        $is_past = $current_datetime > $appointment_datetime;
                    ?>
                        <tr class="<?php echo $status_class; ?> <?php echo $is_past ? 'past-appointment' : ''; ?>">
                            <td><?php echo htmlspecialchars($appt['ref_number']); ?></td>
                            <td><?php echo htmlspecialchars($appt['location']); ?></td>
                            <td>
                                <a href="customer_profile.php?id=<?php echo $appt['customer_id']; ?>" class="customer-link">
                                    <?php echo htmlspecialchars($appt['customer_name']); ?>
                                </a>
                                <div class="customer-contact">
                                    <span><?php echo htmlspecialchars($appt['customer_mobile']); ?></span>
                                    <a href="mailto:<?php echo htmlspecialchars($appt['customer_email']); ?>">Send Email</a>
                                </div>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($appt['appointment_date'])); ?></td>
                            <td><?php echo date('H:i', strtotime($appt['appointment_time'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php 
                                        if ($appt['status'] == 'pending') echo 'Pending Approval';
                                        elseif ($appt['status'] == 'accepted') echo 'Accepted';
                                        elseif ($appt['status'] == 'rejected') echo 'Rejected';
                                        elseif ($appt['status'] == 'completed') echo 'Completed';
                                    ?>
                                </span>
                                <?php if ($is_past && $appt['status'] == 'accepted' && $appt['status'] != 'completed'): ?>
                                    <span class="status-badge past-due">Expired</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <?php if ($appt['status'] == 'pending'): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                        <button type="submit" name="action" value="accept" class="accept-btn">Accept</button>
                                        <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                                    </form>
                                <?php elseif ($appt['status'] == 'accepted' && !$is_past): ?>
                                    <a href="contact_customer.php?id=<?php echo $appt['customer_id']; ?>" class="contact-btn">Contact</a>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                        <button type="submit" name="action" value="complete" class="complete-btn">Complete</button>
                                    </form>
                                <?php elseif ($appt['status'] == 'accepted' && $is_past): ?>
                                    <span class="no-action">Completed</span>
                                <?php else: ?>
                                    <span class="no-action">--</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-appointments">
                <p>No inspection appointments to display.</p>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>

<?php
// Include page footer
include 'footer.php';
?>
