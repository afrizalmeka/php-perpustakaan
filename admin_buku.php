<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';
requireAdmin();

$pdo = getDB();
$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
        $judul = trim($_POST['judul'] ?? '');
        $peng  = trim($_POST['pengarang'] ?? '');
        $isbn  = trim($_POST['isbn'] ?? '');
        $penerbit = trim($_POST['penerbit'] ?? '');
        $tahun = (int)($_POST['tahun'] ?? 0);
        $stok  = (int)($_POST['stok'] ?? 1);
        $kat   = trim($_POST['kategori'] ?? '');
        if ($judul === '' || $peng === '') { $error = 'Judul dan pengarang wajib diisi.'; }
        elseif ($stok < 0) { $error = 'Stok tidak boleh negatif.'; }
        else {
            try {
                $pdo->prepare("INSERT INTO buku (judul, pengarang, isbn, penerbit, tahun, stok, kategori) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$judul, $peng, $isbn ?: null, $penerbit, $tahun ?: null, $stok, $kat ?: null]);
                $msg = 'Buku berhasil ditambahkan.';
            } catch (Exception $e) { $error = 'ISBN sudah ada.'; }
        }
    } elseif ($act === 'edit') {
        $id    = (int)($_POST['id'] ?? 0);
        $judul = trim($_POST['judul'] ?? '');
        $peng  = trim($_POST['pengarang'] ?? '');
        $stok  = (int)($_POST['stok'] ?? 0);
        $isbn  = trim($_POST['isbn'] ?? '');
        $penerbit = trim($_POST['penerbit'] ?? '');
        $tahun = (int)($_POST['tahun'] ?? 0);
        $kat   = trim($_POST['kategori'] ?? '');
        if ($judul === '' || $peng === '') { $error = 'Judul dan pengarang wajib diisi.'; }
        else {
            $pdo->prepare("UPDATE buku SET judul=?,pengarang=?,isbn=?,penerbit=?,tahun=?,stok=?,kategori=? WHERE id=?")
                ->execute([$judul, $peng, $isbn ?: null, $penerbit, $tahun ?: null, $stok, $kat ?: null, $id]);
            $msg = 'Buku berhasil diperbarui.';
        }
    } elseif ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE buku_id = ? AND status = 'dipinjam'");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) { $error = 'Buku masih dipinjam, tidak dapat dihapus.'; }
        else { $pdo->prepare("DELETE FROM buku WHERE id = ?")->execute([$id]); $msg = 'Buku berhasil dihapus.'; }
    }
}

$bukuList = $pdo->query("SELECT * FROM buku ORDER BY judul")->fetchAll();
$editBuku = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM buku WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editBuku = $stmt->fetch();
}

$pageTitle = 'Kelola Buku — PerpusKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <div class="page-header"><h1>Kelola Buku</h1></div>
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-header"><?= $editBuku ? 'Edit Buku' : 'Tambah Buku' ?></div>
        <div class="card-body">
            <form method="post" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr 1fr auto;gap:.6rem;align-items:end;">
                <input type="hidden" name="action" value="<?= $editBuku ? 'edit' : 'add' ?>">
                <?php if ($editBuku): ?><input type="hidden" name="id" value="<?= $editBuku['id'] ?>"><?php endif; ?>
                <div class="form-group" style="margin:0;"><label>Judul</label><input type="text" name="judul" value="<?= htmlspecialchars($editBuku['judul'] ?? '') ?>" required></div>
                <div class="form-group" style="margin:0;"><label>Pengarang</label><input type="text" name="pengarang" value="<?= htmlspecialchars($editBuku['pengarang'] ?? '') ?>" required></div>
                <div class="form-group" style="margin:0;"><label>ISBN</label><input type="text" name="isbn" value="<?= htmlspecialchars($editBuku['isbn'] ?? '') ?>"></div>
                <div class="form-group" style="margin:0;"><label>Penerbit</label><input type="text" name="penerbit" value="<?= htmlspecialchars($editBuku['penerbit'] ?? '') ?>"></div>
                <div class="form-group" style="margin:0;"><label>Tahun</label><input type="number" name="tahun" value="<?= $editBuku['tahun'] ?? '' ?>" min="1900" max="2099"></div>
                <div class="form-group" style="margin:0;"><label>Stok</label><input type="number" name="stok" value="<?= $editBuku['stok'] ?? 1 ?>" min="0" required></div>
                <button type="submit" class="btn btn-<?= $editBuku ? 'primary' : 'success' ?>"><?= $editBuku ? 'Update' : 'Tambah' ?></button>
            </form>
        </div>
    </div>

    <div class="card"><div class="card-body" style="padding:0;">
        <table>
            <thead><tr><th>Judul</th><th>Pengarang</th><th>ISBN</th><th>Stok</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($bukuList as $b): ?>
            <tr>
                <td><?= htmlspecialchars($b['judul']) ?></td>
                <td><?= htmlspecialchars($b['pengarang']) ?></td>
                <td><?= htmlspecialchars($b['isbn'] ?? '-') ?></td>
                <td><?= $b['stok'] ?></td>
                <td style="display:flex;gap:.4rem;">
                    <a href="admin_buku.php?edit=<?= $b['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                    <form method="post" onsubmit="return confirm('Hapus buku ini?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
</div>
</body>
</html>
