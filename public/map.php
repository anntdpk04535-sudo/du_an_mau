<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = __('page_title_map');

// Fetch all destinations
$destinations = getAllDestinations(null);
$mapDestinations = [];
foreach ($destinations as $d) {
    if (!empty($d['latitude']) && !empty($d['longitude'])) {
        $mapDestinations[] = [
            'name'            => htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8'),
            'slug'            => $d['slug'],
            'short_desc'      => htmlspecialchars($d['short_desc'], ENT_QUOTES, 'UTF-8'),
            'lat'             => (float)$d['latitude'],
            'lng'             => (float)$d['longitude'],
            'rating'          => $d['avg_rating'] !== null ? round((float)$d['avg_rating'], 1) : null,
            'review_count'    => (int)$d['review_count'],
            'avg_visit_hours' => !empty($d['avg_visit_hours']) ? (float)$d['avg_visit_hours'] : null,
            'price_level'     => $d['price_level'],
            'image_url'       => $d['image_url']
        ];
    }
}

include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<style>
/* Override default container max-width to make map full screen width */
.main-content {
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    display: flex;
    flex-direction: column;
    height: calc(100vh - 70px); /* Adjust based on header height */
}
.map-layout {
    display: flex;
    flex: 1;
    overflow: hidden;
}
.map-sidebar {
    width: 380px;
    background: #fff;
    border-right: 1px solid #ddd;
    display: flex;
    flex-direction: column;
    z-index: 10;
    box-shadow: 2px 0 10px rgba(0,0,0,0.05);
}
.map-sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
}
.map-sidebar-header h1 {
    font-size: 20px;
    margin: 0 0 5px 0;
    color: var(--green-900);
}
.map-sidebar-header p {
    margin: 0;
    font-size: 13px;
    color: #666;
}
.map-sidebar-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}
.map-sidebar-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.2s;
    margin-bottom: 8px;
    border: 1px solid transparent;
}
.map-sidebar-item:hover {
    background: #f8fafc;
    border-color: #e2e8f0;
}
.map-sidebar-item.active {
    background: #f0fdf4;
    border-color: #bbf7d0;
}
.map-sidebar-item img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    flex-shrink: 0;
}
.map-sidebar-info h3 {
    margin: 0 0 4px;
    font-size: 15px;
    color: var(--text-dark);
}
.map-sidebar-info p {
    margin: 0 0 6px;
    font-size: 12px;
    color: #666;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.map-sidebar-meta {
    font-size: 11px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.map-area {
    flex: 1;
    position: relative;
}
#full-map {
    width: 100%;
    height: 100%;
    z-index: 1;
}
@media (max-width: 768px) {
    .map-layout { flex-direction: column-reverse; }
    .map-sidebar { width: 100%; height: 40%; border-right: none; border-top: 1px solid #ddd; }
    .map-area { height: 60%; }
}

/* Badge styles */
.badge-rating {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  color: #92400e;
  border: 1px solid #f59e0b;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 8px;
}
.badge-norating {
  background: #f3f4f6;
  color: #9ca3af;
  padding: 2px 6px;
  border-radius: 8px;
}
</style>

<div class="map-layout">
    <div class="map-sidebar">
        <div class="map-sidebar-header">
            <h1>🗺️ <?= __('map_title') ?></h1>
            <p><?= __('map_sub_1') ?> <?= count($mapDestinations) ?> <?= __('map_sub_2') ?></p>
        </div>
        <div class="map-sidebar-list" id="mapSidebarList">
            <!-- Items rendered via JS -->
        </div>
    </div>
    <div class="map-area">
        <div id="full-map"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const destinations = <?= json_encode($mapDestinations, JSON_UNESCAPED_UNICODE) ?>;
    
    if (destinations.length === 0) return;

    // Adjust body overflow to prevent double scrollbars
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';

    const map = L.map('full-map').setView([12.6667, 108.0500], 9);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> © <a href="https://carto.com/">CARTO</a>',
        subdomains: 'abcd', maxZoom: 19
    }).addTo(map);

    const priceColors = { free: '#2d6a4f', low: '#3a86c8', medium: '#e76f51', high: '#8338ec' };
    function getPriceColor(lvl) { return priceColors[lvl] || '#555'; }
    const priceLabelVi = { free: '<?= __('price_free') ?>', low: '<?= __('price_low') ?>', medium: '<?= __('price_medium') ?>', high: '<?= __('price_high') ?>' };
    function getPriceLabelVi(lvl) { return priceLabelVi[lvl] || lvl; }

    function createDestIcon(color) {
        return L.divIcon({
            className: '',
            html: `<div style="width:32px;height:32px;border-radius:50% 50% 50% 0;background:${color};border:3px solid white;box-shadow:0 4px 10px rgba(0,0,0,.3);transform:rotate(-45deg);"></div>`,
            iconSize: [32,32], iconAnchor: [16,32], popupAnchor: [0,-36], tooltipAnchor: [0,-36]
        });
    }

    const cluster = L.markerClusterGroup({ maxClusterRadius: 50, disableClusteringAtZoom: 13 });
    const allMarkers = [];
    const sidebarList = document.getElementById('mapSidebarList');

    destinations.forEach((d, index) => {
        const color = getPriceColor(d.price_level);
        const marker = L.marker([d.lat, d.lng], { icon: createDestIcon(color) });
        
        let popupContent = `
            <div style="font-family:inherit;min-width:220px;max-width:260px;">
                <div style="background:${color};color:white;border-radius:6px 6px 0 0;padding:8px 12px;margin:-1px -1px 10px;font-weight:700;font-size:14px;">${d.name}</div>
                <div style="padding:0 2px;">
                    <img src="${d.image_url || 'https://via.placeholder.com/260x120?text=No+Image'}" style="width:100%;height:100px;object-fit:cover;border-radius:6px;margin-bottom:8px;">
                    <div style="font-size:12px;margin-bottom:8px;display:flex;gap:6px;flex-wrap:wrap;">
                        ${d.rating !== null 
                            ? `<span class="badge-rating">${'★'.repeat(Math.round(d.rating))}${'☆'.repeat(5-Math.round(d.rating))} ${d.rating.toFixed(1)}</span>`
                            : `<span class="badge-norating"><?= __('dest_no_reviews_yet') ?></span>`
                        }
                        ${d.avg_visit_hours !== null ? `<span style="background:#f1f1f1;padding:2px 7px;border-radius:8px;">~${d.avg_visit_hours}h</span>` : ''}
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;">
                        <a href="<?= url('/public/destination.php') ?>?slug=${d.slug}" style="background:${color};color:white;padding:5px 12px;border-radius:6px;text-decoration:none;font-size:12px;font-weight:600;display:block;text-align:center;flex:1;"><?= __('view_details') ?></a>
                    </div>
                </div>
            </div>`;

        marker.bindPopup(popupContent, { maxWidth: 280 });
        marker.bindTooltip(d.name, { permanent: false, direction: 'top', offset: [0,-32] });
        cluster.addLayer(marker);
        allMarkers.push(marker);

        // Sidebar Item
        const item = document.createElement('div');
        item.className = 'map-sidebar-item';
        item.dataset.index = index;
        item.innerHTML = `
            <img src="${d.image_url || 'https://via.placeholder.com/80'}" alt="${d.name}">
            <div class="map-sidebar-info">
                <h3>${d.name}</h3>
                <p>${d.short_desc}</p>
                <div class="map-sidebar-meta">
                    ${d.rating !== null 
                        ? `<span style="color:#d97706;font-weight:600;">★ ${d.rating.toFixed(1)}</span>` 
                        : `<span style="color:#9ca3af;"><?= __('dest_no_reviews_yet') ?></span>`}
                    <span>•</span>
                    <span style="color:${color};font-weight:600;">${getPriceLabelVi(d.price_level)}</span>
                </div>
            </div>
        `;
        
        item.addEventListener('click', () => {
            document.querySelectorAll('.map-sidebar-item').forEach(el => el.classList.remove('active'));
            item.classList.add('active');
            
            // Zoom to marker and open popup
            map.setView([d.lat, d.lng], 15, { animate: true });
            cluster.zoomToShowLayer(marker, () => {
                marker.openPopup();
            });
            
            // On mobile, scroll up to map
            if (window.innerWidth <= 768) {
                document.querySelector('.map-area').scrollIntoView({ behavior: 'smooth' });
            }
        });

        sidebarList.appendChild(item);
    });

    map.addLayer(cluster);

    if (allMarkers.length > 0) {
        const group = new L.featureGroup(allMarkers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
});
</script>

<?php 
// We include footer but hide it since it's full screen. 
// We can use a trick to hide footer on this page.
?>
<style>
.site-footer { display: none !important; }
</style>
<?php include __DIR__ . '/../includes/footer.php'; ?>
