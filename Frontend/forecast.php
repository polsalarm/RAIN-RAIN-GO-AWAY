<?php
session_start();
$locations = [
    "manila" => ["label"=>"Manila","lat"=>14.5995,"lon"=>120.9842],
    "quezon-city" => ["label"=>"Quezon City","lat"=>14.6760,"lon"=>121.0437],
    "makati" => ["label"=>"Makati","lat"=>14.5547,"lon"=>121.0244],
    "pasig" => ["label"=>"Pasig","lat"=>14.5764,"lon"=>121.0851],
    "mandaluyong" => ["label"=>"Mandaluyong","lat"=>14.5794,"lon"=>121.0359],
    "paranaque" => ["label"=>"Parañaque","lat"=>14.4793,"lon"=>121.0198],
    "pasay" => ["label"=>"Pasay","lat"=>14.5378,"lon"=>121.0014],
    "taguig" => ["label"=>"Taguig","lat"=>14.5176,"lon"=>121.0509],
    "caloocan" => ["label"=>"Caloocan","lat"=>14.7566,"lon"=>121.0450],
    "las-pinas" => ["label"=>"Las Piñas","lat"=>14.4497,"lon"=>120.9820],
    "muntinlupa" => ["label"=>"Muntinlupa","lat"=>14.4081,"lon"=>121.0415],
    "valenzuela" => ["label"=>"Valenzuela","lat"=>14.7008,"lon"=>120.9830],
    "malabon" => ["label"=>"Malabon","lat"=>14.6687,"lon"=>120.9575],
    "navotas" => ["label"=>"Navotas","lat"=>14.6667,"lon"=>120.9417],
    "san-juan" => ["label"=>"San Juan","lat"=>14.6019,"lon"=>121.0354],
    "marikina" => ["label"=>"Marikina","lat"=>14.6507,"lon"=>121.1029],
    "pateros" => ["label"=>"Pateros","lat"=>14.5444,"lon"=>121.0667]
];
$selectedLoc = isset($_GET['location']) && isset($locations[$_GET['location']]) ? $_GET['location'] : "manila";
$lat = $locations[$selectedLoc]['lat'];
$lon = $locations[$selectedLoc]['lon'];
$displayLoc = $locations[$selectedLoc]['label'];
$today = date("Y-m-d");
$apiKey = "83440ce05a6d835721864be71c2f032f";
$forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
$response = @file_get_contents($forecastUrl);
$forecastData = [];
$dayForecast = [];
if ($response) {
    $forecastData = json_decode($response, true);
    if (isset($forecastData['list'])) {
        foreach ($forecastData['list'] as $f) {
            $entryDate = date("Y-m-d", $f['dt']);
            if ($entryDate === $today) $dayForecast[] = $f;
        }
    }
}
if (empty($dayForecast)) die("<p>No data for today.</p>");
$weatherIcons = [
    "Clear" => "☀️",   "Clouds" => "☁️", "Rain" => "🌧️",
    "Thunderstorm" => "⛈️", "Snow" => "❄️", "Drizzle" => "🌦️",
    "Mist" => "🌫️", "Fog" => "🌫️", "Haze" => "🌫️", "Smoke" => "🌫️"
];
$weatherBg = [
    "clear" => "summary-clear",      "clouds" => "summary-clouds",
    "rain" => "summary-rain",        "thunderstorm" => "summary-thunder",
    "snow" => "summary-snow",        "drizzle" => "summary-rain",
    "mist" => "summary-mist",        "fog" => "summary-mist",
    "haze" => "summary-mist",        "smoke" => "summary-mist"
];
$selIndex = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Today's Forecast: <?php echo $displayLoc; ?></title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { background: #22223b; color: #f1f2f3; font-family: 'Segoe UI', Arial, sans-serif; margin: 0; min-height: 100vh; }
.container {max-width: 850px; margin:40px auto 0 auto; border-radius:18px; background:#242642; padding:26px;}
.topbar {display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
.loc-picker select {padding:8px 14px;border:none;border-radius:8px;background:#2d3251;color:#fafafa;font-size:1em;}
.summary-main { display:flex;align-items:center;gap:28px;padding:32px 28px 28px 28px;margin:22px 0 26px 0;border-radius:19px;background:linear-gradient(80deg,#283151 85%,#232437 100%);box-shadow:0 2px 18px #17142039;  transition: background 0.6s; min-height:128px; position:relative; overflow:hidden;}
.summary-clear   { background: linear-gradient(90deg,#c1e8ff 66%,#87c6fe 100%)!important;}
.summary-clouds  { background: linear-gradient(90deg,#9fa8b8 66%,#606c88 100%)!important;}
.summary-thunder {background:linear-gradient(90deg,#414345 55%,#232526 100%)!important;}
.summary-rain    { background: linear-gradient(90deg,#4b6cb7 70%,#182848 100%)!important;}
.summary-mist    { background: linear-gradient(90deg,#bfc6c7 50%,#aabbc6 100%)!important;}
.summary-snow    { background: linear-gradient(90deg,#e6e9f0 80%, #c9deed 100%)!important;}
/* Weather animation layers */
.summary-anim-layer {
    position:absolute; left:0; top:0; width:100%; height:100%; pointer-events:none;
    z-index:2; overflow:hidden;
}
/* Rain */
.rain-anim span {
    display:block; position:absolute; bottom:0; width:2.3px; background:rgba(66,180,255,0.22);
    border-radius:2px; animation:raindrop 0.7s linear infinite;
}
@keyframes raindrop {
    0% {top:-50px;opacity:0;}
    16% {opacity:0.31;}
    100% {top:148px;opacity:0;}
}
/* Clouds */
.cloud-anim span, .cloud-anim span.cloud2 {
    position:absolute; width:38%; height:39px; border-radius:22px; 
    background:rgba(255,255,255,0.13); filter:blur(0.5px);
    bottom:48px;
    animation:cloudmove 7s linear infinite alternate;
}
.cloud-anim span.cloud2 { left:50%; width:34%; height:28px;bottom:73px;opacity:0.6;}
@keyframes cloudmove {
    0% {left:10%;}
    100% {left:34%;}
}
/* Sun */
.sun-anim span {
    position:absolute; left:38px;top:29px;width:36px;height:36px;
    background:radial-gradient(circle,#ffe066 63%,#fffbe7 100%);
    border-radius:50%;box-shadow:0 0 39px 11px #ffe06644;
    animation:sunpulse 2s infinite alternate;
}
@keyframes sunpulse {
    0% {box-shadow:0 0 7px 2px #ffe066bb;}
    100% {box-shadow:0 0 36px 7px #ffe06655;}
}
/* Thunderbolt */
.boltspan {
    width:13px;height:25px;background:#fbe300;position:absolute; left:66%; top:67px;
    transform:skew(-16deg); opacity:.95; border-radius:0 0 12px 2px; z-index:3;
    box-shadow:0 0 8px #fbe30099;
    animation:boltflash .65s infinite alternate;
}
@keyframes boltflash { 0% {opacity:.95;} 100% {opacity:.56;} }

/* General fade for background changes */
.summary-main, .summary-main * {transition:background 0.6s;}
.bigicon {font-size:3.6em;}
.bigmain {font-size:2.13em;font-weight:700;color:#222;padding-bottom:1px;}
.bigdesc {color:#313956;font-size:1.1em;}
.bigmeta {margin-top:7px;color:#305369;font-size:1.04em;}
.threeday-row {display:flex;gap:18px;overflow-x:auto;padding:16px 0 6px 0;}
.hourcard {
    cursor:pointer; min-width:118px; padding:18px 11px 18px 11px; border-radius:14px;
    background:#232437;color:#fff; box-shadow:0 2px 10px #1a172740;
    display:flex;flex-direction:column;align-items:center;transition:box-shadow .15s, border .15s;
    border:2px solid transparent; position:relative; font-size:1.08em;
}
.hourcard.selected,.hourcard:hover {background:#292f41;border-color:#3396ff;}
.cardtime{color:#bbbbff;font-size:1.1em;}
.cardicon{font-size:2em;}
.cardtemp{font-size:1.33em;font-weight:700;letter-spacing:.03em;}
.carddesc{font-size:.97em;line-height:1.18;}
.cardmeta{font-size:.94em;color:#c6cbe2;}
.graphbox{margin-top:34px;background:#21234a;border-radius:11px;padding:17px;}
@media (max-width:700px){ 
    .container{padding:7px;}
    .summary-main{flex-direction:column;align-items:flex-start;padding:18px 10px;}
    .threeday-row{gap:8px;}
}
</style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <div style="font-size:1.15em;font-weight:bold;">Today's Weather - <?php echo $displayLoc; ?></div>
        <form class="loc-picker" method="get">
            <select name="location" id="locationSelect">
                <?php foreach ($locations as $key=>$info): ?>
                  <option value="<?php echo $key; ?>" <?php if ($key == $selectedLoc) echo "selected"; ?>><?php echo $info['label']; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="calendar.php" style="color:#fff;font-size:.92em;text-decoration:underline;">← Back to Calendar</a>
    </div>
    <?php
        $sel = $dayForecast[$selIndex];
        $mainType = strtolower($sel['weather'][0]['main']);
        $desc = ucfirst($sel['weather'][0]['description']);
        $icon = isset($weatherIcons[$sel['weather'][0]['main']]) ? $weatherIcons[$sel['weather'][0]['main']] : "❔";
        $wind = round($sel['wind']['speed']*3.6);
        $vis = isset($sel['visibility']) ? round($sel['visibility']/1000,1) : '—';
        $summaryClass = isset($weatherBg[$mainType]) ? $weatherBg[$mainType] : '';
    ?>
    <div class="summary-main <?php echo $summaryClass; ?>" id="detailBox" data-bg="<?php echo $summaryClass; ?>">
        <div class="summary-anim-layer" id="animLayer"></div>
        <div class="bigicon" id="boxIcon"><?php echo $icon; ?></div>
        <div>
            <div class="bigmain" id="boxTemp"><?php echo round($sel['main']['temp']); ?>°C</div>
            <div class="bigdesc" id="boxDesc"><?php echo $desc; ?></div>
            <div class="bigmeta" id="boxMeta">
                Feels: <span id="boxFeels"><?php echo round($sel['main']['feels_like']); ?>°C</span> — 
                Humidity: <span id="boxHum"><?php echo $sel['main']['humidity']; ?></span>% — 
                Wind: <span id="boxWind"><?php echo $wind; ?></span>km/h —
                Visibility: <span id="boxVis"><?php echo $vis; ?></span>km
            </div>
        </div>
    </div>
    <div class="threeday-row" id="hourRow">
    <?php foreach($dayForecast as $i=>$entry):
        $hr = date("g A", $entry["dt"]);
        $cond = $entry['weather'][0]['main'];
        $lowerType = strtolower($cond);
        $desc = ucfirst($entry['weather'][0]['description']);
        $ic = isset($weatherIcons[$cond]) ? $weatherIcons[$cond] : "❔";
        $te = round($entry['main']['temp']);
        $fl = round($entry['main']['feels_like']);
        $hu = $entry['main']['humidity'];
        $cardBg = isset($weatherBg[$lowerType]) ? $weatherBg[$lowerType] : '';
    ?>
      <div class="hourcard<?php if($i==0)echo" selected"; ?>" 
        data-icon="<?php echo $ic; ?>" data-temp="<?php echo $te; ?>"
        data-desc="<?php echo $desc; ?>"
        data-feels="<?php echo $fl; ?>" data-hum="<?php echo $hu; ?>"
        data-wind="<?php echo round($entry['wind']['speed']*3.6); ?>"
        data-vis="<?php echo isset($entry['visibility'])? round($entry['visibility']/1000,1):'—'; ?>"
        data-bg="<?php echo $cardBg; ?>">
        <div class="cardtime"><?php echo $hr; ?></div>
        <div class="cardicon"><?php echo $ic; ?></div>
        <div class="cardtemp"><?php echo $te; ?>°C</div>
        <div class="carddesc"><?php echo $desc; ?></div>
        <div class="cardmeta"><?php echo $hu; ?>% • Feels <?php echo $fl; ?>°C</div>
      </div>
    <?php endforeach; ?>
    </div>
    <div class="graphbox">
        <canvas id="tempGraph" height="63"></canvas>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.getElementById('locationSelect').addEventListener('change', function() {
    window.location = '?location=' + this.value;
});
let hourCards = document.querySelectorAll('.hourcard');
let detailBox = document.getElementById('detailBox');
function setWeatherAnim(type) {
    let anim = document.getElementById('animLayer');
    anim.innerHTML = "";
    if(type==="summary-rain") {
        for(let i=0;i<12;i++) {
            let drop = document.createElement("span");
            drop.style.left = (8 + Math.random()*85) + "%";
            drop.style.height = (30+Math.random()*68) + "px";
            drop.style.animationDelay = (Math.random()*1.2)+"s";
            anim.appendChild(drop);
        }
        anim.className="summary-anim-layer rain-anim";
    } else if(type==="summary-clouds"||type==="summary-mist") {
        let cl1 = document.createElement("span");
        anim.appendChild(cl1);
        let cl2 = document.createElement("span");
        cl2.className="cloud2"; anim.appendChild(cl2);
        anim.className="summary-anim-layer cloud-anim";
    } else if(type==="summary-clear"||type==="summary-snow") {
        let sun = document.createElement("span");
        anim.className="summary-anim-layer sun-anim";
        anim.appendChild(sun);
    } else if(type==="summary-thunder") {
        let cloud = document.createElement("span"); anim.appendChild(cloud);
        let bolt = document.createElement("span"); bolt.className="boltspan"; anim.appendChild(bolt);
        anim.className="summary-anim-layer cloud-anim";
    } else {
        anim.className="summary-anim-layer";
    }
}
hourCards.forEach(card=>{
    card.addEventListener('mouseenter', highlightCard);
    card.addEventListener('click', highlightCard);
});
function highlightCard(e) {
    hourCards.forEach(c=>c.classList.remove('selected'));
    this.classList.add('selected');
    document.getElementById('boxIcon').textContent = this.dataset.icon;
    document.getElementById('boxTemp').textContent = this.dataset.temp+"°C";
    document.getElementById('boxDesc').textContent = this.dataset.desc;
    document.getElementById('boxFeels').textContent = this.dataset.feels+"°C";
    document.getElementById('boxHum').textContent = this.dataset.hum;
    document.getElementById('boxWind').textContent = this.dataset.wind;
    document.getElementById('boxVis').textContent = this.dataset.vis;
    let wbg = this.dataset.bg;
    detailBox.className = "summary-main "+wbg;
    setWeatherAnim(wbg);
}
setWeatherAnim(document.querySelector('.hourcard.selected').dataset.bg);

let temps = <?php echo json_encode(array_map(fn($e)=>$e['main']['temp'],$dayForecast)); ?>;
let labels = <?php echo json_encode(array_map(fn($e)=>date("ga",$e['dt']),$dayForecast)); ?>;
const ctx = document.getElementById("tempGraph").getContext("2d");
const tempGraph = new Chart(ctx, {
    type:'line', data:{labels:labels, datasets:[{
        label:'Temperature (°C)', data:temps, borderColor:'#ffe066', backgroundColor:'rgba(255,224,102,0.13)', fill:true, tension:.43,
        pointRadius:3, pointBackgroundColor:'#fff', borderWidth:2
    }]},
    options:{
        plugins: { legend: { labels: { color: '#ffe066', font: { size:13 }}}},
        scales: {
            x: { ticks: { color:'#fff' }, grid:{ color:'#233',drawBorder:false }},
            y: { ticks:{ color:'#fff' }, grid:{ color:'#233',drawBorder:false }, beginAtZero:false }
        }
    }
});
</script>
</body>
</html>
