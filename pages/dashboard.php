<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/leave_policy.php';
Auth::check();
$pageTitle = 'Dashboard — ' . APP_NAME;
$role = Auth::role();
$empId = Auth::empId();
$deptId = Auth::deptId();
$userId = Auth::id();
if ($role === 'Manager' && !$deptId) {
    $deptId = (int)DB::fetchScalar("SELECT department_id FROM employee WHERE user_id=? AND department_id IS NOT NULL LIMIT 1", [$userId]);
}

if ($role === 'Super Admin') {
    $totalEmp = (int)DB::fetchScalar("SELECT COUNT(*) FROM employee WHERE status IN ('Regular','Probationary')");
    $presentToday = (int)DB::fetchScalar("SELECT COUNT(*) FROM attendance WHERE date=CURDATE() AND status IN ('On Time','Late')");
    $onLeave = (int)DB::fetchScalar("SELECT COUNT(*) FROM leave_request WHERE status='Approved' AND start_date<=CURDATE() AND end_date>=CURDATE()");
    $depts = DB::fetchAll("SELECT d.name, COUNT(e.id) as cnt FROM department d LEFT JOIN employee e ON e.department_id=d.id AND e.status IN ('Regular','Probationary') GROUP BY d.id, d.name");
    $deptCount = count($depts);
    $announcements = DB::fetchAll("SELECT * FROM announcement ORDER BY pinned DESC, created_at DESC LIMIT 3");
    $leaveStats = DB::fetchAll("SELECT status, COUNT(*) as cnt FROM leave_request GROUP BY status");
    $leaveMap = [];
    foreach($leaveStats as $l) $leaveMap[$l['status']] = $l['cnt'];
    $pendingLeaves = (int)DB::fetchScalar("SELECT COUNT(*) FROM leave_request WHERE status='Pending'");
    $recentActivity = DB::fetchAll("SELECT al.*, u.username FROM audit_log al LEFT JOIN `user` u ON al.user_id=u.id ORDER BY al.created_at DESC LIMIT 8");
} elseif ($role === 'Manager') {
    $dept = DB::fetchOne("SELECT * FROM department WHERE id=?", [$deptId]);
    $deptName = $dept['name'] ?? '';
    $teamCount = (int)DB::fetchScalar("SELECT COUNT(*) FROM employee WHERE department_id=? AND status IN ('Regular','Probationary')", [$deptId]);
    $teamPresent = (int)DB::fetchScalar("SELECT COUNT(*) FROM attendance a JOIN employee e ON a.employee_id=e.id WHERE e.department_id=? AND a.date=CURDATE() AND a.status IN ('On Time','Late')", [$deptId]);
    $teamOnLeave = (int)DB::fetchScalar("SELECT COUNT(*) FROM leave_request lr JOIN employee e ON lr.employee_id=e.id WHERE e.department_id=? AND lr.status='Approved' AND lr.start_date<=CURDATE() AND lr.end_date>=CURDATE()", [$deptId]);
    $pendingLeaves = (int)DB::fetchScalar("SELECT COUNT(*) FROM leave_request lr JOIN employee e ON lr.employee_id=e.id WHERE e.department_id=? AND lr.status='Pending'", [$deptId]);
    $teamAttendance = DB::fetchAll("SELECT e.first_name, e.last_name, a.clock_in, a.clock_out, a.status FROM attendance a JOIN employee e ON a.employee_id=e.id WHERE e.department_id=? AND a.date=CURDATE()", [$deptId]);
    $upcomingShifts = DB::fetchAll("SELECT s.*, e.first_name, e.last_name FROM shift s JOIN employee e ON s.employee_id=e.id WHERE e.department_id=? AND s.date>=CURDATE() ORDER BY s.date, s.start_time LIMIT 5", [$deptId]);
    $announcements = DB::fetchAll("SELECT * FROM announcement WHERE target_audience='All' OR department_id=? ORDER BY pinned DESC, created_at DESC LIMIT 3", [$deptId]);
} else {
    $emp = DB::fetchOne("SELECT e.*, d.name as dept_name, p.title as position_title FROM employee e LEFT JOIN department d ON e.department_id=d.id LEFT JOIN position p ON e.position_id=p.id WHERE e.id=?", [$empId]);
    $empName  = $emp ? ($emp['first_name'].' '.$emp['last_name']) : Auth::name();
    $empTitle = $emp['position_title'] ?? $emp['job_title'] ?? '';
    $todayAtt = DB::fetchOne("SELECT * FROM attendance WHERE employee_id=? AND date=CURDATE() ORDER BY id DESC", [$empId]);
    $isClockedIn = $todayAtt && empty($todayAtt['clock_out']);
    $isClockedOut = $todayAtt && !empty($todayAtt['clock_out']);
    $hoursThisMonth = (int)DB::fetchScalar("SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE employee_id=? AND DATE_FORMAT(date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')", [$empId]);
    $vacEntitlement = 15;
    // Use real SIL policy engine
    LeavePolicy::syncAccrued($empId, $emp);
    $leaveRemaining = LeavePolicy::silRemaining($empId, $emp);
    // payslip query removed — payroll module dropped
    $attTotal = (int)DB::fetchScalar("SELECT COUNT(*) FROM attendance WHERE employee_id=? AND date>=?", [$empId, date('Y-01-01')]);
    $attOnTime = (int)DB::fetchScalar("SELECT COUNT(*) FROM attendance WHERE employee_id=? AND status='On Time' AND date>=?", [$empId, date('Y-01-01')]);
    $attRate = $attTotal > 0 ? round($attOnTime/$attTotal*100) : 0;
    $empDeptId = $emp['department_id'] ?? 0;
    $announcements = DB::fetchAll("SELECT * FROM announcement WHERE target_audience='All' OR department_id=? ORDER BY pinned DESC, created_at DESC LIMIT 3", [$empDeptId]);
    // payslips query removed — payroll module dropped
    $recentLeaves = DB::fetchAll("SELECT * FROM leave_request WHERE employee_id=? ORDER BY created_at DESC LIMIT 3", [$empId]);
    $upcomingShifts = DB::fetchAll("SELECT * FROM shift WHERE employee_id=? AND date>=CURDATE() ORDER BY date, start_time LIMIT 5", [$empId]);
    $todayShift = DB::fetchOne("SELECT * FROM shift WHERE employee_id=? AND date=CURDATE()", [$empId]);
    $workRule = $emp['work_rule_id'] ? DB::fetchOne("SELECT * FROM work_rule WHERE id=?", [$emp['work_rule_id']]) : null;
    $pendingDisputes = (int)DB::fetchScalar("SELECT COUNT(*) FROM timelog_dispute WHERE employee_id=? AND status='Pending'", [$empId]);


   // =========================
// CLOCK IN
// =========================
if (isset($_GET['action']) && $_GET['action'] === 'clockIn') {

    // [TEMPORARILY REMOVED FOR TESTING]
    // if ($isClockedIn) {
    //     Auth::flash('error', 'You are already clocked in.');
    //     header('Location: ' . url('dashboard'));
    //     exit;
    // }

    //Get user IP
    $ip = $_SERVER['REMOTE_ADDR'];
    $allowedNetwork = '192.168.1.';

    // Allow localhost for XAMPP testing
    if (
        $ip !== '::1' &&
        $ip !== '127.0.0.1' &&
        strpos($ip, $allowedNetwork) !== 0
    ) {
        Auth::flash('error', 'Clock-in allowed only inside company WiFi.');
        header('Location: ' . url('dashboard'));
        exit;
    }

    // Get employee shift for today
    $shift = DB::fetchOne(
        "SELECT * FROM shift WHERE employee_id = ? AND date = CURDATE()",
        [$empId]
    );

    $currentTime = date('H:i:s');
    $status = 'On Time';

    // Late check
    if ($shift && !empty($shift['start_time'])) {
        if ($currentTime > $shift['start_time']) {
            $status = 'Late';
        }
    }

    // Check if already has attendance today
    $existing = DB::fetchOne(
        "SELECT id FROM attendance 
         WHERE employee_id = ? 
         AND date = CURDATE()",
        [$empId]
    );

    // [TEMPORARILY REMOVED FOR TESTING]
    // if ($existing) {
    //     Auth::flash('error', 'You already clocked in today.');
    //     header('Location: ' . url('dashboard'));
    //     exit;
    // }

    // INSERT attendance record ← THIS WAS MISSING
    DB::insert(
        "INSERT INTO attendance (
            employee_id,
            date,
            clock_in,
            status,
            ip_address
        ) VALUES (?, CURDATE(), NOW(), ?, ?)",
        [
            $empId,
            $status,
            $ip
        ]
    );

    Auth::flash('success', 'Clocked in successfully!');
    header('Location: ' . url('dashboard'));
    exit;
}
    // =========================
    // CLOCK OUT
    // =========================
    if (isset($_GET['action']) && $_GET['action'] === 'clockOut') {

        if (!$isClockedIn) {
            Auth::flash('error', 'You need to clock in first.');
            header('Location: ' . url('dashboard'));
            exit;
        }

        // Get shift for today
        $shift = DB::fetchOne(
            "SELECT * FROM shift WHERE employee_id = ? AND date = CURDATE()",
            [$empId]
        );

        $status = $todayAtt['status'];
        $currentTime = date('H:i:s');

        // Undertime / Overtime check
        if ($shift && !empty($shift['end_time'])) {
            if ($currentTime < $shift['end_time']) {
                $status = 'Undertime';
            } elseif ($currentTime > $shift['end_time']) {
                // If they clock out past their end time, mark as Overtime
                $status .= ' (Overtime)';
            }
        }

        DB::execute(
            "UPDATE attendance
             SET
                clock_out = NOW(),
                total_hours = ROUND(
                    TIMESTAMPDIFF(MINUTE, clock_in, NOW()) / 60,
                    2
                ),
                status = ?
             WHERE id = ?",
            [
                $status,
                $todayAtt['id']
            ]
        );

        Auth::flash('success', 'Clocked out successfully!');
        header('Location: ' . url('dashboard'));
        exit;
    }
}

