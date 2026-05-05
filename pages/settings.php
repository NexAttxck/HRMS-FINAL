<?php
require_once __DIR__."/../config.php";
require_once __DIR__."/../includes/db.php";
require_once __DIR__."/../includes/auth.php";
Auth::check();
$isAdmin = Auth::isAdmin();
$userId  = Auth::id();
$empId   = Auth::empId();
$pageTitle = "Settings — ".APP_NAME;
$tab = $_GET['tab'] ?? 'profile';

// ── POST handlers ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? '';

    // Profile save
    if ($action === 'saveProfile') {
        DB::execute(
            "UPDATE `user` SET username=?, email=?, updated_at=? WHERE id=?",
            [$_POST['username'], $_POST['email'], time(), $userId]
        );
        if ($empId && !empty($_POST['first_name'])) {
            DB::execute(
                "UPDATE employee SET first_name=?,last_name=?,phone=?,address=?,updated_at=? WHERE id=?",
                [$_POST['first_name'],$_POST['last_name'],$_POST['phone']??null,$_POST['address']??null,time(),$empId]
            );
        }
        if (!empty($_POST['new_password'])) {
            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                Auth::flash('error','Passwords do not match.');
            } else {
                $cur = DB::fetchOne("SELECT password FROM `user` WHERE id=?", [$userId]);
                if ($cur && md5($_POST['current_password']) !== $cur['password']) {
                    Auth::flash('error','Current password is incorrect.');
                } else {
                    DB::execute("UPDATE `user` SET password=? WHERE id=?", [md5($_POST['new_password']), $userId]);
                    Auth::flash('success','Password changed!');
                }
            }
        }
        $_SESSION['username'] = $_POST['username'];
        $_SESSION['email']    = $_POST['email'];
        Auth::audit('Updated profile','Settings');
        if (empty($_SESSION['flash']['error'])) Auth::flash('success','Profile updated!');
        header("Location: ".url('settings',['tab'=>'profile'])); exit;
    }

    // System settings save (admin only)
    if ($isAdmin && $action === 'saveSystem') {
        $savedTab = $_POST['tab'] ?? 'company';
        if (!empty($_POST['settings']) && is_array($_POST['settings'])) {
            foreach ($_POST['settings'] as $k => $v) {
                $k = preg_replace('/[^a-z0-9_]/','', strtolower($k));
                DB::execute(
                    "INSERT INTO system_setting (`key`,`value`,updated_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE `value`=?,updated_at=?",
                    [$k,$v,time(),$v,time()]
                );
            }
        }
        Auth::audit('Updated system settings ('.$savedTab.')','Settings');
        Auth::flash('success','Settings saved!');
        header("Location: ".url('settings',['tab'=>$savedTab])); exit;
    }

    // Work Rule save/delete/assign logic
    if ($action === 'save_work_rule' && ($isAdmin || Auth::isManager())) {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            $_POST['name'],
            $_POST['schedule_type'],
            (float)($_POST['work_hours'] ?? 8),
            (int)($_POST['break_minutes'] ?? 60),
            (int)($_POST['break_count'] ?? 4),
            $_POST['start_time'] ?: null,
            $_POST['end_time'] ?: null,
            (int)($_POST['buffer_before'] ?? 0),
            (int)($_POST['buffer_after'] ?? 0),
            $_POST['core_start'] ?: null,
            $_POST['core_duration'] ? (float)$_POST['core_duration'] : null,
            implode(',', $_POST['working_days'] ?? []),
            isset($_POST['is_default']) ? 1 : 0,
        ];
        if ($id) {
            if (isset($_POST['is_default'])) DB::execute("UPDATE work_rule SET is_default=0 WHERE id!=?", [$id]);
            DB::execute("UPDATE work_rule SET name=?,schedule_type=?,work_hours=?,break_minutes=?,break_count=?,start_time=?,end_time=?,buffer_before=?,buffer_after=?,core_start=?,core_duration=?,working_days=?,is_default=? WHERE id=?", array_merge($data, [$id]));
        } else {
            if (isset($_POST['is_default'])) DB::execute("UPDATE work_rule SET is_default=0");
            DB::execute("INSERT INTO work_rule (name,schedule_type,work_hours,break_minutes,break_count,start_time,end_time,buffer_before,buffer_after,core_start,core_duration,working_days,is_default,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", array_merge($data, [Auth::id(), time()]));
        }
        Auth::flash('success', 'Work rule saved!');
        header('Location: '.url('settings',['tab'=>'work_rules'])); exit;
    }

    if ($action === 'delete_work_rule' && $isAdmin) {
        $id = (int)$_POST['id'];
        $count = (int)DB::fetchScalar("SELECT COUNT(*) FROM employee WHERE work_rule_id=?", [$id]);
        if ($count > 0) Auth::flash('error', "Cannot delete: $count employee(s) still assigned to this rule.");
        else { DB::execute("DELETE FROM work_rule WHERE id=?", [$id]); Auth::flash('success', 'Work rule deleted.'); }
        header('Location: '.url('settings',['tab'=>'work_rules'])); exit;
    }

    if ($action === 'assign_work_rule' && ($isAdmin || Auth::isManager())) {
        $ruleId = (int)$_POST['rule_id'];
        $empIds = $_POST['emp_ids'] ?? [];
        $mgrDept = Auth::deptId();
        foreach ($empIds as $eid) {
            $eid = (int)$eid;
            // Managers can only assign rules to employees in their own department
            if (!$isAdmin && $mgrDept) {
                $check = DB::fetchScalar("SELECT department_id FROM employee WHERE id=?", [$eid]);
                if ((int)$check !== (int)$mgrDept) continue;
            }
            DB::execute("UPDATE employee SET work_rule_id=? WHERE id=?", [$ruleId, $eid]);
        }
        Auth::flash('success', count($empIds).' employee(s) assigned.');
        header('Location: '.url('settings',['tab'=>'work_rules'])); exit;
    }
}

// ── Data fetch ─────────────────────────────────────────────────────────────
$user = DB::fetchOne("SELECT * FROM `user` WHERE id=?", [$userId]);
$emp  = $empId ? DB::fetchOne("SELECT * FROM employee WHERE id=?", [$empId]) : null;
$sys  = [];
foreach (DB::fetchAll("SELECT `key`,`value` FROM system_setting") as $s) $sys[$s['key']] = $s['value'];

function sv(array $sys, string $k, string $def=''): string { return $sys[$k] ?? $def; }

require_once __DIR__."/../includes/layout_header.php";

