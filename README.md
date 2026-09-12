# TransactiWar

A secure PHP + PostgreSQL money-transfer web application built for **CS6903: Network Security** at IIT Hyderabad.

Users can register, transfer money to each other, upload profile photos, and view transaction history — all defended against SQL injection, XSS, CSRF, session hijacking, brute force, race conditions, and file upload attacks.

---

## Tech Stack

| Layer      | Technology                             |
|------------|----------------------------------------|
| Frontend   | HTML, Tailwind CSS (local, no CDN)     |
| Backend    | PHP 8.2 (pure — no frameworks)         |
| Database   | PostgreSQL 15                          |
| Web Server | Apache 2 with HTTPS (self-signed TLS)  |
| Container  | Docker + Docker Compose                |

---

## Quick Start

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running
- Git

### Run the app

```bash
git clone https://github.com/YOUR_USERNAME/transactiwar.git
cd transactiwar
docker compose up --build
```

Wait 2–4 minutes for the first build. When you see:

```
web-1  | Created account: alice
web-1  | ...
web-1  | Starting Apache...
```

Open **https://localhost** in your browser.

Chrome will warn about the self-signed certificate — click **Advanced → Proceed to localhost (unsafe)**. This is expected for a dev cert.

### Test Accounts

All accounts have the password **`Test@12345678`** and start with Rs. 100 balance:

| Username | User ID |
|----------|---------|
| alice    | 1       |
| bob      | 2       |
| charlie  | 3       |
| dave     | 4       |
| eve      | 5       |

You can also register new accounts from the app.

### Stop the app

```bash
docker compose down          # stop containers, keep data
docker compose down -v       # stop containers + wipe all DB data
```

---

## Features

- User registration, login, logout with session management
- Profile management: full name, biography, profile photo upload
- User search by username or ID
- Money transfer with optional comment
- Transaction history (paginated)
- View other users' public profiles
- Every request logged with page, username, IP, and timestamp
- Security events logged separately (CSRF violations, brute force attempts, etc.)

---

## Security Features

### Authentication & Sessions
- **bcrypt password hashing** with cost factor 12
- Password policy: 10+ chars, must contain uppercase, lowercase, digit, and special character
- Session ID regenerated on login (prevents session fixation)
- Session bound to IP + user-agent hash (detects hijacking)
- 30-minute inactivity timeout
- `HttpOnly`, `Secure`, `SameSite=Strict` session cookies

### Brute Force Protection
- Account lockout after 5 failed logins per IP (10-minute cooldown)
- Atomic rate limiting using PostgreSQL upsert (no race condition bypass)
- Dummy bcrypt on unknown usernames (prevents timing-based user enumeration)
- Rate limits on registration, login, search, and transfer endpoints

### SQL Injection Prevention
- All queries use PDO prepared statements
- `PDO::ATTR_EMULATE_PREPARES = false` (real server-side parameterization)
- LIKE wildcards (`%`, `_`) escaped before binding
- Zero string concatenation in SQL anywhere in the codebase

### XSS Prevention
- All output escaped with `htmlspecialchars(ENT_QUOTES | ENT_HTML5, 'UTF-8')`
- Content-Security-Policy header restricts script sources
- Input length validated on all fields

### CSRF Protection
- Per-session tokens generated with `random_bytes(32)`
- Validated on every POST using constant-time `hash_equals()`
- Tokens rotated after every validation (single-use)
- Even logout requires a POST + CSRF token

### File Upload Security
- MIME type validation via `finfo` (not client-reported `$_FILES['type']`)
- Extension whitelist: `jpg`, `jpeg`, `png` only
- 2MB size limit
- `getimagesize()` verification
- Images re-encoded via GD (strips EXIF metadata and embedded payloads)
- Random filenames via `bin2hex(random_bytes(16))`
- Files stored outside web root (`/var/uploads/`)
- Served through PHP proxy with MIME re-verification

### Race Condition Prevention (Money Transfer)
- PostgreSQL transactions with `SELECT ... FOR UPDATE` row-level locking
- Consistent lock ordering (by user ID) prevents deadlocks
- `UPDATE ... WHERE balance >= :amount` as a second check
- Database-level `CHECK (balance >= 0)` constraint as final safety net

### HTTPS / TLS
- Self-signed TLS certificate (RSA 2048-bit)
- Only TLS 1.2 and 1.3 allowed (SSLv3, TLS 1.0/1.1 disabled)
- Strong cipher suites (ECDHE-AES-GCM)
- HSTS header with 2-year max-age

