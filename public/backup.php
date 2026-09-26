<?php
require_once '/var/www/app/db.php';

$expectedToken = getenv('BACKUP_TOKEN') ?: '';
$error = null;
$ready = $expectedToken !== '';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function backupFilename(string $database): string
{
    $safeDb = preg_replace('/[^A-Za-z0-9._-]/', '_', $database) ?: 'database';
    return $safeDb . '-backup-' . gmdate('Ymd-His') . 'Z.sql.gz';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$ready) {
        $error = 'BACKUP_TOKEN is not configured in Railway.';
    } elseif (!hash_equals($expectedToken, (string)($_POST['backup_token'] ?? ''))) {
        $error = 'Invalid backup token.';
    } else {
        $host = getenv('MYSQLHOST') ?: '';
        $port = getenv('MYSQLPORT') ?: '3306';
        $user = getenv('MYSQLUSER') ?: '';
        $password = getenv('MYSQLPASSWORD') ?: '';
        $database = getenv('MYSQLDATABASE') ?: '';

        if ($host === '' || $user === '' || $database === '') {
            $error = 'Database environment variables are incomplete.';
        } else {
            $sqlFile = tempnam(sys_get_temp_dir(), 'mysql-backup-');
            if ($sqlFile === false) {
                $error = 'Could not create a temporary backup file.';
            } else {
                $cmd = [
                    'mysqldump',
                    '--host=' . $host,
                    '--port=' . $port,
                    '--user=' . $user,
                    '--single-transaction',
                    '--quick',
                    '--routines',
                    '--triggers',
                    '--events',
                    '--hex-blob',
                    '--default-character-set=utf8mb4',
                    '--skip-comments',
                    $database,
                ];

                $stderr = fopen('php://temp', 'w+');
                $stdout = fopen($sqlFile, 'wb');
                $env = array_merge($_ENV, [
                    'MYSQL_PWD' => $password,
                    'PATH' => getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
                ]);

                $process = proc_open($cmd, [
                    0 => ['file', '/dev/null', 'r'],
                    1 => $stdout,
                    2 => $stderr,
                ], $pipes, null, $env);

                $exitCode = is_resource($process) ? proc_close($process) : 127;
                fclose($stdout);
                rewind($stderr);
                $stderrText = trim(stream_get_contents($stderr) ?: '');
                fclose($stderr);

                if ($exitCode !== 0) {
                    @unlink($sqlFile);
                    $error = 'Backup failed' . ($stderrText !== '' ? ': ' . $stderrText : '.');
                } else {
                    $sql = file_get_contents($sqlFile);
                    @unlink($sqlFile);

                    if ($sql === false) {
                        $error = 'Backup was created but could not be read.';
                    } else {
                        $gzip = gzencode($sql, 6);
                        if ($gzip === false) {
                            $error = 'Could not compress the backup.';
                        } else {
                            try {
                                $pdo = db();
                                $stmt = $pdo->prepare('INSERT INTO audit_events (event_type, message) VALUES (?, ?)');
                                $stmt->execute(['backup', 'Database backup generated from web dashboard']);
                            } catch (Throwable $ignored) {
                                // The backup download should not fail if audit logging is unavailable.
                            }

                            $filename = backupFilename($database);
                            header('Content-Type: application/gzip');
                            header('Content-Disposition: attachment; filename="' . $filename . '"');
                            header('Content-Length: ' . strlen($gzip));
                            header('Cache-Control: no-store, no-cache, must-revalidate');
                            header('Pragma: no-cache');
                            echo $gzip;
                            exit;
                        }
                    }
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database Backup - Railway PHP Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0f1117; }
        .sidebar { min-height: 100vh; background: #151922; }
        .brand-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; background: #7c5cff; }
        .card { border-color: #2a3140; background: #171c26; }
        .text-soft { color: #9ba7b4; }
        code { color: #c9b8ff; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 d-md-block sidebar p-4">
            <div class="fs-5 fw-semibold mb-4"><span class="brand-dot me-2"></span>Railway Lab</div>
            <nav class="nav flex-column gap-2">
                <a class="nav-link text-secondary" href="/">Dashboard</a>
                <a class="nav-link active bg-primary-subtle rounded" href="/backup.php">Backup</a>
            </nav>
        </aside>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="mb-4">
                <h1 class="h3 mb-1">Database Backup</h1>
                <div class="text-soft">Create a portable MySQL SQL dump from the running Railway database.</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= h($error) ?></div>
            <?php endif; ?>

            <?php if (!$ready): ?>
                <div class="alert alert-warning">
                    Backup is disabled until the Railway variable <code>BACKUP_TOKEN</code> is configured.
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-xl-7">
                    <div class="card p-4">
                        <h2 class="h5">Download backup</h2>
                        <p class="text-soft">
                            The export contains tables, data, triggers, routines and events. The database password is not stored in the downloaded file.
                        </p>
                        <div class="table-responsive mb-3">
                            <table class="table table-dark table-borderless mb-0">
                                <tr><th>Host</th><td><?= h(getenv('MYSQLHOST') ?: 'Not configured') ?></td></tr>
                                <tr><th>Database</th><td><?= h(getenv('MYSQLDATABASE') ?: 'Not configured') ?></td></tr>
                                <tr><th>Format</th><td>Compressed SQL (.sql.gz)</td></tr>
                            </table>
                        </div>
                        <form method="post" autocomplete="off">
                            <label class="form-label" for="backup_token">Backup token</label>
                            <input class="form-control mb-3" type="password" id="backup_token" name="backup_token" required <?= !$ready ? 'disabled' : '' ?> autocomplete="current-password">
                            <button class="btn btn-primary" type="submit" <?= !$ready ? 'disabled' : '' ?>>Create &amp; download backup</button>
                        </form>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card p-4">
                        <h2 class="h5">Restore on another MySQL server</h2>
                        <p class="text-soft">Uncompress the file and import it into an existing target database:</p>
                        <pre class="bg-black rounded p-3 small"><code>gunzip railway-backup.sql.gz
mysql -h TARGET_HOST -P 3306 -u TARGET_USER -p TARGET_DATABASE &lt; railway-backup.sql</code></pre>
                        <p class="text-soft mb-0">
                            The dump does not create the target database itself, so you can restore it under a different database name.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
