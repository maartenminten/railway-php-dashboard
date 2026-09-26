# Railway PHP Dashboard

A minimal Railway-ready Apache/PHP 8.3 dashboard using Bootstrap 5 and a Railway MySQL service.

## 1. Create MySQL in Railway

Create a Railway MySQL database service.

## 2. Deploy this repository

Create a new service from your GitHub repository containing these files. Railway will build the included Dockerfile.

## 3. Add variable references to the Web service

Assuming the Railway database service is named `MySQL`, add these variables to the Web service:

```text
MYSQLHOST=${{MySQL.MYSQLHOST}}
MYSQLPORT=${{MySQL.MYSQLPORT}}
MYSQLUSER=${{MySQL.MYSQLUSER}}
MYSQLPASSWORD=${{MySQL.MYSQLPASSWORD}}
MYSQLDATABASE=${{MySQL.MYSQLDATABASE}}
```

Do not copy the actual database password into GitHub.

## 4. Generate a public domain

On the Web service, go to Settings / Networking and generate a Railway domain.

## 5. Initialize the database

Open the Railway console/shell for the Web service and run:

```bash
php /var/www/scripts/init-db.php
```

The script is idempotent: the tables are created only if needed and the demo users use an upsert.

## 6. Optional phpMyAdmin

Create another Railway service from the Docker image:

```text
phpmyadmin:apache
```

Set:

```text
PMA_HOST=${{MySQL.MYSQLHOST}}
PMA_PORT=${{MySQL.MYSQLPORT}}
PMA_USER=${{MySQL.MYSQLUSER}}
PMA_PASSWORD=${{MySQL.MYSQLPASSWORD}}
```

Then generate a public domain for phpMyAdmin.

## Security notes

- Keep MySQL private; do not expose it to the public Internet unless required.
- Prefer a dedicated non-root application database user for production.
- Protect phpMyAdmin with authentication/access controls or keep it disabled when not needed.
- Rotate any credential that has been exposed in screenshots, chat, logs, or source control.

## Web backup feature

This version includes `/backup.php`, which creates a portable compressed SQL dump using `mysqldump`.

Add a strong Railway variable to the Web service:

```text
BACKUP_TOKEN=choose-a-long-random-secret
```

Open `/backup.php`, enter that token, and click **Create & download backup**.

The Docker image installs `default-mysql-client`, so the export includes tables, data, triggers, routines, and events.

Example restore on another MySQL server:

```bash
gunzip railway-backup.sql.gz
mysql -h TARGET_HOST -P 3306 -u TARGET_USER -p TARGET_DATABASE < railway-backup.sql
```

Security: keep `BACKUP_TOKEN` out of GitHub and rotate it if exposed. The backup endpoint is intentionally disabled when `BACKUP_TOKEN` is unset.
