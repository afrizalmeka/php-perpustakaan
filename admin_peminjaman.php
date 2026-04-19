<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';
requireAdmin();

$pdo = getDB();
$msg = '';
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'kembalikan') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT p.*, b.id AS b_id FROM peminjaman p JOIN buku b ON p.buku_id = b.id WHERE p.id = ? AND p.status = 'dipinjam'");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if ($p) {
        $denda = 0;
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE peminjaman SET status='dikembalikan', tanggal_kembali_aktual=?, denda=? WHERE id=?")
            ->execute([$today, $denda, $id]);
        $pdo->prepare("UPDATE buku SET stok = stok + 1 WHERE id = ?")->execute([$p['b_id']]);
        $pdo->commit();
        $msg = 'Buku berhasil dikembalikan.';
    }
}

$stmt = $pdo->query("SELECT p.*, b.judul, u.name AS peminjam, u.nim FROM peminjaman p JOIN buku b ON p.buku_id = b.id JOIN users u ON p.user_id = u.id ORDER BY p.status, p.tanggal_pinjam DESC");
$peminjamanList = $stmt->fetchAll();

$pageTitle = 'Kelola Peminjaman — PerpusKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <div class="page-header"><h1>Kelola Peminjaman</h1></div>
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="card"><div class="card-body" style="padding:0;">
        <table>
            <thead><tr><th>Peminjam</th><th>NIM</th><th>Buku</th><th>Tgl Pinjam</th><th>Tgl Kembali</th><th>Status</th><th>Denda</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($peminjamanList as $p): ?>
            <?php $isOverdue = $p['status'] === 'dipinjam' && $today > $p['tanggal_kembali_rencana']; ?>
            <tr>
                <td><?= htmlspecialchars($p['peminjam']) ?></td>
                <td><?= htmlspecialchars($p['nim'] ?? '-') ?></td>
                <td><?= htmlspecialchars($p['judul']) ?></td>
                <td><?= $p['tanggal_pinjam'] ?></td>
                <td><?= $p['tanggal_kembali_rencana'] ?></td>
                <td>
                    <?php if ($p['status'] === 'dikembalikan'): ?>
                        <span class="badge badge-success">Dikembalikan</span>
                    <?php elseif ($isOverdue): ?>
                        <span class="badge badge-danger">Terlambat</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Dipinjam</span>
                    <?php endif; ?>
                </td>
                <td>Rp <?= number_format($p['denda'],0,',','.') ?></td>
                <td>
                    <?php if ($p['status'] === 'dipinjam'): ?>
                    <form method="post" onsubmit="return confirm('Proses pengembalian?')">
                        <input type="hidden" name="action" value="kembalikan">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-success btn-sm">Kembalikan</button>
                    </form>
                    <?php else: ?>✓<?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>
</body>
</html>
