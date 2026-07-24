<?php
/**
 * API: Lấy dữ liệu Virtual Tour 360° cho một điểm đến
 * GET /api/virtual_tour.php?destination_id=1
 * GET /api/virtual_tour.php?action=log  (POST — ghi log tương tác)
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'get';

if ($action === 'get') {
    // Lấy dữ liệu tour
    $destId = (int)($_GET['destination_id'] ?? 0);
    if ($destId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Missing destination_id']);
        exit;
    }

    $db = getDB();

    // Kiểm tra destination có bật virtual tour không
    $stmt = $db->prepare("SELECT id, name, name_en, slug, virtual_tour_enabled, virtual_tour_type, audio_guide_url FROM destinations WHERE id = ?");
    $stmt->execute([$destId]);
    $dest = $stmt->fetch();

    if (!$dest || !$dest['virtual_tour_enabled']) {
        echo json_encode(['success' => false, 'error' => 'Virtual tour not available for this destination']);
        exit;
    }

    $dest = translateDbRow($dest, ['name']);

    // Lấy tất cả scenes
    $stmt = $db->prepare("SELECT * FROM virtual_tour_scenes WHERE destination_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$destId]);
    $scenes = $stmt->fetchAll();

    // Dịch
    foreach ($scenes as &$scene) {
        $scene = translateDbRow($scene, ['title', 'description', 'audio_url']);
    }
    unset($scene);

    // Lấy tất cả hotspots cho các scenes
    $sceneIds = array_column($scenes, 'id');
    $hotspots = [];

    if (!empty($sceneIds)) {
        $placeholders = implode(',', array_fill(0, count($sceneIds), '?'));
        $stmt = $db->prepare("
            SELECT h.*, s.scene_key as target_scene_key
            FROM virtual_tour_hotspots h
            LEFT JOIN virtual_tour_scenes s ON h.target_scene_id = s.id
            WHERE h.scene_id IN ($placeholders)
            ORDER BY h.sort_order ASC, h.id ASC
        ");
        $stmt->execute($sceneIds);
        $allHotspots = $stmt->fetchAll();

        // Dịch hotspots
        foreach ($allHotspots as &$hs) {
            $hs = translateDbRow($hs, ['text']);
        }
        unset($hs);

        // Nhóm hotspots theo scene_id
        foreach ($allHotspots as $hs) {
            $hotspots[$hs['scene_id']][] = $hs;
        }
    }

    // Build response
    $tourScenes = [];
    $defaultScene = null;

    foreach ($scenes as $scene) {
        $sceneData = [
            'id' => (int)$scene['id'],
            'scene_key' => $scene['scene_key'],
            'title' => $scene['title'],
            'panorama_url' => $scene['panorama_url'],
            'thumbnail_url' => $scene['thumbnail_url'],
            'audio_url' => $scene['audio_url'],
            'description' => $scene['description'],
            'pitch' => (float)$scene['pitch'],
            'yaw' => (float)$scene['yaw'],
            'hfov' => (int)$scene['hfov'],
            'sort_order' => (int)$scene['sort_order'],
            'is_default' => (bool)$scene['is_default'],
            'hotspots' => [],
        ];

        // Thêm hotspots cho scene này
        if (isset($hotspots[$scene['id']])) {
            foreach ($hotspots[$scene['id']] as $hs) {
                $sceneData['hotspots'][] = [
                    'id' => (int)$hs['id'],
                    'type' => $hs['type'],
                    'pitch' => (float)$hs['pitch'],
                    'yaw' => (float)$hs['yaw'],
                    'text' => $hs['text'],
                    'target_scene_key' => $hs['target_scene_key'],
                    'url' => $hs['url'],
                    'icon' => $hs['icon'],
                    'css_class' => $hs['css_class'],
                ];
            }
        }

        $tourScenes[$scene['scene_key']] = $sceneData;

        if ($scene['is_default']) {
            $defaultScene = $scene['scene_key'];
        }
    }

    // Fallback: nếu không có scene mặc định, lấy scene đầu tiên
    if (!$defaultScene && !empty($tourScenes)) {
        $defaultScene = array_key_first($tourScenes);
    }

    echo json_encode([
        'success' => true,
        'destination' => [
            'id' => (int)$dest['id'],
            'name' => $dest['name'],
            'slug' => $dest['slug'],
            'tour_type' => $dest['virtual_tour_type'],
            'audio_guide_url' => $dest['audio_guide_url'],
        ],
        'default_scene' => $defaultScene,
        'total_scenes' => count($tourScenes),
        'scenes' => $tourScenes,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} elseif ($action === 'log' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ghi log tương tác (analytics)
    $db = getDB();
    $user = currentUser();
    $userId = $user ? $user['id'] : null;

    $destId = (int)($_POST['destination_id'] ?? 0);
    $sceneId = ($_POST['scene_id'] ?? '') !== '' ? (int)$_POST['scene_id'] : null;
    $logAction = $_POST['log_action'] ?? '';
    $duration = ($_POST['duration'] ?? '') !== '' ? (int)$_POST['duration'] : null;

    // Detect device type
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $deviceType = 'desktop';
    if (preg_match('/tablet|ipad/i', $ua)) $deviceType = 'tablet';
    elseif (preg_match('/mobile|android|iphone/i', $ua)) $deviceType = 'mobile';

    $validActions = ['view_tour', 'change_scene', 'click_hotspot', 'play_audio', 'complete_tour'];

    if ($destId > 0 && in_array($logAction, $validActions)) {
        $stmt = $db->prepare("
            INSERT INTO virtual_tour_interactions
                (user_id, destination_id, scene_id, action, duration_seconds, device_type)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $destId, $sceneId, $logAction, $duration, $deviceType]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
