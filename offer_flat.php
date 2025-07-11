<?php
session_start();

// Check if logged in as owner
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'owner') {
    header("Location: login.php");
    exit();
}

require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $owner_id = $_SESSION['owner_id'];
        $location = $_POST['location'];
        $address = $_POST['address'];
        $rent_cost = $_POST['rent_cost'];
        $available_from = $_POST['available_from'];
        $available_to = $_POST['available_to'];
        $num_bedrooms = $_POST['num_bedrooms'];
        $num_bathrooms = $_POST['num_bathrooms'];
        $size_sqm = $_POST['size_sqm'];
        $heating_system = isset($_POST['heating_system']) ? 1 : 0;
        $air_conditioning = isset($_POST['air_conditioning']) ? 1 : 0;
        $conditions = $_POST['conditions'];
        $access_control = isset($_POST['access_control']) ? 1 : 0;
        $backyard = $_POST['backyard'] ?? 'none';
        $playground = isset($_POST['playground']) ? 1 : 0;
        $storage = isset($_POST['storage']) ? 1 : 0;
        $furnished = isset($_POST['furnished']) ? 1 : 0;

        $ref_number = 'APT' . rand(100, 999);

        $stmt = $pdo->prepare("INSERT INTO flats (
            owner_id, ref_number, location, address, rent_cost, available_from, available_to, 
            num_bedrooms, num_bathrooms, size_sqm, heating_system, air_conditioning, 
            conditions, access_control, backyard, playground, storage, furnished, is_approved
        ) VALUES (
            :owner_id, :ref_number, :location, :address, :rent_cost, :available_from, :available_to, 
            :num_bedrooms, :num_bathrooms, :size_sqm, :heating_system, :air_conditioning, 
            :conditions, :access_control, :backyard, :playground, :storage, :furnished, 0
        )");

        $stmt->execute([
            ':owner_id' => $owner_id,
            ':ref_number' => $ref_number,
            ':location' => $location,
            ':address' => $address,
            ':rent_cost' => $rent_cost,
            ':available_from' => $available_from,
            ':available_to' => $available_to,
            ':num_bedrooms' => $num_bedrooms,
            ':num_bathrooms' => $num_bathrooms,
            ':size_sqm' => $size_sqm,
            ':heating_system' => $heating_system,
            ':air_conditioning' => $air_conditioning,
            ':conditions' => $conditions,
            ':access_control' => $access_control,
            ':backyard' => $backyard,
            ':playground' => $playground,
            ':storage' => $storage,
            ':furnished' => $furnished,
        ]);

        $flat_id = $pdo->lastInsertId();

        if (!empty($_FILES['photos']['name'][0])) {
            $upload_dir = 'uploads/flats/' . $flat_id . '/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['photos']['name'][$key];
                $file_path = $upload_dir . basename($file_name);

                if (move_uploaded_file($tmp_name, $file_path)) {
                    // Insert photo info if needed
                }
            }
        }

        $_SESSION['success_message'] = "Flat listing request submitted successfully. Awaiting manager approval.";
        header("Location: owner_dashboard.php");
        exit();
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>List a Flat for Rent</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main id="offer-flat-page">
    <h1>List a Flat for Rent</h1>

    <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form action="offer_flat.php" method="post" enctype="multipart/form-data" class="flat-form">
        <section class="form-section">

            <div class="form-group">
                <label for="location">City/Area:</label>
                <input type="text" id="location" name="location" required>
            </div>

            <div class="form-group">
                <label for="address">Detailed Address:</label>
                <textarea id="address" name="address" rows="3" required></textarea>
            </div>

            <div class="form-group">
                <label for="rent_cost">Monthly Rent (JD):</label>
                <input type="number" id="rent_cost" name="rent_cost" min="100" step="50" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="available_from">Available From:</label>
                    <input type="date" id="available_from" name="available_from" required>
                </div>

                <div class="form-group">
                    <label for="available_to">Available Until:</label>
                    <input type="date" id="available_to" name="available_to" required>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2>Flat Specifications</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="num_bedrooms">Number of Bedrooms:</label>
                    <input type="number" id="num_bedrooms" name="num_bedrooms" min="1" required>
                </div>

                <div class="form-group">
                    <label for="num_bathrooms">Number of Bathrooms:</label>
                    <input type="number" id="num_bathrooms" name="num_bathrooms" min="1" required>
                </div>

                <div class="form-group">
                    <label for="size_sqm">Size (sqm):</label>
                    <input type="number" id="size_sqm" name="size_sqm" min="30" step="5" required>
                </div>
            </div>

            <div class="form-group">
                <label>Amenities:</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="heating_system"> Heating System</label>
                    <label><input type="checkbox" name="air_conditioning"> Air Conditioning</label>
                    <label><input type="checkbox" name="access_control"> Access Control</label>
                    <label><input type="checkbox" name="playground"> Playground</label>
                    <label><input type="checkbox" name="storage"> Storage</label>
                    <label><input type="checkbox" name="furnished"> Furnished</label>
                </div>
            </div>

            <div class="form-group">
                <label for="backyard">Backyard:</label>
                <select id="backyard" name="backyard">
                    <option value="none">None</option>
                    <option value="individual">individual</option>
                    <option value="shared">Shared</option>
                </select>
            </div>

            <div class="form-group">
                <label for="conditions">Rental Conditions:</label>
                <textarea id="conditions" name="conditions" rows="4"></textarea>
            </div>
        </section>

        <section class="form-section">
            <h2>Flat Photos</h2>
            <div class="form-group">
                <label for="photos">Upload Photos (at least 3):</label>
                <input type="file" id="photos" name="photos[]" multiple accept="image/*" required>
            </div>
        </section>

        <section class="form-section">
            <h2>Marketing Information (Optional)</h2>
            <div class="form-group">
                <label for="marketing_info">Nearby Landmarks (Schools, Markets, Parks, etc.):</label>
                <textarea id="marketing_info" name="marketing_info" rows="4"></textarea>
            </div>

            <div class="form-group">
                <label for="viewing_times">Viewing Schedule:</label>
                <textarea id="viewing_times" name="viewing_times" rows="3" 
                          placeholder="Example: Sat & Sun 4-6 PM, call 0599123456"></textarea>
            </div>
        </section>

        <div class="form-actions">
            <button type="submit" class="submit-btn">Submit Listing</button>
            <button type="reset" class="cancel-btn">Cancel</button>
        </div>
    </form>
</main>

<?php include 'footer.php'; ?>
</body>
</html>