// ── Sidebar tabs definition ────────────────────────────────────────────────
$settingsTabs = [
    'profile'    => ['label'=>'My Profile',          'icon'=>'<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>'],
    'company'    => ['label'=>'Company Info',         'icon'=>'<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>'],
    'compliance' => ['label'=>'Gov Compliance',       'icon'=>'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>'],
    'schedule'   => ['label'=>'Work Hours',            'icon'=>'<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>'],
    'leave'      => ['label'=>'Leave Policy',         'icon'=>'<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>'],
    // 'payroll' tab REMOVED — payroll module dropped
    'attendance' => ['label'=>'Attendance',           'icon'=>'<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>'],
    'wifi'       => ['label'=>'Wi-Fi Restriction',    'icon'=>'<path d="M5 12.55a11 11 0 0 1 14.08 0"></path><path d="M1.42 9a16 16 0 0 1 21.16 0"></path><path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path><circle cx="12" cy="20" r="1"></circle>'],
    'work_rules' => ['label'=>'Work Rules',           'icon'=>'<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>'],
    'display'    => ['label'=>'Display',              'icon'=>'<circle cx="12" cy="12" r="3"></circle><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>'],
];

// Restrict non-admins: only profile + display tabs
$allowedTabs = $isAdmin ? array_keys($settingsTabs) : (Auth::isManager() ? ['profile','display','work_rules'] : ['profile','display']);
if (!in_array($tab, $allowedTabs)) $tab = 'profile';
?>

<div style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
    <div>
        <h1 style="font-size:28px;font-weight:700;margin:0;">Settings</h1>
        <p style="color:var(--text-muted);margin:4px 0 0;">Manage your profile and system configuration</p>
    </div>
</div>

<div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">

<!-- Sidebar Nav -->
<div class="hrms-card" style="width:220px;flex-shrink:0;padding:8px;">
<?php foreach($settingsTabs as $k => $t):
    if (!in_array($k, $allowedTabs)) continue;
    $active = ($tab === $k); ?>
    <a href="<?php echo url('settings',['tab'=>$k]); ?>"
       style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:8px;text-decoration:none;margin-bottom:4px;font-size:14px;font-weight:<?php echo $active?'600':'400'; ?>;color:<?php echo $active?'#fff':'var(--text-muted)'; ?>;background:<?php echo $active?'var(--primary)':'transparent'; ?>;transition:all .2s;"
       onmouseenter="if(!<?php echo $active?'true':'false'; ?>){this.style.background='rgba(255,255,255,0.06)';this.style.color='var(--text)';}"
       onmouseleave="if(!<?php echo $active?'true':'false'; ?>){this.style.background='transparent';this.style.color='var(--text-muted)';}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?php echo $t['icon']; ?></svg>
        <?php echo $t['label']; ?>
    </a>
<?php endforeach; ?>
</div>

<!-- Content Panel -->
<div style="flex:1;min-width:300px;">

<?php if ($tab === 'profile'): ?>
<form method="POST">
<input type="hidden" name="_action" value="saveProfile">
<div class="hrms-card" style="margin-bottom:20px;">
    <div class="card-header"><h3>Account Info</h3></div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div>
            <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;">Username</label>
            <input type="text" name="username" value="<?php echo e($user['username']??''); ?>" class="hrms-input" style="width:100%;box-sizing:border-box;">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;">Email</label>
            <input type="email" name="email" value="<?php echo e($user['email']??''); ?>" class="hrms-input" style="width:100%;box-sizing:border-box;">
        </div>
        <?php if($emp): ?>
        <div>
            <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;">First Name</label>
            <input type="text" name="first_name" value="<?php echo e($emp['first_name']??''); ?>" class="hrms-input" style="width:100%;box-sizing:border-box;">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;">Last Name</label>
            <input type="text" name="last_name" value="<?php echo e($emp['last_name']??''); ?>" class="hrms-input" style="width:100%;box-sizing:border-box;">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;">Phone</label>
            <input type="text" name="phone" value="<?php echo e($emp['phone']??''); ?>" class="hrms-input" style="width:100%;box-sizing:border-box;">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;">Address</label>
            <input type="text" name="address" value="<?php echo e($emp['address']??''); ?>" class="hrms-input" style="width:100%;box-sizing:border-box;">
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="hrms-card" style="margin-bottom:20px;">
    <div class="card-header"><h3>Change Password</h3><p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Leave blank to keep current password</p></div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
        <div>
            <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;">Current Password</label>
            <input type="password" name="current_password" class="hrms-input" style="width:100%;box-sizing:border-box;">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;">New Password</label>
            <input type="password" name="new_password" class="hrms-input" style="width:100%;box-sizing:border-box;">
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;">Confirm Password</label>
            <input type="password" name="confirm_password" class="hrms-input" style="width:100%;box-sizing:border-box;">
        </div>
    </div>
</div>
<div style="display:flex;justify-content:flex-end;gap:12px;">
    <button type="submit" class="btn btn-accent" style="display:flex;align-items:center;gap:8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
        Save Profile
    </button>
</div>
</form>

<?php elseif($tab==='company' && $isAdmin): ?>
<form method="POST"><input type="hidden" name="_action" value="saveSystem"><input type="hidden" name="tab" value="company">
<div class="hrms-card">
    <div class="card-header"><h3>Company Information</h3><p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Basic company details used in official reports and documents</p></div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <?php foreach(['company_name'=>['Company Name *','text','Staffora HRMS'],'company_address'=>['Company Address','text',''],'company_email'=>['Company Email','email',''],'company_phone'=>['Company Phone','text',''],'company_tin'=>['Tax ID (TIN)','text','']] as $k=>[$lbl,$type,$def]): ?>
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;"><?php echo $lbl; ?></label>
            <input type="<?php echo $type; ?>" name="settings[<?php echo $k; ?>]" class="hrms-input" style="width:100%;box-sizing:border-box;" value="<?php echo e(sv($sys,$k,$def)); ?>">
        </div>
        <?php endforeach; ?>
        <!-- Currency field removed: payroll module dropped -->
    </div>
</div>
<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:12px;">
    <a href="<?php echo url('settings',['tab'=>'company']); ?>" class="btn btn-outline">Cancel</a>
    <button type="submit" class="btn btn-accent" style="display:flex;align-items:center;gap:8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
        Save Settings
    </button>
</div>
</form>

