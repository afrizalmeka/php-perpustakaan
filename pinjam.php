<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';
requireLogin();

if ($_SESSION['user_role'] === 'admin') { header('Location: index.php'); exit; }

$pdo = getDB();
$bukuId = (int)($_POST['buku_id'] ?? $_GET['id'] ?? 0);
if ($bukuId === 0) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM buku WHERE id = ?");
$stmt->execute([$bukuId]);
$buku = $stmt->fetch();

if (!$buku || $buku['stok'] < 1) {
    $_SESSION['flash'] = ['type'=>'error','msg'=>'Buku tidak tersedia.'];
    header('Location: index.php');
    exit;
}

// BUG 3: Tidak mengecek apakah user sudah meminjam buku yang sama
// sehingga user bisa meminjam buku yang sama lebih dari satu kali

$today      = date('Y-m-d');
// BUG 4: Masa pinjam hanya 3 hari (seharusnya 14 hari)
$returnPlan = date('Y-m-d', strtotime('+3 days'));

$pdo->beginTransaction();
$pdo->prepare("INSERT INTO peminjaman (user_id, buku_id, tanggal_pinjam, tanggal_kembali_rencana) VALUES (?,?,?,?)")
    ->execute([$_SESSION['user_id'], $bukuId, $today, $returnPlan]);
$pdo->prepare("UPDATE buku SET stok = stok - 1 WHERE id = ?")->execute([$bukuId]);
$pdo->commit();

$_SESSION['flash'] = ['type'=>'success','msg'=>"Buku berhasil dipinjam. Kembalikan sebelum $returnPlan."];
header('Location: peminjaman.php');
exit;
