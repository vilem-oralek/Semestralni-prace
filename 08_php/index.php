<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
</head>
<body>
    <?php
        
        $datum = "12.6.2008";

 
        list($den, $mesic, $rok) = explode(".", $datum);

      
        $timestamp = mktime(0, 0, 0, $mesic, $den, $rok);

       
        $denVTydnu = strftime("%A", $timestamp);

        $ceskeDny = [
            "Monday" => "pondělí",
            "Tuesday" => "úterý",
            "Wednesday" => "středa",
            "Thursday" => "čtvrtek",
            "Friday" => "pátek",
            "Saturday" => "sobota",
            "Sunday" => "neděle"
        ];
        $denVTydnuCesky = $ceskeDny[$denVTydnu];
    ?>

    <p><?php echo "$datum je $denVTydnuCesky"; ?></p>
</body>
</html>