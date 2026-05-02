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
    'schedule'   => ['label'=>'Pay Schedule',         'icon'=>'<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>'],
    'leave'      => ['label'=>'Leave Policy',         'icon'=>'<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>'],
    'payroll'    => ['label'=>'Payroll Rates',        'icon'=>'<line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>'],
    'attendance' => ['label'=>'Attendance',           'icon'=>'<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>'],
    'display'    => ['label'=>'Display',              'icon'=>'<circle cx="12" cy="12" r="3"></circle><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>'],
];

// Restrict non-admins: only profile + display tabs
$allowedTabs = $isAdmin ? array_keys($settingsTabs) : ['profile','display'];
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
    <div class="card-header"><h3>Company Information</h3><p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Basic details displayed on payslips and reports</p></div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <?php foreach(['company_name'=>['Company Name *','text','Staffora HRMS'],'company_address'=>['Company Address','text',''],'company_email'=>['Company Email','email',''],'company_phone'=>['Company Phone','text',''],'company_tin'=>['Tax ID (TIN)','text','']] as $k=>[$lbl,$type,$def]): ?>
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;"><?php echo $lbl; ?></label>
            <input type="<?php echo $type; ?>" name="settings[<?php echo $k; ?>]" class="hrms-input" style="width:100%;box-sizing:border-box;" value="<?php echo e(sv($sys,$k,$def)); ?>">
        </div>
        <?php endforeach; ?>
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;">Payroll Currency</label>
            <select name="settings[currency]" class="hrms-input" style="width:100%;">
                <option value="PHP" <?php if(sv($sys,'currency','PHP')==='PHP') echo 'selected'; ?>>PHP — Philippine Peso (₱)</option>
                <option value="USD" <?php if(sv($sys,'currency','PHP')==='USD') echo 'selected'; ?>>USD — US Dollar ($)</option>
            </select>
        </div>
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
<div class="hrms-card" style="margin-bottom:20px;">
    <div class="card-header"><h3>Pay Frequency</h3><p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Configure how often employees are paid</p></div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;">Pay Frequency</label>
            <select name="settings[pay_frequency]" class="hrms-input" style="width:100%;">
                <?php $pf=sv($sys,'pay_frequency','SEMI_MONTHLY'); foreach(['SEMI_MONTHLY'=>'Semi-Monthly (Most common)','MONTHLY'=>'Monthly','WEEKLY'=>'Weekly','DAILY'=>'Daily'] as $v=>$l): ?>
                <option value="<?php echo $v; ?>" <?php if($pf===$v) echo 'selected'; ?>><?php echo $l; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;">First Cutoff Day</label>
            <input type="number" name="settings[first_cutoff]" class="hrms-input" style="width:100%;box-sizing:border-box;" min="1" max="31" value="<?php echo e(sv($sys,'first_cutoff','15')); ?>">
            <p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">Day of month for 1st cutoff</p>
        </div>
        <div>
            <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;">Second Cutoff Day</label>
            <input type="number" name="settings[second_cutoff]" class="hrms-input" style="width:100%;box-sizing:border-box;" min="1" max="31" value="<?php echo e(sv($sys,'second_cutoff','30')); ?>">
            <p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">Day of month for 2nd cutoff</p>
        </div>
    </div>
</div>
<div class="hrms-card" style="margin-bottom:20px;">
    <div class="card-header"><h3>Work Hour Defaults</h3><p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Company-wide defaults (can be overridden per schedule)</p></div>
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