<?php elseif($tab==='compliance' && $isAdmin): ?>
<form method="POST"><input type="hidden" name="_action" value="saveSystem"><input type="hidden" name="tab" value="compliance">
<div class="hrms-card">
    <div class="card-header"><h3>Government Compliance</h3><p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Employer registration numbers for government remittances</p></div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <?php foreach(['company_tin'=>['TIN (Employer)','123-456-789-000','Format: 123-456-789-000'],'company_sss'=>['SSS Employer Number','34-1234567-8','Format: 34-1234567-8'],'company_philhealth'=>['PhilHealth Employer Number','12-345678901-2','Format: 12-345678901-2'],'company_pagibig'=>['Pag-IBIG Employer Number','1234-5678-9012','Format: 1234-5678-9012']] as $k=>[$lbl,$ph,$hint]): ?>
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;"><?php echo $lbl; ?></label>
            <input type="text" name="settings[<?php echo $k; ?>]" class="hrms-input" style="width:100%;box-sizing:border-box;" placeholder="<?php echo $ph; ?>" value="<?php echo e(sv($sys,$k)); ?>">
            <p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;"><?php echo $hint; ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:12px;">
    <a href="<?php echo url('settings',['tab'=>'compliance']); ?>" class="btn btn-outline">Cancel</a>
    <button type="submit" class="btn btn-accent" style="display:flex;align-items:center;gap:8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
        Save Settings
    </button>
</div>
</form>

<?php elseif($tab==='schedule' && $isAdmin): ?>
<form method="POST"><input type="hidden" name="_action" value="saveSystem"><input type="hidden" name="tab" value="schedule">
<?php /* Pay Frequency & Cutoff settings REMOVED — payroll module dropped */ ?>
<div class="hrms-card" style="margin-bottom:20px;">
    <div class="card-header"><h3>Work Hour Defaults</h3><p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Company-wide defaults for attendance and scheduling</p></div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;">Work Hours / Day</label>
            <div style="display:flex;align-items:center;gap:8px;">
                <input type="number" name="settings[work_hours_per_day]" class="hrms-input" style="width:80px;" min="1" max="24" value="<?php echo e(sv($sys,'work_hours_per_day','8')); ?>">
                <span style="font-size:12px;color:var(--text-muted);">hours</span>
            </div>
        </div>
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;">Work Days / Week</label>
            <div style="display:flex;align-items:center;gap:8px;">
                <input type="number" name="settings[work_days_per_week]" class="hrms-input" style="width:80px;" min="1" max="7" value="<?php echo e(sv($sys,'work_days_per_week','5')); ?>">
                <span style="font-size:12px;color:var(--text-muted);">days</span>
            </div>
        </div>
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;">OT Rounding</label>
            <div style="display:flex;align-items:center;gap:8px;">
                <input type="number" name="settings[overtime_rounding_minutes]" class="hrms-input" style="width:80px;" min="1" max="60" value="<?php echo e(sv($sys,'overtime_rounding_minutes','15')); ?>">
                <span style="font-size:12px;color:var(--text-muted);">min increments</span>
            </div>
        </div>
    </div>
</div>
<div style="display:flex;justify-content:flex-end;gap:12px;">
    <a href="<?php echo url('settings',['tab'=>'schedule']); ?>" class="btn btn-outline">Cancel</a>
    <button type="submit" class="btn btn-accent" style="display:flex;align-items:center;gap:8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
        Save Settings
    </button>
</div>
</form>

<?php elseif($tab==='leave' && $isAdmin): ?>
<form method="POST"><input type="hidden" name="_action" value="saveSystem"><input type="hidden" name="tab" value="leave">
<div class="hrms-card">
    <div class="card-header"><h3>Leave Entitlements</h3><p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Annual leave days granted per employee per year</p></div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <?php foreach([
            'leave_vacation' =>['Vacation Leave',  15, 'Annual recreational leave'],
            'leave_sick'     =>['Sick Leave',       10, 'Medical / health leave'],
            'leave_personal' =>['Personal Leave',    5, 'Personal matters leave'],
            'leave_emergency'=>['Emergency Leave',   3, 'Unforeseen circumstances'],
            'leave_maternity'=>['Maternity Leave', 105, 'Per R.A. 11210'],
            'leave_paternity'=>['Paternity Leave',   7, 'Per R.A. 8187'],
        ] as $k=>[$lbl,$def,$desc]): ?>
        <div style="background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:8px;padding:16px;">
            <label style="font-size:14px;font-weight:600;display:block;margin-bottom:4px;"><?php echo $lbl; ?></label>
            <p style="font-size:12px;color:var(--text-muted);margin:0 0 10px;"><?php echo $desc; ?></p>
            <div style="display:flex;align-items:center;gap:8px;">
                <input type="number" name="settings[<?php echo $k; ?>]" class="hrms-input" min="0" max="365" style="width:80px;" value="<?php echo e(sv($sys,$k,(string)$def)); ?>">
                <span style="font-size:13px;color:var(--text-muted);">days / year</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:12px;">
    <a href="<?php echo url('settings',['tab'=>'leave']); ?>" class="btn btn-outline">Cancel</a>
    <button type="submit" class="btn btn-accent" style="display:flex;align-items:center;gap:8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
        Save Settings
    </button>
</div>
</form>


<?php elseif($tab==='attendance' && $isAdmin): ?>
<form method="POST"><input type="hidden" name="_action" value="saveSystem"><input type="hidden" name="tab" value="attendance">
<div class="hrms-card">
    <div class="card-header"><h3>Attendance &amp; Time Settings</h3><p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Core work hours and tardiness thresholds</p></div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;">Core Hours Start</label>
            <input type="time" name="settings[core_hours_start]" class="hrms-input" style="width:100%;box-sizing:border-box;" value="<?php echo e(sv($sys,'core_hours_start','09:00')); ?>">
        </div>
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;">Core Hours End</label>
            <input type="time" name="settings[core_hours_end]" class="hrms-input" style="width:100%;box-sizing:border-box;" value="<?php echo e(sv($sys,'core_hours_end','18:00')); ?>">
        </div>
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;">Late Threshold</label>
            <div style="display:flex;align-items:center;gap:8px;">
                <input type="number" name="settings[late_threshold_minutes]" class="hrms-input" style="width:90px;" min="0" value="<?php echo e(sv($sys,'late_threshold_minutes','15')); ?>">
                <span style="font-size:12px;color:var(--text-muted);">mins grace period</span>
            </div>
        </div>
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;">Half-day Threshold</label>
            <div style="display:flex;align-items:center;gap:8px;">
                <input type="number" name="settings[halfday_threshold_hours]" class="hrms-input" style="width:90px;" min="1" value="<?php echo e(sv($sys,'halfday_threshold_hours','4')); ?>">
                <span style="font-size:12px;color:var(--text-muted);">hours minimum</span>
            </div>
        </div>
    </div>
