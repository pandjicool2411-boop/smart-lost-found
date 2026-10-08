-- Smart Lost & Found Kampus - FINAL MIGRATION
-- Database: smart_lost_found
-- Jalankan SEKALI pada database yang SUDAH ADA.
USE smart_lost_found;

ALTER TABLE reports MODIFY status VARCHAR(20) NOT NULL DEFAULT 'PENDING';
ALTER TABLE claims MODIFY status VARCHAR(30) NOT NULL DEFAULT 'PENDING';
ALTER TABLE users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'USER';

ALTER TABLE users
  ADD COLUMN student_status VARCHAR(50) NULL AFTER email,
  ADD COLUMN semester INT NULL AFTER student_status,
  ADD COLUMN fakultas VARCHAR(120) NULL AFTER semester,
  ADD COLUMN nomor_hp VARCHAR(30) NULL AFTER fakultas,
  ADD COLUMN profile_completed TINYINT(1) NOT NULL DEFAULT 0 AFTER nomor_hp;

ALTER TABLE claims
  ADD COLUMN lost_report_id INT NULL AFTER report_id,
  ADD COLUMN finder_status VARCHAR(30) NOT NULL DEFAULT 'PENDING' AFTER status,
  ADD COLUMN finder_note TEXT NULL AFTER finder_status,
  ADD COLUMN finder_approved_at DATETIME NULL AFTER finder_note,
  ADD COLUMN admin_approved_at DATETIME NULL AFTER admin_note;

CREATE INDEX idx_claim_lost ON claims(lost_report_id);
CREATE INDEX idx_claim_finder_status ON claims(finder_status);
CREATE INDEX idx_reports_type_status ON reports(type,status);

-- Setelah membuat akun admin:
-- UPDATE users SET role='ADMIN' WHERE email='email-admin-kamu@example.com';
