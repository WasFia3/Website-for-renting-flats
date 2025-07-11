<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Us - Birzeit Flat Rent</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

 <?php include 'navbar.php'; ?>


<main>
    <section>
        <h2>The Agency</h2>
        <p>Birzeit Flat Rent is a local rental agency established in 2018...</p>
    </section>

    <section>
        <h2>The City</h2>
        <p>Birzeit is a charming town located in the Ramallah...</p>
        <p>For more information: 
            <a href="https://en.wikipedia.org/wiki/Birzeit" target="_blank">Wikipedia</a>
        </p>
    </section>

    <section>
        <h2>Main Business Activities</h2>
        <ul>
            <li>Connecting customers with verified flat owners.</li>
            <li>Providing a platform for flat search, rental, and appointment booking.</li>
            <li>Ensuring smooth rental operations with secure communication.</li>
        </ul>
    </section>
</main>


  <?php include 'footer.php'; ?>


</body>
</html>