require_once __DIR__ . '/../includes/layout_header.php';
?>
<?php if ($role === 'Super Admin'): ?>
<div style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;">
    <div><h1 style="font-size:28px;font-weight:700;margin:0;">CEO Dashboard</h1><p style="color:var(--text-muted);margin:4px 0 0;">Strategic overview of Staffora HRMS</p></div>
    <div style="font-size:13px;color:var(--text-muted);"><?php echo date('l, F j, Y'); ?></div>
</div>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:24px;">
    <div class="hrms-card" style="border-left:4px solid #1e88e5;padding:20px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Total Workforce</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $totalEmp; ?></p><p style="font-size:11px;color:#66bb6a;margin:4px 0 0;">Active employees</p></div><div style="width:48px;height:48px;background:rgba(30,136,229,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1e88e5" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path></svg></div></div></div>
    <div class="hrms-card" style="border-left:4px solid #43a047;padding:20px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Present Today</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $presentToday; ?></p><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">On-site now</p></div><div style="width:48px;height:48px;background:rgba(67,160,71,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#43a047" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg></div></div></div>
    <div class="hrms-card" style="border-left:4px solid #8e24aa;padding:20px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Active Departments</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $deptCount; ?></p><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">Across organization</p></div><div style="width:48px;height:48px;background:rgba(142,36,170,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#8e24aa" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div></div></div>
    <div class="hrms-card" style="border-left:4px solid #ef5350;padding:20px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Pending Leaves</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $pendingLeaves; ?></p><p style="font-size:11px;color:#ef5350;margin:4px 0 0;">Awaiting approval</p></div><div style="width:48px;height:48px;background:rgba(239,83,80,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef5350" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg></div></div></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
    <div class="hrms-card"><div class="card-header"><h3>Recent Announcements</h3><a href="<?php echo url('announcement'); ?>" class="btn btn-sm btn-outline">View All</a></div><div class="card-body" style="padding:12px 16px;">
    <?php if(empty($announcements)): ?><p style="color:var(--text-muted);text-align:center;padding:20px;font-size:13px;">No announcements yet.</p><?php endif; ?>
    <?php foreach($announcements as $ann): ?><div style="padding:12px;background:rgba(255,255,255,0.03);border-radius:8px;border:1px solid var(--border);margin-bottom:10px;"><div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;"><h4 style="margin:0;font-size:13px;font-weight:600;"><?php echo e($ann['title']); ?></h4><span class="badge <?php echo in_array($ann['priority'],['High','Urgent'])?'badge-warning':'badge-muted'; ?>" style="font-size:10px;"><?php echo e($ann['priority']); ?></span></div><div style="font-size:11px;color:var(--text-muted);"><?php echo date('M j, Y', $ann['created_at']); ?></div></div><?php endforeach; ?>
    </div></div>
    <div class="hrms-card"><div class="card-header"><h3>Leave Requests</h3><a href="<?php echo url('leave'); ?>" class="btn btn-sm btn-outline">Manage</a></div><div class="card-body">
    <?php $leaveData=[['name'=>'Approved','cnt'=>$leaveMap['Approved']??0,'color'=>'#7C9368'],['name'=>'Pending','cnt'=>$leaveMap['Pending']??0,'color'=>'#E07A45'],['name'=>'Denied','cnt'=>$leaveMap['Denied']??0,'color'=>'#d4183d']];$total=array_sum(array_column($leaveData,'cnt'))?:1; ?>
    <?php foreach($leaveData as $item): ?><div style="margin-bottom:12px;"><div style="display:flex;justify-content:space-between;margin-bottom:4px;"><div style="display:flex;align-items:center;gap:8px;font-size:13px;"><span style="width:10px;height:10px;border-radius:50%;background:<?php echo $item['color']; ?>;display:inline-block;"></span><?php echo $item['name']; ?></div><strong><?php echo $item['cnt']; ?></strong></div><div style="height:6px;background:rgba(255,255,255,0.08);border-radius:3px;"><div style="height:100%;width:<?php echo round($item['cnt']/$total*100); ?>%;background:<?php echo $item['color']; ?>;border-radius:3px;"></div></div></div><?php endforeach; ?>
    </div></div>
