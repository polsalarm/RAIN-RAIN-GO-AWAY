<?php
session_start();
// Mock login for testing
if (!isset($_SESSION['user'])) { $_SESSION['user'] = "test_user"; }
$currentUser = $_SESSION['user'];

// Get month/year from query, fallback to current
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$firstDay = date('w', strtotime("$year-$month-01"));
$totalDays = date('t', strtotime("$year-$month-01"));

// Load events (your backend, expects date => [ {title, user}, ... ])
$eventsFile = __DIR__ . "/data/events.json";
$events = file_exists($eventsFile) ? json_decode(file_get_contents($eventsFile), true) : [];
if (!is_array($events)) $events = [];

// Weather config (replace with your API key)
$apiKey = "83440ce05a6d835721864be71c2f032f";
$lat = 14.5995; $lon = 120.9842;

// Get current weather for animation
function fetchCurrentWeather($lat,$lon,$apiKey) {
  $url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&appid=$apiKey&units=metric";
  $resp = @file_get_contents($url);
  if (!$resp) return 'clear';
  $data = json_decode($resp,true);
  return isset($data['weather'][0]['main']) ? strtolower($data['weather'][0]['main']) : 'clear';
}

// Fetch 5-day forecast (shows icon per day)
function fetchWeather($lat,$lon,$apiKey) {
  $url = "https://api.openweathermap.org/data/2.5/forecast?lat=$lat&lon=$lon&appid=$apiKey&units=metric";
  $resp = @file_get_contents($url);
  if (!$resp) return [];
  $data = json_decode($resp,true);
  $daily = [];
  if (isset($data['list'])) foreach($data['list'] as $entry) {
    $date = date('Y-m-d', strtotime($entry['dt_txt']));
    if (!isset($daily[$date])) {
      $daily[$date] = ['icon'=>$entry['weather'][0]['icon'], 'main'=> strtolower($entry['weather'][0]['main'])];
    }
  }
  return $daily;
}
$currentWeather = fetchCurrentWeather($lat, $lon, $apiKey);
$weatherData = fetchWeather($lat, $lon, $apiKey);
$monthNames = [ 1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
$currentMonth = $monthNames[intval($month)].' '.$year;
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Weather Calendar</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; color:#fff; margin:0; padding:0; min-height:100vh; overflow-x:hidden; }
.weather-bg { position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:-1; }
.weather-bg.clear { background:linear-gradient(120deg,#667eea 0%,#764ba2 100%); }
.weather-bg.rain { background:linear-gradient(135deg,#232526 0%,#314755 100%); }
.weather-bg.clouds { background:linear-gradient(120deg,#bdc3c7 0%,#2c3e50 100%);}
.weather-bg.thunderstorm { background:linear-gradient(135deg,#414345 0%,#232526 100%);}
.weather-bg.snow { background:linear-gradient(120deg,#e6ddd4 0%,#d5d4d0 100%);}
.weather-bg.default { background:linear-gradient(120deg,#202c3b 0%,#162035 100%);}
.weather-bg.rain:after {
  content:""; position:fixed; z-index:-1; left:0;top:0;width:100vw;height:100vh;
  pointer-events:none; animation:rainAnimate 2s linear infinite; background:repeating-linear-gradient(transparent 0px,#fff4 2px,transparent 4px,transparent 15px);
}
@keyframes rainAnimate { 0%{background-position:0 0;} 100%{background-position:50px 100vh;} }
.weather-bg.thunderstorm:before {
  content:""; position:fixed; left:0;top:0;width:100vw;height:100vh;
  pointer-events:none; animation:flash 2.5s infinite;
}
@keyframes flash { 0%,97%{background:transparent;} 98%{background:#fff8;} 100%{background:transparent;} }

.container { max-width:1100px; margin:32px auto 0 auto; background:rgba(22,32,53,0.9); padding:30px 22px; border-radius:18px; box-shadow:0 8px 32px #0006; }

.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;}
.header h1{font-size:2em;letter-spacing:.02em;}
.user-info{background:#345;padding:8px 18px;border-radius:20px;}

.calendar-nav {
  display:flex;justify-content:center;align-items:center;gap:18px;margin-bottom:10px;
}
.nav-btn { background:#22314f; border:none; color:#fff;padding:6px 14px;font-size:1.2em;margin:2px;border-radius:7px; cursor:pointer; }
.nav-btn:hover { background:#0066cc; }

.month-title { font-size:1.3em;font-weight:600;min-width:140px;text-align:center;padding:0 24px 0 24px; }

.calendar { display:grid; grid-template-columns:repeat(7,1fr); gap:1.5px; background:rgba(255,255,255,0.05); border-radius:12px; padding:2px 2px; margin-bottom:32px; }
.day-header{background:rgba(255,255,255,0.08);padding:8px 4px;font-weight:700;text-align:center;}
.day {
  background:rgba(34,53,79,0.76); min-height:110px; position:relative; border-radius:7px;transition:0.2s;cursor:pointer;
}
.day.today { background:rgba(0,102,204,.3);border:2px solid #009;}
.day-number { font-weight:700; font-size:1.1em; padding:6px 0 4px 0; color:#fff;}
.event { font-size:12px; padding:4px 6px;margin:3px;border-radius:6px;background:rgba(0,102,204,0.76);color:#fff;cursor:pointer;border-left:3px solid #0066cc;transition:.2s;}
.event:hover { background:rgba(0,102,204,.95);transform:translateX(2px);}
.event.rainy { background:rgba(255,102,102,0.8); border-left-color:#ff6666; }
.weather-link { position:absolute;top:8px;right:8px;display:inline-block;z-index:10; }
.weather-icon { width:26px;height:26px;border-radius:6px; }
.weather-icon:hover {transform:scale(1.12);}
.popup { display:none;position:absolute; top:36px;right:8px;background:rgba(34,53,79,.95);backdrop-filter:blur(10px);padding:12px;border-radius:8px;box-shadow:0 8px 24px #0004;width:210px;font-size:13px;z-index:50;color:#fff;}
.weather-link:hover + .popup, .weather-link:focus + .popup { display:block; }

.add-event-form { background:rgba(34,53,79,0.85); padding:22px; border-radius:12px;margin-top:24px; }
.add-event-form h3 { margin-bottom:12px; color:#fff;}
.form-group{margin-bottom:12px;}
.form-group label{display:block;margin-bottom:4px;color:#9eb7da;}
.form-group input{width:99%;padding:10px;border:1px solid #6780a2;border-radius:8px;background:#232437;color:#fff;font-size:1em;}
.form-group input:focus{outline:none; border-color:#0066cc;}
.btn{background:#0066cc;color:#fff;border:none;padding:11px 22px;border-radius:8px;font-size:1em;cursor:pointer;}
.btn:hover{background:#0052a3;}
.home-link{position:fixed;top:20px;right:20px;background:#0066cc;color:#fff;padding:6px 19px;border-radius:20px;font-weight:500;z-index:100;} .home-link:hover{background:#0052a3;}
/* MODAL */
#eventModal { display:none; position:fixed; top:0; left:0; width:100vw;height:100vh; align-items:center; justify-content:center;z-index:200; background:#0007; }
#eventModal .modal-content {
  background: #232437; color:#fff; border-radius:11px; padding:34px 22px; box-shadow:0 12px 40px #0009; min-width:320px;position:relative;display:flex;flex-direction:column;align-items:center;
}

#eventModal .close { position:absolute;right:18px;top:18px; background:transparent; font-size:1.5em; color:#aaa; border:none;cursor:pointer; }
#eventModal label { margin-top:12px; margin-bottom:4px; color:#c3d1e6; text-align:left;width:100%; }
#eventModal input, #eventModal select { width:90%; padding:8px 12px;margin-bottom:12px; border-radius:7px; border:1px solid #6780a2; background:#22314f;color:#fff;}
#eventModal .modal-buttons { display:flex; gap:12px;margin-top:10px;}
#eventModal .modal-buttons .btn { flex:1; }

/* Responsive */
@media (max-width:700px){ .container{margin:10px;padding:6px;} .calendar{gap:1px;} .day{min-height:75px;}.modal-content{min-width:0;width:90vw;} }
</style>
</head>
<body>
<div class="weather-bg <?php echo $currentWeather ?: 'default'; ?>"></div>
<a href="home.php" class="home-link">← Home</a>
<div class="container">
  <div class="header">
    <h1>Weather Calendar</h1>
    <div class="user-info">Welcome, <strong><?php echo htmlspecialchars($currentUser); ?></strong></div>
  </div>
  <!-- Month Navigation -->
  <div class="calendar-nav">
    <?php
      $prevMonth = $month - 1; $prevYear = $year; if ($prevMonth == 0) { $prevMonth = 12; $prevYear--; }
      $nextMonth = $month + 1; $nextYear = $year; if ($nextMonth == 13) { $nextMonth = 1; $nextYear++; }
    ?>
    <a href="?month=<?php echo $prevMonth;?>&year=<?php echo $prevYear;?>" class="nav-btn">‹</a>
    <div class="month-title"><?php echo $currentMonth;?></div>
    <a href="?month=<?php echo $nextMonth;?>&year=<?php echo $nextYear;?>" class="nav-btn">›</a>
  </div>
  <!-- Calendar grid -->
  <div class="calendar">
    <div class="day-header">Sun</div>
    <div class="day-header">Mon</div>
    <div class="day-header">Tue</div>
    <div class="day-header">Wed</div>
    <div class="day-header">Thu</div>
    <div class="day-header">Fri</div>
    <div class="day-header">Sat</div>
    <?php for ($i=0; $i<$firstDay; $i++) echo "<div class='day' aria-hidden='true'></div>"; ?>
    <?php
    $today = date('Y-m-d');
    for($d=1; $d<=$totalDays; $d++) {
      $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $d); $isToday = ($dateStr===$today)?'today':'';
      echo "<div class='day $isToday' data-date='{$dateStr}'>";
      echo "<div class='day-number'>{$d}</div>";
      // Weather icon/popup
      if (isset($weatherData[$dateStr])) {
        $icon = htmlspecialchars($weatherData[$dateStr]['icon']);
        $main = htmlspecialchars($weatherData[$dateStr]['main']); $tip = "Weather: ".ucfirst($main);
        echo "<a class='weather-link' href='forecast.php?date={$dateStr}' aria-label='Forecast for {$dateStr}'>";
        echo "<img class='weather-icon' src='https://openweathermap.org/img/wn/{$icon}.png' alt='{$main}'>";
        echo "</a><div class='popup'>{$tip}</div>";
      }
      // Events for date
      if (isset($events[$dateStr]) && is_array($events[$dateStr])) foreach ($events[$dateStr] as $idx=>$ev) {
        $title = isset($ev['title']) ? htmlspecialchars($ev['title']) : 'Untitled';
        $cls = (isset($weatherData[$dateStr]) && strpos($weatherData[$dateStr]['main'],'rain')!==false)?'rainy':'';
        echo "<div class='event {$cls}' data-date='{$dateStr}' data-index='{$idx}'>{$title}</div>";
      }
      echo "</div>";
    }
    $used = ($firstDay+$totalDays)%7; if ($used!=0) { $rem = 7-$used; for($i=0;$i<$rem;$i++) echo "<div class='day' aria-hidden='true'></div>"; }
    ?>
  </div>
  <!-- Add Event Form -->
  <div class="add-event-form">
    <h3>Add Event</h3>
    <form method="POST" action="save_event.php">
      <div class="form-group"><label>Date</label><input type="date" name="date" required></div>
      <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
      <button type="submit" class="btn">Add Event</button>
    </form>
  </div>
</div>
<!-- MODAL -->
<div id="eventModal">
  <div class="modal-content">
    <button class="close" onclick="closeModal()">&times;</button>
    <form id="editForm">
      <label>Edit Title</label>
      <input type="text" id="modalTitle">
      <label>Move to Date</label>
      <input type="date" id="modalDate">
      <div class="modal-buttons">
        <button type="submit" class="btn">Save</button>
        <button type="button" class="btn" id="deleteBtn" style="background:#cf2020;">Delete</button>
      </div>
    </form>
  </div>
</div>
<script>
// Centered modal open/close logic
function openModal(date, idx, title) {
  document.getElementById('eventModal').style.display = 'flex';
  document.getElementById('modalTitle').value = title;
  document.getElementById('modalDate').value = date;
  document.getElementById('editForm').onsubmit = function(e){
    e.preventDefault();
    // send update to server
    fetch('update_event.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({
        date: date,
        index: idx,
        newTitle: document.getElementById('modalTitle').value,
        newDate: document.getElementById('modalDate').value
      })
    }).then(resp=>resp.json()).then(json=>{
      if(json.success) location.reload();
      else alert('Error updating event');
    });
  };
  document.getElementById('deleteBtn').onclick = function(){
    if(confirm('Delete this event?')) {
      fetch('delete_event.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({ date: date, index: idx })
      }).then(resp=>resp.json()).then(json=>{
        if(json.success) location.reload();
        else alert('Delete failed');
      });
    }
  };
}
function closeModal() {
  document.getElementById('eventModal').style.display = 'none';
}
document.querySelectorAll('.event').forEach(el=>{
  el.addEventListener('click', function(e){
    e.stopPropagation();
    var date = this.getAttribute('data-date');
    var idx = this.getAttribute('data-index');
    var title = this.textContent;
    openModal(date, idx, title);
  });
});
document.getElementById('eventModal').onclick = function(e){
  if(e.target===this) closeModal();
};
// Animate clouds for demo (CSS, append)
if(document.querySelector('.weather-bg.clouds')) {
  var cloud = document.createElement('div');
  cloud.style.position='fixed';
  cloud.style.top='15%';
  cloud.style.left='0';
  cloud.style.width='240px';
  cloud.style.height='80px';
  cloud.style.background='rgba(255,255,255,0.29)';
  cloud.style.borderRadius='80px';
  cloud.style.filter='blur(8px)';
  cloud.style.animation='cloudMove 16s linear infinite';
  cloud.style.zIndex = -1;
  document.body.appendChild(cloud);
}
</script>
<style>
@keyframes cloudMove { 0%{left:-240px;} 100%{left:100vw;} }
</style>
</body>
</html>
