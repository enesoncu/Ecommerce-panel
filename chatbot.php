<?php
header('Content-Type: application/json');

// 1) Veritabanı bağlantısı
require_once 'includes/db.php';

// 2) Gelen soruyu al ve normalize et
$input    = json_decode(file_get_contents("php://input"), true);
$question = trim($input['question'] ?? '');
if (!$question) {
    echo json_encode(['response' => 'Lütfen bir soru girin.']);
    exit;
}
$lower = mb_strtolower($question, 'UTF-8');

// 0) Basit selamlaşma
if (preg_match('/\b(merhaba|selam|günaydın|iyi akşamlar)\b/ui', $lower)) {
    echo json_encode(['response' => 'Merhaba! Size nasıl yardımcı olabilirim?']);
    exit;
}

// 1) Tüm ürünleri listeleme
if (str_contains($lower, 'tüm ürün') || str_contains($lower, 'ürünleri listele')) {
    $rows = $pdo->query("SELECT name, description, price, stock, image FROM products")
                ->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) === 0) {
        echo json_encode(['response' => 'Ürün Mevcut Değildir']);
    } else {
        $html = '';
        foreach ($rows as $p) {
            $html .= '<div style="display:flex; align-items:center; margin-bottom:10px;">';
            $html .= '<img src="uploads/' . htmlspecialchars($p['image']) . '" '
                   . 'style="width:50px;height:50px;object-fit:cover;border-radius:4px;margin-right:10px;">';
            $html .= '<div>';
            $html .= '<strong>' . htmlspecialchars($p['name']) . '</strong><br>';
            $html .= 'Fiyat: ' . number_format($p['price'],2) . '₺<br>';
            $html .= 'Stok: ' . intval($p['stock']) . ' adet';
            $html .= '</div>';
            $html .= '</div>';
        }
        echo json_encode(['response' => $html]);
    }
    exit;
}

// 2) Fiyatı X TL olan ürünler
if (preg_match('/fiyat(?:ı|u)?\s*([0-9]+(?:\.[0-9]+)?)/ui', $question, $m)) {
    $stmt = $pdo->prepare("SELECT name, description, price, stock, image FROM products WHERE price = ?");
    $stmt->execute([floatval($m[1])]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) === 0) {
        echo json_encode(['response' => 'Ürün Mevcut Değildir']);
    } else {
        $html = '';
        foreach ($rows as $p) {
            $html .= '<div style="display:flex; align-items:center; margin-bottom:10px;">';
            $html .= '<img src="uploads/' . htmlspecialchars($p['image']) . '" '
                   . 'style="width:50px;height:50px;object-fit:cover;border-radius:4px;margin-right:10px;">';
            $html .= '<div>';
            $html .= '<strong>' . htmlspecialchars($p['name']) . '</strong><br>';
            $html .= 'Fiyat: ' . number_format($p['price'],2) . '₺<br>';
            $html .= 'Stok: ' . intval($p['stock']) . ' adet';
            $html .= '</div>';
            $html .= '</div>';
        }
        echo json_encode(['response' => $html]);
    }
    exit;
}

// 3) Stok sayısı X olan ürünler
if (preg_match('/stok(?:\s*sayısı)?\s*([0-9]+)/ui', $question, $m)) {
    $stmt = $pdo->prepare("SELECT name, description, price, stock, image FROM products WHERE stock = ?");
    $stmt->execute([intval($m[1])]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) === 0) {
        echo json_encode(['response' => 'Ürün Mevcut Değildir']);
    } else {
        $html = '';
        foreach ($rows as $p) {
            $html .= '<div style="display:flex; align-items:center; margin-bottom:10px;">';
            $html .= '<img src="uploads/' . htmlspecialchars($p['image']) . '" '
                   . 'style="width:50px;height:50px;object-fit:cover;border-radius:4px;margin-right:10px;">';
            $html .= '<div>';
            $html .= '<strong>' . htmlspecialchars($p['name']) . '</strong><br>';
            $html .= 'Fiyat: ' . number_format($p['price'],2) . '₺<br>';
            $html .= 'Stok: ' . intval($p['stock']) . ' adet';
            $html .= '</div>';
            $html .= '</div>';
        }
        echo json_encode(['response' => $html]);
    }
    exit;
}

// 4) Belirli ürün adı sorgusu
if (preg_match('/ürün.*?([^\s]+)$/i', $question, $m)) {
    $name = trim($m[1]);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name = ?");
    $stmt->execute([$name]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$p) {
        echo json_encode(['response' => 'Ürün Mevcut Değildir']);
    } else {
        $html  = '<div style="display:flex; align-items:center; margin-bottom:10px;">';
        $html .= '<img src="uploads/' . htmlspecialchars($p['image']) . '" '
               . 'style="width:50px;height:50px;object-fit:cover;border-radius:4px;margin-right:10px;">';
        $html .= '<div>';
        $html .= '<strong>' . htmlspecialchars($p['name']) . '</strong><br>';
        $html .= 'Fiyat: ' . number_format($p['price'],2) . '₺<br>';
        $html .= 'Stok: ' . intval($p['stock']) . ' adet';
        $html .= '</div>';
        $html .= '</div>';
        echo json_encode(['response' => $html]);
    }
    exit;
}

// 5) Genel filtre: name, description, price, stock üzerinde LIKE
$term = trim(preg_replace('/\b(getir|listele|söyle|adet|kaç|lütfen)\b/iu', '', $lower));
if (strlen($term) > 0) {
    $like = "%{$term}%";
    $stmt = $pdo->prepare("
        SELECT name, description, price, stock, image
        FROM products
        WHERE name LIKE ?
           OR description LIKE ?
           OR CAST(price AS CHAR) LIKE ?
           OR CAST(stock AS CHAR) LIKE ?
    ");
    $stmt->execute([$like, $like, $like, $like]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) === 0) {
        echo json_encode(['response' => 'Ürün Mevcut Değildir']);
    } else {
        $html = '';
        foreach ($rows as $p) {
            $html .= '<div style="display:flex; align-items:center; margin-bottom:10px;">';
            $html .= '<img src="uploads/' . htmlspecialchars($p['image']) . '" '
                   . 'style="width:50px;height:50px;object-fit:cover;border-radius:4px;margin-right:10px;">';
            $html .= '<div>';
            $html .= '<strong>' . htmlspecialchars($p['name']) . '</strong><br>';
            $html .= 'Fiyat: ' . number_format($p['price'],2) . '₺<br>';
            $html .= 'Stok: ' . intval($p['stock']) . ' adet';
            $html .= '</div>';
            $html .= '</div>';
        }
        echo json_encode(['response' => $html]);
    }
    exit;
}

// 6) Fallback: OpenAI ile serbest yorum
$apiKey = 'sk-...';
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        "Authorization: Bearer {$apiKey}"
    ],
    CURLOPT_POST       => true,
    CURLOPT_POSTFIELDS => json_encode([
        'model'    => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'E-ticaret ürün yönetimi konusunda uzman bir asistan.'],
            ['role' => 'user',   'content' => $question]
        ]
    ]),
    CURLOPT_TIMEOUT    => 30
]);
$result = curl_exec($ch);
$code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($code !== 200) {
    echo json_encode(['response' => "OpenAI API hatası (HTTP {$code})."]);
    exit;
}
$resp   = json_decode($result, true);
$answer = $resp['choices'][0]['message']['content'] ?? 'API’den geçerli bir yanıt alınamadı.';
echo json_encode(['response' => $answer]);
?>
