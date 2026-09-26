INSERT INTO users (username, display_name, role)
VALUES
    ('admin', 'Demo Administrator', 'admin'),
    ('analyst', 'Demo Analyst', 'analyst'),
    ('viewer', 'Demo Viewer', 'viewer')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), role = VALUES(role);

INSERT INTO audit_events (event_type, message)
SELECT 'DEPLOY', 'Application database initialized on Railway'
WHERE NOT EXISTS (
    SELECT 1 FROM audit_events WHERE event_type = 'DEPLOY' AND message = 'Application database initialized on Railway'
);
