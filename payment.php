<?php

$product = "10 credits";
$price = 20;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Plans</title>
</head>
<body>
    <form action="checkout.php" method="POST">
        <button type="submit" name="plan" value="basic">Basic</button>
        <button type="submit" name="plan" value="standard">Standard</button>
        <button type="submit" name="plan" value="pro">Pro</button>
    </form>
</body>
</html>