<?php
require_once 'includes/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("Geçersiz ID");
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Ürün bulunamadı.");
}

$name = $product['name'];
$description = $product['description'];
$price = $product['price'];
$stock = $product['stock'];
$currentImage = $product['image'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $newImageName = $currentImage;

    // Yeni görsel yüklendiyse
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $newImageName = uniqid() . '_' . basename($_FILES['image']['name']);
        $uploadPath = 'uploads/' . $newImageName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            // İsteğe bağlı: eski görseli sil
            if ($currentImage && file_exists("uploads/$currentImage")) {
                unlink("uploads/$currentImage");
            }
        } else {
            $errors[] = "Görsel yüklenemedi.";
        }
    }

    if (empty($name) || $price <= 0 || $stock < 0) {
        $errors[] = "Lütfen tüm alanları doğru doldurun.";
    }

    if (empty($errors)) {
        $update = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, image=? WHERE id=?");
        $update->execute([$name, $description, $price, $stock, $newImageName, $id]);
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ürünü Güncelle</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h1>Ürünü Güncelle</h1>
    <a href="index.php">← Geri Dön</a>

    <?php if (!empty($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $err): ?>
                <li><?= $err ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Ürün Adı:</label><br>
        <input type="text" name="name" value="<?= $name ?>"><br><br>

        <label>Açıklama:</label><br>
        <textarea name="description"><?= $description ?></textarea><br><br>

        <label>Fiyat (₺):</label><br>
        <input type="number" step="0.01" name="price" value="<?= $price ?>"><br><br>

        <label>Stok:</label><br>
        <input type="number" name="stock" value="<?= $stock ?>"><br><br>

        <label>Mevcut Görsel:</label><br>
        <?php if ($currentImage): ?>
            <img src="uploads/<?= htmlspecialchars($currentImage) ?>" style="max-width: 120px; border-radius: 6px;"><br><br>
        <?php else: ?>
            <em>Görsel yok</em><br><br>
        <?php endif; ?>

        <label>Yeni Görsel (İsteğe Bağlı):</label><br>
        <input type="file" name="image"><br><br>

        <button type="submit">Güncelle</button>
    </form>
</div>
</body>
</html>
