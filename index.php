<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';

$pdo = getDB();
$search = trim($_GET['search'] ?? '');

// BUG 2: Pencarian hanya di kolom judul — tidak mencari berdasarkan pengarang
// dan tidak menggunakan parameter binding yang benar untuk dua kolom
$params = [];
$whereClause = '';
if ($search !== '') {
    $whereClause = "WHERE judul LIKE ?";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("SELECT * FROM buku $whereClause ORDER BY judul");
$stmt->execute($params);
$bukuList = $stmt->fetchAll();

$pageTitle = 'Daftar Buku — PerpusKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] ?>"><?= htmlspecialchars($_SESSION['flash']['msg']) ?></div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="page-header"><h1>📚 Daftar Buku</h1></div>

    <form method="get" style="display:flex;gap:.75rem;margin-bottom:1.5rem;">
        <input type="text" name="search" placeholder="Cari judul buku..." value="<?= htmlspecialchars($search) ?>" style="flex:1;padding:.6rem .9rem;border:1px solid #dee2e6;border-radius:6px;">
        <button type="submit" class="btn btn-primary">Cari</button>
        <?php if ($search): ?><a href="index.php" class="btn btn-secondary">Reset</a><?php endif; ?>
    </form>

    <?php if (empty($bukuList)): ?>
        <p style="color:#888;text-align:center;padding:3rem 0;">Tidak ada buku ditemukan.</p>
    <?php else: ?>
    <div class="buku-grid">
        <?php foreach ($bukuList as $b): ?>
        <div class="buku-card">
            <div class="buku-judul"><?= htmlspecialchars($b['judul']) ?></div>
            <div class="buku-pengarang">✍️ <?= htmlspecialchars($b['pengarang']) ?></div>
            <div class="buku-info">
                <?php if ($b['penerbit']): ?>📖 <?= htmlspecialchars($b['penerbit']) ?> (<?= $b['tahun'] ?>)<br><?php endif; ?>
                📦 Stok: <?= $b['stok'] ?>
            </div>
            <?php if (!empty($_SESSION['user_id']) && $b['stok'] > 0 && $_SESSION['user_role'] !== 'admin'): ?>
            <form method="post" action="pinjam.php">
                <input type="hidden" name="buku_id" value="<?= $b['id'] ?>">
                <button type="submit" class="btn btn-primary btn-sm">Pinjam Buku</button>
            </form>
            <?php elseif ($b['stok'] == 0): ?>
                <span class="badge badge-danger">Stok Habis</span>
            <?php elseif (empty($_SESSION['user_id'])): ?>
                <a href="login.php" class="btn btn-secondary btn-sm">Login untuk meminjam</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
