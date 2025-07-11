<?php
session_start();
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Get raw input data
        $rawData = [
            'name' => $_POST['name'] ?? '',
            'national_id' => $_POST['national_id'] ?? '',
            // 'address' => $_POST['address'] ?? '',  // Removed
            'postal_code' => $_POST['postal_code'] ?? '',
            'date_of_birth' => $_POST['date_of_birth'] ?? '',
            'email' => $_POST['email'] ?? '',
            'mobile' => $_POST['mobile'] ?? '',
            'telephone' => $_POST['telephone'] ?? '',
            'bank_name' => $_POST['bank_name'] ?? '',
            'bank_branch' => $_POST['bank_branch'] ?? '',
            'account_number' => $_POST['account_number'] ?? '',
            'street_name' => $_POST['street_name'] ?? '',
            'city' => $_POST['city'] ?? ''
        ];

        // Sanitize inputs (prevents XSS)
        $data = array_map('htmlspecialchars', $rawData);

        // Validate name
        if (!preg_match("/^[a-zA-Z\s]+$/", $data['name'])) {
            throw new Exception("Name must contain letters and spaces only.");
        }

        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Validate mobile number format
        if (!preg_match('/^[0-9]{10,15}$/', $data['mobile'])) {
            throw new Exception("Mobile number must be 10-15 digits.");
        }

        // Store in session
        $_SESSION['step1'] = $data;

        // Redirect to next step
        header("Location: owner_eaccount.php");
        exit();

    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Confirmation</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="registration-page">
        <section class="registration-container">
            <h2>Owner Registration Form</h2>

            <?php if (!empty($errorMsg)): ?>
                <div class="error-message" style="color: red; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($errorMsg); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="" class="registration-form">
                <div class="form-group">
                    <label for="name">Full Name (Letters only)<span>*</span>:</label>
                    <input type="text" name="name" id="name" 
                           value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                           pattern="[A-Za-z\s]+" 
                           title="Only letters and spaces allowed" 
                           required>
                </div>

                <div class="form-group">
                    <label for="national_id">National ID Number<span>*</span>:</label>
                    <input type="text" name="national_id" id="national_id" 
                           value="<?= isset($_POST['national_id']) ? htmlspecialchars($_POST['national_id']) : '' ?>" 
                           required>
                </div>

                <!-- Address field removed -->

                <div class="form-group">
                    <label for="postal_code">Postal Code<span>*</span>:</label>
                    <input type="text" name="postal_code" id="postal_code" 
                           value="<?= isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : '' ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="date_of_birth">Date of Birth<span>*</span>:</label>
                    <input type="date" name="date_of_birth" id="date_of_birth" 
                           value="<?= isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : '' ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="email">Email<span>*</span>:</label>
                    <input type="email" name="email" id="email" 
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="mobile">Mobile Number<span>*</span>:</label>
                    <input type="tel" name="mobile" id="mobile" 
                           value="<?= isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : '' ?>" 
                           pattern="[0-9]{10,15}" 
                           title="10-15 digits" 
                           required>
                </div>

                <div class="form-group">
                    <label for="telephone">Telephone:</label>
                    <input type="tel" name="telephone" id="telephone" 
                           value="<?= isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="bank_name">Bank Name<span>*</span>:</label>
                    <input type="text" name="bank_name" id="bank_name" 
                           value="<?= isset($_POST['bank_name']) ? htmlspecialchars($_POST['bank_name']) : '' ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="bank_branch">Bank Branch<span>*</span>:</label>
                    <input type="text" name="bank_branch" id="bank_branch" 
                           value="<?= isset($_POST['bank_branch']) ? htmlspecialchars($_POST['bank_branch']) : '' ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="account_number">Account Number<span>*</span>:</label>
                    <input type="text" name="account_number" id="account_number" 
                           value="<?= isset($_POST['account_number']) ? htmlspecialchars($_POST['account_number']) : '' ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="street_name">Street Name<span>*</span>:</label>
                    <input type="text" name="street_name" id="street_name" 
                           value="<?= isset($_POST['street_name']) ? htmlspecialchars($_POST['street_name']) : '' ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="city">City<span>*</span>:</label>
                    <input type="text" name="city" id="city" 
                           value="<?= isset($_POST['city']) ? htmlspecialchars($_POST['city']) : '' ?>" 
                           required>
                </div>

                <button type="submit">Next Step</button>
            </form>
        </section>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>
