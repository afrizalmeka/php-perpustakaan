<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';
requireLogin();

$pdo = getDB();
$today = date('Y-m-d');

$stmt = $pdo->prepare("SELECT p.*, b.judul, b.pengarang FROM peminjaman p JOIN buku b ON p.buku_id = b.id WHERE p.user_id = ? ORDER BY p.tanggal_pinjam DESC");
$stmt->execute([$_SESSION['user_id']]);
$peminjamanList = $stmt->fetchAll();

$pageTitle = 'Peminjaman Saya — PerpusKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] ?>"><?= htmlspecialchars($_SESSION['flash']['msg']) ?></div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="page-header"><h1>📖 Peminjaman Saya</h1></div>

    <?php if (empty($peminjamanList)): ?>
        <div class="card"><div class="card-body" style="text-align:center;padding:2rem;">Belum ada peminjaman.</div></div>
    <?php else: ?>
    <div class="card"><div class="card-body" style="padding:0;">
        <table>
            <thead><tr><th>Buku</th><th>Tgl Pinjam</th><th>Tgl Kembali</th><th>Status</th><th>Denda</th></tr></thead>
            <tbody>
            <?php foreach ($peminjamanList as $p): ?>
            <?php
                $isOverdue = $p['status'] === 'dipinjam' && $today > $p['tanggal_kembali_rencana'];
                $lateDays = $isOverdue ? intval((strtotime($today) - strtotime($p['tanggal_kembali_rencana'])) / 86400) : 0;
                $estimDenda = $lateDays * 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($p['judul']) ?></td>
                <td><?= $p['tanggal_pinjam'] ?></td>
                <td><?= $p['tanggal_kembali_rencana'] ?></td>
                <td>
                    <?php if ($p['status'] === 'dipinjam' && $isOverdue): ?>
                        <span class="badge badge-danger">Terlambat <?= $lateDays ?> hari</span>
                    <?php elseif ($p['status'] === 'dipinjam'): ?>
                        <span class="badge badge-warning">Dipinjam</span>
                    <?php else: ?>
                        <span class="badge badge-success">Dikembalikan</span>
                    <?php endif; ?>
                </td>
                <td><?= $p['status'] === 'dikembalikan' ? 'Rp '.number_format($p['denda'],0,',','.') : ($estimDenda > 0 ? 'Est. Rp '.number_format($estimDenda,0,',','.') : '-') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
    <?php endif; ?>
</div>
</body>
</html>