</div>
<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:12px;">
    <a href="<?php echo url('settings',['tab'=>'attendance']); ?>" class="btn btn-outline">Cancel</a>
    <button type="submit" class="btn btn-accent" style="display:flex;align-items:center;gap:8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
        Save Settings
    </button>
</div>
</form>

<?php elseif($tab==='work_rules'): ?>
<?php
$rules = DB::fetchAll("SELECT wr.*, u.username as created_by_name, (SELECT COUNT(*) FROM employee WHERE work_rule_id=wr.id) as emp_count FROM work_rule wr LEFT JOIN user u ON wr.created_by=u.id ORDER BY wr.is_default DESC, wr.name");
$_mgrDeptId = Auth::deptId();
$_deptClause = (!$isAdmin && Auth::isManager() && $_mgrDeptId) ? " AND e.department_id=".(int)$_mgrDeptId : "";
$employees = DB::fetchAll("SELECT e.id, e.first_name, e.last_name, e.employee_no, e.work_rule_id, d.name as dept_name FROM employee e LEFT JOIN department d ON e.department_id=d.id WHERE e.status IN ('Regular','Probationary'){$_deptClause} ORDER BY e.first_name");

$action = $_GET['action'] ?? 'index';
$editRule = null;
if ($action === 'edit' || $action === 'create') {
    $editId = (int)($_GET['id'] ?? 0);
    if ($editId) $editRule = DB::fetchOne("SELECT * FROM work_rule WHERE id=?", [$editId]);
}



