# AGENTS.md — Varlet

Laravel 13 + Inertia.js + Vue 3 + TailwindCSS 4. Sistem absensi + undian berhadiah.

## Stack
- PHP 8.3, Laravel 13.8, SQLite
- Inertia 3 (SSR-ready) + Vue 3 + `@varlet/ui` (komponen Varlet, bukan Material)
- TailwindCSS 4 via `@tailwindcss/vite`
- `spatie/laravel-permission` (RBAC admin)
- `laravel/mcp` (server MCP untuk AI agent, sudah aktif di `opencode.json`)

## Perintah Developer

```bash
composer setup   # install deps, copy .env, key:generate, migrate, npm install, build
composer dev     # concurrently: artisan serve + queue:listen + pail + vite
composer test    # config:clear lalu phpunit (SQLite in-memory)
npm run dev      # vite dev server saja
npm run build    # vite production build
```

Test tunggal:
```bash
php artisan test --filter=NamaTest
./vendor/bin/phpunit --filter=NamaTest
```

Lint/format:
```bash
./vendor/bin/pint
```

## Struktur Direktori Penting

```
app/Models/         Participant, Prize, WinnerLog, Setting, User, Admin
app/Http/Controllers/
    AttendanceController.php           # public absensi
    Admin/AdminController.php          # SSO callback + dashboard + PIN
    Admin/LuckyDrawController.php      # CRUD prize + draw control + display
    Admin/ParticipantController.php    # CRUD peserta
app/Mcp/
    Servers/AppServer.php              # MCP server (stdio + http /mcp/app)
    Tools/CheckDatabaseTool.php
    Tools/ArtisanRunnerTool.php
resources/js/Pages/
    Attendance/{Index,Kupon}.vue       # form absensi + kupon
    Admin/{Login,Dashboard,Prizes,Draw,Participants}.vue
    LuckyDraw/Display.vue              # fullscreen untuk proyektor
routes/web.php        # semua route web + mcp
routes/ai.php         # registrasi MCP server (local stdio + web http)
```

## Alur Aplikasi

### 1. Absensi Karyawan (public, `/absensi`)
```
GET  /absensi               → form cari NIK (render Inertia Attendance/Index)
POST /absensi/search        → AJAX cari Participant by nomor_induk
POST /absensi/checkin       → validasi PIN (Setting::getValue('attendance_pin', '782'))
                              → set is_present=true, attended_at=now()
                              → jika eligible_for_draw: generate MD-XXXX (auto-increment, anti-dobel walau reset)
                              → redirect ke /absensi/kupon/{nomor_induk}
GET  /absensi/kupon/{nik}   → tampilkan kupon (render Attendance/Kupon)
```
Cegah double check-in: `if ($participant->is_present) return back()->withErrors(...)`

### 2. Admin Panel (SSO OAuth, BUKAN Laravel auth default)
```
GET  /admin/login           → tampilkan Login.vue
GET  /admin/redirect        → redirect ke SSO /oauth/authorize (config services.sso)
GET  /admin/callback        → tukar code → token → fetch user → cari User by nik
                              → wajib hasRole('admin'), jika tidak → balik ke login
                              → simpan session('admin_user')
GET  /admin/dashboard       → dashboard + PIN hari ini
POST /admin/pin             → update PIN absensi (Setting::setValue)
POST /admin/logout          → forget session
```

Session-based auth, bukan token. Cek `session()->has('admin_user')` di setiap method admin.

### 3. Undian (Lucky Draw)
**Dua peran terpisah** yang berkomunikasi via `Setting::setValue('current_draw_prize_id', ...)`:

```
ADMIN (kontrol):
POST /admin/lucky-draw/draw/start  → set current_draw_prize_id di Setting
POST /admin/lucky-draw/reset       → truncate WinnerLog + Prize::query()->update(['is_drawn'=>false])

MC/DISPLAY (eksekusi, di halaman proyektor):
POST /undian/draw                  → validasi current_draw_prize_id cocok dengan request
                                    → ambil peserta: is_present + eligible_for_draw
                                      + belum ada di WinnerLog
                                    → Participant random
                                    → WinnerLog::create + Prize::update(is_drawn=true)
                                    → clear current_draw_prize_id
                                    → return JSON winner + prize
```

Eligible peserta: `is_present=true AND eligible_for_draw=true AND id NOT IN WinnerLog::pluck('participant_id')`