</div>
<div class="hrms-card" style="margin-bottom:24px;"><div class="card-header"><h3>Department Overview</h3><a href="<?php echo url('department'); ?>" class="btn btn-sm btn-outline">View All</a></div><div class="card-body" style="padding:8px 16px;">
<?php foreach($depts as $dept): ?><div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);"><div style="flex:1;"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;"><span style="font-weight:600;font-size:13px;"><?php echo e($dept['name']); ?></span></div><div style="font-size:12px;color:var(--text-muted);">&#128101; <?php echo $dept['cnt']; ?> Employees</div></div></div><?php endforeach; ?>
</div></div>
<div class="hrms-card"><div class="card-header"><h3>Quick Actions</h3></div><div class="card-body"><div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
<a href="<?php echo url('user_management'); ?>" class="btn btn-outline" style="height:80px;flex-direction:column;gap:8px;justify-content:center;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1e88e5" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg><span style="font-size:13px;">User Management</span></a>
<a href="<?php echo url('department'); ?>" class="btn btn-outline" style="height:80px;flex-direction:column;gap:8px;justify-content:center;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#43a047" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg><span style="font-size:13px;">Departments</span></a>
<a href="<?php echo url('report'); ?>" class="btn btn-outline" style="height:80px;flex-direction:column;gap:8px;justify-content:center;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#8e24aa" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg><span style="font-size:13px;">Analytics</span></a>
<a href="<?php echo url('settings'); ?>" class="btn btn-outline" style="height:80px;flex-direction:column;gap:8px;justify-content:center;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fb8c00" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg><span style="font-size:13px;">System Settings</span></a>
</div></div></div>
<?php elseif ($role === 'Manager'): ?>
<div style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;"><div><h1 style="font-size:28px;font-weight:700;margin:0;">Manager Dashboard</h1><p style="color:var(--text-muted);margin:4px 0 0;"><?php echo e($deptName); ?> Department Overview</p></div><span style="padding:6px 14px;background:rgba(30,136,229,0.12);color:#4da6ff;border-radius:20px;font-size:13px;font-weight:600;"><?php echo e($deptName); ?> Dept</span></div>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:24px;">
<div class="hrms-card" style="border-left:4px solid #1e88e5;padding:20px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Team Size</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $teamCount; ?></p><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">Active employees</p></div></div></div>
<div class="hrms-card" style="border-left:4px solid #43a047;padding:20px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Present Today</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $teamPresent; ?></p><p style="font-size:11px;color:#66bb6a;margin:4px 0 0;"><?php echo $teamCount>0?round($teamPresent/$teamCount*100):0; ?>% rate</p></div></div></div>
<div class="hrms-card" style="border-left:4px solid #fb8c00;padding:20px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">On Leave Today</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $teamOnLeave; ?></p><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">Approved leaves</p></div></div></div>
<div class="hrms-card" style="border-left:4px solid #ef5350;padding:20px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Pending Leaves</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $pendingLeaves; ?></p><p style="font-size:11px;color:#ef5350;margin:4px 0 0;">Awaiting approval</p></div></div></div>
</div>
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">
<div class="hrms-card"><div class="card-header"><h3>Today Team Attendance</h3><a href="<?php echo url('attendance'); ?>" class="btn btn-sm btn-outline">View Full</a></div><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;"><thead><tr><th>Employee</th><th>Clock In</th><th>Clock Out</th><th>Status</th></tr></thead><tbody>
<?php foreach($teamAttendance as $att): ?><tr><td style="font-weight:600;"><?php echo e($att['first_name'].' '.$att['last_name']); ?></td><td><?php echo $att['clock_in']?date('h:i A',strtotime($att['clock_in'])):'—'; ?></td><td><?php echo $att['clock_out']?date('h:i A',strtotime($att['clock_out'])):'—'; ?></td><td><span class="badge <?php echo strpos($att['status'],'On Time')!==false?'badge-success':(strpos($att['status'],'Late')!==false?'badge-warning':'badge-danger'); ?>"><?php echo e($att['status']); ?></span></td></tr><?php endforeach; ?>
<?php if(empty($teamAttendance)): ?><tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:20px;">No records today.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="hrms-card"><div class="card-header"><h3>Announcements</h3></div><div class="card-body" style="padding:12px 16px;">
<?php foreach($announcements as $ann): ?><div style="padding:12px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:8px;margin-bottom:8px;"><span style="font-size:13px;font-weight:600;"><?php echo e($ann['title']); ?></span><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;"><?php echo date('M j',$ann['created_at']); ?></p></div><?php endforeach; ?>
<?php if(empty($announcements)): ?><p style="text-align:center;color:var(--text-muted);font-size:13px;">No announcements.</p><?php endif; ?>
</div></div></div>
<?php else: // Employee ?>
<div style="margin-bottom:24px;"><h1 style="font-size:28px;font-weight:700;margin:0;">My Dashboard</h1><p style="color:var(--text-muted);margin:4px 0 0;">Welcome back, <?php echo e($empName); ?><?php if($empTitle): ?> &mdash; <?php echo e($empTitle); ?><?php endif; ?></p></div>
<div class="hrms-card" style="border:2px solid <?php echo $isClockedIn?'#43a047':'#1e88e5'; ?>;background:<?php echo $isClockedIn?'rgba(67,160,71,0.05)':'rgba(30,136,229,0.05)'; ?>;margin-bottom:24px;">
<div class="card-body"><div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
<div style="display:flex;align-items:center;gap:16px;"><div style="width:56px;height:56px;border-radius:50%;background:<?php echo $isClockedIn?'#43a047':'#1e88e5'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
<div><h3 style="font-size:18px;font-weight:700;margin:0;"><?php echo $isClockedIn?"You're Clocked In":"Ready to Start Your Day?"; ?></h3><p style="margin:4px 0 0;font-size:13px;color:var(--text-muted);"><strong>Today:</strong> <?php echo date('l, F j, Y'); ?> &nbsp;|&nbsp; <strong>Clock In:</strong> <?php echo $todayAtt&&$todayAtt['clock_in']?date('h:i A',strtotime($todayAtt['clock_in'])):'Not yet'; ?></p></div></div>
<div>
<div><?php if(!$isClockedIn): ?>
    <a href="<?php echo url('dashboard',['action'=>'clockIn']); ?>" 
       class="btn" 
       style="background:#43a047;color:#fff;padding:12px 24px;font-size:15px;">
       Clock In
    </a>

