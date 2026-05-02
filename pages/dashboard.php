<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
Auth::check();
$pageTitle = 'Dashboard — ' . APP_NAME;
$role = Auth::role();
$empId = Auth::empId();
$deptId = Auth::deptId();
$userId = Auth::id();

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
    $emp = DB::fetchOne("SELECT e.*, d.name as dept_name FROM employee e LEFT JOIN department d ON e.department_id=d.id WHERE e.id=?", [$empId]);
    $empName = $emp ? ($emp['first_name'].' '.$emp['last_name']) : Auth::name();
    $empTitle = $emp['job_title'] ?? '';
    $todayAtt = DB::fetchOne("SELECT * FROM attendance WHERE employee_id=? AND date=CURDATE()", [$empId]);
    $isClockedIn = $todayAtt && $todayAtt['clock_in'] && !$todayAtt['clock_out'];
    $hoursThisMonth = (int)DB::fetchScalar("SELECT COALESCE(SUM(total_hours),0) FROM attendance WHERE employee_id=? AND DATE_FORMAT(date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')", [$empId]);
    $vacEntitlement = 15; $vacUsed = (int)DB::fetchScalar("SELECT COALESCE(SUM(days),0) FROM leave_request WHERE employee_id=? AND leave_type='Vacation Leave' AND status='Approved' AND YEAR(start_date)=YEAR(CURDATE())", [$empId]);
    $leaveRemaining = $vacEntitlement - $vacUsed;
    $totalPayslips = (int)DB::fetchScalar("SELECT COUNT(*) FROM payslip ps JOIN payroll p ON ps.payroll_id=p.id WHERE ps.employee_id=? AND YEAR(p.pay_date)=YEAR(CURDATE())", [$empId]);
    $attTotal = (int)DB::fetchScalar("SELECT COUNT(*) FROM attendance WHERE employee_id=? AND date>=?", [$empId, date('Y-01-01')]);
    $attOnTime = (int)DB::fetchScalar("SELECT COUNT(*) FROM attendance WHERE employee_id=? AND status='On Time' AND date>=?", [$empId, date('Y-01-01')]);
    $attRate = $attTotal > 0 ? round($attOnTime/$attTotal*100) : 0;
    $empDeptId = $emp['department_id'] ?? 0;
    $announcements = DB::fetchAll("SELECT * FROM announcement WHERE target_audience='All' OR department_id=? ORDER BY pinned DESC, created_at DESC LIMIT 3", [$empDeptId]);
    $payslips = DB::fetchAll("SELECT ps.*, p.period_label, p.pay_date FROM payslip ps JOIN payroll p ON ps.payroll_id=p.id WHERE ps.employee_id=? ORDER BY p.pay_date DESC LIMIT 3", [$empId]);
    // Clock In/Out action
    if (isset($_GET['action'])) {
        if ($_GET['action'] === 'clockIn' && (!$todayAtt || !$todayAtt['clock_in'])) {
            if ($todayAtt) { DB::execute("UPDATE attendance SET clock_in=NOW(), status='On Time', updated_at=? WHERE id=?", [time(), $todayAtt['id']]); }
            else { DB::execute("INSERT INTO attendance (employee_id, date, clock_in, status, created_at) VALUES (?, CURDATE(), NOW(), 'On Time', ?)", [$empId, time()]); }
            Auth::flash('success', 'Clocked in successfully!');
            header('Location: ' . url('dashboard')); exit;
        }
        if ($_GET['action'] === 'clockOut' && $isClockedIn) {
            DB::execute("UPDATE attendance SET clock_out=NOW(), total_hours=TIMESTAMPDIFF(MINUTE, clock_in, NOW())/60, updated_at=? WHERE id=?", [time(), $todayAtt['id']]);
            Auth::flash('success', 'Clocked out successfully!');
            header('Location: ' . url('dashboard')); exit;
        }
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
<?php foreach($teamAttendance as $att): ?><tr><td style="font-weight:600;"><?php echo e($att['first_name'].' '.$att['last_name']); ?></td><td><?php echo $att['clock_in']?date('h:i A',strtotime($att['clock_in'])):'—'; ?></td><td><?php echo $att['clock_out']?date('h:i A',strtotime($att['clock_out'])):'—'; ?></td><td><span class="badge <?php echo $att['status']==='On Time'?'badge-success':($att['status']==='Late'?'badge-warning':'badge-danger'); ?>"><?php echo e($att['status']); ?></span></td></tr><?php endforeach; ?>
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
<div><?php if(!$isClockedIn): ?><a href="<?php echo url('dashboard',['action'=>'clockIn']); ?>" class="btn" style="background:#43a047;color:#fff;padding:12px 24px;font-size:15px;">Clock In</a><?php else: ?><a href="<?php echo url('dashboard',['action'=>'clockOut']); ?>" class="btn" style="background:#d4183d;color:#fff;padding:12px 24px;font-size:15px;">Clock Out</a><?php endif; ?></div>
</div></div></div>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:24px;">
<div class="hrms-card" style="padding:24px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Hours This Month</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $hoursThisMonth; ?></p><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">Logged hours</p></div><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#4da6ff" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div></div>
<div class="hrms-card" style="padding:24px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Leave Balance</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $leaveRemaining; ?></p><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">Days remaining</p></div><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#66bb6a" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div></div>
<div class="hrms-card" style="padding:24px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Payslips This Year</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $totalPayslips; ?></p><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">Released payslips</p></div><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg></div></div>
<div class="hrms-card" style="padding:24px;"><div style="display:flex;justify-content:space-between;align-items:center;"><div><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Attendance Rate</p><p style="font-size:28px;font-weight:700;margin:0;"><?php echo $attRate; ?>%</p><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;">This year</p></div><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#fb8c00" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div></div>
</div>
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
<div class="hrms-card"><div class="card-header"><h3>Recent Payslips</h3><a href="<?php echo url('my_payslips'); ?>" class="btn btn-sm btn-outline">View All</a></div><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;"><thead><tr><th>Period</th><th>Net Pay</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php foreach($payslips as $ps): ?><tr><td style="font-weight:600;"><?php echo e($ps['period_label']); ?></td><td style="color:#66bb6a;font-weight:600;">&#8369;<?php echo number_format($ps['net_pay'],2); ?></td><td><span class="badge badge-success">Paid</span></td><td style="color:var(--text-muted);"><?php echo date('M j, Y',strtotime($ps['pay_date'])); ?></td></tr><?php endforeach; ?>
<?php if(empty($payslips)): ?><tr><td colspan="4" class="text-muted text-center" style="padding:20px;">No payslips yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="hrms-card"><div class="card-header"><h3>Announcements</h3></div><div class="card-body" style="padding:12px 16px;">
<?php foreach($announcements as $ann): ?><div style="padding:12px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:8px;margin-bottom:8px;"><h4 style="margin:0;font-size:13px;font-weight:600;"><?php echo e($ann['title']); ?></h4><p style="font-size:11px;color:var(--text-muted);margin:4px 0 0;"><?php echo date('M j',$ann['created_at']); ?></p></div><?php endforeach; ?>
<?php if(empty($announcements)): ?><p style="text-align:center;color:var(--text-muted);">No announcements.</p><?php endif; ?>
</div></div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
