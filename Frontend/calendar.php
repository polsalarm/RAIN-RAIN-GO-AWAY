<?php
session_start();

// Mock login for testing (keep your real login flow)
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = "test_user";
}
$currentUser = $_SESSION['user'];

// Load events (expects data/events.json to be an object: date => [ {title, user}, ... ])
$eventsFile = __DIR__ . "/data/events.json";
$events = file_exists($eventsFile) ? json_decode(file_get_contents($eventsFile), true) : [];
if (!is_array($events)) $events = [];

// Calendar setup (keeps current month)
$month = date('m');
$year = date('Y');
$firstDay = date('w', strtotime("$year-$month-01"));
$totalDays = date('t', strtotime("$year-$month-01"));

// Weather API details (your key)
$apiKey = "83440ce05a6d835721864be71c2f032f";
$defaultLat = 14.5995;
$defaultLon = 120.9842;

// fetch 5-day forecast (group by date). returns [ 'YYYY-MM-DD' => ['icon'=>'10d','main'=>'rain'], ... ]
function fetchWeather($lat, $lon, $apiKey) {
    $url = "https://api.openweathermap.org/data/2.5/forecast?lat=$lat&lon=$lon&appid=$apiKey&units=metric";
    $response = @file_get_contents($url);
    if ($response === FALSE) return [];
    $data = json_decode($response, true);
    $daily = [];
    if (isset($data['list'])) {
        foreach ($data['list'] as $entry) {
            // entry['dt_txt'] like "2025-09-30 12:00:00"
            $date = date('Y-m-d', strtotime($entry['dt_txt']));
            if (!isset($daily[$date])) {
                // store icon and normalized main (lowercase)
                $daily[$date] = [
                    'icon' => $entry['weather'][0]['icon'],
                    'main' => strtolower($entry['weather'][0]['main'])
                ];
            }
        }
    }
    return $daily;
}

$weatherData = fetchWeather($defaultLat, $defaultLon, $apiKey);

// PHP tips mapping (used to render popup content server-side)
$weatherTips = [
    'clear' => "Do: Wear sunscreen and stay hydrated.<br>Don't: Stay too long under direct sun.",
    'rain' => "Do: Bring an umbrella / waterproof jacket and wear anti-slip shoes.<br>Don't: Drive fast on wet roads.",
    'clouds' => "Do: Enjoy the cooler air.<br>Don't: Forget a light jacket if needed.",
    'thunderstorm' => "Do: Stay indoors and unplug sensitive electronics.<br>Don't: Go near tall trees or open fields.",
    'drizzle' => "Do: Carry a small umbrella.<br>Don't: Assume roads won't be slippery.",
    'snow' => "Do: Wear warm layers and non-slip boots.<br>Don't: Drive without winter precautions.",
    'mist' => "Do: Drive carefully and use low beams.<br>Don't: Use high beams in fog.",
    'haze' => "Do: Reduce outdoor exertion.<br>Don't: Rely only on sunglasses.",
    'default' => "Do: Stay prepared and check full forecast.<br>Don't: Ignore official warnings."
];

