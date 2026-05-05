<?php
// includes/layout_header.php
// Requires $pageTitle to be set by each page
Auth::check();
$role     = Auth::role();
$username = Auth::name();
$userId   = Auth::id();

// Display label map — internal role stays unchanged for auth logic
$roleLabelMap = ['Super Admin' => 'HR Manager', 'Manager' => 'Manager', 'Employee' => 'Employee'];
$roleLabel    = $roleLabelMap[$role] ?? $role;

// Notifications
$unreadCount = (int) DB::fetchScalar("SELECT COUNT(*) FROM system_notification WHERE user_id = ? AND is_read = 0", [$userId]);
$recentNotifs = DB::fetchAll("SELECT * FROM system_notification WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$userId]);

// Sidebar menu per role
$adminMenu = [
    ['Dashboard',          'dashboard',        '&#9632;'],
    ['Employee Directory', 'employees',        '&#128101;'],
    ['Personnel Actions',  'personnel_action', '&#128221;'],
    ['Departments',        'department',       '&#9960;'],
    ['Positions',          'position',         '&#9733;'],
    ['Onboarding',         'onboarding',       '&#9998;'],
    ['Announcements',      'announcement',     '&#128227;'],
    ['Attendance',         'attendance',       '&#128339;'],
    ['Leave Management',   'leave',            '&#128197;'],
    ['Leave Balances',     'leave_balance',    '&#128200;'],
    ['Shift Scheduling',   'shift',            '&#128197;'],
    ['Timelog Disputes',   'timelog_dispute',   '&#128221;'],
    // ['Holidays',        'holiday',          '&#127878;'],   // DISABLED
    // ['Payroll',         'payroll',          '&#128181;'],  // REMOVED
    // ['Benefits',        'benefit',          '&#127873;'],   // DISABLED
    // ['Recruitment',     'recruitment',      '&#128203;'],   // DISABLED
    // ['Projects',        'project',          '&#128196;'],   // DISABLED
    // ['L&D / LMS',       'lms',              '&#127891;'],  // REMOVED
    ['Reports',            'report',           '&#128202;'],
    ['Feedback',           'feedback',         '&#128172;'],
    ['Audit Logs',         'audit',            '&#128220;'],
    ['User Management',    'user_management',  '&#128101;'],
    ['System Settings',    'settings',         '&#9881;'],
];
$managerMenu = [
    ['Dashboard',          'dashboard',        '&#9632;'],
    ['My Profile',         'my_profile',       '&#128100;'],
    ['Employee Directory', 'employees',        '&#128101;'],
    ['Personnel Actions',  'personnel_action', '&#128221;'],
    ['Departments',        'department',       '&#9960;'],
    ['Positions',          'position',         '&#9733;'],
    ['Onboarding',         'onboarding',       '&#9998;'],
    ['Announcements',      'announcement',     '&#128227;'],
    ['Attendance',         'attendance',       '&#128339;'],
    ['Leave Management',   'leave',            '&#128197;'],
    ['Leave Balances',     'leave_balance',    '&#128200;'],
    ['Shift Scheduling',   'shift',            '&#128197;'],
    ['Timelog Disputes',   'timelog_dispute',   '&#128221;'],
    // ['Payroll',         'payroll',          '&#128181;'],  // REMOVED
    ['Feedback',           'feedback',         '&#128172;'],
    ['Reports',            'report',           '&#128202;'],
    ['System Settings',    'settings',         '&#9881;'],
];
$employeeMenu = [
    ['Dashboard',      'dashboard',   '&#9632;'],
    ['My Profile',     'my_profile',  '&#128100;'],
    ['Announcements',  'announcement','&#128227;'],
    ['My Attendance',  'attendance',  '&#128339;'],
    ['My Schedule',    'my_schedule', '&#128197;'],
    ['My Leave',       'my_leave',    '&#128197;'],
    // ['My Payslips',  'my_payslips', '&#128181;'],  // REMOVED
    ['My Onboarding',  'my_onboarding','&#9998;'],
    ['Timelog Disputes','timelog_dispute','&#128221;'],
    ['Feedback',       'feedback',    '&#128172;'],
];
$menu = $role === 'Super Admin' ? $adminMenu : ($role === 'Manager' ? $managerMenu : $employeeMenu);
$currentPage = $_GET['page'] ?? 'dashboard';
$flashes = Auth::getFlashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($pageTitle ?? APP_NAME); ?></title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/web/css/hrms.css?v=<?php echo time(); ?>">
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script>
(function () {
    var dm = localStorage.getItem('hrms_dark_mode') || 'on';
    function applyDarkMode(val) {
        var root = document.documentElement;
        if (val === 'on') { root.removeAttribute('data-theme'); }
        else if (val === 'off') {
            root.setAttribute('data-theme', 'light');
            var s = document.createElement('style'); s.id = 'light-theme-style';
            s.textContent = [
                /* ── Light Mode Token Overrides ── */
                ':root[data-theme="light"] {',
                '  --bg: #eef3ee;',
                '  --sidebar: #1a3d28;',
                '  --card: #f8fbf8;',
                '  --card-hover: #eff5ef;',
                '  --border: rgba(45,90,60,0.14);',
                '  --text: #1a2e1a;',
                '  --text-muted: #4a6b52;',
                '  background: #eef3ee;',   /* kill white gap on html element itself */
                '  margin: 0; padding: 0;', /* ensure no browser default margin on html */
                '}',
                '* { box-sizing: border-box; }',
                'html[data-theme="light"], body { margin:0 !important; padding:0 !important; }',

                /* ── Base ── */
                ':root[data-theme="light"] body.hrms-body { background:var(--bg); color:var(--text); }',

                /* ── Header — medium forest green ── */
                ':root[data-theme="light"] .hrms-header {',
                '  background: #2d5a42;',
                '  border-bottom: 1px solid rgba(0,0,0,0.15);',
                '}',
                ':root[data-theme="light"] .search-input {',
                '  background: rgba(255,255,255,0.12);',
                '  border-color: rgba(255,255,255,0.2);',
                '  color: #fff;',
                '}',
                ':root[data-theme="light"] .search-input::placeholder { color:rgba(255,255,255,0.55); }',
                ':root[data-theme="light"] .search-icon { color:rgba(255,255,255,0.6); }',
                ':root[data-theme="light"] .user-name, :root[data-theme="light"] .user-role { color:#fff; }',
                ':root[data-theme="light"] .header-user .dropdown-toggle { color:#fff; }',
                ':root[data-theme="light"] .notif-bell svg { stroke:#fff; }',

                /* ── Sidebar — dark forest green ── */
                ':root[data-theme="light"] .hrms-sidebar { background:#1a3d28; border-right:1px solid rgba(0,0,0,0.2); }',
                ':root[data-theme="light"] .brand-text {',
                '  background: linear-gradient(135deg,#a8d8b4,#d4edda);',
                '  -webkit-background-clip:text; -webkit-text-fill-color:transparent;',
                '}',
                ':root[data-theme="light"] .nav-item a { color:rgba(255,255,255,0.75); border-left-color:transparent; }',
                ':root[data-theme="light"] .nav-item a:hover { background:rgba(255,255,255,0.08); color:#fff; }',
                ':root[data-theme="light"] .nav-item.active a {',
                '  background:rgba(124,147,104,0.25); color:#fff; border-left-color:#7C9368;',
                '}',

                /* ── Cards ── */
                ':root[data-theme="light"] .hrms-card {',
                '  background:var(--card);',
                '  border:1px solid var(--border);',
                '  box-shadow:0 1px 4px rgba(30,80,50,0.06);',
                '}',
                ':root[data-theme="light"] .hrms-card:hover { border-color:rgba(124,147,104,0.35); }',
                ':root[data-theme="light"] .card-header { border-bottom:1px solid var(--border); }',
                ':root[data-theme="light"] .card-header h3 { color:var(--text); }',

                /* ── Inputs ── */
                ':root[data-theme="light"] .hrms-input, :root[data-theme="light"] .form-control.hrms-input {',
                '  background:#fff;',
                '  border:1px solid rgba(45,90,60,0.2);',
                '  color:var(--text);',
                '}',
                ':root[data-theme="light"] .hrms-input:focus {',
                '  border-color:var(--accent);',
                '  box-shadow:0 0 0 2px rgba(124,147,104,0.18);',
                '}',
                ':root[data-theme="light"] select.hrms-input option { background:#fff; color:#1a2e1a; }',

                /* ── Table ── */
                ':root[data-theme="light"] .hrms-table thead th { color:var(--text-muted); border-bottom-color:var(--border); }',
                ':root[data-theme="light"] .hrms-table tbody td { border-bottom-color:var(--border); color:var(--text); }',
                ':root[data-theme="light"] .hrms-table tbody tr:hover { background:rgba(124,147,104,0.06); }',

                /* ── Dropdown ── */
                ':root[data-theme="light"] .hrms-dropdown { background:#fff !important; border-color:var(--border) !important; }',
                ':root[data-theme="light"] .dropdown-menu { background:#fff; border:1px solid var(--border); }',
                ':root[data-theme="light"] .dropdown-menu>li>a { color:var(--text); }',
                ':root[data-theme="light"] .dropdown-menu>li>a:hover { background:var(--card-hover); }',
                ':root[data-theme="light"] .hrms-dropdown li a { color:var(--text) !important; }',
                ':root[data-theme="light"] .hrms-dropdown li a:hover { background:var(--card-hover) !important; }',

                /* ── Buttons ── */
                ':root[data-theme="light"] .btn-outline { border-color:rgba(45,90,60,0.25); color:var(--text); }',
                ':root[data-theme="light"] .btn-outline:hover { border-color:var(--accent); color:var(--accent); }',

                /* ── KPI cards ── */
                ':root[data-theme="light"] .kpi-card { background:var(--card); border-color:var(--border); }',

                /* ── Modal & notification panel ── */
                ':root[data-theme="light"] .hrms-modal { background:#fff; }',
                ':root[data-theme="light"] #notifDropdown { background:#fff; border-color:var(--border); }',

                /* ── Misc text ── */
                ':root[data-theme="light"] .text-muted { color:var(--text-muted) !important; }',
            ].join(' '); document.head.appendChild(s);
        } else {
            root.removeAttribute('data-theme');
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) applyDarkMode('off');
        }
    }
    applyDarkMode(dm);

    // ── Font Size Persistence — uses body zoom to scale ALL px values site-wide ──
    (function() {
        var size = parseInt(localStorage.getItem('hrms_font_size') || '14', 10);
        if (size < 12) size = 12;
        if (size > 20) size = 20;
        var zoom = (size / 14).toFixed(4);
        var el = document.getElementById('hrmsFontStyle');
        if (!el) { el = document.createElement('style'); el.id = 'hrmsFontStyle'; document.head.appendChild(el); }
        el.textContent = 'body.hrms-body { zoom: ' + zoom + '; }';
    })();

    window.hrmsApplyDarkMode = applyDarkMode;
})();
</script>
</head>
<body class="hrms-body">
<div class="hrms-wrapper">

