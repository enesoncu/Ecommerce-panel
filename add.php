<?php
require_once 'includes/db.php';

$name = $description = $price = $stock = '';
$imageName = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);

    // Görsel yükleme
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
        $uploadPath = 'uploads/' . $imageName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            $errors[] = "Görsel yüklenemedi.";
        }
    }

    if (empty($name) || $price <= 0 || $stock < 0) {
        $errors[] = "Tüm gerekli alanları doldurun ve geçerli değerler girin.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $stock, $imageName]);
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Ürün Ekle</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h1>Yeni Ürün Ekle</h1>
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

        <label>Ürün Görseli:</label><br>
        <input type="file" name="image"><br><br>

        <button type="submit">Ekle</button>
    </form>
</div>
</body>
</html>
