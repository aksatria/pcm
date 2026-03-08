# Operasional RAB/RAB Breakdown (Singkat)

## URL Lokal Yang Benar
- Gunakan server Laravel bawaan: `php artisan serve`
- Akses aplikasi di: `http://127.0.0.1:8000` atau `http://localhost:8000`
- Jangan gunakan `http://localhost/...` (port 80/XAMPP) untuk route Laravel ini.

## Login Demo
- HO/Admin: `admin@pcm.local` / `password`
- Staff: `staff@demo.test` / `password`
- Seed demo user:
  - `php artisan db:seed --class=DemoLoginUsersSeeder`

## Alur Dokumen (RAB -> Breakdown)
1. Staff buat/ubah RAB Baseline.
2. Staff submit RAB Baseline untuk approval.
3. HO approve RAB Baseline.
4. Setelah approved, Staff lanjut buat RAB Breakdown per header/item.
5. Staff submit RAB Breakdown untuk approval.
6. HO approve/reject RAB Breakdown.

## Alur Approval Cepat
1. Buka `Approval Center` (akun HO).
2. Filter jenis dokumen bila perlu (RAB Baseline / RAB Breakdown).
3. Buka detail dokumen, lalu `Approve` atau `Reject`.
4. Cek hasil di:
   - Inbox / Notifikasi
   - Badge approval pada header/sidebar

## Aturan Teknis Penting
- Kode header tampil format konstruksi: `A.`, `B.`, `C.` (tanpa prefix `RAB-001` di judul utama).
- Item breakdown disortir natural: `A`, `A.1`, `A.2`, `B`, dst.
- Guardrail alokasi aktif agar qty/jumlah tidak melampaui batas bisnis.
- Field numerik wajib diset default aman agar tidak `NULL` pada kolom NOT NULL.

## Troubleshooting Cepat
- Error `View [errors.404] not found`:
  - Pastikan file ada di `resources/views/errors/404.blade.php`.
- Error URL not found saat buka `/dev/...`:
  - Pastikan URL memakai `:8000`.
- Notifikasi mengarah ke host salah:
  - Pastikan `.env` memakai `APP_URL=http://localhost:8000`.
  - Jalankan `php artisan optimize:clear`.

## Perintah Harian
- Jalankan app: `php artisan serve`
- Seed demo user: `php artisan db:seed --class=DemoLoginUsersSeeder`
- Jalankan test: `php artisan test`
