<?php
if (isset($_COOKIE['counter'])) {
    $counter = (int)$_COOKIE['counter'];
    $counter++;
} else {
    $counter = 1;
}

setcookie('counter', $counter, time() + (24 * 60 * 60));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <p>Počet návštěv: <?php echo $counter; ?></p>
</body>
</html>