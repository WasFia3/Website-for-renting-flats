<div class="auth-buttons">
  <?php if (isset($_SESSION['role'])): ?>
    <div class="user-info">
      <span class="logged-in">
        Hi, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?>
      </span>
      <div class="user-links">
        <a href="<?php echo $_SESSION['role'] === 'owner' ? 'owner_profile.php' : 'profile.php'; ?>" class="profile-link">
          <i class="fas fa-user"></i> Profile
        </a>
        <a href="logout.php" class="logout-link">
          <button class="sign-in">
            <i class="fas fa-sign-out-alt"></i> Logout
          </button>
        </a>
      </div>
    </div>
  <?php else: ?>
    <div class="guest-buttons">
      <a href="login.php">
        <button class="sign-in">
          <i class="fas fa-sign-in-alt"></i> Login
        </button>
      </a>
      <a href="register.php">
        <button class="sign-up">
          <i class="fas fa-user-plus"></i> Sign Up
        </button>
      </a>
    </div>
  <?php endif; ?>
</div>