$dayNames = ['','Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
$inputStyle = "width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;";
$labelStyle = "display:block;font-size:11px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;";
?>
<style>
.wr-card{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:22px;transition:all .2s;position:relative;overflow:hidden}
.wr-card:hover{border-color:var(--accent);transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,0.15)}
.wr-card.is-default{border-color:var(--accent)}
.wr-card.is-default::before{content:'DEFAULT';position:absolute;top:10px;right:-28px;background:var(--accent);color:#fff;font-size:9px;font-weight:700;padding:2px 32px;transform:rotate(45deg);letter-spacing:.5px}
.wr-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
.wr-basic{background:rgba(30,136,229,0.15);color:#42a5f5}
.wr-strict{background:rgba(251,140,0,0.15);color:#ffa726}
.wr-relax{background:rgba(124,147,104,0.15);color:var(--accent)}
.wr-custom{background:rgba(171,71,188,0.15);color:#ce93d8}
.wr-meta{display:flex;gap:16px;margin-top:14px;flex-wrap:wrap}
.wr-meta-item{font-size:11px;color:var(--text-muted)}
.wr-meta-item strong{color:var(--text);font-weight:600}
.day-check{display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border:1px solid var(--border);border-radius:6px;font-size:11px;cursor:pointer;transition:all .15s;user-select:none}
.day-check:has(input:checked){background:var(--accent);border-color:var(--accent);color:#fff}
.day-check input{display:none}
.schedule-fields{display:none;margin-top:16px;padding:16px;background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:10px}
.schedule-fields.active{display:block}
</style>

<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-size:24px;font-weight:700;margin:0;">Work Rules</h1>
        <p style="color:var(--text-muted);margin:4px 0 0;font-size:13px;">Define schedule templates and employment rules</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="<?php echo url('settings',['tab'=>'work_rules','action'=>'create']); ?>" class="btn btn-accent">+ New Work Rule</a>
    </div>
</div>

<?php if ($action === 'create' || $action === 'edit'): ?>
<!-- Create/Edit Form -->
<div class="hrms-card" style="margin-bottom:24px;">
    <div class="card-header"><h3 style="margin:0;"><?php echo $editRule ? 'Edit Work Rule' : 'Create Work Rule'; ?></h3></div>
    <div class="card-body" style="padding:24px;">
        <form method="POST" id="wrForm">
            <input type="hidden" name="_action" value="save_work_rule">
            <?php if($editRule): ?><input type="hidden" name="id" value="<?php echo $editRule['id']; ?>"><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label style="<?php echo $labelStyle; ?>">Rule Name</label>
                    <input type="text" name="name" value="<?php echo e($editRule['name'] ?? ''); ?>" required placeholder="e.g. Standard 9-to-6" style="<?php echo $inputStyle; ?>">
                </div>
                <div>
                    <label style="<?php echo $labelStyle; ?>">Schedule Type</label>
                    <select name="schedule_type" id="schedType" onchange="toggleScheduleFields()" style="<?php echo $inputStyle; ?>">
                        <?php foreach(['Basic','Strict Flexi','Relax Flexi','Custom'] as $st): ?>
                        <option value="<?php echo $st; ?>" <?php echo ($editRule['schedule_type'] ?? 'Basic') === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="<?php echo $labelStyle; ?>">Work Hours / Day</label>
                    <input type="number" name="work_hours" step="0.5" min="1" max="24" value="<?php echo $editRule['work_hours'] ?? 8; ?>" style="<?php echo $inputStyle; ?>">
                </div>
                <div>
                    <label style="<?php echo $labelStyle; ?>">Total Break (minutes)</label>
                    <input type="number" name="break_minutes" min="0" max="240" value="<?php echo $editRule['break_minutes'] ?? 60; ?>" style="<?php echo $inputStyle; ?>">
                </div>
                <div>
                    <label style="<?php echo $labelStyle; ?>">Number of Breaks</label>
                    <input type="number" name="break_count" min="1" max="10" value="<?php echo $editRule['break_count'] ?? 4; ?>" style="<?php echo $inputStyle; ?>">
                </div>
                <div>
                    <label style="<?php echo $labelStyle; ?>">Set as Default</label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--text);">
                        <input type="checkbox" name="is_default" <?php echo ($editRule['is_default'] ?? 0) ? 'checked' : ''; ?>> Standard
                    </label>
                </div>
            </div>

            <!-- Working Days -->
            <div style="margin-top:16px;">
                <label style="<?php echo $labelStyle; ?>">Working Days</label>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <?php
                    $currentDays = explode(',', $editRule['working_days'] ?? '1,2,3,4,5');
                    for ($d = 1; $d <= 7; $d++): ?>
                    <label class="day-check">
                        <input type="checkbox" name="working_days[]" value="<?php echo $d; ?>" <?php echo in_array($d, $currentDays) ? 'checked' : ''; ?>>
                        <?php echo $dayNames[$d]; ?>
                    </label>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Basic / Custom Fields -->
            <div id="fieldsBasic" class="schedule-fields">
                <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:12px;">Fixed Schedule Times</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label style="<?php echo $labelStyle; ?>">Start Time</label>
                        <input type="time" name="start_time" value="<?php echo $editRule['start_time'] ?? '09:00'; ?>" style="<?php echo $inputStyle; ?>">
                    </div>
                    <div>
                        <label style="<?php echo $labelStyle; ?>">End Time</label>
                        <input type="time" name="end_time" value="<?php echo $editRule['end_time'] ?? '18:00'; ?>" style="<?php echo $inputStyle; ?>">
                    </div>
                </div>
            </div>

            <!-- Strict Flexi Fields -->
            <div id="fieldsFlexi" class="schedule-fields">
                <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:12px;">Flexible Schedule Configuration</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label style="<?php echo $labelStyle; ?>">Core Start Time</label>
                        <input type="time" name="core_start" value="<?php echo $editRule['core_start'] ?? '10:00'; ?>" style="<?php echo $inputStyle; ?>">
                    </div>
                    <div>
                        <label style="<?php echo $labelStyle; ?>">Core Duration (hours)</label>
                        <input type="number" name="core_duration" step="0.5" min="1" max="12" value="<?php echo $editRule['core_duration'] ?? 4; ?>" style="<?php echo $inputStyle; ?>">
                    </div>
                    <div>
                        <label style="<?php echo $labelStyle; ?>">Buffer Before Start (hours)</label>
                        <select name="buffer_before" style="<?php echo $inputStyle; ?>">
                            <?php for($h=0;$h<=4;$h++): ?>
                            <option value="<?php echo $h*60; ?>" <?php echo ($editRule['buffer_before'] ?? 0) == $h*60 ? 'selected' : ''; ?>><?php echo $h; ?> hour<?php echo $h!==1?'s':''; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label style="<?php echo $labelStyle; ?>">Buffer After End (hours)</label>
                        <select name="buffer_after" style="<?php echo $inputStyle; ?>">
                            <?php for($h=0;$h<=4;$h++): ?>
                            <option value="<?php echo $h*60; ?>" <?php echo ($editRule['buffer_after'] ?? 0) == $h*60 ? 'selected' : ''; ?>><?php echo $h; ?> hour<?php echo $h!==1?'s':''; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div style="margin-top:20px;display:flex;gap:12px;">
                <button type="submit" class="btn btn-accent">Save Work Rule</button>
                <a href="<?php echo url('settings',['tab'=>'work_rules']); ?>" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleScheduleFields() {
    var type = document.getElementById('schedType').value;
    document.getElementById('fieldsBasic').className = 'schedule-fields' + (['Basic','Custom'].includes(type) ? ' active' : '');
    document.getElementById('fieldsFlexi').className = 'schedule-fields' + (['Strict Flexi','Custom'].includes(type) ? ' active' : '');
}
toggleScheduleFields();
</script>
<?php endif; ?>

<!-- Work Rules Grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;margin-bottom:24px;">
<?php foreach($rules as $r):
    $typeClass = match($r['schedule_type']) { 'Basic'=>'wr-basic','Strict Flexi'=>'wr-strict','Relax Flexi'=>'wr-relax',default=>'wr-custom' };
    $days = array_map(fn($d) => $dayNames[(int)$d] ?? '', explode(',', $r['working_days']));
?>
<div class="wr-card<?php echo $r['is_default'] ? ' is-default' : ''; ?>">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <h3 style="margin:0;font-size:16px;font-weight:700;"><?php echo e($r['name']); ?></h3>
        <span class="wr-badge <?php echo $typeClass; ?>"><?php echo $r['schedule_type']; ?></span>
    </div>

    <div class="wr-meta">
        <div class="wr-meta-item"><strong><?php echo $r['work_hours']; ?>h</strong> work</div>
        <div class="wr-meta-item"><strong><?php echo $r['break_minutes']; ?>m</strong> break (<?php echo $r['break_count']; ?>×<?php echo (int)($r['break_minutes']/$r['break_count']); ?>m)</div>
        <?php if($r['start_time'] && $r['schedule_type'] !== 'Relax Flexi'): ?>
        <div class="wr-meta-item"><strong><?php echo date('g:i A', strtotime($r['start_time'])); ?></strong> – <strong><?php echo date('g:i A', strtotime($r['end_time'])); ?></strong></div>
        <?php endif; ?>
        <?php if($r['buffer_before'] > 0 || $r['buffer_after'] > 0): ?>
        <div class="wr-meta-item">Buffer: <strong><?php echo ($r['buffer_before']/60); ?>h</strong> before, <strong><?php echo ($r['buffer_after']/60); ?>h</strong> after</div>
        <?php endif; ?>
    </div>

    <div style="margin-top:10px;font-size:11px;color:var(--text-muted);">
        <?php echo implode(', ', array_filter($days)); ?>
    </div>

    <div style="margin-top:14px;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:11px;color:var(--text-muted);">
            <span style="color:var(--accent);font-weight:700;"><?php echo $r['emp_count']; ?></span> employee<?php echo $r['emp_count']!=1?'s':''; ?> assigned
        </span>
        <div style="display:flex;gap:6px;">
            <a href="<?php echo url('settings',['tab'=>'work_rules','action'=>'edit','id'=>$r['id']]); ?>" class="btn btn-sm btn-outline" style="padding:4px 10px;font-size:11px;">Edit</a>
            <button onclick="openAssign(<?php echo $r['id']; ?>,'<?php echo e($r['name']); ?>')" class="btn btn-sm btn-accent" style="padding:4px 10px;font-size:11px;">Assign</button>
            <?php if($isAdmin && !$r['is_default']): ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this work rule?');"><input type="hidden" name="_action" value="delete_work_rule"><input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button type="submit" class="btn btn-sm btn-outline" style="padding:4px 10px;font-size:11px;color:#ef5350;border-color:#ef5350;">Delete</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php if(empty($rules)): ?>
<div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--text-muted);">
    <p style="font-size:14px;">No work rules created yet.</p>
    <a href="<?php echo url('settings',['tab'=>'work_rules','action'=>'create']); ?>" class="btn btn-accent" style="margin-top:12px;">+ Create First Rule</a>
</div>
<?php endif; ?>
</div>

<!-- Employee Assignment Table -->
<div class="hrms-card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h3 style="margin:0;">Employee Schedule Assignments</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="table hrms-table" style="margin:0;">
            <thead><tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Assigned Rule</th>
                <th style="text-align:center;">Type</th>
            </tr></thead>
            <tbody>
            <?php foreach($employees as $emp):
                $assigned = null;
                if ($emp['work_rule_id']) {
                    foreach ($rules as $r) { if ($r['id'] == $emp['work_rule_id']) { $assigned = $r; break; } }
                }
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:30px;height:30px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;">
                            <?php echo strtoupper(substr($emp['first_name'],0,1).substr($emp['last_name'],0,1)); ?>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:13px;"><?php echo e($emp['first_name'].' '.$emp['last_name']); ?></div>
                            <div style="font-size:11px;color:var(--text-muted);"><?php echo e($emp['employee_no'] ?? ''); ?></div>
                        </div>
                    </div>
                </td>
                <td style="font-size:12px;color:var(--text-muted);"><?php echo e($emp['dept_name'] ?? ''); ?></td>
                <td>
                    <?php if($assigned): ?>
                    <span style="font-weight:600;font-size:13px;"><?php echo e($assigned['name']); ?></span>
                    <?php else: ?>
                    <span style="font-size:12px;color:var(--text-muted);font-style:italic;">Not assigned</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;">
                    <?php if($assigned):
                        $tc = match($assigned['schedule_type']) { 'Basic'=>'wr-basic','Strict Flexi'=>'wr-strict','Relax Flexi'=>'wr-relax',default=>'wr-custom' };
                    ?>
                    <span class="wr-badge <?php echo $tc; ?>"><?php echo $assigned['schedule_type']; ?></span>
                    <?php else: ?>
                    <span class="wr-badge" style="background:rgba(255,255,255,0.05);color:var(--text-muted);">None</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Assign Modal -->
<div id="assignModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:9999;justify-content:center;align-items:center;">
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:16px;width:460px;max-height:80vh;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,0.5);overflow-y:auto;">
        <h3 style="margin:0 0 4px;font-size:18px;">Assign Employees</h3>
        <p id="assignRuleName" style="margin:0 0 16px;font-size:13px;color:var(--text-muted);"></p>
        <form method="POST">
            <input type="hidden" name="_action" value="assign_work_rule">
            <input type="hidden" name="rule_id" id="assignRuleId">
            <div style="max-height:340px;overflow-y:auto;border:1px solid var(--border);border-radius:10px;padding:8px;">
            <?php foreach($employees as $emp): ?>
                <label style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:6px;cursor:pointer;transition:background .15s;" onmouseenter="this.style.background='rgba(255,255,255,0.04)'" onmouseleave="this.style.background='transparent'">
                    <input type="checkbox" name="emp_ids[]" value="<?php echo $emp['id']; ?>">
                    <div style="width:26px;height:26px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;">
                        <?php echo strtoupper(substr($emp['first_name'],0,1).substr($emp['last_name'],0,1)); ?>
                    </div>
                    <div>
                        <div style="font-size:12px;font-weight:600;"><?php echo e($emp['first_name'].' '.$emp['last_name']); ?></div>
                        <div style="font-size:10px;color:var(--text-muted);"><?php echo e($emp['dept_name'] ?? ''); ?></div>
                    </div>
                </label>
            <?php endforeach; ?>
            </div>
            <div style="margin-top:16px;display:flex;gap:10px;">
                <button type="submit" class="btn btn-accent" style="flex:1;">Assign Selected</button>
                <button type="button" onclick="closeAssign()" class="btn btn-outline" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script>
function openAssign(id,name){document.getElementById('assignRuleId').value=id;document.getElementById('assignRuleName').textContent='Assign to: '+name;document.getElementById('assignModal').style.display='flex';}
function closeAssign(){document.getElementById('assignModal').style.display='none';}
</script>

<?php elseif($tab==='wifi' && $isAdmin): ?>
<style>
.hrms-toggle { position:relative; display:inline-block; width:44px; height:24px; flex-shrink:0; }
.hrms-toggle input[type="checkbox"] { opacity:0; width:0; height:0; margin:0; }
.hrms-toggle .slider { position:absolute; cursor:pointer; inset:0; background-color:var(--border); border-radius:24px; transition:.3s; }
.hrms-toggle .slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background-color:#fff; border-radius:50%; transition:.3s; box-shadow:0 1px 3px rgba(0,0,0,0.3); }
.hrms-toggle input:checked + .slider { background-color:var(--accent); }
.hrms-toggle input:checked + .slider:before { transform:translateX(20px); }
</style>
<form method="POST">
    <input type="hidden" name="_action" value="saveSystem">
    <input type="hidden" name="tab" value="wifi">
    <div class="hrms-card" style="margin-bottom:24px;">
        <div class="card-header">
            <h3>Wi-Fi SSID Restriction</h3>
            <p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Restrict clock-in attempts to specific company Wi-Fi networks.</p>
        </div>
        <div class="card-body">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:12px;margin-bottom:16px;">
                <div>
                    <h4 style="margin:0 0 4px;font-size:15px;">Enable Wi-Fi SSID Restriction</h4>
                    <p style="margin:0;font-size:12px;color:var(--text-muted);">Clock-in will be blocked if the device is not connected to an approved network.</p>
                </div>
                <label class="hrms-toggle">
                    <input type="hidden" name="settings[wifi_restriction_enabled]" value="0">
                    <input type="checkbox" name="settings[wifi_restriction_enabled]" value="1" <?php echo sv($sys,'wifi_restriction_enabled')==='1'?'checked':''; ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:12px;margin-bottom:24px;">
                <div>
                    <h4 style="margin:0 0 4px;font-size:15px;">Strict Mode</h4>
                    <p style="margin:0;font-size:12px;color:var(--text-muted);">If disabled, clock-ins will be allowed but flagged as "unverified" if they don't match.</p>
                </div>
                <label class="hrms-toggle">
                    <input type="hidden" name="settings[wifi_strict_mode]" value="0">
                    <input type="checkbox" name="settings[wifi_strict_mode]" value="1" <?php echo sv($sys,'wifi_strict_mode','1')==='1'?'checked':''; ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-accent">Save Settings</button>
            </div>
        </div>
    </div>
</form>

<div class="hrms-card" style="margin-bottom:24px;">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <div>
            <h3 style="margin:0;">Approved SSID Whitelist</h3>
            <p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Networks permitted for clock-in.</p>
        </div>
        <button onclick="document.getElementById('addSsidModal').style.display='flex'" class="btn btn-accent btn-sm">+ Add SSID</button>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="table hrms-table" style="margin:0;" id="ssidTable">
            <thead>
                <tr>
                    <th>SSID Name</th>
                    <th>Location Label</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Populated via JS -->
            </tbody>
        </table>
    </div>
</div>

<div class="hrms-card">
    <div class="card-header">
        <h3 style="margin:0;">Failed / Unverified Clock-In Logs</h3>
        <p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Recent attempts blocked by restriction policies.</p>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="table hrms-table" style="margin:0;" id="failedLogTable">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Employee</th>
                    <th>Detected SSID</th>
                    <th>Status</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <!-- Populated via JS -->
            </tbody>
        </table>
    </div>
</div>

<style>
.switch input:checked + span { background: var(--accent); }
.switch input:checked + span + .slider { transform: translateX(26px); }
</style>

<!-- Add SSID Modal -->
<div id="addSsidModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:9999;justify-content:center;align-items:center;">
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:16px;width:400px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,0.5);">
        <h3 style="margin:0 0 16px;font-size:18px;">Add Approved SSID</h3>
        <div style="margin-bottom:16px;">
            <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;">SSID Name *</label>
            <input type="text" id="newSsidName" class="hrms-input" style="width:100%;box-sizing:border-box;" placeholder="e.g. OfficeNet_5G">
            <p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">Must exactly match the network name (case-insensitive).</p>
        </div>
        <div style="margin-bottom:24px;">
            <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;">Location Label (Optional)</label>
            <input type="text" id="newSsidLocation" class="hrms-input" style="width:100%;box-sizing:border-box;" placeholder="e.g. Main Office 3rd Floor">
        </div>
        <div style="display:flex;gap:10px;">
            <button onclick="saveSsid()" class="btn btn-accent" style="flex:1;">Save SSID</button>
            <button onclick="document.getElementById('addSsidModal').style.display='none'" class="btn btn-outline" style="flex:1;">Cancel</button>
        </div>
    </div>
</div>

<script>
const apiBase = '<?php echo BASE_URL; ?>/api/wifi.php';

async function loadSsids() {
    try {
        const r = await fetch(apiBase + '?action=allowed-ssids');
        const d = await r.json();
        const tbody = document.querySelector('#ssidTable tbody');
        if(!d.ssids || !d.ssids.length) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:30px;color:var(--text-muted);">No SSIDs configured.</td></tr>';
            return;
        }
        tbody.innerHTML = d.ssids.map(s => `
            <tr>
                <td style="font-weight:600;color:var(--accent);">${s.ssid_name}</td>
                <td style="font-size:13px;color:var(--text-muted);">${s.location_label || '—'}</td>
                <td style="text-align:right;">
                    <button onclick="deleteSsid(${s.id})" class="btn btn-sm btn-outline" style="color:#ef5350;border-color:#ef5350;">Remove</button>
                </td>
            </tr>
        `).join('');
    } catch(e) { console.error(e); }
}

