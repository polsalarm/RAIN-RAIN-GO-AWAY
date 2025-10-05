<?php
session_start();

// Get selected date
$date = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");

// OWM API key + location
$apiKey = "83440ce05a6d835721864be71c2f032f";
$lat = 14.5995; // default Manila
$lon = 120.9842;

$forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
$response = @file_get_contents($forecastUrl);
$forecastData = [];
$dayForecast = [];

if ($response) {
    $forecastData = json_decode($response, true);
    if (isset($forecastData['list'])) {
        foreach ($forecastData['list'] as $entry) {
            $entryDate = date("Y-m-d", $entry['dt']);
            if ($entryDate === $date) {
                $dayForecast[] = $entry;
            }
        }
    }
}

// weather → icon mapping
$weatherIcons = [
    "Clear" => "🌞",
    "Clouds" => "☁️",
    "Rain" => "🌧️",
    "Thunderstorm" => "⛈️",
    "Snow" => "❄️",
    "Drizzle" => "🌦️",
    "Mist" => "🌫️",
    "Fog" => "🌫️",
    "Haze" => "🌫️",
    "Smoke" => "🌫️"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forecast for <?php echo htmlspecialchars($date); ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background: #f4f4f4; }
    .back-link { margin-top: 10px; display: inline-block; }
  </style>
</head>
<body>
  <h2>Weather Forecast for <?php echo htmlspecialchars($date); ?></h2>
  <a href="calendar.php" class="back-link">← Back to Calendar</a>

  <?php if (empty($dayForecast)): ?>
    <p>No forecast data available for this date (outside 5-day free API limit).</p>
  <?php else: ?>
    <table>
      <tr>
        <th>Time</th>
        <th>Condition</th>
        <th>Temperature (°C)</th>
        <th>Feels Like (°C)</th>
        <th>Humidity (%)</th>
      </tr>
      <?php foreach ($dayForecast as $entry): ?>
        <?php
          $time = date("H:i", $entry['dt']);
          $condition = $entry['weather'][0]['main'];
          $icon = isset($weatherIcons[$condition]) ? $weatherIcons[$condition] : "❔";
          $temp = round($entry['main']['temp']);
          $feels = round($entry['main']['feels_like']);
          $humidity = $entry['main']['humidity'];
        ?>
        <tr>
          <td><?php echo $time; ?></td>
          <td><?php echo $icon . " " . $condition; ?></td>
          <td><?php echo $temp; ?></td>
          <td><?php echo $feels; ?></td>
          <td><?php echo $humidity; ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</body>
</html>