<?php elseif($tab==='payroll' && $isAdmin): ?>
<form method="POST"><input type="hidden" name="_action" value="saveSystem"><input type="hidden" name="tab" value="payroll">
<div class="hrms-card">
    <div class="card-header"><h3>Payroll Computation Rates</h3><p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;">Overtime multipliers and night differential rates</p></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:24px;">
            <?php foreach([
                'overtime_rate_regular' =>['OT Rate — Regular Day','1.25','× rate'],
                'overtime_rate_restday' =>['OT Rate — Rest Day',   '1.30','× rate'],
                'overtime_rate_holiday' =>['OT Rate — Holiday',    '2.00','× rate'],
                'night_diff_rate'       =>['Night Differential',   '0.10','× rate (+10%)'],
                'working_days_month'    =>['Working Days / Month',  '22',  'days'],
                'working_hours_day'     =>['Working Hours / Day',    '8',  'hours'],
            ] as $k=>[$lbl,$def,$unit]): ?>
            <div>
                <label style="font-size:13px;color:var(--text-muted);display:block;margin-bottom:6px;font-weight:500;"><?php echo $lbl; ?></label>
                <div style="display:flex;align-items:center;gap:8px;">
                    <input type="text" name="settings[<?php echo $k; ?>]" class="hrms-input" style="width:90px;" value="<?php echo e(sv($sys,$k,$def)); ?>">
                    <span style="font-size:12px;color:var(--text-muted);"><?php echo $unit; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="background:rgba(77,166,255,0.08);border:1px solid rgba(77,166,255,0.2);border-radius:8px;padding:16px;">
            <p style="margin:0;font-size:13px;color:#4da6ff;font-weight:500;">&#9432; Government Contributions (SSS, PhilHealth, Pag-IBIG) are computed automatically using the official contribution tables per DOLE regulations.</p>
        </div>
    </div>
</div>
<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:12px;">
    <a href="<?php echo url('settings',['tab'=>'payroll']); ?>" class="btn btn-outline">Cancel</a>
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

        <!-- Compact Mode -->
        <div style="padding:24px 28px;">
            <div style="display:flex;align-items:center;gap:18px;margin-bottom:20px;">
                <div style="width:48px;height:48px;border-radius:50%;background:rgba(91,138,114,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                </div>
                <div>
                    <div style="font-size:16px;font-weight:700;margin-bottom:4px;">Compact mode</div>
                    <div style="font-size:13px;color:var(--text-muted);line-height:1.5;">Make font size smaller so more content fits on screen.</div>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:2px;">
                <?php foreach(['off'=>'Off','on'=>'On'] as $val=>$lbl): ?>
                <label id="compact-mode-<?php echo $val; ?>" onclick="setCompactMode('<?php echo $val; ?>')" style="display:flex;align-items:center;justify-content:space-between;padding:14px 4px;border-bottom:1px solid var(--border);cursor:pointer;border-radius:6px;">
                    <span style="font-size:15px;font-weight:500;"><?php echo $lbl; ?></span>
                    <span class="cm-radio cm-radio-<?php echo $val; ?>" style="width:22px;height:22px;border-radius:50%;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:border-color .2s;">
                        <span class="cm-dot cm-dot-<?php echo $val; ?>" style="width:12px;height:12px;border-radius:50%;background:var(--accent);display:none;"></span>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script>
(function(){
    window.setDarkMode=function(val){localStorage.setItem('hrms_dark_mode',val);if(window.hrmsApplyDarkMode)hrmsApplyDarkMode(val);updateDM(val);};
    window.setCompactMode=function(val){localStorage.setItem('hrms_compact_mode',val);if(window.hrmsApplyCompactMode)hrmsApplyCompactMode(val);updateCM(val);};
    function updateDM(val){['off','on','auto'].forEach(function(v){var d=document.querySelector('.dm-dot-'+v),r=document.querySelector('.dm-radio-'+v);if(!d||!r)return;var a=v===val;d.style.display=a?'block':'none';r.style.borderColor=a?'var(--accent)':'var(--border)';});}
    function updateCM(val){['off','on'].forEach(function(v){var d=document.querySelector('.cm-dot-'+v),r=document.querySelector('.cm-radio-'+v);if(!d||!r)return;var a=v===val;d.style.display=a?'block':'none';r.style.borderColor=a?'var(--accent)':'var(--border)';});}
    updateDM(localStorage.getItem('hrms_dark_mode')||'on');
    updateCM(localStorage.getItem('hrms_compact_mode')||'off');
})();
</script>

<?php endif; ?>
</div><!-- /content panel -->
</div><!-- /flex row -->
<?php require_once __DIR__."/../includes/layout_footer.php"; ?>
