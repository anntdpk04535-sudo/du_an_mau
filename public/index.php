<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = __('page_title_home');
$featured = array_slice(getAllDestinations(), 0, 6);
$user = currentUser();

$myItineraries = [];
if ($user) {
  $db = getDB();
  $stmt = $db->prepare("SELECT * FROM itineraries WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
  $stmt->execute([$user['id']]);
  $myItineraries = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<?php if ($user): ?>
  <section class="hero">
    <h1><?= __('hello') ?>, <?= e($user['full_name']) ?> 👋</h1>
    <p><?= __('welcome_desc') ?></p>
    <div class="cta">
      <a href="<?= url('/public/itinerary.php') ?>" class="btn"><?= __('create_itinerary') ?></a>
      <a href="<?= url('/public/chatbot.php') ?>" class="btn secondary"><?= __('ask_chatbot') ?></a>
    </div>
  </section>

  <h2 class="section-title"><?= __('my_saved_iti') ?></h2>
  <?php if ($myItineraries): ?>
    <p class="section-sub"><?= __('my_saved_iti_sub') ?></p>
    <div class="grid">
      <?php foreach ($myItineraries as $it): 
        $it = translateItineraryDynamic($it);
      ?>
        <a href="<?= url('/public/itinerary_view.php?id=' . $it['id']) ?>" class="card" style="text-decoration:none;">
          <div class="card-body">
            <h3 style="color:var(--green-900);"><?= e($it['title']) ?></h3>
            <p>📅 <?= e((string) $it['days']) ?> <?= __('days') ?><?= $it['preferences'] ? ' · ' . e($it['preferences']) : '' ?></p>
            <span class="badge"><?= e(date('d/m/Y', strtotime($it['created_at']))) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="section-sub"><?= __('no_itinerary') ?> <a href="<?= url('/public/itinerary.php') ?>"><?= __('create_first_iti') ?></a></p>
  <?php endif; ?>
<?php else: ?>
  <section class="hero">
    <h1><?= __('welcome_ai') ?></h1>
    <p><?= __('welcome_desc') ?></p>
    <div class="cta">
      <a href="<?= url('/public/itinerary.php') ?>" class="btn"><?= __('create_itinerary') ?></a>
      <a href="<?= url('/public/chatbot.php') ?>" class="btn secondary"><?= __('ask_chatbot') ?></a>
      <a href="<?= url('/public/register.php') ?>" class="btn secondary"><?= __('register_save') ?></a>
    </div>
  </section>
<?php endif; ?>

<div id="weather-widget" style="background: linear-gradient(135deg, #e0f2fe, #bae6fd); border-radius: var(--radius); padding: 16px 24px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,.05);">
  <div style="display: flex; align-items: center; gap: 12px;">
    <div id="weather-icon" style="font-size: 32px;">⛅</div>
    <div>
      <h3 style="margin: 0; color: #0369a1; font-size: 16px;"><?= __('weather_title') ?></h3>
      <p id="weather-desc" style="margin: 2px 0 0; color: #0284c7; font-size: 13px;"><?= __('loading') ?></p>
    </div>
  </div>
  <div style="text-align: right;">
    <div id="weather-temp" style="font-size: 28px; font-weight: 800; color: #0369a1; line-height: 1;">--°C</div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
  try {
    const res = await fetch('<?= url('/api/weather.php') ?>');
    const data = await res.json();
    if (data.current_weather) {
      const temp = Math.round(data.current_weather.temperature);
      const code = data.current_weather.weathercode;
      document.getElementById('weather-temp').textContent = temp + '°C';
      
      let icon = '⛅';
      let desc = '<?= __('weather_cloudy') ?>';
      if (code === 0) { icon = '☀️'; desc = '<?= __('weather_clear') ?>'; }
      else if (code >= 1 && code <= 3) { icon = '⛅'; desc = '<?= __('weather_mostly_cloudy') ?>'; }
      else if (code >= 51 && code <= 67) { icon = '🌧️'; desc = '<?= __('weather_light_rain') ?>'; }
      else if (code >= 71 && code <= 82) { icon = '🌧️'; desc = '<?= __('weather_showers') ?>'; }
      else if (code >= 95) { icon = '⛈️'; desc = '<?= __('weather_thunderstorm') ?>'; }
      
      document.getElementById('weather-icon').textContent = icon;
      document.getElementById('weather-desc').textContent = desc;
    }
  } catch (err) {
    document.getElementById('weather-desc').textContent = '<?= __('weather_error') ?>';
  }
});
</script>

<h2 class="section-title"><?= __('featured_destinations') ?></h2>
<p class="section-sub"><?= __('featured_sub') ?></p>

<div class="grid">
  <?php foreach ($featured as $d): ?>
    <a href="<?= url('/diem-den/' . $d['slug']) ?>" class="card">
      <div class="card-img">
        <?php if (!empty($d['image_url'])): ?>
          <img src="<?= e($d['image_url']) ?>" alt="<?= e($d['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
        <?php else: ?>
          🌄
        <?php endif; ?>
      </div>
      <div class="card-body">
        <h3><?= e($d['name']) ?></h3>
        <p><?= e($d['short_desc']) ?></p>
        <span class="badge">⭐ <?= e((string) $d['rating']) ?></span>
        <span class="badge">~<?= e((string) $d['avg_visit_hours']) ?>h</span>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<div style="text-align: center; margin-top: 30px; margin-bottom: 20px;">
    <a href="<?= url('/diem-den') ?>" class="btn secondary" style="padding: 10px 25px; border-radius: 30px; font-weight: 600;"><?= __('view_all_dest') ?></a>
</div>



<?php include __DIR__ . '/../includes/footer.php'; ?>