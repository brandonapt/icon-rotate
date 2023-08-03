// authored by @brandonns. with api help from @crscd
<?php
use GuzzleHttp\Client;

$client = new Client([
    'timeout'  => 2.0,
]);

$hello = "Hello World!";
echo $hello;
?>