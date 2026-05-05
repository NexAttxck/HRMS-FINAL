<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

// Public pages (no auth required)
$publicPages = ['login', 'logout'];

if ($page === 'logout') { Auth::logout(); exit; }

if (!in_array($page, $publicPages)) {
    Auth::check();
    // Seed welcome notification once per user session
    if (!isset($_SESSION['notif_seeded'])) {
        $existing = DB::fetchScalar("SELECT COUNT(*) FROM system_notification WHERE user_id = ? AND title = 'Welcome back!'", [Auth::id()]);
        if (!$existing) {
            DB::execute("INSERT INTO system_notification (user_id, type, title, message, link, is_read, created_at) VALUES (?, 'info', 'Welcome back!', 'Your session is active. Have a great day!', ?, 0, NOW())", [
                Auth::id(), BASE_URL . '/index.php?page=dashboard'
            ]);
        }
        $_SESSION['notif_seeded'] = true;
    }
}

// Mark notifications as read via AJAX
if ($page === 'notification' && $action === 'markRead') {
    if (Auth::id()) {
        DB::execute("UPDATE system_notification SET is_read = 1 WHERE user_id = ?", [Auth::id()]);
    }
    echo json_encode(['ok' => true]);
    exit;
}

// Route to page
// NOTE: 'holiday','benefit','recruitment','project' are DISABLED — remove the comment below to re-enable
$allowed = [
    'login','dashboard','employees','attendance','leave','leave_balance','shift',
    'department','announcement','onboarding','timelog_dispute',
    'feedback','audit','settings','user_management','report',
    'my_profile','my_leave','my_schedule','my_onboarding', // 'payroll','my_payslips' REMOVED
    'notification','personnel_action','position'
    // ,'holiday','benefit','recruitment','project'  // <-- re-enable here
];

if (!in_array($page, $allowed)) {
    $page = 'dashboard';
}

$file = __DIR__ . '/pages/' . $page . '.php';
if (!file_exists($file)) {
    $page = 'dashboard';
    $file = __DIR__ . '/pages/dashboard.php';
}

require $file;
