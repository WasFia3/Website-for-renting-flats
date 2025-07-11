<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="styles.css">
    <title>Registration Cards</title>
</head>
<body id="register-page">

    <div class="page-wrapper">

    <?php include 'navbar.php'; ?>

   <main>

    <div class="container" id="card-container">
        <div class="card" id="owner-card">
            <div class="circle-img" id="owner-img">
                <img src="images/owner.png" alt="Owner" />
            </div>
            <h2 id="owner-title">Register as Owner</h2>
            <a href="owner_registeraton.php" class="go-btn">Go</a>
        </div>

        <div class="card" id="customer-card" >
            <div class="circle-img" id="customer-img">
                <img src="images/customer.png" alt="Customer" />
            </div>
            <h2 id="customer-title">Register as Customer</h2>
        <a href="customer_registeraton.php" class="go-btn">Go</a>
        </div>

           </main> 
    </div>
        <?php include 'footer.php'; ?>


        </div>

</body>


</html>