<?php
declare(strict_types=1);

require_once __DIR__ . '/content_helpers.php';
require_once __DIR__ . '/geo.php';

function ragEmbedding(string $text): ?array
{
    if (getenv('RAG_EMBEDDINGS_ENABLED') === '0') return null;
    $key = getenv('GEMINI_API_KEY') ?: '';
    if ($key === '' || trim($text) === '') return null;
    $model = getenv('GEMINI_EMBEDDING_MODEL') ?: 'gemini-embedding-2';
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':embedContent?key=' . rawurlencode($key);
    $payload = ['content' => ['parts' => [['text' => mb_substr($text, 0, 12000)]]], 'output_dimensionality' => 768];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>json_encode($payload, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_TIMEOUT=>20]);
    $raw = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (!$raw || $code < 200 || $code >= 300) return null;
    $data = json_decode($raw, true);
    $values = $data['embedding']['values'] ?? $data['embeddings'][0]['values'] ?? null;
    return is_array($values) ? array_map('floatval', $values) : null;
}

function ragCosine(array $a, array $b): float
{
    $dot = 0.0; $na = 0.0; $nb = 0.0; $n = min(count($a), count($b));
    for ($i=0; $i<$n; $i++) { $x=(float)$a[$i]; $y=(float)$b[$i]; $dot += $x*$y; $na += $x*$x; $nb += $y*$y; }
    return ($na > 0 && $nb > 0) ? $dot / sqrt($na*$nb) : 0.0;
}

function ragEntityRows(PDO $db): array
{
    $rows = [];
    $provinceColumn = columnExists($db, 'destinations', 'province') ? 'province' : 'NULL AS province';
    $dest = $db->query("SELECT id, name, name_en, slug, short_desc, short_desc_en, description, description_en, address, {$provinceColumn}, tags, price_level, rating FROM destinations")->fetchAll() ?: [];
    foreach ($dest as $r) { $rows[] = ['entity_type'=>'destination','entity_id'=>(int)$r['id'],'title'=>$r['name'],'slug'=>$r['slug'],'address'=>$r['address']??'','content'=>implode(' | ', array_filter([$r['name'],$r['name_en'],$r['short_desc'],$r['short_desc_en'],$r['description'],$r['description_en'],$r['address'],$r['province'],$r['tags'],'giá '.$r['price_level'],'rating '.$r['rating']]))]; }
    if (tableExists($db, 'foods')) { foreach ($db->query("SELECT id,name,name_en,slug,description,description_en,address,entity_type,price_min,price_max FROM foods WHERE status='published'")->fetchAll() ?: [] as $r) $rows[]=['entity_type'=>'food','entity_id'=>(int)$r['id'],'title'=>$r['name'],'slug'=>$r['slug'],'address'=>$r['address']??'','content'=>implode(' | ',array_filter([$r['name'],$r['name_en'],$r['description'],$r['description_en'],$r['address'],$r['entity_type'],'giá '.$r['price_min'].'-'.$r['price_max']]))]; }
    if (tableExists($db, 'accommodations')) { foreach ($db->query("SELECT id,name,name_en,slug,description,description_en,address,accommodation_type,price_min,price_max FROM accommodations WHERE status='published'")->fetchAll() ?: [] as $r) $rows[]=['entity_type'=>'accommodation','entity_id'=>(int)$r['id'],'title'=>$r['name'],'slug'=>$r['slug'],'address'=>$r['address']??'','content'=>implode(' | ',array_filter([$r['name'],$r['name_en'],$r['description'],$r['description_en'],$r['address'],$r['accommodation_type'],'giá '.$r['price_min'].'-'.$r['price_max']]))]; }
    return $rows;
}

function ragSearch(string $query, int $limit = 8): array
{
    $db = getDB(); $embedding = ragEmbedding($query); $results=[];
    if (tableExists($db, 'search_documents') && $embedding) {
        foreach ($db->query('SELECT * FROM search_documents WHERE embedding_json IS NOT NULL')->fetchAll() ?: [] as $row) { $v=json_decode((string)$row['embedding_json'],true); if(!is_array($v)) continue; $results[]=['entity_type'=>$row['entity_type'],'entity_id'=>(int)$row['entity_id'],'content'=>$row['content'],'score'=>ragCosine($embedding,$v)]; }
    }
    if (!$results) {
        $terms = preg_split('/\s+/u', mb_strtolower(trim($query))) ?: [];
        foreach (ragEntityRows($db) as $row) { $hay=mb_strtolower($row['content']); $hits=0; foreach($terms as $term){if(mb_strlen($term)>1 && mb_strpos($hay,$term)!==false)$hits++;} if($hits) $results[]=[...$row,'score'=>min(0.99,$hits/max(1,count($terms)))]; }
    }
    usort($results, fn($a,$b)=>(float)$b['score']<=>(float)$a['score']);
    return array_slice($results,0,$limit);
}