<?php else: ?>
    <button 
        onclick="openClockOutModal()" 
        class="btn"
        style="background:#d4183d;color:#fff;padding:12px 24px;font-size:15px;border:none;cursor:pointer;">
        Clock Out
    </button>
<?php endif; ?>
</div>


<!-- CLOCK OUT MODAL -->
<div id="clockOutModal" 
     style="display:none;
            position:fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background:rgba(0,0,0,0.7);
            justify-content:center;
            align-items:center;
            z-index:9999;">

    <div style="background:#1e1e2f;
                color:white;
                padding:30px;
                border-radius:12px;
                width:400px;
                text-align:center;
                box-shadow:0 10px 30px rgba(0,0,0,0.5);">

        <h3 style="margin-bottom:10px;color:#fff;">Confirm Clock Out</h3>

        <p style="color:#cfcfcf;">
            Are you sure you want to clock out?
        </p>

        <div style="margin-top:20px;">

            <a href="<?php echo url('dashboard',['action'=>'clockOut']); ?>"
               style="background:#d4183d;
                      color:white;
                      text-decoration:none;
                      padding:10px 20px;
                      border-radius:6px;
                      margin-right:10px;
                      display:inline-block;">
                Yes
            </a>

            <button onclick="closeClockOutModal()"
                style="background:#444;
                       color:white;
                       border:none;
                       padding:10px 20px;
                       border-radius:6px;
                       cursor:pointer;">
                No
            </button>

        </div>

    </div>
