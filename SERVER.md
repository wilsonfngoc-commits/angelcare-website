# AngelCare — Server Reference

> **Purpose:** Connection details, server config, backup procedures, and troubleshooting log for angelcare.dnow.hk.
> **Scope:** This file is brand-specific. For the cross-brand template, see `_shared/_templates/SERVER.md`.

---

## Connection

| Detail | Value |
|---|---|
| **Host** | `47.52.68.243` |
| **User** | `angelcareuser` |
| **Password** | `ud]1uDy8vU)6Q#8~,@%A` |
| **Port** | 22 (SSH) |
| **Method** | Node.js `ssh2` module (no sshpass/paramiko on local machine) |

```javascript
// Connect via ssh2 (Node.js)
const { Client } = require('ssh2');
conn.connect({
  host: '47.52.68.243',
  username: 'angelcareuser',
  password: 'ud]1uDy8vU)6Q#8~,@%A',
  readyTimeout: 10000
});
```

---

## Server Overview

| Detail | Value |
|---|---|
| **Hostname** | `iZj6c9ng9hibplfjuopgvqZ` (Alibaba Cloud ECS) |
| **OS** | Ubuntu 16.04.7 LTS (kernel 4.4.0-117-generic, x86_64) |
| **⚠️ OS Status** | End of Life since 2021 — no security patches |
| **Disk** | 128 GB SSD (/dev/vda1), 94% used (8.1 GB free) |
| **Web Server** | Nginx 1.10.3 + PHP-FPM 8.2 |

---

## Directory Structure

```
/data/angelcare_wordpress/        ← WordPress root (same as /home/angelcareuser/)
├── wp-admin/
├── wp-content/
│   ├── plugins/       (24 plugins)
│   ├── themes/        (hello-elementor)
│   ├── uploads/       (621 MB)
│   └── upgrade-temp-backup/
├── wp-includes/
├── wp-config.php
├── index.php
└── backups/
    └── 2026-06-07/
        ├── angelcare-files.tar.gz    (706 MB — full WP directory)
        └── angelcare-db.sql          (61 MB — MySQL dump, 74 tables)

/var/www/html/                     ← Default nginx root (not AngelCare)
/home/developer/                   ← Other developer home (46 subdirs)
/home/homewp-developer-1/          ← Other WP developer
/home/homewp-developer-2/          ← Other WP developer
/home/staging.cliniquedelight.com/  ← Clinique DeLight staging
```

---

## WordPress Details

