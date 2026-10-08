# Smart Lost & Found Kampus — FINAL

Versi final PHP + MySQL berdasarkan requirement dan prototype.

## Fitur
- Landing page responsive + animasi
- Register / Login / Logout
- Profil wajib: status, semester, fakultas, nomor HP
- Dashboard user: laporan sendiri + semua laporan terverifikasi
- Laporan LOST dan FOUND + upload foto
- Pencarian barang: kata kunci, jenis, kategori, lokasi, tanggal
- Smart Matching dua arah LOST ↔ FOUND
- Score matching berdasarkan kategori, lokasi, nama, deskripsi, tanggal
- Klaim hanya dari akun pemilik laporan LOST
- Klaim harus berasal dari hasil Smart Matching
- Akun penemu barang menyetujui/menolak klaim
- Admin memverifikasi data pelapor/penemu
- Admin finalisasi klaim
- Saat finalisasi: FOUND = CLAIMED, LOST = RETURNED
- Dashboard admin dan verifikasi laporan
- Animasi halaman, card, button, loading, count-up, hover dan progress

## Setup dari database yang sudah ada
1. Extract folder ke `C:\xampp\htdocs\smart-lost-found`.
2. Nyalakan Apache dan MySQL.
3. Pastikan database `smart_lost_found` sudah ada.
4. phpMyAdmin → database `smart_lost_found` → SQL → jalankan **sekali** isi `database-finalisasi.sql`.
5. Jika SQL sudah berhasil, buka `http://localhost/smart-lost-found/`.
6. Register akun USER.
7. Lengkapi profil setelah login.
8. Register akun lain untuk penemu jika ingin testing dua akun.
9. Jadikan satu akun admin dengan:
   `UPDATE users SET role='ADMIN' WHERE email='email-admin-kamu@example.com';`
10. Logout/login ulang akun admin.

## Alur final
USER A mengisi profil → membuat LOST → admin verifikasi → Smart Matching menemukan FOUND milik USER B → USER A mengajukan klaim → USER B memeriksa data dan ACC → admin melihat data kedua pihak + hasil matching → admin finalisasi → FOUND menjadi CLAIMED dan LOST menjadi RETURNED.

## Catatan
- Jangan DROP database lama.
- Jalankan migration SQL hanya sekali.
- Folder `uploads` harus dapat ditulis PHP.
- Jika data profil belum lengkap, user diarahkan ke Profil sebelum memakai fitur utama.
