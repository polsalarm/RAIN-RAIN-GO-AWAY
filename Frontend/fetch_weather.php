<?php
$apiKey = "83440ce05a6d835721864be71c2f032f";

if (isset($_GET['lat']) && isset($_GET['lon'])) {
    $lat = $_GET['lat'];
    $lon = $_GET['lon'];
    $url = "https://api.openweathermap.org/data/2.5/forecast?lat=$lat&lon=$lon&appid=$apiKey&units=metric";
    $response = @file_get_contents($url);
    $result = [];

    if ($response !== FALSE) {
        $data = json_decode($response, true);
        if (isset($data['list'])) {
            foreach ($data['list'] as $entry) {
                $date = date('Y-m-d', strtotime($entry['dt_txt']));
                if (!isset($result[$date])) {
                    $result[$date] = $entry['weather'][0]['icon'];
                }
            }
        }
    }
    echo json_encode($result);
}
?>
