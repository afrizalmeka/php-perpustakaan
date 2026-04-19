<?php require_once __DIR__ . '/../php/auth.php'; ?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle ?? 'PerpusKu') ?></title>
<link rel="stylesheet" href="<?= $cssPath ?? 'css/style.css' ?>">
</head><body>
<nav class="navbar">
    <a href="index.php" class="brand">📚 PerpusKu</a>
    <nav>
        <a href="index.php">Daftar Buku</a>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="peminjaman.php">Peminjaman Saya</a>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="admin_buku.php">Kelola Buku</a>
                <a href="admin_peminjaman.php">Kelola Peminjaman</a>
            <?php endif; ?>
            <a href="logout.php">Keluar (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
        <?php else: ?>
            <a href="login.php">Masuk</a>
            <a href="register.php">Daftar</a>
        <?php endif; ?>
    </nav>
</nav>
