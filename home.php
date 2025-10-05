<?php
require 'config.php';
if (!isset($_SESSION['user'])) header("Location: index.php");

// Metro Manila locations
$locations = [
    "manila" => ["label" => "Manila", "lat" => 14.5995, "lon" => 120.9842],
    "quezon-city" => ["label" => "Quezon City", "lat" => 14.6760, "lon" => 121.0437],
    "makati" => ["label" => "Makati", "lat" => 14.5547, "lon" => 121.0244],
    "pasig" => ["label" => "Pasig", "lat" => 14.5764, "lon" => 121.0851],
    "mandaluyong" => ["label" => "Mandaluyong", "lat" => 14.5794, "lon" => 121.0359],
    "paranaque" => ["label" => "Parañaque", "lat" => 14.4793, "lon" => 121.0198],
    "pasay" => ["label" => "Pasay", "lat" => 14.5378, "lon" => 121.0014],
    "taguig" => ["label" => "Taguig", "lat" => 14.5176, "lon" => 121.0509],
    "caloocan" => ["label" => "Caloocan", "lat" => 14.7566, "lon" => 121.0450],
    "las-pinas" => ["label" => "Las Piñas", "lat" => 14.4497, "lon" => 120.9820],
    "muntinlupa" => ["label" => "Muntinlupa", "lat" => 14.4081, "lon" => 121.0415],
    "valenzuela" => ["label" => "Valenzuela", "lat" => 14.7008, "lon" => 120.9830],
    "malabon" => ["label" => "Malabon", "lat" => 14.6687, "lon" => 120.9575],
    "navotas" => ["label" => "Navotas", "lat" => 14.6667, "lon" => 120.9417],
    "san-juan" => ["label" => "San Juan", "lat" => 14.6019, "lon" => 121.0354],
    "marikina" => ["label" => "Marikina", "lat" => 14.6507, "lon" => 121.1029],
    "pateros" => ["label" => "Pateros", "lat" => 14.5444, "lon" => 121.0667]
];
$selectedLoc = isset($_GET['location']) && isset($locations[$_GET['location']]) ? $_GET['location'] : "manila";
$lat = $locations[$selectedLoc]['lat'];
$lon = $locations[$selectedLoc]['lon'];
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get weather data
$apiKey = "83440ce05a6d835721864be71c2f032f";
$url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
$json = @file_get_contents($url);
$data = @json_decode($json, true);

// Extract data for dashboard/statistics/chart
$nowStats = null;
$minDiff = 999;
$temps = $winds = $precips = [];
$chartData = [];

if (isset($data['list'])) {
    foreach ($data['list'] as $entry) {
        $chartData[] = [
            'time' => $entry['dt_txt'],
            'temp' => $entry['main']['temp'],
            'wind' => $entry['wind']['speed']*3.6,
            'precip' => isset($entry['rain']['3h']) ? $entry['rain']['3h'] : 0
        ];
        if (strpos($entry['dt_txt'], $selectedDate)!==false) {
            $h = (int)substr($entry['dt_txt'],11,2);
            $diff = abs($h-12);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nowStats = $entry;
            }
            $temps[] = $entry['main']['temp'];
            $winds[] = $entry['wind']['speed']*3.6;
            $precips[] = isset($entry['rain']['3h']) ? $entry['rain']['3h'] : 0;
        }
    }
}
if (!$nowStats) $nowStats = $data['list'][0];
$temp = round($nowStats['main']['temp'],1);
$wind = round($nowStats['wind']['speed'] * 3.6, 1);
$precip = isset($nowStats['rain']['3h']) ? $nowStats['rain']['3h'] : 0;
$aqi = 99.3; // Demo value, update with a real AQI API

function safe_mean($arr) { return count($arr) ? round(array_sum($arr) / count($arr),1) : 0; }
function prob_exceed($arr, $thr) {
    $count = count($arr); if (!$count) return 0;
    $exceed = 0; foreach ($arr as $v) if ($v > $thr) $exceed++;
    return round(100 * $exceed / $count);
}
$meanTemp = safe_mean($temps);
$meanWind = safe_mean($winds);
$meanPrecip = safe_mean($precips);
$tempProb = prob_exceed($temps, 35);
$windProb = prob_exceed($winds, 25);
$precipProb = prob_exceed($precips, 20);
$aqiProb = 70; // Demo, set AQI logic if you have data

