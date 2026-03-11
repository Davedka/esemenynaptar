📅 Eseménynaptár
Egy PHP alapú webalkalmazás, amellyel eseményeket lehet létrehozni, kezelni és naptárban megjeleníteni. A felhasználók regisztrálhatnak, bejelentkezhetnek, és saját eseményeiket kezelhetik egy letisztult, sötét témájú felületen.

✨ Funkciók

🔐 Teljes autentikáció – regisztráció, bejelentkezés, kijelentkezés
🔑 Elfelejtett jelszó – emailben küldött visszaállító link (PHPMailer + SMTP)
📆 Esemény naptár – havi nézet, navigálható hónapok között
➕ Esemény hozzáadása – cím, dátum, leírás megadásával
🗑️ Esemény törlése – saját események eltávolítása
👤 Fiók törlése – saját account végleges megszüntetése
🎨 Egyedi dizájn – navy–arany színvilágú, glass morphism stílusú UI


🛠️ Technológiák
RétegTechnológiaBackendPHP 8+AdatbázisSupabase (PostgreSQL)EmailPHPMailerStílusNatív CSS (egyedi, Google Fonts)KonténerDocker

📁 Fájlstruktúra
esemenynaptar/
├── config.php            # Adatbázis + SMTP beállítások
├── index.php             # Főoldal / naptár nézet
├── dashboard.php         # Bejelentkezett főoldal
├── login.php             # Bejelentkezés
├── register.php          # Regisztráció
├── logout.php            # Kijelentkezés
├── forgot_password.php   # Elfelejtett jelszó kérés
├── reset_password.php    # Új jelszó beállítása
├── add_event.php         # Esemény hozzáadása
├── delete_event.php      # Esemény törlése
├── delete_account.php    # Fiók törlése
├── style.css             # Teljes UI stíluslap
├── composer.json         # PHPMailer függőség
└── Dockerfile            # Docker konfiguráció

🚀 Telepítés
Előfeltételek

PHP 8.0+
Composer
Supabase fiók (ingyenes)

1. Repository klónozása
bashgit clone https://github.com/Davedka/esemenynaptar.git
cd esemenynaptar
2. Függőségek telepítése
bashcomposer install
3. Supabase adatbázis beállítása
Futtasd le a következő SQL-t a Supabase SQL Editor-ban:
sql-- Felhasználók tábla
CREATE TABLE public.users (
    id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    fullname      TEXT NOT NULL,
    email         TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    reset_token   TEXT DEFAULT NULL,
    reset_expires TIMESTAMPTZ DEFAULT NULL,
    created_at    TIMESTAMPTZ DEFAULT NOW()
);

-- Események tábla
CREATE TABLE public.events (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID REFERENCES public.users(id) ON DELETE CASCADE,
    title       TEXT NOT NULL,
    description TEXT,
    event_date  DATE NOT NULL,
    created_at  TIMESTAMPTZ DEFAULT NOW()
);
4. config.php kitöltése
php// Supabase PostgreSQL kapcsolat
// Dashboard → Settings → Database → Connection string → PHP
$db_host = 'aws-0-eu-central-1.pooler.supabase.com';
$db_port = '6543';
$db_name = 'postgres';
$db_user = 'postgres.XXXXXXXXXXXXXXXX';
$db_pass = 'SUPABASE_JELSZÓD';

// SMTP email (pl. Gmail App Password)
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'te@gmail.com');
define('SMTP_PASS',     'xxxx xxxx xxxx xxxx');
define('APP_URL',       'https://sajatoldal.hu');

Gmail App Password: Google Fiók → Biztonság → 2FA bekapcsolva → App Passwords

5. Futtatás Docker-rel (opcionális)
bashdocker build -t esemenynaptar .
docker run -p 8080:80 esemenynaptar
Ezután a böngészőben: http://localhost:8080

🔒 Biztonság

Jelszavak password_hash() + PASSWORD_DEFAULT algoritmussal tárolva
SQL injection védelem PDO prepared statement-ekkel
Jelszó visszaállító tokenek 1 óra után lejárnak
Új jelszó minimum 9 karakter, számot és speciális karaktert kell tartalmazzon
Email enumeration ellen: elfelejtett jelszónál mindig ugyanaz az üzenet jelenik meg


📸 Képernyőképek

Hamarosan


📄 Licensz
MIT License – szabadon felhasználható és módosítható.
