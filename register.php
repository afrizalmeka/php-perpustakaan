<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $nim  = trim($_POST['nim'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if ($name === '' || $email === '' || $pass === '') { $error = 'Nama, email, dan password wajib diisi.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Format email tidak valid.'; }
    elseif (strlen($pass) < 6) { $error = 'Password minimal 6 karakter.'; }
    elseif ($pass !== $confirm) { $error = 'Konfirmasi password tidak cocok.'; }
    else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) { $error = 'Email sudah terdaftar.'; }
        else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (name, nim, email, password) VALUES (?,?,?,?)")->execute([$name, $nim ?: null, $email, $hash]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Registrasi berhasil! Silakan masuk.'];
            header('Location: login.php'); exit;
        }
    }
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Daftar — PerpusKu</title><link rel="stylesheet" href="css/style.css"></head><body>
<div class="auth-wrapper"><div class="auth-card">
    <h2>Daftar Anggota</h2>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
        <div class="form-group"><label>Nama</label><input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required></div>
        <div class="form-group"><label>NIM (opsional)</label><input type="text" name="nim" value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Konfirmasi Password</label><input type="password" name="confirm" required></div>
        <button type="submit" class="btn btn-primary" style="width:100%">Daftar</button>
    </form>
    <div class="auth-footer">Sudah punya akun? <a href="login.php">Masuk</a></div>
</div></div></body></html>
