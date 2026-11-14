<?php
/**
 * LEULA EVENTS MANAGEMENT SYSTEM
 * Database Configuration & Core System Helpers
 * Universal Digital Invitation Design, Distribution & Multi-Method Guest Verification
 */

if (session_status() === PHP_SESSION_NONE) {
    $sessDir = __DIR__ . '/../storage/sessions';
    if (!is_dir($sessDir)) {
        @mkdir($sessDir, 0777, true);
    }
    if (is_dir($sessDir) && is_writable($sessDir)) {
        session_save_path($sessDir);
    }
    @session_start();
}

// System Brand Configuration
define('APP_NAME', 'Leula Events');
define('APP_TAGLINE', 'Digital Invitation Design, Distribution & Multi-Method Verification');
define('APP_VERSION', '2.0.0');
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$rawDir = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) : '';
if (strpos($rawDir, '/Invitation') !== false || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/Invitation') === 0)) {
    $basePath = '/Invitation';
} else {
    $basePath = ($rawDir === '/' || $rawDir === '.') ? '' : rtrim($rawDir, '/');
}
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
define('APP_URL', $protocol . '://' . $host . $basePath);

// Database Credentials (XAMPP Defaults)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'invitation_system');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Get Database Connection with Automatic Fallback
 */
function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // Try connecting to MySQL
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 2,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        try {
            $pdo->exec("SET SESSION max_allowed_packet = 67108864;");
        } catch (Throwable $t) {}
        try {
            $pdo->exec("SET GLOBAL max_allowed_packet = 67108864;");
        } catch (Throwable $t) {}
        return $pdo;
    } catch (PDOException $e) {
        // Try creating MySQL database if it just doesn't exist
        try {
            $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
            $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, [PDO::ATTR_TIMEOUT => 2]);
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Seed schema from database.sql if present
            $sqlFile = dirname(__DIR__) . '/database.sql';
            if (file_exists($sqlFile)) {
                $sqlContent = file_get_contents($sqlFile);
                $pdo->exec($sqlContent);
            }
            return $pdo;
        } catch (Exception $ex) {
            // If MySQL is completely stopped or unconfigured, gracefully fallback to high-speed SQLite
            // to ensure the user's application NEVER breaks or throws a fatal 500 error!
            $sqlitePath = __DIR__ . '/invitation_system.sqlite';
            $isNew = !file_exists($sqlitePath);
            $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            if ($isNew) {
                init_sqlite_schema($pdo);
            }
            $pdo->exec("CREATE TABLE IF NOT EXISTS invitation_designs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER NOT NULL,
                name TEXT,
                orientation TEXT DEFAULT 'portrait',
                width INTEGER DEFAULT 420,
                height INTEGER DEFAULT 640,
                background_color TEXT DEFAULT '#FFFFFF',
                canvas_data TEXT,
                canva_bg_data BLOB,
                canva_bg_mime TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                organization_id INTEGER,
                event_type TEXT NOT NULL,
                action TEXT NOT NULL,
                description TEXT,
                ip_address TEXT,
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            return $pdo;
        }
    }
}

/**
 * Initialize Fallback SQLite Schema with Initial Demo Data
 */
