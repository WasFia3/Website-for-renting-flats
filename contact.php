<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recipientEmail = 'jonyqais1@gmail.com';
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $headers = 'From: ' . $_POST['email'];
    mail($recipientEmail, $subject, $message, $headers);
   
    header("Location: contact.php?success=true");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Us - Birzeit Flat Rent</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="contact-page">
    <section class="contact-container">
        <h2>Contact Us</h2>
        <p>Have a question, feedback, or an issue? Reach out to us using the form below. We'll get back to you as soon as possible.</p>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'true') : ?>
            <p class="success-message">✅ Thank you for your message! We’ll be in touch soon.</p>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="contact-form">
            <div class="form-group">
                <label for="email">Your Email<span>*</span>:</label>
                <input type="email" id="email" name="email" placeholder="your.email@example.com" required>
            </div>

            <div class="form-group">
                <label for="subject">Subject<span>*</span>:</label>
                <input type="text" id="subject" name="subject" placeholder="Booking inquiry, website issue..." required>
            </div>

            <div class="form-group">
                <label for="message">Message<span>*</span>:</label>
                <textarea id="message" name="message" rows="5" placeholder="Type your message here..." required></textarea>
            </div>

            <button type="submit">Send</button>
        </form>
    </section>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