### Security Headers
- `Strict-Transport-Security` (HSTS)
- `X-Frame-Options: DENY` (clickjacking prevention)
- `X-Content-Type-Options: nosniff`
- `Content-Security-Policy` (restricts inline scripts, external resources)
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` (disables camera, microphone, geolocation)
- `Cache-Control: no-store` on sensitive pages

### Additional Hardening
- Apache: directory listing disabled, non-public dirs 403'd, version hidden
- PHP: `expose_php = Off`, dangerous functions disabled (`exec`, `system`, `shell_exec`, etc.)
- Single entry point architecture (all requests through `index.php`)
- Path traversal attempts logged as security events

---

## Project Structure

```
transactiwar/
├── app/
│   ├── config/                Database + security constants
│   ├── controllers/           Auth, Profile, Transfer, Search, Dashboard
│   ├── middleware/            Auth, CSRF, rate limit, logging, validation, headers
│   ├── models/                User, Transaction (with FOR UPDATE locking)
│   ├── public/                Web root — only folder Apache serves
│   │   ├── .htaccess          URL rewriting to index.php
│   │   └── index.php          Router — single entry point
│   └── views/                 HTML templates
├── docker/
│   ├── apache.conf            HTTPS-only Apache config
│   ├── create_accounts.php    Seeds 5 test accounts at startup
│   ├── entrypoint.sh          Container startup script
│   ├── init.sql               Database schema (6 tables)
│   ├── php.ini                Hardened PHP config
│   └── seed.sql               (intentionally empty)
├── uploads/                   Profile images (NOT web-accessible)
├── Dockerfile
├── docker-compose.yml
└── README.md
```

---

## Database Schema

Six tables managed by `docker/init.sql`:

1. **`users`** — accounts with `CHECK (balance >= 0)` constraint
2. **`transactions`** — money transfer records with `CHECK (amount > 0)` and no-self-transfer constraint
3. **`activity_logs`** — every request (page, username, IP, user-agent, timestamp)
4. **`attack_logs`** — security events with severity (CSRF violations, brute force, path traversal, etc.)
5. **`failed_logins`** — tracks failed login attempts for lockout logic
6. **`rate_limits`** — atomic rate limit counters per (identifier, action)

---

## Useful Commands

### View live logs
```bash
docker compose logs -f web       # PHP + Apache
docker compose logs -f db        # PostgreSQL
```

### Access the database
```bash
docker compose exec db psql -U twuser -d transactiwar
```

Sample queries:
```sql
-- All users and balances
SELECT id, username, balance FROM users;

-- Recent activity
SELECT username, page, action, ip_address, created_at
FROM activity_logs
ORDER BY created_at DESC LIMIT 20;

-- Security events, high severity first
SELECT username, event_type, description, ip_address, created_at
FROM attack_logs
WHERE severity = 'high'
ORDER BY created_at DESC;
```

### Reset everything
```bash
docker compose down -v          # wipes database and uploads
docker compose up --build       # fresh install
```

### Change the port
Edit `docker-compose.yml`:
```yaml
services:
  web:
    ports:
      - "8443:443"     # then use https://localhost:8443
```

---

## Environment Variables

Configured in `docker-compose.yml`. If you're deploying elsewhere, override these:

| Variable   | Default        | Purpose                    |
|------------|----------------|----------------------------|
| `DB_HOST`  | `db`           | PostgreSQL hostname        |
| `DB_PORT`  | `5432`         | PostgreSQL port            |
| `DB_NAME`  | `transactiwar` | Database name              |
| `DB_USER`  | `twuser`       | Database user              |
| `DB_PASS`  | *(set in compose file)* | Database password |

---

## Troubleshooting

**Browser shows "Not Secure" and a blank "Not Found" page**
You accepted HTTP instead of HTTPS. Make sure the URL is `https://localhost` (not `http://`). Click Advanced → Proceed on the cert warning.

**`404 Not Found` on every route except `/`**
The `.htaccess` file is missing from `app/public/`. It's a hidden file — copy it explicitly and rebuild.

**`password authentication failed for user "twuser"`**
The Postgres data volume has an old password. Fix:
```bash
docker compose down -v
docker compose up --build
```

**Login says "invalid username or password"**
- Passwords are case-sensitive: `Test@12345678` (capital T, capital @)
- If you tried too many wrong passwords, wait 10 minutes or clear the lockout:
  ```bash
  docker compose exec db psql -U twuser -d transactiwar \
    -c "DELETE FROM failed_logins WHERE username = 'alice';"
  ```

**Port 443 already in use**
Change the port mapping in `docker-compose.yml` to something like `8443:443`.

---

## References

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)
- [PHP Manual](https://www.php.net/manual/)
- [PostgreSQL 15 Documentation](https://www.postgresql.org/docs/15/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)

---

## License

Educational project — CS6903 Network Security, IIT Hyderabad.
