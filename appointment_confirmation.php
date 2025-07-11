<?php
session_start();
$requestId = $_GET['request_id'] ?? null;

?>
<h2>Appointment Request Confirmation</h2>
<p>Your request has been submitted successfully!</p>
<p>Request ID: <?= $requestId ?></p>
<p>The owner will contact you shortly to confirm.</p>