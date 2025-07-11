<?php
session_start();
require_once 'database.php';



$flatId = filter_input(INPUT_GET, 'flat_id', FILTER_VALIDATE_INT);
$_SESSION['flat_id'] = $flatId;
echo $_SESSION['flat_id'];

if (!$flatId) {
    http_response_code(400);
    echo "Invalid flat ID.";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM flats WHERE flat_id = :flat_id AND is_approved = 1");
    $stmt->bindParam(':flat_id', $flatId, PDO::PARAM_INT);
    $stmt->execute();
    $flat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$flat) {
        echo "Flat not found or not approved.";
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "An error occurred. Please try again later.";
    exit;
}

function formatBool($val) {
    return $val ? 'Yes' : 'No';
}
function safe($val) {
    return htmlspecialchars($val ?? 'N/A');
}

$userRole = null;

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && !empty($user['role'])) {
            $userRole = $user['role']; // could be 'admin', 'student', etc.
        }
    } catch (PDOException $e) {
        error_log("User fetch error: " . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flat Details - FL<?= str_pad($flat['flat_id'], 6, '0', STR_PAD_LEFT) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<main class="flat-details">
    <h1>Flat Details</h1>

    <div class="flat-header">
        <img src="<?= file_exists("images/flats/{$flatId}/main.jpg") ? "images/flats/{$flatId}/main.jpg" : 'images/default.jpg' ?>" 
             alt="Flat Image" class="flat-main-image">
        <div class="flat-summary">
            <h2>FL<?= str_pad($flat['flat_id'], 6, '0', STR_PAD_LEFT) ?></h2>
            <p><strong>Reference #:</strong> <?= safe($flat['ref_number']) ?></p>
            <p><strong>Owner ID:</strong> <?= safe($flat['owner_id']) ?></p>
            <p><strong>Location:</strong> <?= safe($flat['location']) ?></p>
            <p><strong>Address:</strong> <?= safe($flat['address']) ?></p>
            <p><strong>Rent:</strong> ₪<?= number_format($flat['rent_cost'], 2) ?>/month</p>
            <p><strong>Available From:</strong> <?= date('d/m/Y', strtotime($flat['available_from'])) ?></p>
            <p><strong>Available To:</strong> <?= date('d/m/Y', strtotime($flat['available_to'])) ?></p>
            <p><strong>Bedrooms:</strong> <?= (int)$flat['num_bedrooms'] ?></p>
            <p><strong>Bathrooms:</strong> <?= (int)$flat['num_bathrooms'] ?></p>
            <p><strong>Size:</strong> <?= (int)$flat['size_sqm'] ?> m²</p>
            <p><strong>Heating System:</strong> <?= safe($flat['heating_system']) ?></p>
            <p><strong>Air Conditioning:</strong> <?= formatBool($flat['air_conditioning']) ?></p>
            <p><strong>Access Control:</strong> <?= formatBool($flat['access_control']) ?></p>
            <p><strong>Backyard:</strong> <?= formatBool($flat['backyard']) ?></p>
            <p><strong>Playground:</strong> <?= formatBool($flat['playground']) ?></p>
            <p><strong>Storage:</strong> <?= formatBool($flat['storage']) ?></p>
            <p><strong>Furnished:</strong> <?= formatBool($flat['furnished']) ?></p>
            <p><strong>Conditions:</strong> <?= safe($flat['conditions']) ?></p>
            <p><strong>Rented:</strong> <?= formatBool($flat['is_rented']) ?></p>
        </div>
    </div>

    <?php if (!empty($flat['description'])): ?>
        <section class="flat-description">
            <h3>Description</h3>
            <p><?= nl2br(safe($flat['description'])) ?></p>
        </section>
    <?php endif; ?>

    <section class="flat-actions">
     <?php if ($userRole !== null): ?>
    <section class="flat-actions">
        <form action="request_appointment.php" method="GET">
            <input type="hidden" name="flat_id" value="<?= $flatId ?>">
            <button type="submit" class="btn-schedule">Schedule Appointment</button>
        </form>
    </section>
<?php endif; ?>

    </section>
</main>

</body>
</html>