<!-- Top Header -->
<header class="hrms-header">
    <div class="header-left">
        <button class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">&#9776;</button>
    </div>
    <div class="header-right">
        <div class="notif-bell" onclick="toggleNotifications()" style="position:relative;cursor:pointer;padding:6px;border-radius:8px;transition:background .2s;" onmouseenter="this.style.background='rgba(255,255,255,0.07)'" onmouseleave="this.style.background='transparent'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            <?php if($unreadCount > 0): ?><span id="notifBadge" style="position:absolute;top:2px;right:2px;min-width:16px;height:16px;background:#ef5350;color:#fff;font-size:9px;font-weight:700;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid var(--bg);"><?php echo $unreadCount; ?></span><?php endif; ?>
        </div>
        <div id="notifDropdown" style="display:none;position:absolute;right:16px;top:58px;width:340px;background:var(--card);border:1px solid var(--border);border-radius:12px;z-index:1001;box-shadow:0 12px 40px rgba(0,0,0,0.45);overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <span style="font-weight:700;font-size:15px;color:var(--text);">Notifications</span>
                <?php if($unreadCount > 0): ?><a href="<?php echo url('notification', ['action'=>'markRead']); ?>" style="font-size:12px;color:var(--accent);text-decoration:none;" onclick="event.stopPropagation();">Mark all as read</a><?php endif; ?>
            </div>
            <div style="max-height:340px;overflow-y:auto;">
                <?php if(empty($recentNotifs)): ?>
                    <div style="padding:36px 24px;text-align:center;color:var(--text-muted);">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:10px;opacity:0.4;display:block;margin-left:auto;margin-right:auto;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        <p style="margin:0;font-size:13px;">You are all caught up!</p>
                    </div>
                <?php else: foreach($recentNotifs as $n):
                    $ic = $n['type']==='success'?'#66bb6a':($n['type']==='warning'?'#E07A45':($n['type']==='danger'?'#ef5350':'var(--accent)'));
                    $ip = $n['type']==='success'?'<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>':'<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>';
                    $bg = $n['is_read']?'transparent':'rgba(124,147,104,0.07)';
                ?>
                <a href="<?php echo e($n['link'] ?: '#'); ?>" style="display:flex;gap:12px;align-items:flex-start;padding:13px 18px;border-bottom:1px solid var(--border);text-decoration:none;background:<?php echo $bg; ?>;transition:background .15s;">
                    <div style="width:34px;height:34px;border-radius:50%;background:<?php echo $ic; ?>20;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="<?php echo $ic; ?>" stroke-width="2"><?php echo $ip; ?></svg>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:<?php echo $n['is_read']?'400':'600'; ?>;color:var(--text);margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($n['title']); ?></div>
                        <div style="font-size:12px;color:var(--text-muted);line-height:1.4;"><?php echo e($n['message']); ?></div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;opacity:.7;"><?php echo date('M j, g:i A', strtotime($n['created_at'])); ?></div>
                    </div>
                    <?php if(!$n['is_read']): ?><div style="width:8px;height:8px;border-radius:50%;background:var(--accent);flex-shrink:0;margin-top:6px;"></div><?php endif; ?>
                </a>
                <?php endforeach; endif; ?>
            </div>
            <div style="padding:10px 18px;border-top:1px solid var(--border);text-align:center;">
                <a href="<?php echo url('notification'); ?>" style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:600;">View all notifications &rarr;</a>
            </div>
        </div>
        <div class="header-user dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <span class="user-avatar"><?php echo strtoupper(substr($username ?: 'U', 0, 2)); ?></span>
                <span class="user-info">
                    <span class="user-name"><?php echo e($username); ?></span>
                    <span class="user-role"><?php echo e($roleLabel); ?></span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-right hrms-dropdown">
                <li><a href="<?php echo url('settings', ['tab'=>'profile']); ?>">&#9881; My Settings</a></li>
                <li class="divider"></li>
                <li><a href="<?php echo url('logout'); ?>" class="text-danger">&#10148; Logout</a></li>
            </ul>
        </div>
    </div>
