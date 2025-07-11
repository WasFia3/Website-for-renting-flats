<?php
session_start();
require_once("database.php");



function getAllFlatsForVisitors($searchTerm = null) {
    global $pdo;

    $sql = "SELECT flat_id, location, rent_cost, num_bedrooms, furnished
            FROM flats
            WHERE is_approved = 1";

    if ($searchTerm) {
        $sql .= " AND location LIKE :searchTerm";
    }

    try {
        $stmt = $pdo->prepare($sql);

        if ($searchTerm) {
            $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching flats: " . $e->getMessage();
        return [];
    }
}

$searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : null;
$products = getAllFlatsForVisitors($searchTerm);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Birzeit Flat Rent - Home</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

  <div class="container">


    <main>
      <section class="hero">
        <div class="hero-text">
         <?php include 'navbar.php'; ?>

          <?php if (isset($_SESSION['customer_id'])): ?>
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
          <?php else: ?>
            <h1>Welcome to Birzeit Flat Rent</h1>
          <?php endif; ?>

          <p>Birzeit Flat Rent helps you easily search, view, and rent apartments around Birzeit – perfect for students and professionals alike.</p>

          <div class="hero-buttons">
            <a href="search.php"><button class="join">Search Flats</button></a>
            <a href="about_us.php"><button class="learn">Learn More</button></a>
          </div>
        </div>

        <div class="hero-image">
          <img src="images/figure.gif" alt="woman figure" />
        </div>
      </section>
    </main>

  </div>

  <?php include 'footer.php'; ?>

</body>
</html>

