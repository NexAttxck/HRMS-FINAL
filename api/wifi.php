<?php
/**
 * Wi-Fi SSID Restriction API
 * Endpoints:
 *   GET  ?action=allowed-ssids          → public (session-guarded) whitelist
 *   POST ?action=clock-in               → process clock-in with SSID metadata
 *   POST ?action=log-failed             → log a blocked clock-in attempt
 *   GET  ?action=failed-logs            → paginated failed attempts (admin)
 *   POST ?action=add-ssid               → add SSID to whitelist (admin)
 *   POST ?action=delete-ssid            → remove SSID from whitelist (admin)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth guard ────────────────────────────────────────────────────────────────
if (!Auth::id()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

// ── GET /api/wifi?action=allowed-ssids ────────────────────────────────────────
if ($action === 'allowed-ssids' && $method === 'GET') {
    $ssids = DB::fetchAll(
        "SELECT id, ssid_name, location_label FROM allowed_ssids WHERE is_active=1 ORDER BY ssid_name"
    );
    echo json_encode(['ok' => true, 'ssids' => $ssids]);
    exit;
}

// ── POST /api/wifi?action=clock-in ────────────────────────────────────────────
if ($action === 'clock-in' && $method === 'POST') {
    $empId       = Auth::empId();
    $detectedSSID = trim($_POST['detectedSSID'] ?? '');
    $deviceId    = trim($_POST['deviceId'] ?? '');
    $ip          = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    if (!$empId) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'No linked employee record.']);
        exit;
    }

    // Prevent duplicate clock-in on same day
    $existing = DB::fetchOne(
        "SELECT id FROM attendance WHERE employee_id=? AND date=CURDATE()",
        [$empId]
    );
    if ($existing) {
        echo json_encode(['ok' => false, 'error' => 'Already clocked in today.']);
        exit;
    }

    // Insert attendance record with SSID metadata stored in notes
    DB::execute(
        "INSERT INTO attendance (employee_id, date, clock_in, status, notes, ip_address, created_at)
         VALUES (?, CURDATE(), NOW(), 'On Time', ?, ?, ?)",
        [$empId, $detectedSSID ? 'SSID: ' . $detectedSSID : null, $ip, time()]
    );

    // Log successful attempt
    DB::execute(
        "INSERT INTO clock_in_attempts (employee_id, detected_ssid, status, device_id, ip_address, timestamp)
         VALUES (?, ?, 'success', ?, ?, NOW())",
        [$empId, $detectedSSID ?: null, $deviceId ?: null, $ip]
    );

    Auth::audit('Clock In (Wi-Fi Verified)', 'Attendance', $empId, 'SSID: ' . $detectedSSID);

    echo json_encode(['ok' => true, 'message' => 'Clocked in successfully!']);
    exit;
}

// ── POST /api/wifi?action=log-failed ──────────────────────────────────────────
if ($action === 'log-failed' && $method === 'POST') {
    $empId        = Auth::empId();
    $detectedSSID = trim($_POST['detectedSSID'] ?? '');
    $reason       = $_POST['reason'] ?? 'ssid_mismatch'; // ssid_mismatch | no_wifi | permission_denied
    $deviceId     = trim($_POST['deviceId'] ?? '');
    $ip           = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    $allowedReasons = ['ssid_mismatch', 'no_wifi', 'permission_denied'];
    if (!in_array($reason, $allowedReasons)) $reason = 'ssid_mismatch';

    if ($empId) {
        DB::execute(
            "INSERT INTO clock_in_attempts (employee_id, detected_ssid, status, fail_reason, device_id, ip_address, timestamp)
             VALUES (?, ?, 'failed', ?, ?, ?, NOW())",
            [$empId, $detectedSSID ?: null, $reason, $deviceId ?: null, $ip]
        );
        Auth::audit('Clock In Blocked', 'Attendance', $empId, json_encode(['reason' => $reason, 'ssid' => $detectedSSID]));
    }

    echo json_encode(['ok' => true, 'message' => 'Failed attempt logged.']);
    exit;
}

// ── GET /api/wifi?action=failed-logs ─────────────────────────────────────────
if ($action === 'failed-logs' && $method === 'GET') {
    if (!Auth::isAdmin()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Admin only.']);
        exit;
    }

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 20;
    $offset  = ($page - 1) * $perPage;

    // Filters
    $where  = ['1=1'];
    $params = [];

    if (!empty($_GET['from'])) {
        $where[]  = 'cia.timestamp >= ?';
        $params[] = $_GET['from'] . ' 00:00:00';
    }
    if (!empty($_GET['to'])) {
        $where[]  = 'cia.timestamp <= ?';
        $params[] = $_GET['to'] . ' 23:59:59';
    }
    if (!empty($_GET['emp'])) {
        $where[]  = 'cia.employee_id = ?';
        $params[] = (int)$_GET['emp'];
    }
    if (!empty($_GET['reason'])) {
        $where[]  = 'cia.fail_reason = ?';
        $params[] = $_GET['reason'];
    }

    $whereStr = implode(' AND ', $where);
    $total    = (int)DB::fetchScalar(
        "SELECT COUNT(*) FROM clock_in_attempts cia WHERE $whereStr", $params
    );

    $rows = DB::fetchAll(
        "SELECT cia.*, CONCAT(e.first_name,' ',e.last_name) AS employee_name, e.employee_no
         FROM clock_in_attempts cia
         JOIN employee e ON cia.employee_id = e.id
         WHERE $whereStr
         ORDER BY cia.timestamp DESC
         LIMIT $perPage OFFSET $offset",
        $params
    );

    echo json_encode([
        'ok'    => true,
        'total' => $total,
        'page'  => $page,
        'rows'  => $rows,
    ]);
    exit;
}

// ── POST /api/wifi?action=add-ssid ────────────────────────────────────────────
if ($action === 'add-ssid' && $method === 'POST') {
    if (!Auth::isAdmin()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Admin only.']);
        exit;
    }

    $ssidName      = trim($_POST['ssid_name'] ?? '');
    $locationLabel = trim($_POST['location_label'] ?? '');

    if ($ssidName === '') {
        echo json_encode(['ok' => false, 'error' => 'SSID name is required.']);
        exit;
    }

    // Check duplicate
    $dup = DB::fetchOne(
        "SELECT id FROM allowed_ssids WHERE ssid_name = ? AND is_active = 1",
        [$ssidName]
    );
    if ($dup) {
        echo json_encode(['ok' => false, 'error' => 'This SSID is already in the whitelist.']);
        exit;
    }

    DB::execute(
        "INSERT INTO allowed_ssids (ssid_name, location_label, is_active, created_by, created_at)
         VALUES (?, ?, 1, ?, ?)",
        [$ssidName, $locationLabel ?: null, Auth::id(), time()]
    );

    Auth::audit('Add Allowed SSID', 'Settings', null, $ssidName);
    echo json_encode(['ok' => true, 'message' => 'SSID added successfully.']);
    exit;
}

// ── POST /api/wifi?action=delete-ssid ────────────────────────────────────────
if ($action === 'delete-ssid' && $method === 'POST') {
    if (!Auth::isAdmin()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Admin only.']);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['ok' => false, 'error' => 'Invalid ID.']);
        exit;
    }

    DB::execute("UPDATE allowed_ssids SET is_active=0 WHERE id=?", [$id]);
    Auth::audit('Remove Allowed SSID', 'Settings', $id);
    echo json_encode(['ok' => true, 'message' => 'SSID removed.']);
    exit;
}

// ── Fallback ──────────────────────────────────────────────────────────────────
http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Unknown action.']);
