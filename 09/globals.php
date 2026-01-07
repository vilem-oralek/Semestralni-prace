<?php 

foreach($GLOBALS as $global => $value) {
    // Do something with each global variable
    echo "Global variable '$global' has value: $value";

}

function test(){
    $a = 10;
    echo "Value of a is: $a";
    global $a;
}

test();

foreach($GLOBALS as $global => $value) {
    // Do something with each global variable
    echo "Global variable '$global' has value: $value";

}


?>