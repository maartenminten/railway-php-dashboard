<?php
require_once '/var/www/app/db.php';

$dbStatus = 'Disconnected';
$dbClass = 'danger';
$dbVersion = '-';
$stats = ['users' => 0, 'events' => 0];
$recent = [];
$error = null;

try {
    $pdo = db();
    $dbStatus = 'Connected';
    $dbClass = 'success';
    $dbVersion = (string) $pdo->query('SELECT VERSION()')->fetchColumn();

    $stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['events'] = (int) $pdo->query('SELECT COUNT(*) FROM audit_events')->fetchColumn();
    $recent = $pdo->query('SELECT event_type, message, created_at FROM audit_events ORDER BY id DESC LIMIT 5')->fetchAll();
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Railway PHP Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0f1117; }
        .sidebar { min-height: 100vh; background: #151922; }
        .brand-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; background: #7c5cff; }
        .card { border-color: #2a3140; background: #171c26; }
        .metric { font-size: 2rem; font-weight: 700; }
        .text-soft { color: #9ba7b4; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 d-md-block sidebar p-4">
            <div class="fs-5 fw-semibold mb-4"><span class="brand-dot me-2"></span>Railway Lab</div>
            <nav class="nav flex-column gap-2">
                <a class="nav-link active bg-primary-subtle rounded" href="#">Dashboard</a>
                <a class="nav-link text-secondary" href="#database">Database</a>
                <a class="nav-link text-secondary" href="#events">Events</a>
                <a class="nav-link text-secondary" href="/backup.php">Backup</a>
            </nav>
        </aside>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">System Dashboard</h1>
                    <div class="text-soft">Apache + PHP 8.3 + Railway MySQL</div>
                </div>
                <span class="badge text-bg-<?= htmlspecialchars($dbClass) ?> p-2">Database: <?= htmlspecialchars($dbStatus) ?></span>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-warning">
                    Database connection or schema is not ready yet. Run the database initialization script from the Railway console.<br>
                    <small><?= htmlspecialchars($error) ?></small>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100">
                        <div class="text-soft">PHP version</div>
                        <div class="metric"><?= htmlspecialchars(PHP_VERSION) ?></div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100">
                        <div class="text-soft">MySQL version</div>
                        <div class="metric fs-4"><?= htmlspecialchars($dbVersion) ?></div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100">
                        <div class="text-soft">Demo users</div>
                        <div class="metric"><?= $stats['users'] ?></div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100">
                        <div class="text-soft">Audit events</div>
                        <div class="metric"><?= $stats['events'] ?></div>
                    </div>
                </div>
            </div>

            <div class="card p-4 mb-4" id="database">
                <h2 class="h5">Database connection</h2>
                <div class="table-responsive">
                    <table class="table table-dark table-borderless align-middle mb-0">
                        <tr><th>Host</th><td><?= htmlspecialchars(getenv('MYSQLHOST') ?: 'Not configured') ?></td></tr>
                        <tr><th>Port</th><td><?= htmlspecialchars(getenv('MYSQLPORT') ?: '3306') ?></td></tr>
                        <tr><th>Database</th><td><?= htmlspecialchars(getenv('MYSQLDATABASE') ?: 'Not configured') ?></td></tr>
                        <tr><th>User</th><td><?= htmlspecialchars(getenv('MYSQLUSER') ?: 'Not configured') ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="card p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h2 class="h5 mb-1">Database backup</h2>
                        <div class="text-soft">Generate a portable compressed SQL backup directly from the web dashboard.</div>
                    </div>
                    <a href="/backup.php" class="btn btn-outline-primary">Open backup tool</a>
                </div>
            </div>

            <div class="card p-4" id="events">
                <h2 class="h5 mb-3">Recent audit events</h2>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead><tr><th>Type</th><th>Message</th><th>Created</th></tr></thead>
                        <tbody>
                        <?php if (!$recent): ?>
                            <tr><td colspan="3" class="text-soft">No data yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent as $row): ?>
                                <tr>
                                    <td><span class="badge text-bg-secondary"><?= htmlspecialchars($row['event_type']) ?></span></td>
                                    <td><?= htmlspecialchars($row['message']) ?></td>
                                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