</div>
</div></div></div>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:24px;">
<div class="hrms-card" style="padding:24px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Hours This Month</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $hoursThisMonth; ?></p><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">Logged hours</p></div><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#4da6ff" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div></div>
<div class="hrms-card" style="padding:24px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Leave Balance</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $leaveRemaining; ?></p><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">Days remaining</p></div><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#66bb6a" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div></div>
<div class="hrms-card" style="padding:24px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Pending Disputes</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $pendingDisputes; ?></p><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">Timelog corrections</p></div><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg></div></div>
<div class="hrms-card" style="padding:24px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Attendance Rate</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $attRate; ?>%</p><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">This year</p></div><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#fb8c00" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div></div>
</div>

<!-- Work Schedule & Disputes Row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
<div class="hrms-card" style="padding:20px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span style="font-size:13px;font-weight:700;">My Work Schedule</span>
    </div>
    <?php if($workRule): ?>
    <div style="padding:14px;background:rgba(124,147,104,0.06);border:1px solid var(--border);border-radius:10px;">
        <div style="font-size:15px;font-weight:700;margin-bottom:4px;"><?php echo e($workRule['name']); ?></div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;font-size:11px;color:var(--text-muted);">
            <span style="padding:2px 8px;border-radius:12px;font-weight:700;font-size:10px;background:rgba(124,147,104,0.15);color:var(--accent);"><?php echo $workRule['schedule_type']; ?></span>
            <?php if($workRule['start_time'] && $workRule['schedule_type'] !== 'Relax Flexi'): ?>
            <span><?php echo date('g:i A', strtotime($workRule['start_time'])); ?> – <?php echo date('g:i A', strtotime($workRule['end_time'])); ?></span>
            <?php endif; ?>
            <span><?php echo $workRule['work_hours']; ?>h work · <?php echo $workRule['break_minutes']; ?>m break</span>
        </div>
    </div>
    <?php else: ?>
    <p style="font-size:12px;color:var(--text-muted);font-style:italic;margin:0;">No schedule assigned yet. Contact your manager.</p>
    <?php endif; ?>