/**
 * RAG có trọng số địa lý: final = 0.6*semantic + 0.4*proximity.
 * proximity = max(0, 1 - distance_km/radiusKm); entity thiếu tọa độ (own/FK)
 * nhận proximity trung tính 0.35 + cờ geo_unknown (không loại, không bịa khoảng cách).
 * Không truyền lat/lng → chấm điểm thuần semantic (hành vi ragSearch cũ + metadata).
 *
 * $opts: ['exclude_outdoor' => true] loại destination indoor_type='outdoor' (ngày mưa/dông).
 */
function ragSearchGeo(string $query, ?float $lat = null, ?float $lng = null, float $radiusKm = 25.0, int $limit = 20, array $opts = []): array
{
    $db = getDB();
    $hasOrigin = $lat !== null && $lng !== null && geoIsValidPoint($lat, $lng);
    $radiusKm = max(1.0, $radiusKm);
    $candidates = ragSearch($query, max($limit * 3, 30));
    if (!$candidates) return [];

    $idsByType = [];
    foreach ($candidates as $c) $idsByType[$c['entity_type']][] = (int)$c['entity_id'];

    $meta = [];
    if (!empty($idsByType['destination'])) {
        $in = implode(',', array_map('intval', array_unique($idsByType['destination'])));
        $hasIndoor = columnExists($db, 'destinations', 'indoor_type');
        $indoorCols = $hasIndoor ? ', indoor_type, weather_sensitivity' : ', NULL AS indoor_type, NULL AS weather_sensitivity';
        foreach ($db->query("SELECT id, name, slug, address, latitude, longitude, avg_visit_hours, price_level, rating{$indoorCols} FROM destinations WHERE id IN ({$in})")->fetchAll() ?: [] as $r) {
            $meta['destination'][(int)$r['id']] = $r;
        }
    }
    foreach (['food' => 'foods', 'accommodation' => 'accommodations'] as $type => $table) {
        if (empty($idsByType[$type]) || !tableExists($db, $table)) continue;
        $in = implode(',', array_map('intval', array_unique($idsByType[$type])));
        foreach ($db->query("SELECT t.id, t.name, t.slug, t.address, t.latitude, t.longitude, t.price_min, t.price_max, d.latitude AS dlat, d.longitude AS dlng FROM {$table} t LEFT JOIN destinations d ON d.id = t.destination_id WHERE t.id IN ({$in})")->fetchAll() ?: [] as $r) {
            $meta[$type][(int)$r['id']] = $r;
        }
    }

    $out = [];
    foreach ($candidates as $c) {
        $m = $meta[$c['entity_type']][(int)$c['entity_id']] ?? null;
        if (!$m) continue;

        $pLat = $m['latitude'] !== null ? (float)$m['latitude'] : null;
        $pLng = $m['longitude'] !== null ? (float)$m['longitude'] : null;
        if (!geoIsValidPoint($pLat, $pLng) && isset($m['dlat']) && $m['dlat'] !== null) {
            $pLat = (float)$m['dlat'];
            $pLng = (float)$m['dlng'];
        }
        $hasPoint = geoIsValidPoint($pLat, $pLng);

        if (!empty($opts['exclude_outdoor']) && $c['entity_type'] === 'destination' && ($m['indoor_type'] ?? null) === 'outdoor') {
            continue;
        }

        $distanceKm = null;
        $geoUnknown = false;
        if ($hasOrigin && $hasPoint) {
            $distanceKm = round(geoHaversineMeters($lat, $lng, $pLat, $pLng) / 1000, 1);
            $proximity = max(0.0, 1.0 - $distanceKm / $radiusKm);
        } elseif ($hasOrigin) {
            $proximity = 0.35;
            $geoUnknown = true;
        } else {
            $proximity = 0.0;
        }

        $semantic = (float)$c['score'];
        $out[] = [
            'entity_type' => $c['entity_type'],
            'entity_id' => (int)$c['entity_id'],
            'title' => (string)$m['name'],
            'slug' => (string)($m['slug'] ?? ''),
            'address' => (string)($m['address'] ?? ''),
            'lat' => $hasPoint ? $pLat : null,
            'lng' => $hasPoint ? $pLng : null,
            'distance_km' => $distanceKm,
            'geo_unknown' => $geoUnknown,
            'indoor_type' => $m['indoor_type'] ?? null,
            'weather_sensitivity' => isset($m['weather_sensitivity']) && $m['weather_sensitivity'] !== null ? (int)$m['weather_sensitivity'] : null,
            'avg_visit_hours' => isset($m['avg_visit_hours']) ? (float)$m['avg_visit_hours'] : null,
            'price_level' => $m['price_level'] ?? null,
            'price_min' => isset($m['price_min']) ? $m['price_min'] : null,
            'price_max' => isset($m['price_max']) ? $m['price_max'] : null,
            'rating' => isset($m['rating']) ? $m['rating'] : null,
            'semantic_score' => round($semantic, 4),
            'proximity_score' => round($proximity, 4),
            'score' => round($hasOrigin ? 0.6 * $semantic + 0.4 * $proximity : $semantic, 4),
        ];
    }

    usort($out, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($out, 0, $limit);
}

/**
 * Biến kết quả ragSearchGeo thành các dòng context cho prompt AI (tối đa $max dòng).
 * Kèm ID để AI trả về đúng destination_id, khoảng cách và nhãn trong nhà/ngoài trời.
 */
function ragContextLines(array $results, int $max = 40): array
{
    $indoorLabel = ['indoor' => 'trong nhà', 'outdoor' => 'ngoài trời', 'mixed' => 'kết hợp'];
    $typeLabel = ['destination' => 'Điểm đến', 'food' => 'Ẩm thực', 'accommodation' => 'Lưu trú'];
    $lines = [];
    foreach (array_slice($results, 0, $max) as $r) {
        $parts = [];
        if ($r['distance_km'] !== null) $parts[] = 'cách điểm xuất phát ' . $r['distance_km'] . 'km';
        elseif (!empty($r['geo_unknown'])) $parts[] = 'chưa rõ tọa độ';
        if (!empty($r['indoor_type'])) $parts[] = $indoorLabel[$r['indoor_type']] ?? $r['indoor_type'];
        if ($r['entity_type'] === 'destination') {
            if (!empty($r['avg_visit_hours'])) $parts[] = '~' . $r['avg_visit_hours'] . 'h tham quan';
            if (!empty($r['rating'])) $parts[] = 'rating ' . $r['rating'];
        } elseif ($r['price_min'] !== null || $r['price_max'] !== null) {
            $parts[] = 'giá ' . (float)$r['price_min'] . '-' . (float)$r['price_max'];
        }
        if ($r['address'] !== '') $parts[] = $r['address'];
        $lines[] = sprintf('- %s ID %d: %s (%s)', $typeLabel[$r['entity_type']] ?? $r['entity_type'], $r['entity_id'], $r['title'], implode('; ', $parts));
    }
    return $lines;
}

function ragUpsertDocuments(): array
{
    $db=getDB(); if(!tableExists($db,'search_documents')) return ['indexed'=>0,'skipped'=>0]; $indexed=0;$skipped=0;$model=getenv('GEMINI_EMBEDDING_MODEL')?:'gemini-embedding-2';
    foreach(ragEntityRows($db) as $row){$hash=hash('sha256',$row['content']);$check=$db->prepare('SELECT content_hash FROM search_documents WHERE entity_type=? AND entity_id=? AND locale=?');$check->execute([$row['entity_type'],$row['entity_id'],'vi']);if($check->fetchColumn()===$hash){$skipped++;continue;} $v=ragEmbedding($row['content']); $s=$db->prepare('INSERT INTO search_documents(entity_type,entity_id,locale,content,content_hash,embedding_model,embedding_json) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE content=VALUES(content),content_hash=VALUES(content_hash),embedding_model=VALUES(embedding_model),embedding_json=VALUES(embedding_json)');$s->execute([$row['entity_type'],$row['entity_id'],'vi',$row['content'],$hash,$v?$model:null,$v?json_encode($v):null]);$indexed++;}
    return compact('indexed','skipped');
}

function ragResultCards(array $results): array
{
    $db = getDB(); $cards = [];
    foreach ($results as $r) {
        $id = (int)$r['entity_id']; $type = $r['entity_type']; $x = null; $url = ''; $image = '';
        if ($type === 'destination') {
            $s=$db->prepare('SELECT id,name,slug,address,image_url FROM destinations WHERE id=?'); $s->execute([$id]); $x=$s->fetch();
            if ($x) {$url=url('/public/destination.php?slug='.rawurlencode((string)$x['slug']));$image=(string)($x['image_url']??'');}
        } elseif ($type === 'food' && tableExists($db,'foods')) {
            $s=$db->prepare('SELECT id,name,address FROM foods WHERE id=?'); $s->execute([$id]); $x=$s->fetch();
            if ($x) {$url=url('/public/chatbot.php?ask='.rawurlencode('Thông tin về '.$x['name']));}
            if ($x && tableExists($db,'food_images')) {$i=$db->prepare('SELECT image_url FROM food_images WHERE food_id=? ORDER BY is_primary DESC,sort_order,id LIMIT 1');$i->execute([$id]);$image=(string)($i->fetchColumn()?:'');}
        } elseif ($type === 'accommodation' && tableExists($db,'accommodations')) {
            $s=$db->prepare('SELECT id,name,address FROM accommodations WHERE id=?'); $s->execute([$id]); $x=$s->fetch();
            if ($x) {$url=url('/public/chatbot.php?ask='.rawurlencode('Thông tin về '.$x['name']));}
            if ($x && tableExists($db,'accommodation_images')) {$i=$db->prepare('SELECT image_url FROM accommodation_images WHERE accommodation_id=? ORDER BY is_primary DESC,sort_order,id LIMIT 1');$i->execute([$id]);$image=(string)($i->fetchColumn()?:'');}
        }
        if ($x) $cards[]=['type'=>$type,'id'=>$id,'title'=>$x['name'],'image_url'=>$image,'address'=>$x['address']??'','url'=>$url,'score'=>(float)$r['score']];
    }
    return $cards;
}