### 4. Display Proyektor (public, fullscreen)
```
GET  /undian/show       → render LuckyDraw/Display (current winner + history)
GET  /undian/data       → JSON untuk polling real-time (current_winner + winners + pending_draw)
```

## Model Penting

| Model | Field Kunci | Catatan |
|---|---|---|
| `Participant` | `nomor_induk`, `nama_lengkap`, `is_present`, `nomor_kupon` (MD-XXXX), `eligible_for_draw`, `attended_at` | tidak ada relasi Eloquent |
| `Prize` | `nama`, `deskripsi`, `urutan`, `is_drawn` | hasMany WinnerLog |
| `WinnerLog` | `prize_id`, `participant_id`, `nomor_kupon`, `nama_pemenang`, `departemen`, `lokasi_kerja`, `drawn_at` | snapshot data winner (tidak hilang walau Participant berubah) |
| `Setting` | `key`, `value` | key-value store global; `getValue($key, $default)` / `setValue($key, $value)` |
| `User` | `nik`, `name`, `email` | pakai `HasRoles` (Spatie), role `admin` wajib |

## Konvensi Spesifik Repo Ini

- **User menggunakan bahasa Indonesia** untuk semua komunikasi, docblock, dan pesan error UI
- **Varlet UI** (`@varlet/ui`) untuk komponen, BUKAN Material/Tailwind UI
- **Inertia closure props**: data berat dibungkus `function () use (...)` di controller agar lazy-loaded (lihat `AttendanceController::index`)
- **Kupon format**: `MD-` + 4 digit, dihitung dari `MAX` existing bukan auto-increment DB (biar anti-dobel saat reset)
- **WinnerLog adalah snapshot**: field `nama_pemenang`, `departemen`, `lokasi_kerja` di-copy saat undian, tidak reference ke Participant
- **SSO config di `config/services.php`**: `services.sso.base_url`, `client_id`, `client_secret`. SSL verify auto-disable di `local` env (`AdminController::callback`)
- **MCP server sudah jalan**: cek `opencode.json`, tool `laravel-app`. Bisa pakai `laravel-app_check-database-tool` & `laravel-app_artisan-runner-tool`
- **Vite ignore**: `storage/framework/views/**` (supaya HMR tidak trigger recompile view)
- **Test DB**: SQLite `:memory:`, `APP_ENV=testing`, `PULSE_ENABLED=false`, `NIGHTWATCH_ENABLED=false`

## Testing

- 2 suite PHPUnit: `tests/Unit` + `tests/Feature`
- Tinggal 2 file example test (`ExampleTest.php`), belum ada test fitur spesifik
- Saat tambah test: prefer `php artisan test --filter=X` daripada full suite

## Environment

`.env` di-copy dari `.env.example` saat `composer setup`. Wajib diset:
```env
SSO_BASE_URL=...
SSO_CLIENT_ID=...
SSO_CLIENT_SECRET=...
DB_CONNECTION=sqlite
```

## MCP Integration

Server MCP di `app/Mcp/Servers/AppServer.php` otomatis terdaftar di `routes/ai.php`:
- Akses stdio lokal: `php artisan mcp:start app-server` (sudah di-config di `opencode.json`)
- Akses HTTP: `POST /mcp/app`
- Tool tersedia: cek DB, jalankan Artisan command

## Yang SERING Bikin Agent Salah

1. **Jangan pakai Material/Tailwind UI components** — pakai Varlet (`Button` dari `@varlet/ui`, bukan `<button class="bg-blue-500">`)
2. **Jangan generate UUID/auto-increment untuk kupon** — pakai `MD-` + 4 digit sequential dari MAX existing
3. **Admin auth = SSO session, BUKAN Laravel Auth::user()** — pakai `session('admin_user')`
4. **WinnerLog harus snapshot data winner** — jangan cuma simpan `participant_id`, copy field `nama_pemenang`/`departemen`/`lokasi_kerja`
5. **Reset undian** = `WinnerLog::truncate()` + `Prize::query()->update(['is_drawn'=>false])`, JANGAN hapus Participant
6. **MCP server sudah aktif** — pakai `laravel-app_*` tools, jangan buat MCP baru
7. **Pesan error & UI text** pakai bahasa Indonesia, konsisten dengan yang sudah ada
