// authored by @brandonns. with api help from @crscd
<?php
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

// define config

// roblox .ROBLOSECURITY cookie
$ROBLOX_COOKIE = "_|WARNING:-DO-NOT-SHARE-THIS.--Sharing-this-will-allow-someone-to-log-in-as-you-and-to-steal-your-ROBUX-and-items.|_782F368E1A0833090596A0B93EA84B9F93279D707DE9B984914BBE4A306351AAE561AAFCFDA1C57FAC7CBC2BFB2D4A987CC4F168939DA9B8D978ADAC4ED8C5804B94BFBCC66FFB3CAF9654B35D27AB5224445562D29CFB50941F936DA811237A85EDDBD5A03C942F552410A6E21056E642B739752F2FF07A83D15A3764C5DD8E4BAA642C582424719E3D9C7078CCA9CEBAF64FCAD2786AFDFFE8FFA32509C53236EF68C8069EE6F2CBB5FD5B4EFFD6DDE408B6FF998709189150940257B252DD1379F6938854E07424CB897FB10A79F36C76875CD667E0482A38CFD90154EEE81421512E5A343482BAAF6B3D685106EFD0BA3E67E4294EECA13ADF1C7E8547BC95C688FB899BF8BAE16E190E904AEAA5168286ED8D9B4BBFC68869727E70B383305CE0775D331916133586B9F4456F4A33D343856CF2A7BE01DB5B946B045C24CAAB7957A46F8E145DD7A7ADC3EF083B54BFC2C58DC103FF1D24E9320E4B9928FB9F1675";

// PLACE ID of the game you want to monitor
$PLACE_ID = "6647706396";

// the directory to get thumbnails from
$THUMBNAILS_DIR = "/thumbnails";

// the thumbnail to start with for the IN_ORDER option. if left blank, it will randomly select one from the directory. 
$STARTING_THUMBNAIL = "2.png";

// the order to go through the thumbnails. if true, you need your thumbnails to be named 1.png, 2.png, 3.png, etc. if false, it will randomly select one from the directory
$IN_ORDER = true;
// END OF CONFIG. DON'T TOUCH FROM HERE

$jar = \GuzzleHttp\Cookie\CookieJar::fromArray(
    [
        '.ROBLOSECURITY' => $ROBLOX_COOKIE,
    ],
    'roblox.com'
);


$client = new Client([
    'timeout'  => 10.0,
    'verify' => false,
    "http_errors" => false,
    'cookies' => $jar,
]);

validateCookie();

$cur_dir = getcwd();
$files1 = scandir($cur_dir . $THUMBNAILS_DIR);

// remove . and .. from array
unset($files1[0]);
unset($files1[1]);

// filter out non png, jpg, and jpeg files
$files1 = array_filter($files1, function($item) {
    return strpos($item, ".png") || strpos($item, ".jpg") || strpos($item, ".jpeg");
});

// reindex array
$files1 = array_values($files1);

print_r($files1);

echo("Found " . count($files1) . " thumbnails.\n");

echo("Working...\n");

function getCSRFToken() {
    echo "Getting CSRF token...\n";
    global $client;
    $r = $client->post("https://auth.roblox.com/v2/logout");
    $token = $r->getHeader("x-csrf-token")[0];
    if ($token == null) {
        echo "Failed to get CSRF token. Please try again.\n";
        exit();
    }
    echo "Got CSRF token: " . $token . "\n";
    return $token;
}

function getBlobFromThumbnail($filename) {
    echo "Getting blob from " . $filename . "...\n";
    $blob = fopen($filename, 'r');
    if ($blob == null) {
        echo "Failed to get blob from " . $filename . ". Please try again.\n";
        exit();
    }
    echo "Got blob from " . $filename . "!\n";
    return $blob;
}

function changeThumbnail($filename) {
    echo "Changing thumbnail to " . $filename . "...\n";
    global $client, $PLACE_ID;
    $token = getCSRFToken();
    $blob = getBlobFromThumbnail(getcwd() . "/thumbnails/" . $filename);

    $r = $client->post("https://www.roblox.com/places/icons/add-icon", [
        RequestOptions::MULTIPART => [
            [
                'name' => 'placeId',
                'contents' => $PLACE_ID,
            ],
            [
                'name' => 'iconImageFile',
                'contents' => $blob,
                'filename' => 'Icon.png',
            ],
        ],
        'headers' => [
            'x-csrf-token' => $token,
        ],
    ]);

    $body = $r->getBody();

    // write $body->getContents(); to response.html to see the response
    file_put_contents("response.html", $body->getContents());

    $code = $r->getStatusCode();
    if ($code != 200) {
        echo "Failed to change thumbnail. Please try again.\n";
        exit();
    }

    echo "Changed thumbnail to " . $filename . "!\n";
}

function writeConfig($key, $value) {
    $config = file_get_contents("data.json");
    $config = json_decode($config, true);
    $config[$key] = $value;
    $config = json_encode($config);
    echo "Writing config...\n";
    file_put_contents("data.json", $config);
}

function getConfig($key) {
    $config = file_get_contents("data.json");
    $config = json_decode($config, true);
    return $config[$key];
}

function validateCookie() {
    echo "Validating cookie...\n";
    global $client;
    $r = $client->get("http://users.roblox.com/v1/users/authenticated");
    $body = $r->getBody();
    $code = $r->getStatusCode();
    $remainingBytes = $body->getContents();
    $json_response = json_decode($remainingBytes, true);
    if ($code != 200) {
        echo "Cookie invalid! Please check your .ROBLOSECURITY cookie and try again.\n";
        exit();
    }
    echo "Cookie validated! Signed in as " . $json_response["displayName"] . ".\n";
}

function getRandomThumbnail() {
    global $files1;
    $rand = rand(0, count($files1) - 1);
    return $files1[$rand];
}

if ($IN_ORDER == true) {
    $pastIcon = getConfig("pastIcon");
    if (!$pastIcon) {
        if ($STARTING_THUMBNAIL == "") {
            // find the first thumbnail
            $pastIcon = $files1[0];
            writeConfig("pastIcon", $pastIcon);
        } else {
            $pastIcon = $STARTING_THUMBNAIL;
            writeConfig("pastIcon", $pastIcon);
        }
    } else {
        // get the index of the past icon
        $pastIcon = array_search($pastIcon, $files1);
        // add one to the index
        $pastIcon++;
        // if the index is greater than the amount of thumbnails, set it to the first thumbnail
        // cut off the .png
        $pastIcon2 = str_replace(".png", "", $pastIcon);
        echo $pastIcon2;
        if ($pastIcon2 > count($files1) - 1) {
            $pastIcon = "1.png";
        } else {
            // get the thumbnail from the index
            $pastIcon = $files1[$pastIcon];

            writeConfig("pastIcon", $pastIcon);
        }
 
    }

    changeThumbnail($pastIcon);
} else {
    changeThumbnail(getRandomThumbnail());
}
?>