| Detail | Value |
|---|---|
| **Version** | 6.6.2 (⚠️ outdated — 3 major versions behind) |
| **Theme** | `hello-elementor` (Elementor's base theme) |
| **Page Builder** | Elementor + Elementor Pro |
| **SEO Plugin** | Rank Math SEO Pro |
| **Key Plugins** | Google Site Kit, Ninja Tables, various survey/chart/quiz plugins |
| **Total Plugins** | 24 active |
| **Uploads Size** | 621 MB |
| **WP_DEBUG** | Not set |
| **DISALLOW_FILE_EDIT** | Not set |
| **Auto-updates** | Not disabled (but likely not working due to old OS) |

---

## Database

| Detail | Value |
|---|---|
| **Type** | MySQL (Alibaba Cloud RDS) |
| **Host** | `rm-3nsd2db8f226efy18.mysql.rds.aliyuncs.com` |
| **Port** | 3306 |
| **Database** | `angelcare_wordpress_dev` |
| **User** | `doctornow` |
| **Password** | `g5mnN7g2LUavN3MP` |
| **Table Prefix** | `wp_` |
| **Tables** | 74 |
| **⚠️ Access** | Only accessible from AngelCare server IP. External connections blocked by RDS whitelist. |

---

## Running Services

| Service | Notes |
|---|---|
| **Nginx** | Multiple instances. Default root: `/var/www/html`. `app-dev.dnow.hk` proxied to `127.0.0.1:13010`. |
| **PHP-FPM 8.2** | Multiple pools. Config at `/usr/local/etc/php-fpm.conf`. Runs as user `82` (www) and `developer`. |
| **Docker** | Running `dnow-push` containers (AngelCare push notification workers). **angelcareuser cannot access Docker** (permission denied to daemon socket). |
| **Supervisor** | `supervisord.pid` present in WP root |

---

## Access Limitations

| Tool | Available? | Note |
|---|---|---|
| `mysqldump` | ❌ | Not installed |
| `mysql` CLI | ❌ | Not installed |
| `php` CLI | ❌ | PHP-FPM running but CLI binary not in PATH |
| `wp` (WP-CLI) | ❌ | Not installed |
| `sshpass` | ✅ | /usr/bin/sshpass 1.09 on local machine (verified 2026-09-04) |
| `paramiko` (Python SSH) | ❌ | Not on local machine |
| **Node.js `ssh2`** | ✅ | Works; superseded by simpler sshpass method |
| **Node.js `mysql2`** | ✅ | Works through SSH tunnel |
| Docker | ✅ for angelcareuser via SSH | `docker ps`/`docker exec` worked from SSH session (verified 2026-09-04). Server.md earlier claim of "permission denied" appears outdated — either user was added to docker group or access changed. |
| `python` + `pymysql` | ❌ | Not installed on server |

**Lesson:** For database access, use SSH tunnel via `ssh2.forwardOut()` → `mysql2` from local machine.

### Prod deploy facts (2026-09-04 ground truth)
- Single container: `angelcare_wordpress` (nginx+PHP-FPM, bind-mount `/data/angelcare_wordpress` → `/var/www/html`, host port 8096). **No `_php8` container exists** (old docs stale).
- mu-plugins dir owned by systemd-network: CANNOT create new files as angelcareuser, but **CAN overwrite in-place** files owned by angelcareuser (e.g. `ac-gclid-beacon.php` owned by angelcareuser → in-place edit works).
- WP Super Cache files owned by systemd-network: delete from inside container via `docker exec angelcare_wordpress sh -c "rm -rf /var/www/html/wp-content/cache/*"`.
- No `php` in host PATH → use `docker exec angelcare_wordpress php -l`.
- Upload method: scp can drop mid-transfer ("Connection closed" observed 2026-09-04). **Reliable method:** `cat local_file | sshpass -e ssh … 'cat > /tmp/target'` then move into place.
- **⚠️ NEVER test write permissions against live prod files.** Test on `/tmp` copies first (prod corruption incident 11:57 HKT 2026-09-04: overwrite test wrote into live `ac-gclid-beacon.php`, emptied it [md5 d41d8cd9… = empty], restored via sshpipe).

---

## Backup Procedures

### Create Backup

**Files (on server):**
```bash
tar czf ~/backups/YYYY-MM-DD/angelcare-files.tar.gz --exclude=backups --exclude=.cache .
```

**Database (via SSH tunnel — from local machine):**
```javascript
// 1. SSH connect to server
// 2. ssh.forwardOut() to tunnel RDS MySQL through server
// 3. mysql2 connect through tunnel
// 4. Export all tables with CREATE TABLE + INSERT statements
// 5. Write to local /tmp/, then SFTP upload to server
```

See `backup-via-tunnel.js` in this project's scripts directory for the full script.

### Restore

**Files:**
```bash
tar xzf ~/backups/YYYY-MM-DD/angelcare-files.tar.gz
```

**Database:**
```bash
# Need mysqldump or mysql client on server, OR use SSH tunnel in reverse
mysql -h rm-3nsd2db8f226efy18.mysql.rds.aliyuncs.com \
  -u doctornow -p angelcare_wordpress_dev < ~/backups/YYYY-MM-DD/angelcare-db.sql
```

---

## WF6 Diagnostic Results (2026-06-07)

| Metric | Score | Target | Status |
|---|---|---|---|
| Performance | 55/100 | ≥ 75 | 🚨 Critical |
| LCP | 13.5s | < 2.5s | 🚨 Critical |
| Total Page Weight | 21 MB | < 2 MB | 🚨 Critical |
| Server Response Time | 859ms | < 600ms | ⚠️ |
| SEO | 92/100 | ≥ 90 | ✅ |
| Accessibility | 88/100 | — | ✅ |
| Best Practices | 100/100 | — | ✅ |
| CLS | 0.001 | < 0.1 | ✅ |
| Agentic Browsing | 50/100 | — | 🚨 |
| llms.txt | Missing | Required | 🚨 |

---

## Troubleshooting Log

### 2026-06-07 — DB Backup Attempts

1. **Attempt 1:** `mysqldump` directly on server → ❌ Not installed
2. **Attempt 2:** Docker `mysql:8` container for mysqldump → ❌ angelcareuser denied Docker access
3. **Attempt 3:** PHP script via nginx web access → ❌ nginx returned 404 — script not in webroot
4. **Attempt 4:** Place PHP script in `/var/www/html/` → ❌ nginx doesn't execute PHP from default root
5. **Attempt 5:** SSH port forwarding to RDS via `ssh2.forwardOut()` → ✅ **SUCCESS** (60.5 MB dump in ~30s)
6. **Attempt 6:** SFTP upload of DB dump → ❌ Path error (used `~` instead of full path)
7. **Attempt 7:** SFTP with absolute path `/data/angelcare_wordpress/backups/...` → ✅ **SUCCESS**

**Key lesson:** Always use absolute paths with SFTP. The `~` home directory shortcut doesn't resolve in SFTP.

### 2026-06-07 — PHP CLI Not Found

- PHP-FPM IS running (visible in `ps aux`) at `/usr/local/etc/php-fpm.conf`
- But `php` binary not in angelcareuser's PATH
- `find / -name php` times out
- Best approach: use Docker or SSH tunnel instead of relying on PHP CLI

---

## Version History

| Date | Version | Changes |
|---|---|---|
| 2026-06-07 | v1.0 | Created. Connection details, server config, WP details, DB info, backup procedures, WF6 diagnostics, troubleshooting log. |
