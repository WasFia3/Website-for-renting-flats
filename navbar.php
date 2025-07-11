<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
echo '<!-- DEBUG SESSION: ';
var_dump($_SESSION);
echo ' -->';
?>


<aside class="sidebar">
  <div class="logo">Birzeit Flat Rent</div>

  <nav class="nav-links">
    <a href="homepage.php" class="active">Home</a>
    <a href="about_us.php">About Us</a>
    <a href="search.php">Flats</a>

    <?php if (isset($_SESSION['role'])): ?>
      <?php if ($_SESSION['role'] === 'customer'): ?>
        <!-- روابط العميل (Customer) -->
        <a href="my_rentals.php">My Rentals</a>
        <a href="my_appointments.php">My Appointments</a>
        <a href="messages.php">Messages</a>
      <?php elseif ($_SESSION['role'] === 'owner'): ?>
        <!-- روابط المالك (Owner) -->
        <a href="my_flats.php">My Flats</a>
        <a href="offer_flat.php">Offer New Flat</a>
        <a href="owner_appointments.php">Appointments</a>
        <a href="owner_messages.php">Messages</a>
      <?php endif; ?>
    <?php else: ?>
      <!-- روابط الزائر (Guest) -->
      <a href="register.php">Register</a>
      <a href="contact.php">Contact Us</a>
    <?php endif; ?>
  </nav>

  <div class="auth-buttons">
    <?php if (isset($_SESSION['role'])): ?>
  <span class="logged-in">
    Hi, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?>
  </span>
  <a href="<?php echo $_SESSION['role'] === 'owner' ? 'owner_profile.php' : 'profile.php'; ?>">Profile</a>
  <a href="logout.php"><button class="sign-in">Logout</button></a>
<?php else: ?>
  <a href="login.php"><button class="sign-in">Login</button></a>
  <a href="register.php"><button class="sign-up">Sign Up</button></a>
<?php endif; ?>

  </div>
</aside>