</div>
<div class="hrms-card" style="padding:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <div style="display:flex;align-items:center;gap:12px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="<?php echo $pendingDisputes > 0 ? '#fb8c00' : 'var(--text-muted)'; ?>" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span style="font-size:13px;font-weight:700;">Timelog Disputes</span>
        </div>
        <a href="<?php echo url('timelog_dispute',['action'=>'new']); ?>" class="btn btn-sm btn-outline" style="padding:3px 10px;font-size:10px;">+ File</a>
    </div>
    <?php if($pendingDisputes > 0): ?>
    <div style="padding:14px;background:rgba(251,140,0,0.06);border:1px solid rgba(251,140,0,0.2);border-radius:10px;">
        <span style="font-size:24px;font-weight:700;color:#fb8c00;"><?php echo $pendingDisputes; ?></span>
        <span style="font-size:12px;color:var(--text-muted);margin-left:6px;">pending dispute<?php echo $pendingDisputes!==1?'s':''; ?></span>
    </div>
    <?php else: ?>
    <p style="font-size:12px;color:var(--text-muted);font-style:italic;margin:0;">No pending disputes. <a href="<?php echo url('timelog_dispute'); ?>" style="color:var(--accent);">View history</a></p>
    <?php endif; ?>
</div>
</div>

<!-- Upcoming Shifts -->
<div class="hrms-card" style="margin-bottom:24px;">
    <div class="card-header"><h3>Upcoming Shifts</h3><a href="<?php echo url('shift'); ?>" class="btn btn-sm btn-outline">View Calendar</a></div>
    <div class="card-body" style="padding:12px 16px;">
    <?php if(!empty($todayShift)): ?>
    <div style="padding:14px 16px;background:rgba(124,147,104,0.08);border:1px solid var(--accent);border-radius:10px;margin-bottom:12px;display:flex;align-items:center;gap:14px;">
        <div style="width:44px;height:44px;border-radius:10px;background:var(--accent);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div style="flex:1;">
            <div style="font-size:14px;font-weight:700;color:var(--accent);">Today's Shift</div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                <?php echo date('g:i A',strtotime($todayShift['start_time'])); ?> – <?php echo date('g:i A',strtotime($todayShift['end_time'])); ?>
                &bull; <?php echo e($todayShift['type']); ?>
                <?php if($todayShift['shift_name']): ?> &bull; <?php echo e($todayShift['shift_name']); ?><?php endif; ?>
            </div>
        </div>
        <span class="badge badge-success" style="font-size:11px;">Today</span>
    </div>
    <?php endif; ?>
    <?php if(!empty($upcomingShifts)): ?>
    <?php foreach($upcomingShifts as $us): $isShiftToday = ($us['date']===date('Y-m-d')); if($isShiftToday) continue; ?>
    <div style="display:flex;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid var(--border);">
        <div style="width:40px;text-align:center;flex-shrink:0;">
            <div style="font-size:18px;font-weight:700;line-height:1;"><?php echo date('d',strtotime($us['date'])); ?></div>
            <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;"><?php echo date('M',strtotime($us['date'])); ?></div>
        </div>
        <div style="flex:1;">
            <div style="font-size:13px;font-weight:600;"><?php echo date('l',strtotime($us['date'])); ?></div>
            <div style="font-size:11px;color:var(--text-muted);">
                <?php echo date('g:i A',strtotime($us['start_time'])); ?> – <?php echo date('g:i A',strtotime($us['end_time'])); ?>
            </div>
        </div>
        <span class="badge <?php echo $us['type']==='Remote'?'badge-success':($us['type']==='Hybrid'?'badge-warning':'badge-info'); ?>" style="font-size:10px;"><?php echo $us['type']; ?></span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php if(empty($upcomingShifts) && empty($todayShift)): ?>
    <p style="text-align:center;color:var(--text-muted);padding:20px;font-size:13px;">No upcoming shifts scheduled.</p>
    <?php endif; ?>
    </div>
