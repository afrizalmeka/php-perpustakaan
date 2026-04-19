<?php
function initDatabase(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        nim TEXT UNIQUE,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'anggota',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS buku (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        judul TEXT NOT NULL,
        pengarang TEXT NOT NULL,
        isbn TEXT UNIQUE,
        penerbit TEXT,
        tahun INTEGER,
        stok INTEGER NOT NULL DEFAULT 1,
        kategori TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS peminjaman (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        buku_id INTEGER NOT NULL,
        tanggal_pinjam DATE NOT NULL,
        tanggal_kembali_rencana DATE NOT NULL,
        tanggal_kembali_aktual DATE,
        status TEXT NOT NULL DEFAULT 'dipinjam',
        denda REAL NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (buku_id) REFERENCES buku(id)
    )");

    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('Admin Perpustakaan', 'admin@perpusku.com', '$adminPass', 'admin')");
        $userPass = password_hash('user123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (name, nim, email, password, role) VALUES ('Andi Mahasiswa', '21/123456/TK/01', 'andi@student.com', '$userPass', 'anggota')");
        $pdo->exec("INSERT INTO users (name, nim, email, password, role) VALUES ('Sari Pelajar', '21/123457/TK/02', 'sari@student.com', '$userPass', 'anggota')");

        $buku = [
            ['Pemrograman Web PHP', 'Budi Raharjo', '978-1234567890', 'Informatika', 2022, 3, 'Pemrograman'],
            ['Algoritma dan Pemrograman', 'Rinaldi Munir', '978-0987654321', 'Informatika', 2021, 2, 'Algoritma'],
            ['Basis Data', 'Ramez Elmasri', '978-1122334455', 'Pearson', 2020, 2, 'Database'],
            ['Jaringan Komputer', 'Andrew Tanenbaum', '978-5544332211', 'Pearson', 2019, 1, 'Jaringan'],
            ['Kecerdasan Buatan', 'Stuart Russell', '978-6677889900', 'Pearson', 2020, 2, 'AI'],
        ];
        $stmt = $pdo->prepare("INSERT INTO buku (judul, pengarang, isbn, penerbit, tahun, stok, kategori) VALUES (?,?,?,?,?,?,?)");
        foreach ($buku as $b) $stmt->execute($b);

        // Seed active loan
        $pinjam = date('Y-m-d', strtotime('-5 days'));
        $rencana = date('Y-m-d', strtotime('+9 days'));
        $pdo->exec("INSERT INTO peminjaman (user_id, buku_id, tanggal_pinjam, tanggal_kembali_rencana, status) VALUES (2, 1, '$pinjam', '$rencana', 'dipinjam')");
        $pdo->exec("UPDATE buku SET stok = stok - 1 WHERE id = 1");

        // Seed overdue loan (returned)
        $pinjamlate = date('Y-m-d', strtotime('-20 days'));
        $rencanate = date('Y-m-d', strtotime('-10 days'));
        $aktual = date('Y-m-d', strtotime('-5 days'));
        $pdo->exec("INSERT INTO peminjaman (user_id, buku_id, tanggal_pinjam, tanggal_kembali_rencana, tanggal_kembali_aktual, status, denda) VALUES (3, 2, '$pinjamlate', '$rencanate', '$aktual', 'dikembalikan', 2500)");
    }
}
