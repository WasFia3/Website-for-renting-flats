<?php
session_start();

// Check if logged in as owner
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'owner') {
    header("Location: login.php");
    exit();
}

// Connect to database
require_once 'database.php';

// Get owner ID
$owner_id = $_SESSION['owner_id'];

// Handle filtering results
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Handle sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'ref_number';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$newOrder = $order == 'ASC' ? 'DESC' : 'ASC';

// Set cookie for sorting preferences
setcookie('sort_preference', "$sort:$order", time() + (86400 * 30), "/");

// SQL query based on filter type
switch ($filter) {
    case 'approved':
        $sql = "SELECT f.*, r.start_date as rental_start, r.end_date as rental_end, c.name as tenant_name, c.customer_id as tenant_id 
                FROM flats f 
                LEFT JOIN rentals r ON f.flat_id = r.flat_id 
                LEFT JOIN customers c ON r.customer_id = c.customer_id 
                WHERE f.owner_id = :owner_id AND f.is_approved = 1 AND f.is_rented = 0";
        $title = "Available Approved Flats";
        break;
    case 'pending':
        $sql = "SELECT * FROM flats WHERE owner_id = :owner_id AND is_approved = 0";
        $title = "Flats Awaiting Approval";
        break;
    case 'rented':
        $sql = "SELECT f.*, r.start_date as rental_start, r.end_date as rental_end, c.name as tenant_name, c.customer_id as tenant_id 
                FROM flats f 
                JOIN rentals r ON f.flat_id = r.flat_id 
                JOIN customers c ON r.customer_id = c.customer_id 
                WHERE f.owner_id = :owner_id AND f.is_rented = 1";
        $title = "Rented Flats";
        break;
    default:
        $sql = "SELECT f.*, r.start_date as rental_start, r.end_date as rental_end, c.name as tenant_name, c.customer_id as tenant_id 
                FROM flats f 
                LEFT JOIN rentals r ON f.flat_id = r.flat_id 
                LEFT JOIN customers c ON r.customer_id = c.customer_id 
                WHERE f.owner_id = :owner_id";
        $title = "All My Flats";
}

// Add sorting to SQL
$sql .= " ORDER BY $sort $order";

// Prepare and execute query
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
$stmt->execute();
$flats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include page header
include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Flats Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- Main page content -->
<main>
    <h1>My Flats Management</h1>
    
    <!-- Flats filtering -->
    <div class="filter-buttons">
        <a href="my_flats.php?filter=all&sort=<?= $sort ?>&order=<?= $order ?>" class="<?= $filter == 'all' ? 'active' : '' ?>">All</a>
        <a href="my_flats.php?filter=approved&sort=<?= $sort ?>&order=<?= $order ?>" class="<?= $filter == 'approved' ? 'active' : '' ?>">Available Approved</a>
        <a href="my_flats.php?filter=pending&sort=<?= $sort ?>&order=<?= $order ?>" class="<?= $filter == 'pending' ? 'active' : '' ?>">Awaiting Approval</a>
        <a href="my_flats.php?filter=rented&sort=<?= $sort ?>&order=<?= $order ?>" class="<?= $filter == 'rented' ? 'active' : '' ?>">Rented</a>
    </div>
    
    <!-- Flats display table -->
    <div class="flats-table-container">
        <?php if (count($flats) > 0): ?>
            <table class="flats-table">
                <thead>
                    <tr>
                        <th><a href="my_flats.php?filter=<?= $filter ?>&sort=ref_number&order=<?= $sort == 'ref_number' ? $newOrder : 'ASC' ?>">Reference Number <?= $sort == 'ref_number' ? ($order == 'ASC' ? '▲' : '▼') : '' ?></a></th>
                        <th><a href="my_flats.php?filter=<?= $filter ?>&sort=location&order=<?= $sort == 'location' ? $newOrder : 'ASC' ?>">Location <?= $sort == 'location' ? ($order == 'ASC' ? '▲' : '▼') : '' ?></a></th>
                        <th><a href="my_flats.php?filter=<?= $filter ?>&sort=rent_cost&order=<?= $sort == 'rent_cost' ? $newOrder : 'ASC' ?>">Monthly Rent <?= $sort == 'rent_cost' ? ($order == 'ASC' ? '▲' : '▼') : '' ?></a></th>
                        <th>Bedrooms</th>
                        <th>Bathrooms</th>
                        <th>Size (sqm)</th>
                        <th>Furnished</th>
                        <th>Features</th>
                        <th>Status</th>
                        <th>Available From</th>
                        <th>Available To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($flats as $flat): ?>
                        <tr class="<?= $flat['is_rented'] ? 'rented' : ($flat['is_approved'] ? 'approved' : 'pending') ?>">
                            <td><?= htmlspecialchars($flat['ref_number']) ?></td>
                            <td><?= htmlspecialchars($flat['location']) ?></td>
                            <td><?= number_format($flat['rent_cost']) ?> JOD</td>
                            <td><?= $flat['num_bedrooms'] ?></td>
                            <td><?= $flat['num_bathrooms'] ?></td>
                            <td><?= $flat['size_sqm'] ?></td>
                            <td><?= $flat['furnished'] ? 'Yes' : 'No' ?></td>
                            <td>
                                <?= $flat['heating_system'] ? 'Heating, ' : '' ?>
                                <?= $flat['air_conditioning'] ? 'AC, ' : '' ?>
                                <?= $flat['access_control'] ? 'Access Control, ' : '' ?>
                                <?= $flat['backyard'] != 'none' ? ucfirst($flat['backyard']) . ' Backyard, ' : '' ?>
                                <?= $flat['playground'] ? 'Playground, ' : '' ?>
                                <?= $flat['storage'] ? 'Storage' : '' ?>
                            </td>
                            <td>
                                <?php if ($flat['is_rented']): ?>
                                    <span class="status-badge rented">Rented</span>
                                <?php elseif ($flat['is_approved']): ?>
                                    <span class="status-badge approved">Approved</span>
                                <?php else: ?>
                                    <span class="status-badge pending">Awaiting Approval</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('Y-m-d', strtotime($flat['available_from'])) ?></td>
                            <td><?= date('Y-m-d', strtotime($flat['available_to'])) ?></td>
                            <td class="actions">
                                <a href="owner_my_flats_view.php?id=<?= $flat['flat_id'] ?>" class="view-btn">View</a>
                                <?php if (!$flat['is_approved'] && !$flat['is_rented']): ?>
                                    <a href="edit_flat.php?id=<?= $flat['flat_id'] ?>" class="edit-btn">Edit</a>
                                    <a href="delete_flat.php?id=<?= $flat['flat_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this flat?')">Delete</a>
                                <?php endif; ?>
                                <?php if ($flat['is_approved'] || $flat['is_rented']): ?>
                                    <a href="owner_view_flat_msgs.php?flat_id=<?= $flat['flat_id'] ?>" class="messages-btn">Messages</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-flats">
                <p>No flats to display for the selected filter.</p>
                <a href="offer_flat.php" class="add-flat-btn">Add New Flat</a>
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