async function loadLogs() {
    try {
        const r = await fetch(apiBase + '?action=failed-logs');
        const d = await r.json();
        const tbody = document.querySelector('#failedLogTable tbody');
        if(!d.rows || !d.rows.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted);">No failed attempts logged.</td></tr>';
            return;
        }
        tbody.innerHTML = d.rows.map(row => `
            <tr>
                <td style="font-size:12px;color:var(--text-muted);">${row.timestamp}</td>
                <td><div style="font-weight:600;">${row.employee_name}</div><div style="font-size:11px;color:var(--text-muted);">${row.employee_no || ''}</div></td>
                <td><span style="background:rgba(255,255,255,0.06);padding:2px 6px;border-radius:4px;font-family:monospace;font-size:12px;">${row.detected_ssid || 'No Wi-Fi'}</span></td>
                <td><span class="badge ${row.status === 'success' ? 'badge-success' : 'badge-danger'}">${row.status}</span></td>
                <td style="font-size:12px;">${row.fail_reason || '—'}</td>
            </tr>
        `).join('');
    } catch(e) { console.error(e); }
}

async function saveSsid() {
    const name = document.getElementById('newSsidName').value.trim();
    const loc  = document.getElementById('newSsidLocation').value.trim();
    if(!name) return alert('SSID name is required.');
    
    const fd = new FormData();
    fd.append('ssid_name', name);
    fd.append('location_label', loc);
    try {
        const r = await fetch(apiBase + '?action=add-ssid', { method:'POST', body:fd });
        const d = await r.json();
        if(d.ok) {
            document.getElementById('newSsidName').value = '';
            document.getElementById('newSsidLocation').value = '';
            document.getElementById('addSsidModal').style.display = 'none';
            loadSsids();
        } else { alert(d.error || 'Error saving SSID'); }
    } catch(e) { alert('Network error'); }
}