</div>
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
<div class="hrms-card"><div class="card-header"><h3>Recent Leave Requests</h3><a href="<?php echo url('my_leave'); ?>" class="btn btn-sm btn-outline">View All</a></div><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;"><thead><tr><th>Type</th><th>Dates</th><th>Days</th><th>Status</th></tr></thead><tbody>
<?php foreach($recentLeaves as $lr): ?><tr><td style="font-weight:600;"><?php echo e($lr['leave_type']); ?></td><td style="color:var(--text-muted);"><?php echo date('M j',strtotime($lr['start_date'])); ?> – <?php echo date('M j, Y',strtotime($lr['end_date'])); ?></td><td><?php echo $lr['days']; ?>d</td><td><span class="badge <?php echo $lr['status']==='Approved'?'badge-success':($lr['status']==='Denied'?'badge-danger':'badge-warning'); ?>"><?php echo e($lr['status']); ?></span></td></tr><?php endforeach; ?>
<?php if(empty($recentLeaves)): ?><tr><td colspan="4" class="text-muted text-center" style="padding:20px;">No leave requests yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="hrms-card"><div class="card-header"><h3>Announcements</h3></div><div class="card-body" style="padding:12px 16px;">
<?php foreach($announcements as $ann): ?><div style="padding:12px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:8px;margin-bottom:8px;"><h4 style="margin:0;font-size:13px;font-weight:600;"><?php echo e($ann['title']); ?></h4><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;"><?php echo date('M j',$ann['created_at']); ?></p></div><?php endforeach; ?>
<?php if(empty($announcements)): ?><p style="text-align:center;color:var(--text-muted);">No announcements.</p><?php endif; ?>
</div></div></div>
<?php endif; ?>
<script>
function openClockOutModal() {
    document.getElementById("clockOutModal").style.display = "flex";
}

function closeClockOutModal() {
    document.getElementById("clockOutModal").style.display = "none";
}

function confirmClockOut() {
    window.location.href = "<?php echo url('dashboard',['action'=>'clockOut']); ?>";
}
</script>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