function prob($val, $thr, $mean) { if ($thr == 0) return 0; if ($val > $thr) return 95; if ($val > $mean) return 70; return 30; }
function riskLevel($val, $thr) { if ($val > $thr) return 'Moderate Risk'; return 'Low Risk'; }
function colorClass($val, $thr) { return ($val > $thr) ? '' : 'low-risk'; }
$downloadData = [
    'location' => $locations[$selectedLoc]['label'],
    'date' => $selectedDate,
    'temperature' => $temp,
    'wind_speed' => $wind,
    'precipitation' => $precip,
    'aqi' => $aqi
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Home - Weather Calendar</title>
    <link rel="stylesheet" href="home.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Your full original CSS here */
        body { background: #202c3b; color: #fff; font-family: "Segoe UI", Arial, sans-serif; margin: 0; padding: 0;}
        .container { max-width: 1100px; margin: 40px auto 0 auto; padding: 24px; background: #162035; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.12);}
        .logo { display: block; margin: 0 auto 24px auto; width: 120px;}
        .welcome { text-align: center; font-size: 1.7em; font-weight: 500; margin-bottom: 12px;}
        .desc { text-align: center; margin-bottom: 36px; color: #c3d1e6;}
        .dashboard { display: flex; flex-wrap: wrap; gap: 24px; justify-content: space-between; margin-bottom: 32px;}
        .dashboard-section { flex: 1 1 240px; background: #22314f; border-radius: 14px; padding: 20px; min-width: 220px; box-sizing: border-box; display: flex; flex-direction: column; align-items: center;}
        .data-title { font-size: 1.1em; margin-bottom: 6px; color: #9eb7da;}
        .data-value { font-size: 2.2em; font-weight: bold; margin-bottom: 6px;}
        .data-risk { font-size: 1em; padding: 6px 12px; border-radius: 8px; color: #fff; background: #edc300; margin-bottom: 2px; font-weight: 500;}
        .low-risk { background: #009f4c; }
        .dashboard-section:last-child { margin-right: 0; }
        .options { display: flex; gap: 24px; margin-bottom: 36px; justify-content: center;}
        .option-group { background: #22314f; border-radius: 12px; padding: 18px 28px;}
        .option-label { font-size: 1em; color: #c3d1e6; margin-bottom: 8px;}
        .option-input, .option-select { margin-top: 10px; font-size: 1em; padding: 8px 12px; border-radius: 8px; border: none; outline: none; background: #34455e; color: #fff;}
        .calendar-button { display: block; text-align: center; background: #0066cc; color: #fff; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-size: 1.1em; font-weight: 500; margin: 24px auto; width: 200px; transition: background 0.3s ease;}
        .calendar-button:hover { background: #0052a3;}
        .stats-section { background: #22314f; border-radius: 14px; padding: 24px; margin-top: 32px;}
        .stats-title { font-size: 1.3em; font-weight: 600; margin-bottom: 20px; color: #fff; display: flex; align-items: center;}
        .stats-title::before { content: "📊"; margin-right: 10px;}
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;}
        .stat-item { background: #1a2438; border-radius: 10px; padding: 16px; border-left: 4px solid #0066cc;}
        .stat-name { font-size: 0.95em; color: #9eb7da; margin-bottom: 8px;}
        .stat-details { font-size: 0.85em; color: #7a8fa8; margin-bottom: 6px;}
        .stat-percentage { font-size: 1.4em; font-weight: bold; color: #ffcc00;}
        .stat-percentage.green { color: #00cc66;}
        .stat-percentage.orange { color: #ff9933;}
        .stat-percentage.red { color: #ff4444;}
        .stat-status { font-size: 0.9em; color: #c3d1e6; margin-top: 4px;}
        .graph-section { margin-top: 38px; background: #22314f; border-radius: 16px; padding: 26px;}
        .graph-title { font-size: 1.2em; font-weight: 500; color: #9eb7da; margin-bottom: 18px;}
        .download-btns { margin-top: 14px; display: flex; gap: 12px;}
        .download-btn { background: #34455e; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-size: 1em; font-weight: 500; cursor: pointer;}
        .download-btn:hover { background: #0066cc;}
    </style>
</head>
<body>
<div class="container">
    <img class="logo" src="assets/logo2.svg" alt="Logo">
    <div class="welcome">
        Welcome, <?php echo $_SESSION['user']; ?>!
    </div>
    <div class="desc">
        Your real-time climate data visualization and risk assessment web application in Metro Manila, powered by NASA's Earth Observation Data.
    </div>

    <div class="options">
        <div class="option-group">
            <div class="option-label">Location</div>
            <select class="option-select" id="locationSelect" name="location">
                <?php foreach ($locations as $key => $info): ?>
                    <option value="<?php echo $key; ?>" <?php if($key == $selectedLoc) echo "selected"; ?>><?php echo $info['label']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="option-group">
            <div class="option-label">Select Date & Time</div>
            <input type="date" class="option-input" id="selectedDate" value="<?php echo htmlspecialchars($selectedDate); ?>">
            <input type="time" class="option-input">
        </div>
    </div>

    <div class="dashboard">
        <div class="dashboard-section">
            <div class="data-title">Temperature</div>
            <div class="data-value"><?php echo $temp; ?>°C</div>
            <div class="data-risk <?php echo colorClass($temp,35); ?>"><?php echo riskLevel($temp,35); ?></div>
        </div>
        <div class="dashboard-section">
            <div class="data-title">Wind Speed</div>
            <div class="data-value"><?php echo $wind; ?> km/h</div>
            <div class="data-risk <?php echo colorClass($wind,25); ?>">Low Risk</div>
        </div>
        <div class="dashboard-section">
            <div class="data-title">Precipitation</div>
            <div class="data-value"><?php echo $precip; ?> mm</div>
            <div class="data-risk low-risk">Low Risk</div>
        </div>
    </div>

    <a href="calendar.php" class="calendar-button">Go to Calendar</a>

    <div class="stats-section">
        <div class="stats-title">Probability Analysis</div>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-name">Temperature</div>
                <div class="stat-details">Mean: <?php echo $meanTemp; ?>°C | Threshold: >35°C</div>
                <div class="stat-percentage <?php echo $tempProb > 70 ? 'red':'orange'; ?>">
                    <?php echo $tempProb; ?>%
                </div>
                <div class="stat-status">
                    <?php echo $tempProb > 70 ? "Very likely to exceed threshold" : "Low risk of exceeding threshold"; ?>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-name">Wind Speed</div>
                <div class="stat-details">Mean: <?php echo $meanWind; ?>km/h | Threshold: >25km/h</div>
                <div class="stat-percentage orange"><?php echo $windProb; ?>%</div>
                <div class="stat-status">Moderate chance of exceeding threshold</div>
            </div>
            <div class="stat-item">
                <div class="stat-name">Precipitation</div>
                <div class="stat-details">Mean: <?php echo $meanPrecip; ?>mm | Threshold: >20mm</div>
                <div class="stat-percentage orange"><?php echo $precipProb; ?>%</div>
                <div class="stat-status">Moderate chance of exceeding threshold</div>
            </div>
            <div class="stat-item">
                <div class="stat-name">Air Quality Index</div>
                <div class="stat-details">Mean: 97.4AQI | Threshold: >150AQI</div>
                <div class="stat-percentage green"><?php echo $aqiProb; ?>%</div>
                <div class="stat-status">Moderate air quality conditions</div>
            </div>
        </div>
    </div>

    <!-- Chart visual at the bottom -->
    <div class="graph-section">
        <div class="graph-title">24-Hour Weather Trend (Temperature, Wind, Precipitation)</div>
        <canvas id="weatherChart" height="90"></canvas>
        <div class="download-btns">
            <button class="download-btn" id="jsonBtn">Download JSON</button>
            <button class="download-btn" id="csvBtn">Download CSV</button>
        </div>
    </div>
</div>
<script>
document.getElementById('locationSelect').addEventListener('change', function() {
    let url = '?location=' + this.value;
    let dsel = document.getElementById('selectedDate');
    if (dsel && dsel.value) url += '&date=' + dsel.value;
    window.location = url;
});
document.getElementById('selectedDate').addEventListener('change', function() {
    let url = '?date=' + this.value;
    let lsel = document.getElementById('locationSelect');
    if (lsel && lsel.value) url += '&location=' + lsel.value;
    window.location = url;
});
// Chart.js render
let chartData = <?php echo json_encode($chartData); ?>;
let labels = chartData.map(d => d.time.substr(5,11));
let temps = chartData.map(d => d.temp);
let winds = chartData.map(d => d.wind);
let precs = chartData.map(d => d.precip);

const ctx = document.getElementById("weatherChart").getContext("2d");
const weatherChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            { label: 'Temp (°C)', data: temps, borderColor: '#ffcc00', backgroundColor: 'rgba(255,204,0,0.08)', fill:false },
            { label: 'Wind (km/h)', data: winds, borderColor: '#0066cc', backgroundColor: 'rgba(0,102,204,0.08)', fill:false },
            { label: 'Precip (mm)', data: precs, borderColor: '#00cc66', backgroundColor: 'rgba(0,204,102,0.08)', fill:false }
        ]
    },
    options: {
        plugins: { legend: { labels: { color: '#fff' } }},
        scales: { 
            x: { ticks: { color: '#9eb7da' }, grid: { color: '#233' } },
            y: { ticks: { color: '#9eb7da' }, grid: { color: '#233' } }
        }
    }
});

// Download buttons
document.getElementById('jsonBtn').onclick = function() {
    let data = <?php echo json_encode($downloadData); ?>;
    let blob = new Blob([JSON.stringify(data,null,2)], {type: "application/json"});
    let url = URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url; a.download = "weather-<?php echo $selectedLoc; ?>-<?php echo $selectedDate; ?>.json";
    a.click();
    URL.revokeObjectURL(url);
};
document.getElementById('csvBtn').onclick = function() {
    let data = <?php echo json_encode($downloadData); ?>;
    let csv = Object.keys(data).join(",") + "\n" + Object.values(data).join(",");
    let blob = new Blob([csv], {type: "text/csv"});
    let url = URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url; a.download = "weather-<?php echo $selectedLoc; ?>-<?php echo $selectedDate; ?>.csv";
    a.click();
    URL.revokeObjectURL(url);
};
</script>
</body>
</html>