function init_sqlite_schema(PDO $pdo) {
    $schema = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        organization_id INTEGER DEFAULT 1,
        role_id INTEGER DEFAULT 1,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        phone TEXT,
        status TEXT DEFAULT 'active',
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        organization_id INTEGER DEFAULT 1,
        created_by INTEGER DEFAULT 1,
        event_category_id INTEGER DEFAULT 1,
        title TEXT NOT NULL,
        slug TEXT,
        description TEXT,
        start_date TEXT NOT NULL,
        end_date TEXT,
        start_time TEXT NOT NULL,
        end_time TEXT,
        timezone TEXT DEFAULT 'Africa/Dar_es_Salaam',
        venue_name TEXT NOT NULL,
        venue_address TEXT,
        google_maps_link TEXT,
        guest_capacity INTEGER DEFAULT 500,
        status TEXT DEFAULT 'published',
        is_published INTEGER DEFAULT 1,
        rsvp_enabled INTEGER DEFAULT 1,
        rsvp_deadline TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS guest_categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT,
        color TEXT DEFAULT '#FF7A00',
        description TEXT
    );

    CREATE TABLE IF NOT EXISTS guests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        organization_id INTEGER DEFAULT 1,
        event_id INTEGER NOT NULL,
        guest_category_id INTEGER DEFAULT 1,
        first_name TEXT NOT NULL,
        last_name TEXT,
        full_name TEXT NOT NULL,
        email TEXT,
        phone TEXT,
        permitted_attendees INTEGER DEFAULT 1,
        notes TEXT,
        status TEXT DEFAULT 'active',
        is_checked_in INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS gates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        code TEXT NOT NULL,
        description TEXT,
        is_active INTEGER DEFAULT 1,
        allowed_categories TEXT DEFAULT 'all',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS invitations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        organization_id INTEGER DEFAULT 1,
        event_id INTEGER NOT NULL,
        guest_id INTEGER NOT NULL,
        reference_code TEXT UNIQUE NOT NULL,
        status TEXT DEFAULT 'sent',
        sent_via TEXT DEFAULT 'whatsapp',
        sent_at DATETIME,
        delivered_at DATETIME,
        viewed_at DATETIME,
        is_checked_in INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS qr_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        invitation_id INTEGER NOT NULL,
        token TEXT UNIQUE NOT NULL,
        is_active INTEGER DEFAULT 1,
        is_revoked INTEGER DEFAULT 0,
        scan_count INTEGER DEFAULT 0,
        last_scanned_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS rsvp_records (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        invitation_id INTEGER NOT NULL,
        guest_id INTEGER NOT NULL,
        event_id INTEGER NOT NULL,
        response TEXT NOT NULL,
        attendee_count INTEGER DEFAULT 1,
        dietary_notes TEXT,
        message TEXT,
        responded_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS check_ins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        invitation_id INTEGER NOT NULL,
        guest_id INTEGER NOT NULL,
        event_id INTEGER NOT NULL,
        gate_id INTEGER DEFAULT 1,
        verified_by INTEGER DEFAULT 1,
        verification_method TEXT DEFAULT 'qr',
        attendee_count INTEGER DEFAULT 1,
        checked_in_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        notes TEXT
    );
    ";
    $pdo->exec($schema);

    // Seed default admin user (password: password123)
    $passHash = password_hash('password123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT OR IGNORE INTO users (id, name, email, password, phone, role_id) VALUES 
        (1, 'System Administrator', 'admin@leulaevents.com', '{$passHash}', '+255 711 000 001', 1),
        (2, 'Sarah Mwangi (Director)', 'organizer@leulaevents.com', '{$passHash}', '+255 712 345 678', 2),
        (3, 'David Kimani (Gate Lead)', 'officer@leulaevents.com', '{$passHash}', '+255 713 999 888', 3)
    ");

    // Seed Categories
    $pdo->exec("INSERT OR IGNORE INTO guest_categories (id, name, slug, color, description) VALUES
        (1, 'VVIP Dignitary', 'vvip', '#FF7A00', 'Cabinet ministers, keynote speakers and executive dignitaries'),
        (2, 'VIP Delegate', 'vip', '#E96800', 'Corporate partners, sponsors and registered VIP delegates'),
        (3, 'Regular Guest', 'regular', '#FFA64D', 'Standard general admission invitees'),
        (4, 'Staff & Media', 'staff', '#252525', 'Accredited event crew, operations and press media')
    ");

    // Seed Demo Event
    $pdo->exec("INSERT OR IGNORE INTO events (id, title, slug, description, start_date, start_time, end_time, venue_name, venue_address, guest_capacity, status) VALUES
        (1, 'Leula Events Annual Gala & Awards 2026', 'leula-gala-2026', 'The premier corporate gala celebrating technological excellence and leadership. Black tie required.', '2026-11-21', '18:00', '23:30', 'Serena Grand Ballroom', 'Ohio Street, City Centre, Dar es Salaam', 500, 'published'),
        (2, 'Executive Leadership Symposium 2026', 'leadership-symposium-2026', 'An exclusive symposium bringing together industry CEOs, ministers, and venture leaders.', '2026-12-05', '09:00', '17:00', 'Hyatt Regency Dar es Salaam', 'Kivukoni Road, Dar es Salaam', 150, 'published')
    ");

    // Seed Gates
    $pdo->exec("INSERT OR IGNORE INTO gates (id, event_id, name, code) VALUES
        (1, 1, 'VIP North Gate Entrance', 'GATE-VIP-01'),
        (2, 1, 'Main General West Gate', 'GATE-GEN-02')
    ");

    // Seed Demo Guests
    $pdo->exec("INSERT OR IGNORE INTO guests (id, event_id, guest_category_id, first_name, last_name, full_name, email, phone, permitted_attendees) VALUES
        (1, 1, 1, 'Dr. Reginald', 'Massawe', 'Dr. Reginald Massawe', 'reginald.m@tanzaniatech.org', '+255 712 000 001', 2),
        (2, 1, 1, 'Hon. Amina', 'Salum', 'Hon. Amina Salum', 'amina.salum@ict.gov.tz', '+255 712 000 002', 2),
        (3, 1, 2, 'Eng. Joseph', 'Kagame', 'Eng. Joseph Kagame', 'joseph.k@fintechafrica.com', '+255 712 000 003', 1),
        (4, 1, 3, 'Baraka', 'Mushi', 'Baraka Mushi', 'baraka.m@innovations.tz', '+255 712 000 005', 1),
        (5, 1, 3, 'Neema', 'Mwamburi', 'Neema Mwamburi', 'neema.m@mediahouse.tz', '+255 712 000 006', 1)
    ");

    // Seed Demo Invitations & QR Tokens
    $pdo->exec("INSERT OR IGNORE INTO invitations (id, event_id, guest_id, reference_code, status) VALUES
        (1, 1, 1, 'INV-REG-8821', 'delivered'),
        (2, 1, 2, 'INV-AMI-9943', 'delivered'),
        (3, 1, 3, 'INV-JOS-5512', 'sent'),
        (4, 1, 4, 'INV-BAR-1149', 'sent'),
        (5, 1, 5, 'INV-NEE-3320', 'generated')
    ");

    $pdo->exec("INSERT OR IGNORE INTO qr_tokens (id, invitation_id, token) VALUES
        (1, 1, 'DEMO-TOKEN-VIP-REG-01'),
        (2, 2, 'DEMO-TOKEN-VIP-AMI-02'),
        (3, 3, 'DEMO-TOKEN-VIP-JOS-03'),
        (4, 4, 'DEMO-TOKEN-REG-BAR-04'),
        (5, 5, 'DEMO-TOKEN-REG-NEE-05')
    ");

    // Seed Initial RSVP
    $pdo->exec("INSERT OR IGNORE INTO rsvp_records (id, invitation_id, guest_id, event_id, response, attendee_count) VALUES
        (1, 1, 1, 1, 'accepted', 2),
        (2, 2, 2, 1, 'accepted', 2),
        (3, 3, 3, 1, 'tentative', 1)
    ");

    // Seed Check-ins
    $pdo->exec("INSERT OR IGNORE INTO check_ins (id, invitation_id, guest_id, event_id, gate_id, verified_by, attendee_count) VALUES
        (1, 1, 1, 1, 1, 1, 2)
    ");
}

/**
 * Authentication Helpers
 */
function auth_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return $_SESSION['user'] ?? null;
}

function require_auth() {
    if (!auth_user()) {
        header('Location: login.php');
        exit;
    }
}

function user_role_id(): int {
    $u = auth_user();
    return (int)($u['role_id'] ?? 3);
}

function is_admin(): bool {
    return user_role_id() === 1;
}

function is_director(): bool {
    return user_role_id() <= 2;
}

function is_gate_officer(): bool {
    return user_role_id() === 3;
}

function require_role(int $allowedRoleMax = 2) {
    require_auth();
    $role = user_role_id();
    if ($role > $allowedRoleMax) {
        flash_set('warning', 'Access Restricted: Your user account role does not have permission to access that section.');
        if ($role === 3) {
            header('Location: scanner.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    }
}

function flash_set(string $type, string $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array {
    if (isset($_SESSION['flash'])) {
        $msg = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $msg;
    }
    return null;
}

/**
 * Event Scoping and Isolation Helpers
 * Ensures administrators and directors strictly only access events assigned to them.
 */
function get_user_events(?int $userId = null, ?int $roleId = null): array {
    $u = auth_user();
    $userId = $userId ?? ($u['id'] ?? 0);
    $roleId = $roleId ?? ($u['role_id'] ?? 3);

    $db = get_db();
    try {
        if ($roleId === 1) {
            // Super Admin can access all active events
            return db_fetch_all("SELECT * FROM events WHERE deleted_at IS NULL ORDER BY start_date DESC, id DESC");
        } elseif ($roleId === 2) {
            // Event Director can only access their created / assigned events
            return db_fetch_all("SELECT * FROM events WHERE created_by = ? AND deleted_at IS NULL ORDER BY start_date DESC, id DESC", [$userId]);
        } else {
            // Gate Officer sees active published events
            return db_fetch_all("SELECT * FROM events WHERE status = 'published' AND deleted_at IS NULL ORDER BY start_date DESC, id DESC");
        }
    } catch (Throwable $t) {
        // Fallback if deleted_at column is missing on older sqlite schemas
        if ($roleId === 1) {
            return db_fetch_all("SELECT * FROM events ORDER BY start_date DESC, id DESC");
        } elseif ($roleId === 2) {
            return db_fetch_all("SELECT * FROM events WHERE created_by = ? ORDER BY start_date DESC, id DESC", [$userId]);
        } else {
            return db_fetch_all("SELECT * FROM events WHERE status = 'published' ORDER BY start_date DESC, id DESC");
        }
    }
}

function can_user_access_event(int $eventId, ?int $userId = null, ?int $roleId = null): bool {
    $u = auth_user();
    $userId = $userId ?? ($u['id'] ?? 0);
    $roleId = $roleId ?? ($u['role_id'] ?? 3);

    if ($roleId === 1) {
        // Super Admin has universal access
        $ev = db_fetch_one("SELECT id FROM events WHERE id = ?", [$eventId]);
        return (bool)$ev;
    } elseif ($roleId === 2) {
        // Event Director must be owner of the event
        $ev = db_fetch_one("SELECT id FROM events WHERE id = ? AND created_by = ?", [$eventId, $userId]);
        return (bool)$ev;
    } else {
        // Gate Officer can access published events
        $ev = db_fetch_one("SELECT id FROM events WHERE id = ? AND status = 'published'", [$eventId]);
        return (bool)$ev;
    }
}

function require_event_access(int $eventId) {
    require_auth();
    if (!can_user_access_event($eventId)) {
        audit_log('SECURITY_VIOLATION', 'UNAUTHORIZED_EVENT_ACCESS_ATTEMPT', "User attempted to access unauthorized event ID {$eventId}");
        flash_set('danger', 'Access Denied: You do not have permission to access or manage this specific event.');
        header('Location: dashboard.php');
        exit;
    }
}

function get_active_event_id(): ?int {
    $userEvents = get_user_events();
    if (empty($userEvents)) {
        return null;
    }

    $allowedIds = array_column($userEvents, 'id');

    // Check if event requested via GET or POST
    if (isset($_GET['event_id']) && is_numeric($_GET['event_id'])) {
        $reqId = (int)$_GET['event_id'];
        if (in_array($reqId, $allowedIds)) {
            $_SESSION['active_event_id'] = $reqId;
            return $reqId;
        } else {
            audit_log('SECURITY_VIOLATION', 'CROSS_EVENT_ACCESS_BLOCKED', "Attempted to access out-of-scope event ID {$reqId}");
        }
    }

    // Check if session has a valid active event
    if (isset($_SESSION['active_event_id']) && in_array((int)$_SESSION['active_event_id'], $allowedIds)) {
        return (int)$_SESSION['active_event_id'];
    }

    // Default to the first available event
    $firstId = (int)$userEvents[0]['id'];
    $_SESSION['active_event_id'] = $firstId;
    return $firstId;
}

function get_active_event(): ?array {
    $id = get_active_event_id();
    if (!$id) {
        return null;
    }
    return db_fetch_one("SELECT * FROM events WHERE id = ?", [$id]);
}

/**
 * Database Query Utility
 */
function db_query(string $sql, array $params = []) {
    $db = get_db();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_fetch_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

function db_fetch_one(string $sql, array $params = []): ?array {
    $res = db_query($sql, $params)->fetch();
    return $res ?: null;
}

function db_insert(string $table, array $data): int {
    $db = get_db();

    // Auto-inject mandatory foreign keys and timestamps if missing
    $now = date('Y-m-d H:i:s');
    $orgId = $_SESSION['user']['organization_id'] ?? 1;
    $userId = $_SESSION['user_id'] ?? 1;

    if (in_array($table, ['guests', 'invitations', 'events']) && !isset($data['organization_id'])) {
        $data['organization_id'] = $orgId;
    }
    if ($table === 'events' && !isset($data['created_by'])) {
        $data['created_by'] = $userId;
    }
    if ($table === 'check_ins' && !isset($data['checked_in_at'])) {
        $data['checked_in_at'] = $now;
    }
    if ($table === 'rsvp_records' && !isset($data['responded_at'])) {
        $data['responded_at'] = $now;
    }
    if ($table === 'guests' && !isset($data['status'])) {
        $data['status'] = 'active';
    }
    if (in_array($table, ['users', 'events', 'guests', 'invitations', 'audit_logs']) && !isset($data['created_at'])) {
        $data['created_at'] = $now;
    }
    if (in_array($table, ['invitation_designs', 'audit_logs']) && !isset($data['updated_at'])) {
        $data['updated_at'] = $now;
    }

    $keys = array_keys($data);
    $fields = implode(', ', $keys);
    $placeholders = ':' . implode(', :', $keys);
    $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
    $stmt = $db->prepare($sql);
    $stmt->execute($data);
    return (int)$db->lastInsertId();
}

function db_update(string $table, array $data, string $where, array $whereParams = []): int {
    $db = get_db();
    $sets = [];
    $params = [];
    foreach ($data as $k => $v) {
        $sets[] = "{$k} = :set_{$k}";
        $params["set_{$k}"] = $v;
    }
    $setString = implode(', ', $sets);
    $sql = "UPDATE {$table} SET {$setString} WHERE {$where}";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($params, $whereParams));
    return $stmt->rowCount();
}

function audit_log(string $eventType, string $action, ?string $description = null, ?int $userId = null, ?int $orgId = null): int {
    try {
        $user = auth_user();
        $userId = $userId ?? ($user['id'] ?? null);
        $orgId = $orgId ?? ($user['organization_id'] ?? 1);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Browser';

        return db_insert('audit_logs', [
            'user_id' => $userId,
            'organization_id' => $orgId,
            'event_type' => $eventType,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ip,
            'user_agent' => substr($agent, 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Throwable $t) {
        error_log("Audit log failed: " . $t->getMessage());
        return 0;
    }
}

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function e($val): string {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

function svgIcon(string $name, int $size = 18, string $extra = ''): string {
    $icons = [
        'dashboard'     => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
        'calendar'      => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'events'        => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'users'         => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>',
        'user'          => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
        'palette'       => '<circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>',
        'ticket'        => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/><line x1="13" y1="5" x2="13" y2="19" stroke-dasharray="2"/>',
        'send'          => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
        'qr'            => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="4" height="4"/><line x1="18" y1="18" x2="21" y2="18"/><line x1="18" y1="21" x2="21" y2="21"/>',
        'check-circle'  => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'message'       => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'chart'         => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        'bell'          => '<path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/>',
        'building'      => '<path d="M6 22V4a2 2 0 012-2h8a2 2 0 012 2v18z"/><path d="M6 12H4a2 2 0 00-2 2v8h4"/><path d="M18 9h2a2 2 0 012 2v11h-4"/><line x1="10" y1="6" x2="10" y2="6"/><line x1="14" y1="6" x2="14" y2="6"/><line x1="10" y1="10" x2="10" y2="10"/><line x1="14" y1="10" x2="14" y2="10"/><line x1="10" y1="14" x2="10" y2="14"/><line x1="14" y1="14" x2="14" y2="14"/>',
        'settings'      => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>',
        'shield'        => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'search'        => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'plus'          => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'minus'         => '<line x1="5" y1="12" x2="19" y2="12"/>',
        'sliders'       => '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>',
        'maximize'      => '<path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>',
        'plus-circle'   => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',
        'download'      => '<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'upload'        => '<polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>',
        'check'         => '<polyline points="20 6 9 17 4 12"/>',
        'x'             => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'info'          => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'alert'         => '<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'edit'          => '<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'trash'         => '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/>',
        'eye'           => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'filter'        => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        'copy'          => '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>',
        'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
        'chevron-down'  => '<polyline points="6 9 12 15 18 9"/>',
        'logout'        => '<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'clock'         => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'map-pin'       => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>',
        'external-link' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
        'whatsapp'      => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
        'refresh'       => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>',
        'lock'          => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
        'undo'          => '<polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/>',
        'redo'          => '<polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>',
        'mail'          => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>',
        'phone'         => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>',
        'door'          => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><circle cx="10" cy="13" r="1"></circle>'
    ];

    $path = $icons[$name] ?? '<circle cx="12" cy="12" r="10"/>';
    return "<svg xmlns='http://www.w3.org/2000/svg' width='{$size}' height='{$size}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' {$extra}>{$path}</svg>";
}
