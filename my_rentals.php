<?php
// my_rentals.php
session_start();
require_once("database.php");

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

function getCustomerRentals($customer_id) {
    global $pdo;

    $sql = "SELECT 
                r.rental_id,
                f.reference_number AS flat_ref,
                f.rent_cost,
                r.start_date,
                r.end_date,
                f.location,
                o.name AS owner_name,
                o.mobile AS owner_mobile,
                u.email AS owner_email,
                o.city AS owner_city,
                r.status,
                o.owner_id
            FROM rentals r
            JOIN flats f ON r.flat_id = f.flat_id
            JOIN owners o ON f.owner_id = o.owner_id
            JOIN users u ON o.user_id = u.user_id
            WHERE r.customer_id = :customer_id
            AND r.status IN ('current', 'past')
            ORDER BY r.start_date DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching rentals: " . $e->getMessage());
        return [];
    }
}

// جلب بيانات الشقق المؤجرة
$rentals = getCustomerRentals($_SESSION['customer_id']);

// فصل الشقق الحالية عن السابقة
$current_rentals = array_filter($rentals, fn($r) => $r['status'] === 'current');
$past_rentals = array_filter($rentals, fn($r) => $r['status'] === 'past');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Rentals - Birzeit Flat Rent</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main>
        <h1>My Rental History</h1>

        <!-- الشقق الحالية -->
        <section class="rental-section">
            <h2>Current Rentals</h2>
            <?php if (empty($current_rentals)): ?>
                <p>No current rentals found.</p>
            <?php else: ?>
                <table class="rentals-table">
                    <thead>
                        <tr>
                            <th>Flat Ref</th>
                            <th>Monthly Cost</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Location</th>
                            <th>Owner</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_rentals as $rental): ?>
                            <tr class="current-rental">
                                <td>
                                    <a href="flat_details.php?ref=<?= $rental['flat_ref'] ?>" 
                                       target="_blank" 
                                       class="flat-ref-button">
                                        <?= htmlspecialchars($rental['flat_ref']) ?>
                                    </a>
                                </td>
                                <td>$<?= number_format($rental['rent_cost'], 2) ?></td>
                                <td><?= htmlspecialchars($rental['start_date']) ?></td>
                                <td><?= htmlspecialchars($rental['end_date']) ?></td>
                                <td><?= htmlspecialchars($rental['location']) ?></td>
                                <td>
                                    <a href="user_card.php?type=owner&id=<?= $rental['owner_id'] ?>" 
                                       target="_blank"
                                       class="owner-link">
                                        <?= htmlspecialchars($rental['owner_name']) ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <!-- الشقق السابقة -->
        <section class="rental-section">
            <h2>Past Rentals</h2>
            <?php if (empty($past_rentals)): ?>
                <p>No past rentals found.</p>
            <?php else: ?>
                <table class="rentals-table">
                    <thead>
                        <tr>
                            <th>Flat Ref</th>
                            <th>Monthly Cost</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Location</th>
                            <th>Owner</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($past_rentals as $rental): ?>
                            <tr class="past-rental">
                                <td>
                                    <a href="flat_details.php?ref=<?= $rental['flat_ref'] ?>" 
                                       target="_blank" 
                                       class="flat-ref-button">
                                        <?= htmlspecialchars($rental['flat_ref']) ?>
                                    </a>
                                </td>
                                <td>$<?= number_format($rental['rent_cost'], 2) ?></td>
                                <td><?= htmlspecialchars($rental['start_date']) ?></td>
                                <td><?= htmlspecialchars($rental['end_date']) ?></td>
                                <td><?= htmlspecialchars($rental['location']) ?></td>
                                <td>
                                    <a href="user_card.php?type=owner&id=<?= $rental['owner_id'] ?>" 
                                       target="_blank"
                                       class="owner-link">
                                        <?= htmlspecialchars($rental['owner_name']) ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>