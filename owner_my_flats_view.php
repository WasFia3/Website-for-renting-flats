<?php
session_start();
require_once 'database.php';

// Check if flat ID is provided
if (!isset($_GET['id'])) {
    header("Location: search.php");
    exit();
}

$flat_id = $_GET['id'];

// Get flat details
// استعلام معدل لتفاصيل الشقة
$stmt = $pdo->prepare("
    SELECT f.*, o.name as owner_name, o.mobile as owner_mobile, o.email as owner_email,
           o.city as owner_city, o.street_name as owner_street
    FROM flats f
    JOIN owners o ON f.owner_id = o.owner_id
    WHERE f.flat_id = :flat_id
");
$stmt->bindParam(':flat_id', $flat_id, PDO::PARAM_INT);
$stmt->execute();
$flat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$flat) {
    header("Location: search.php");
    exit();
}

// Get flat photos
$stmt = $pdo->prepare("SELECT * FROM flat_photos WHERE flat_id = :flat_id");
$stmt->bindParam(':flat_id', $flat_id, PDO::PARAM_INT);
$stmt->execute();
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get marketing info
$stmt = $pdo->prepare("SELECT * FROM marketing_info WHERE flat_id = :flat_id");
$stmt->bindParam(':flat_id', $flat_id, PDO::PARAM_INT);
$stmt->execute();
$marketing_info = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get appointment slots (only available ones)
$current_date = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT * FROM appointment_slots 
    WHERE flat_id = :flat_id 
    AND slot_date >= :current_date 
    AND status = 'available'
    ORDER BY slot_date, start_time
");
$stmt->bindParam(':flat_id', $flat_id, PDO::PARAM_INT);
$stmt->bindParam(':current_date', $current_date);
$stmt->execute();
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flat <?= htmlspecialchars($flat['ref_number']) ?> Details</title>
    <link rel="stylesheet" href="styles.css">
    
</head>
<body>
    <main>
        <h1>Flat <?= htmlspecialchars($flat['ref_number']) ?> Details</h1>
        
        <div class="flatcard">
            <div class="flat-images">
                <div class="photo-gallery">
                    <?php if (count($photos) > 0): ?>
                        <?php foreach ($photos as $photo): ?>
                            <figure>
                                <img src="<?= htmlspecialchars($photo['photo_url']) ?>" alt="Flat photo">
                                <figcaption>Flat <?= htmlspecialchars($flat['ref_number']) ?></figcaption>
                            </figure>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No photos available for this flat.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="flat-details">
                <h2><?= htmlspecialchars($flat['location']) ?></h2>
                <p><strong>Address:</strong> <?= htmlspecialchars($flat['address']) ?></p>
                <p><strong>Monthly Rent:</strong> <?= number_format($flat['rent_cost'], 2) ?> JOD</p>
                
                <div class="flat-features">
                    <div class="feature-item">
                        <img src="icons/bedroom.png" alt="Bedrooms">
                        <span><?= $flat['num_bedrooms'] ?> Bedrooms</span>
                    </div>
                    <div class="feature-item">
                        <img src="icons/bathroom.png" alt="Bathrooms">
                        <span><?= $flat['num_bathrooms'] ?> Bathrooms</span>
                    </div>
                    <div class="feature-item">
                        <img src="icons/size.png" alt="Size">
                        <span><?= $flat['size_sqm'] ?> sqm</span>
                    </div>
                    <div class="feature-item">
                        <img src="icons/furniture.png" alt="Furnished">
                        <span><?= $flat['furnished'] ? 'Furnished' : 'Not Furnished' ?></span>
                    </div>
                    <?php if ($flat['heating_system']): ?>
                        <div class="feature-item">
                            <img src="icons/heating.png" alt="Heating">
                            <span>Heating System</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($flat['air_conditioning']): ?>
                        <div class="feature-item">
                            <img src="icons/ac.png" alt="AC">
                            <span>Air Conditioning</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($flat['access_control']): ?>
                        <div class="feature-item">
                            <img src="icons/security.png" alt="Access Control">
                            <span>Access Control</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($flat['backyard'] != 'none'): ?>
                        <div class="feature-item">
                            <img src="icons/backyard.png" alt="Backyard">
                            <span><?= ucfirst($flat['backyard']) ?> Backyard</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($flat['playground']): ?>
                        <div class="feature-item">
                            <img src="icons/playground.png" alt="Playground">
                            <span>Playground</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($flat['storage']): ?>
                        <div class="feature-item">
                            <img src="icons/storage.png" alt="Storage">
                            <span>Storage</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h3>Availability</h3>
                <p><strong>From:</strong> <?= date('Y-m-d', strtotime($flat['available_from'])) ?></p>
                <p><strong>To:</strong> <?= date('Y-m-d', strtotime($flat['available_to'])) ?></p>
                
                <h3>Rental Conditions</h3>
                <p><?= htmlspecialchars($flat['conditions']) ?></p>
            </div>
        </div>
        
        <aside class="marketing-aside">
            <h3>Nearby Landmarks</h3>
            <?php if (count($marketing_info) > 0): ?>
                <ul>
                    <?php foreach ($marketing_info as $info): ?>
                        <li>
                            <strong><?= htmlspecialchars($info['title']) ?></strong><br>
                            <?= htmlspecialchars($info['description']) ?><br>
                            <?php if ($info['url']): ?>
                                <a href="<?= htmlspecialchars($info['url']) ?>" target="_blank">More info</a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No marketing information available.</p>
            <?php endif; ?>
            
           <h3>Owner Information</h3>
<p><strong>Name:</strong> <?= htmlspecialchars($flat['owner_name']) ?></p>
<p><strong>Address:</strong> 
    <?= htmlspecialchars($flat['owner_street']) ?>, 
    <?= htmlspecialchars($flat['owner_city']) ?>
</p>
<p><strong>Contact:</strong> <?= htmlspecialchars($flat['owner_mobile']) ?></p>
<p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($flat['owner_email']) ?>"><?= htmlspecialchars($flat['owner_email']) ?></a></p>
        </aside>
        
        <div class="side-nav">
            <h3>Actions</h3>
            <ul>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'customer'): ?>
                    <li><a href="request_appointment.php?flat_id=<?= $flat['flat_id'] ?>">Request Flat Viewing Appointment</a></li>
                    <li><a href="rent_flat.php?flat_id=<?= $flat['flat_id'] ?>">Rent the Flat</a></li>
                <?php elseif (!isset($_SESSION['role'])): ?>
                    <li><a href="login.php?redirect=flat_details.php?id=<?= $flat['flat_id'] ?>">Login to Request Viewing or Rent</a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <?php if (count($slots) > 0 && isset($_SESSION['role']) && $_SESSION['role'] == 'customer'): ?>
            <div class="appointment-slots">
                <h3>Available Viewing Slots</h3>
                <table class="slot-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Contact Number</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slots as $slot): ?>
                            <tr>
                                <td><?= date('Y-m-d', strtotime($slot['slot_date'])) ?></td>
                                <td><?= date('H:i', strtotime($slot['start_time'])) ?> - <?= date('H:i', strtotime($slot['end_time'])) ?></td>
                                <td><?= htmlspecialchars($slot['contact_number']) ?></td>
                                <td>
                                    <form action="book_appointment.php" method="post">
                                        <input type="hidden" name="slot_id" value="<?= $slot['slot_id'] ?>">
                                        <input type="hidden" name="flat_id" value="<?= $flat['flat_id'] ?>">
                                        <button type="submit" class="book-btn">Book</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>

<?php
include 'footer.php';
?>