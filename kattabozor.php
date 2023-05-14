<?php

require __DIR__ . '/phpQuery/phpQuery.php';

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
function parser($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$result = parser('https://www.kattabozor.uz/');

$pq = phpQuery::newDocument($result);
$arrDataCards = array();
$listLinks = $pq->find('.card-body .stretched-link');
foreach ($listLinks as $link) {
    $arrDataCards[] = pq($link)->attr('href');
}


$arrListCards = array();
foreach ($arrDataCards as $card) {

    $linkPage = "https://www.kattabozor.uz" . $card;

    $resultCard = parser($linkPage);
    $pq = phpQuery::newDocument($resultCard);
    if (empty($pq->find('h1')->text())) {
        continue;
    } else {
        $arrListCards[] = [
            "name" => $pq->find('h1')->text(),
            "url" => $linkPage,
            "poster" => $pq->find('.bullet img')->attr('src'),
            "characteristics" => $pq->find('.mt-md-0 .row')->html()
        ];
    }
}


//$jsonData = json_encode($arrListCards);
//file_put_contents("json_data.txt", $jsonData);

// If waiting response is too long we can save in json files

//$jsonData = file_get_contents(__DIR__ . '/json_data.txt');
//$arrDataCards = json_decode($jsonData, true);


$conn = new mysqli("localhost", "root", "", "parser");
if ($conn->connect_error) {
    die("Ошибка: " . $conn->connect_error);
}


foreach ($arrListCards as $card) {
    $sql = "INSERT INTO kattabozor (title, url, characteristics, poster) VALUES ('{$card['name']}', '{$card['url']}', '{$card['characteristics']}', '{$card['poster']}')";
    if ($conn->query($sql) === true) {
        echo $conn->insert_id . "<br>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
    $last_id = $conn->insert_id;


    $result = parser($card['url'] . "/offers");
    $pq = phpQuery::newDocument($result);

    $arrDataOffers = array();
    $listLinks = $pq->find('.align-items-end .text-decoration-none');
    foreach ($listLinks as $link) {
        $arrDataOffers[] = pq($link)->attr('href');
    }

    $arrListOffers = array();
    foreach ($arrDataOffers as $index => $offer) {

        $linkPage = "https://www.kattabozor.uz" . $offer;
        $arrListOffers[] = [
            "name" => $pq->find('.product-name a')->take_one_element($index),
            "url" => $linkPage,
            "price" => intval(preg_replace('/[^0-9]/', '', $pq->find('.fs-18')->take_one_element($index))),
            "poster" => isset($pq->find('.search-item-img img')->attrs('src')[$index]) ? $pq->find('.search-item-img img')->attrs('src')[$index] : null,
            "characteristics" => $card['characteristics'],
            "parent_id" => $last_id
        ];
    }
    foreach ($arrListOffers as $offer) {
        $sql = "INSERT INTO kattabozor (title, price, characteristics, poster, url, parent_id) VALUES ('{$offer['name']}',  '{$offer['price']}',  '{$offer['characteristics']}', '{$offer['poster']}', '{$offer['url']}', '{$offer['parent_id']}')";
        if ($conn->query($sql) === true) {
            echo $conn->insert_id . "<br>";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

}


$conn->close();


?>

