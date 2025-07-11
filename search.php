<?php
// Start session
session_start();

// Database connection (should be in a separate file)
require_once 'database.php';

// Initialize variables
$searchResults = [];
$searchParams = [
    'min_price' => '',
    'max_price' => '',
    'location' => '',
    'bedrooms' => '',
    'bathrooms' => '',
    'furnished' => ''
];

// Process search form
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    // Sanitize inputs
    $searchParams = [
        'min_price' => filter_input(INPUT_GET, 'min_price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
        'max_price' => filter_input(INPUT_GET, 'max_price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
        'location' => filter_input(INPUT_GET, 'location', FILTER_SANITIZE_STRING),
        'bedrooms' => filter_input(INPUT_GET, 'bedrooms', FILTER_SANITIZE_NUMBER_INT),
        'bathrooms' => filter_input(INPUT_GET, 'bathrooms', FILTER_SANITIZE_NUMBER_INT),
        'furnished' => filter_input(INPUT_GET, 'furnished', FILTER_SANITIZE_NUMBER_INT)
    ];

    // Build SQL query with filters
    $sql = "SELECT * FROM flats WHERE is_approved = 1 AND available_from <= CURDATE() AND available_to >= CURDATE()";
    
    // Add filters if provided
    if (!empty($searchParams['min_price'])) {
        $sql .= " AND rent_cost >= :min_price";
    }
    if (!empty($searchParams['max_price'])) {
        $sql .= " AND rent_cost <= :max_price";
    }
    if (!empty($searchParams['location'])) {
        $sql .= " AND location LIKE :location";
    }
    if (!empty($searchParams['bedrooms'])) {
        $sql .= " AND num_bedrooms = :bedrooms";
    }
    if (!empty($searchParams['bathrooms'])) {
        $sql .= " AND num_bathrooms = :bathrooms";
    }
    if ($searchParams['furnished'] !== '') {
        $sql .= " AND furnished = :furnished";
    }
    
    // Default sorting by price ascending
    $sql .= " ORDER BY rent_cost ASC";
    
    try {
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters
        if (!empty($searchParams['min_price'])) {
            $stmt->bindValue(':min_price', $searchParams['min_price'], PDO::PARAM_STR);
        }
        if (!empty($searchParams['max_price'])) {
            $stmt->bindValue(':max_price', $searchParams['max_price'], PDO::PARAM_STR);
        }
        if (!empty($searchParams['location'])) {
            $stmt->bindValue(':location', '%' . $searchParams['location'] . '%', PDO::PARAM_STR);
        }
        if (!empty($searchParams['bedrooms'])) {
            $stmt->bindValue(':bedrooms', $searchParams['bedrooms'], PDO::PARAM_INT);
        }
        if (!empty($searchParams['bathrooms'])) {
            $stmt->bindValue(':bathrooms', $searchParams['bathrooms'], PDO::PARAM_INT);
        }
        if ($searchParams['furnished'] !== '') {
            $stmt->bindValue(':furnished', $searchParams['furnished'], PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error = "Error fetching flats. Please try again later.";
    }
} else {
    // Default view - show all available approved flats
    try {
        $sql = "SELECT * FROM flats WHERE is_approved = 1 AND available_from <= CURDATE() AND available_to >= CURDATE() ORDER BY rent_cost ASC";
        $stmt = $pdo->query($sql);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error = "Error fetching flats. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Flats - Birzeit Flat Rent</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <main>
        <section class="flats-section">
            <h1>Available Flats</h1>
            
            <!-- Search Form -->
            <form method="GET" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="min_price">Min Price (₪)</label>
                        <input type="number" id="min_price" name="min_price" min="0" step="0.01" 
                               value="<?= htmlspecialchars($searchParams['min_price']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="max_price">Max Price (₪)</label>
                        <input type="number" id="max_price" name="max_price" min="0" step="0.01" 
                               value="<?= htmlspecialchars($searchParams['max_price']) ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" 
                               value="<?= htmlspecialchars($searchParams['location']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bedrooms">Bedrooms</label>
                        <select id="bedrooms" name="bedrooms">
                            <option value="">Any</option>
                            <option value="1" <?= $searchParams['bedrooms'] === '1' ? 'selected' : '' ?>>1</option>
                            <option value="2" <?= $searchParams['bedrooms'] === '2' ? 'selected' : '' ?>>2</option>
                            <option value="3" <?= $searchParams['bedrooms'] === '3' ? 'selected' : '' ?>>3</option>
                            <option value="4" <?= $searchParams['bedrooms'] === '4' ? 'selected' : '' ?>>4+</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="bathrooms">Bathrooms</label>
                        <select id="bathrooms" name="bathrooms">
                            <option value="">Any</option>
                            <option value="1" <?= $searchParams['bathrooms'] === '1' ? 'selected' : '' ?>>1</option>
                            <option value="2" <?= $searchParams['bathrooms'] === '2' ? 'selected' : '' ?>>2</option>
                            <option value="3" <?= $searchParams['bathrooms'] === '3' ? 'selected' : '' ?>>3+</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="furnished">Furnished</label>
                        <select id="furnished" name="furnished">
                            <option value="">Any</option>
                            <option value="1" <?= $searchParams['furnished'] === '1' ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= $searchParams['furnished'] === '0' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="search" class="search-button">Search Flats</button>
                <button type="reset" class="reset-button">Reset Filters</button>
            </form>
            
            <!-- Search Results -->
            <?php if (isset($error)): ?>
                <p class="error"><?= $error ?></p>
            <?php elseif (empty($searchResults)): ?>
                <p class="no-results">No flats found matching your criteria.</p>
            <?php else: ?>
                <div class="results-container">
                    <table class="flats-table">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Reference</th>
                                <th>Price (₪/month)</th>
                                <th>Location</th>
                                <th>Bedrooms</th>
                                <th>Bathrooms</th>
                                <th>Furnished</th>
                                <th>Available From</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($searchResults as $flat): ?>
                                <tr>
                                    <td>
                                        <a href="flat_details.php?flat_id=<?= $flat['flat_id'] ?>" target="_blank">
                                            <?php
                                            $imgPath = "images/flats/" . $flat['flat_id'] . "/thumb.jpg";
                                            if (!file_exists($imgPath)) {
                                                $imgPath = "images/default.jpg"; // Fallback image
                                            }
                                            ?>
                                            <img src="<?= $imgPath ?>" 
                                                 alt="Flat <?= $flat['flat_id'] ?>" 
                                                 class="flat-thumb">
                                        </a>
                                    </td>
                                    <td>FL<?= str_pad($flat['flat_id'], 6, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= number_format($flat['rent_cost'], 2) ?></td>
                                    <td><?= htmlspecialchars($flat['location']) ?></td>
                                    <td><?= $flat['num_bedrooms'] ?></td>
                                    <td><?= $flat['num_bathrooms'] ?></td>
                                    <td><?= $flat['furnished'] ? 'Yes' : 'No' ?></td>
                                    <td><?= date('d/m/Y', strtotime($flat['available_from'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
    
    <?php include 'footer.php'; ?>
</body>
</html>