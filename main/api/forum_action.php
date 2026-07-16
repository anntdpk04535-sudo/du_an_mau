<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$user = currentUser();
if (!$user) {
    echo json_encode(['success' => false, 'message' => __('forum_login_prompt')]);
    exit;
}

$action = $_POST['action'] ?? '';

function processMultipleUploads($filesArray) {
    $urls = [];
    if (!isset($filesArray['name']) || !is_array($filesArray['name'])) return $urls;
    
    $fileCount = count($filesArray['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        if ($filesArray['error'][$i] === UPLOAD_ERR_OK) {
            $fileTmpPath = $filesArray['tmp_name'][$i];
            $fileName = $filesArray['name'][$i];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            $allowedfileExtensions = ['jpg', 'gif', 'png', 'jpeg', 'webp'];
            
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $uploadFileDir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($uploadFileDir)) mkdir($uploadFileDir, 0755, true);
                
                $newFileName = md5(time() . rand() . $fileName) . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $urls[] = url('/assets/uploads/' . $newFileName);
                }
            }
        }
    }
    return $urls;
}

if ($action === 'post') {
    $content = trim($_POST['content'] ?? '');
    $destinationId = !empty($_POST['destination_id']) ? (int)$_POST['destination_id'] : null;
    
    if ($content === '') {
        echo json_encode(['success' => false, 'message' => 'Nội dung không được để trống']);
        exit;
    }

    $imageUrls = [];
    if (isset($_FILES['images'])) {
        $imageUrls = processMultipleUploads($_FILES['images']);
    }
    
    // For backwards compatibility, if they only upload one through old UI
    if (isset($_FILES['image'])) {
        $singleUrl = processMultipleUploads(['name'=>[$_FILES['image']['name']], 'type'=>[$_FILES['image']['type']], 'tmp_name'=>[$_FILES['image']['tmp_name']], 'error'=>[$_FILES['image']['error']], 'size'=>[$_FILES['image']['size']]]);
        $imageUrls = array_merge($imageUrls, $singleUrl);
    }

    $imageUrlStr = !empty($imageUrls) ? json_encode($imageUrls, JSON_UNESCAPED_SLASHES) : null;

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO checkins (user_id, destination_id, content, image_url) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['id'], $destinationId, $content, $imageUrlStr]);
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'like') {
    $checkinId = (int)($_POST['checkin_id'] ?? 0);
    $db = getDB();
    
    // Kiểm tra xem đã like chưa
    $stmt = $db->prepare("SELECT id FROM checkin_likes WHERE checkin_id = ? AND user_id = ?");
    $stmt->execute([$checkinId, $user['id']]);
    if ($stmt->fetch()) {
        // Đã like -> unlike
        $db->prepare("DELETE FROM checkin_likes WHERE checkin_id = ? AND user_id = ?")->execute([$checkinId, $user['id']]);
        $db->prepare("UPDATE checkins SET likes_count = likes_count - 1 WHERE id = ?")->execute([$checkinId]);
        echo json_encode(['success' => true, 'liked' => false]);
    } else {
        // Chưa like -> like
        $db->prepare("INSERT INTO checkin_likes (checkin_id, user_id) VALUES (?, ?)")->execute([$checkinId, $user['id']]);
        $db->prepare("UPDATE checkins SET likes_count = likes_count + 1 WHERE id = ?")->execute([$checkinId]);
        echo json_encode(['success' => true, 'liked' => true]);
    }
    exit;
}

if ($action === 'comment') {
    $checkinId = (int)($_POST['checkin_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    
    if ($content !== '') {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO checkin_comments (checkin_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$checkinId, $user['id'], $content]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nội dung bình luận không được trống']);
    }
    exit;
}

if ($action === 'edit_post') {
    $checkinId = (int)($_POST['checkin_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $removeImages = $_POST['remove_images'] ?? []; // array of URLs to remove
    if (!is_array($removeImages)) $removeImages = [];
    
    if ($content === '') {
        echo json_encode(['success' => false, 'message' => 'Nội dung không được để trống']);
        exit;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT image_url FROM checkins WHERE id = ? AND user_id = ?");
    $stmt->execute([$checkinId, $user['id']]);
    $row = $stmt->fetch();
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài viết']);
        exit;
    }
    
    $currentUrls = [];
    if ($row['image_url']) {
        $decoded = json_decode($row['image_url'], true);
        if (is_array($decoded)) {
            $currentUrls = $decoded;
        } else {
            $currentUrls = [$row['image_url']];
        }
    }

    // Process remove_images
    if (!empty($removeImages)) {
        $currentUrls = array_values(array_diff($currentUrls, $removeImages));
    }

    // Process new images
    if (isset($_FILES['images'])) {
        $newUrls = processMultipleUploads($_FILES['images']);
        $currentUrls = array_merge($currentUrls, $newUrls);
    }
    
    // For old single image upload fallback
    if (isset($_FILES['image'])) {
        $singleUrl = processMultipleUploads(['name'=>[$_FILES['image']['name']], 'type'=>[$_FILES['image']['type']], 'tmp_name'=>[$_FILES['image']['tmp_name']], 'error'=>[$_FILES['image']['error']], 'size'=>[$_FILES['image']['size']]]);
        $currentUrls = array_merge($currentUrls, $singleUrl);
    }
    
    // also fallback for old remove_image=1
    $oldRemove = (int)($_POST['remove_image'] ?? 0);
    if ($oldRemove === 1 && !empty($currentUrls)) {
        $currentUrls = [];
    }
    
    $imageUrlStr = !empty($currentUrls) ? json_encode($currentUrls, JSON_UNESCAPED_SLASHES) : null;
    
    $stmt = $db->prepare("UPDATE checkins SET content = ?, image_url = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$content, $imageUrlStr, $checkinId, $user['id']]);
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete_post') {
    $checkinId = (int)($_POST['checkin_id'] ?? 0);
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM checkins WHERE id = ? AND user_id = ?");
    $stmt->execute([$checkinId, $user['id']]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
?>
