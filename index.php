<?php
require_once 'includes/db.php';

// Arama işlemi
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$sql = "SELECT * FROM products WHERE name LIKE :search ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['search' => '%' . $search . '%']);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ürün Yönetimi</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h1>Ürün Yönetim Paneli</h1>

    <form method="get" action="">
        <input type="text" name="search" placeholder="Ürün Ara..." value="<?= $search ?>">
        <button type="submit">Ara</button>
        <a href="add.php"><button type="button">Yeni Ürün Ekle</button></a>
    </form>

    <table>
        <thead>
            <tr>
                <th>Görsel</th>
                <th>Ad</th>
                <th>Açıklama</th>
                <th>Fiyat (₺)</th>
                <th>Stok</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($products) > 0): ?>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <?php if (!empty($product['image'])): ?>
                            <img src="uploads/<?= htmlspecialchars($product['image']) ?>" alt="Ürün Görseli" style="max-width: 80px; border-radius: 6px;">
                        <?php else: ?>
                            <em>Yok</em>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= htmlspecialchars($product['description']) ?></td>
                    <td><?= number_format($product['price'], 2) ?></td>
                    <td><?= $product['stock'] ?></td>
                    <td>
                        <a href="update.php?id=<?= $product['id'] ?>">Güncelle</a> |
                        <a href="delete.php?id=<?= $product['id'] ?>" onclick="return confirm('Silmek istediğinize emin misiniz?')">Sil</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">Ürün bulunamadı.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="chatbox">
        <h2>Yapay Zeka Asistan</h2>
        <input type="text" id="chatInput" placeholder="Bir soru sor...">
        <button onclick="askChat()">Sor</button>
        <p id="chatResponse" style="margin-top: 10px;"></p>
    </div>
</div>

<script>
function askChat() {
    const input = document.getElementById("chatInput").value;
    const responseArea = document.getElementById("chatResponse");
    responseArea.innerHTML = "Yanıt bekleniyor...";

    fetch("chatbot.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ question: input })
    })
    .then(res => res.json())
    .then(data => {
        responseArea.innerHTML = "<strong>Yanıt:</strong> " + data.response;
    })
    .catch(err => {
        responseArea.innerHTML = "Bir hata oluştu: " + err;
    });
}
</script>
</body>
</html>