async function deleteSsid(id) {
    if(!confirm('Remove this SSID?')) return;
    const fd = new FormData();
    fd.append('id', id);
    try {
        const r = await fetch(apiBase + '?action=delete-ssid', { method:'POST', body:fd });
        const d = await r.json();
        if(d.ok) loadSsids();
        else alert(d.error || 'Error removing SSID');
    } catch(e) { alert('Network error'); }
}

// Load data initially
loadSsids();
loadLogs();
</script>

<?php elseif($tab==='display'): ?>
<div class="hrms-card">
    <div class="card-header"><h3>Display &amp; Accessibility</h3><p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Personalise how Staffora looks. Changes apply instantly and are saved in your browser.</p></div>
    <div class="card-body" style="padding:0;">

        <!-- Dark Mode -->
        <div style="padding:24px 28px;border-bottom:1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:18px;margin-bottom:20px;">
                <div style="width:48px;height:48px;border-radius:50%;background:rgba(124,147,104,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </div>
                <div>
                    <div style="font-size:16px;font-weight:700;margin-bottom:4px;">Dark mode</div>
                    <div style="font-size:13px;color:var(--text-muted);line-height:1.5;">Adjust the appearance to reduce glare and give your eyes a break.</div>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:2px;">
                <?php foreach(['off'=>'Off','on'=>'On','auto'=>'Automatic'] as $val=>$lbl): ?>
                <label id="dark-mode-<?php echo $val; ?>" onclick="setDarkMode('<?php echo $val; ?>')" style="display:flex;align-items:center;justify-content:space-between;padding:14px 4px;border-bottom:1px solid var(--border);cursor:pointer;border-radius:6px;">
                    <span style="display:flex;flex-direction:column;">
                        <span style="font-size:15px;font-weight:500;"><?php echo $lbl; ?></span>
                        <?php if($val==='auto'): ?><span style="font-size:12px;color:var(--text-muted);margin-top:2px;">Automatically adjust based on your device settings.</span><?php endif; ?>
                    </span>
                    <span class="dm-radio dm-radio-<?php echo $val; ?>" style="width:22px;height:22px;border-radius:50%;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:border-color .2s;">
                        <span class="dm-dot dm-dot-<?php echo $val; ?>" style="width:12px;height:12px;border-radius:50%;background:var(--accent);display:none;"></span>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Font Size / Reading Size -->
        <div style="padding:24px 28px;">
            <div style="display:flex;align-items:center;gap:18px;margin-bottom:20px;">
                <div style="width:48px;height:48px;border-radius:50%;background:rgba(91,138,114,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="26" height="22" viewBox="0 0 26 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <text x="0" y="18" font-size="18" fill="var(--accent)" font-family="sans-serif" font-weight="800">A</text>
                        <text x="15" y="16" font-size="11" fill="var(--accent)" font-family="sans-serif" font-weight="600">A</text>
                    </svg>
                </div>
                <div>
                    <div style="font-size:16px;font-weight:700;margin-bottom:4px;">Font Size</div>
                    <div style="font-size:13px;color:var(--text-muted);line-height:1.5;">Adjust reading size across the whole app. Great for low-vision users.</div>
                </div>
            </div>

            <!-- Live preview -->
            <div id="fontPreview" style="padding:14px 18px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:10px;margin-bottom:22px;line-height:1.7;color:var(--text);">
                The quick brown fox jumps over the lazy dog. <span style="color:var(--text-muted);">Aa Bb Cc 1 2 3</span>
            </div>

            <!-- Size label + badge -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <label style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.8px;">Size</label>
                <span id="fontSizeLabel" style="font-size:13px;font-weight:700;color:var(--accent);background:rgba(124,147,104,0.14);padding:3px 12px;border-radius:20px;"></span>
            </div>

            <!-- Preset buttons — each IS the control, no broken slider labels -->
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:20px;">
                <?php foreach(['Small'=>12,'Default'=>14,'Large'=>16,'X-Large'=>18] as $lbl=>$sz): ?>
                <button type="button" id="fsPreset-<?php echo $sz; ?>"
                    onclick="selectFontSize(<?php echo $sz; ?>)"
                    style="padding:12px 0;border-radius:10px;border:2px solid var(--border);background:rgba(255,255,255,0.04);color:var(--text);font-weight:600;cursor:pointer;transition:all .18s;line-height:1.3;">
                    <div style="font-size:<?php echo $sz; ?>px;margin-bottom:3px;font-weight:700;"><?php echo $lbl; ?></div>
                    <div style="font-size:10px;opacity:.6;"><?php echo $sz; ?>px</div>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Fine slider (12-20) -->
            <div style="margin-bottom:22px;">
                <input type="range" id="fontSizeSlider" min="12" max="20" step="1"
                    style="width:100%;accent-color:var(--accent);cursor:pointer;"
                    oninput="selectFontSize(parseInt(this.value))">
                <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--text-muted);margin-top:4px;padding:0 2px;">
                    <span>12px</span><span>14px</span><span>16px</span><span>18px</span><span>20px</span>
                </div>
            </div>

            <!-- Save button -->
            <div style="display:flex;justify-content:flex-end;">
                <button type="button" onclick="saveFontSize()" class="btn btn-accent" style="display:flex;align-items:center;gap:8px;padding:10px 22px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Save Font Size
                </button>
            </div>
        </div>
    </div>