</header>

<div class="hrms-body-row">
<!-- Sidebar -->
<nav id="sidebar" class="hrms-sidebar">
    <div class="sidebar-brand"><span class="brand-text">STAFFORA</span></div>
    <ul class="sidebar-nav">
    <?php foreach($menu as $item):
        $active = ($currentPage === $item[1]) ? ' active' : '';
        echo '<li class="nav-item'.$active.'"><a href="'.url($item[1]).'"><span class="nav-icon">'.$item[2].'</span><span class="nav-label">'.e($item[0]).'</span></a></li>';
    endforeach; ?>
    </ul>
</nav>

<!-- Main Content -->
<main class="hrms-main">
    <div class="hrms-toast-container" id="toastContainer">
    <?php foreach($flashes as $type => $msg): ?>
        <div class="hrms-toast toast-<?php echo $type; ?>" onclick="this.classList.add('fade-out');setTimeout(()=>this.remove(),400);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <?php if($type==='success'): ?><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline><?php endif; ?>
                <?php if($type==='error'): ?><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line><?php endif; ?>
            </svg>
            <div><?php echo $msg; ?></div>
        </div>
    <?php endforeach; ?>
    </div>
    <!-- Global Modal -->
    <div class="hrms-modal-backdrop" id="hrmsModalBackdrop">
        <div class="hrms-modal">
            <div class="hrms-modal-header">
                <svg id="hrmsModalIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef5350" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <h3 class="hrms-modal-title" id="hrmsModalTitle">Confirm Action</h3>
            </div>
            <div class="hrms-modal-body" id="hrmsModalBody">Are you sure you want to proceed?</div>
            <div class="hrms-modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeHrmsModal()">Cancel</button>
                <a href="#" id="hrmsModalConfirmBtn" class="btn btn-danger">Confirm</a>
            </div>
        </div>
    </div>