// helper to map a main string to tip key
function tipKeyFor($main) {
    $m = strtolower($main);
    if (strpos($m,'thunder') !== false) return 'thunderstorm';
    if (in_array($m, ['fog','mist','haze','smoke'])) return 'mist';
    if ($m === 'drizzle') return 'drizzle';
    if (in_array($m, ['clear','clouds','rain','snow'])) return $m;
    return 'default';
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Weather Calendar</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;margin:16px}
    .header{display:flex;justify-content:space-between;align-items:center}
    .calendar{display:grid;grid-template-columns:repeat(7,1fr);gap:8px;margin-top:12px}
    .day{border:1px solid #ddd;padding:8px;min-height:110px;position:relative;background:#fff;border-radius:6px}
    .day-number{font-weight:700}
    .event{font-size:13px;padding:6px;border-radius:6px;margin-top:8px;background:#e6f7ef;cursor:pointer}
    .event.rainy{background:#ff6666;color:#fff}
    .weather-link{position:absolute;top:8px;right:8px;display:inline-block}
    .weather-icon{width:28px;height:28px;display:block}
    .popup{display:none;position:absolute;top:36px;right:8px;background:#fff;border:1px solid rgba(0,0,0,0.12);padding:8px;border-radius:6px;box-shadow:0 6px 18px rgba(0,0,0,0.08);width:200px;font-size:13px;z-index:50}
    /* show popup when hovering the weather link (desktop) */
    .weather-link:hover + .popup,
    .weather-link:focus + .popup { display:block; }
    /* responsive */
    @media (max-width:700px){ .calendar{grid-template-columns:repeat(3,1fr)} .day{min-height:90px} }
    @media (max-width:420px){ .calendar{grid-template-columns:repeat(2,1fr)} }
    form{margin-top:16px;max-width:420px}
    input[type="text"], input[type="date"]{padding:8px;width:100%;margin:6px 0;border:1px solid #ddd;border-radius:6px}
    button{padding:8px 12px;border-radius:6px;border:none;background:#0ea5a4;color:#fff;cursor:pointer}
  </style>
  <script>
  // Attach editable behavior to events and send update to server
  async function makeEventsEditable() {
    document.querySelectorAll('.event').forEach(el => {
      el.addEventListener('click', async function(e){
        e.stopPropagation();
        const old = this.textContent;
        const newTitle = prompt('Edit event title:', old);
        if (newTitle === null) return; // cancel
        // update UI immediately
        this.textContent = newTitle;
        // send update to server
        const date = this.dataset.date;
        const index = this.dataset.index;
        try {
          const resp = await fetch('update_event.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ date: date, index: index, title: newTitle })
          });
          const json = await resp.json();
          if (!json.success) {
            alert('Could not save event: ' + (json.error || 'unknown'));
            this.textContent = old; // revert
          }
        } catch (err) {
          alert('Save failed (network).');
          this.textContent = old;
        }
      });
    });
  }
  window.addEventListener('DOMContentLoaded', makeEventsEditable);
  </script>
</head>
<body>
  <div class="header">
    <h1>Weather Calendar — <?php echo htmlspecialchars(date('F Y')); ?></h1>
    <div>Welcome, <strong><?php echo htmlspecialchars($currentUser); ?></strong></div>
  </div>

  <div class="calendar">
    <?php
    // empty placeholders before month start
    for ($i=0; $i < $firstDay; $i++) {
        echo "<div class='day' aria-hidden='true'></div>";
    }

    // loop through month days
    for ($d = 1; $d <= $totalDays; $d++) {
        $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $d);
        echo "<div class='day' data-date='{$dateStr}'>";
        echo "<div class='day-number'>{$d}</div>";

        // weather icon AND popup tips (hover shows popup, click goes to forecast.php)
        if (isset($weatherData[$dateStr])) {
            $icon = htmlspecialchars($weatherData[$dateStr]['icon']);
            $main = htmlspecialchars($weatherData[$dateStr]['main']);
            $tipKey = tipKeyFor($main);
            $tipHtml = $weatherTips[$tipKey] ?? $weatherTips['default'];
            // weather link + popup
            echo "<a class='weather-link' href='forecast.php?date={$dateStr}' aria-label='Forecast for {$dateStr}'>";
            echo "<img class='weather-icon' src='https://openweathermap.org/img/wn/{$icon}.png' alt='{$main}'>";
            echo "</a>";
            echo "<div class='popup'>{$tipHtml}</div>";
        } else {
            // no weather data — still show link to forecast page and a default small icon
            echo "<a class='weather-link' href='forecast.php?date={$dateStr}' aria-label='Forecast for {$dateStr}'>";
            echo "<img class='weather-icon' src='' alt='forecast'>";
            echo "</a>";
            echo "<div class='popup'>{$weatherTips['default']}</div>";
        }

        // events for this date (render with index so JS can update)
        if (isset($events[$dateStr]) && is_array($events[$dateStr])) {
            foreach ($events[$dateStr] as $idx => $ev) {
                $title = isset($ev['title']) ? htmlspecialchars($ev['title']) : 'Untitled';
                $cls = '';
                // if weather indicates rain -> mark event rainy
                if (isset($weatherData[$dateStr]) && strpos($weatherData[$dateStr]['main'], 'rain') !== false) {
                    $cls = 'rainy';
                }
                echo "<div class='event {$cls}' data-date='{$dateStr}' data-index='{$idx}'>{$title}</div>";
            }
        }

        echo "</div>"; // .day
    }

    // trailing placeholders to complete last row
    $used = ($firstDay + $totalDays) % 7;
    if ($used != 0) {
        $remaining = 7 - $used;
        for ($i=0; $i < $remaining; $i++) echo "<div class='day' aria-hidden='true'></div>";
    }
    ?>
  </div>

  <!-- Add event form (POSTs to your existing save_event.php) -->
  <form method="POST" action="save_event.php">
    <h3>Add Event</h3>
    <label>Date<br><input type="date" name="date" required></label><br>
    <label>Title<br><input type="text" name="title" required></label><br>
    <button type="submit">Add Event</button>
  </form>
</body>
</html>