</div>
<script>
(function(){
    var SIZES  = [12, 14, 16, 18];
    var _current = parseInt(localStorage.getItem('hrms_font_size') || '14', 10);

    function injectFontStyle(size) {
        var el = document.getElementById('hrmsFontStyle');
        if (!el) { el = document.createElement('style'); el.id = 'hrmsFontStyle'; document.head.appendChild(el); }
        var zoom = (size / 14).toFixed(4);
        el.textContent = 'body.hrms-body { zoom: ' + zoom + '; }';
    }

    function selectFontSize(size) {
        size = Math.min(20, Math.max(12, parseInt(size, 10)));
        _current = size;
        // Live preview: zoom the preview box to show effect
        var prev = document.getElementById('fontPreview');
        if (prev) prev.style.fontSize = size + 'px';
        // Also apply zoom live to the whole page so user sees it immediately
        var el = document.getElementById('hrmsFontStyle');
        if (!el) { el = document.createElement('style'); el.id = 'hrmsFontStyle'; document.head.appendChild(el); }
        el.textContent = 'body.hrms-body { zoom: ' + (size/14).toFixed(4) + '; }';
        var lbl = document.getElementById('fontSizeLabel');
        if (lbl) lbl.textContent = size + 'px';
        var slider = document.getElementById('fontSizeSlider');
        if (slider) slider.value = size;
        // highlight preset buttons
        SIZES.forEach(function(p){
            var btn = document.getElementById('fsPreset-' + p);
            if (!btn) return;
            var on = p === size;
            btn.style.background   = on ? 'var(--accent)' : 'rgba(255,255,255,0.04)';
            btn.style.color        = on ? '#fff' : 'var(--text)';
            btn.style.borderColor  = on ? 'var(--accent)' : 'var(--border)';
        });
    }

    function saveFontSize() {
        localStorage.setItem('hrms_font_size', _current);
        injectFontStyle(_current);
        // show a quick toast
        var t = document.createElement('div');
        t.style.cssText = 'position:fixed;bottom:28px;right:28px;background:var(--accent);color:#fff;padding:12px 22px;border-radius:10px;font-size:13px;font-weight:600;z-index:99999;box-shadow:0 8px 24px rgba(0,0,0,0.3);transition:opacity .4s;';
        t.textContent = '✓ Font size saved (' + _current + 'px)';
        document.body.appendChild(t);
        setTimeout(function(){ t.style.opacity='0'; setTimeout(function(){ t.remove(); }, 400); }, 2000);
    }

    window.selectFontSize = selectFontSize;
    window.saveFontSize   = saveFontSize;

    // init page
    selectFontSize(_current);
    injectFontStyle(_current); // apply saved size on settings page itself

    // Dark mode
    window.setDarkMode = function(val) {
        localStorage.setItem('hrms_dark_mode', val);
        if (window.hrmsApplyDarkMode) hrmsApplyDarkMode(val);
        updateDM(val);
    };
    function updateDM(val) {
        ['off','on','auto'].forEach(function(v) {
            var d = document.querySelector('.dm-dot-' + v);
            var r = document.querySelector('.dm-radio-' + v);
            if (!d || !r) return;
            var a = v === val;
            d.style.display     = a ? 'block' : 'none';
            r.style.borderColor = a ? 'var(--accent)' : 'var(--border)';
        });
    }
    updateDM(localStorage.getItem('hrms_dark_mode') || 'on');
})();
</script>

<?php endif; ?>
</div><!-- /content panel -->
</div><!-- /flex row -->
<?php require_once __DIR__."/../includes/layout_footer.php"; ?>
