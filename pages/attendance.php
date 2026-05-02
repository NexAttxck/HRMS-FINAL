<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
Auth::check();
$pageTitle = 'Attendance — ' . APP_NAME;
$isAdmin = Auth::isAdmin(); $isManager = Auth::isManager();
$empId = Auth::empId(); $deptId = Auth::deptId();
$action = $_GET['action'] ?? 'index';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['_action'] ?? '';
    if ($a === 'clockIn' && $empId) {
        $ex = DB::fetchOne("SELECT id FROM attendance WHERE employee_id=? AND date=CURDATE()", [$empId]);
        if (!$ex) DB::execute("INSERT INTO attendance (employee_id,date,clock_in,status,created_at) VALUES (?,CURDATE(),NOW(),'On Time',?)", [$empId, time()]);
        Auth::audit('Clock In', 'Attendance', $empId);
        Auth::flash('success','Clocked in!'); header('Location: '.url('attendance',['action'=>'my'])); exit;
    }
    if ($a === 'clockOut' && $empId) {
        DB::execute("UPDATE attendance SET clock_out=NOW(),total_hours=TIMESTAMPDIFF(MINUTE,clock_in,NOW())/60,updated_at=? WHERE employee_id=? AND date=CURDATE()", [time(),$empId]);
        Auth::audit('Clock Out', 'Attendance', $empId);
        Auth::flash('success','Clocked out!'); header('Location: '.url('attendance',['action'=>'my'])); exit;
    }
    if (($isAdmin||$isManager) && $a === 'save') {
        $id = (int)($_POST['id']??0);
        if ($id) {
            DB::execute("UPDATE attendance SET clock_in=?,clock_out=?,status=?,notes=?,updated_at=? WHERE id=?", [$_POST['clock_in'],$_POST['clock_out'],$_POST['status'],$_POST['notes']??null,time(),$id]);
        } else {
            DB::execute("INSERT INTO attendance (employee_id,date,clock_in,clock_out,status,notes,created_at) VALUES (?,?,?,?,?,?,?)", [$_POST['employee_id'],$_POST['date'],$_POST['clock_in'],$_POST['clock_out'],$_POST['status'],$_POST['notes']??null,time()]);
        }
        Auth::audit($id ? 'Update Attendance' : 'Log Attendance', 'Attendance', $id ?: (int)($_POST['employee_id'] ?? 0));
        Auth::flash('success','Attendance saved!'); header('Location: '.url('attendance')); exit;
    }
}

$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo   = $_GET['to']   ?? date('Y-m-d');
$empFilter = (int)($_GET['emp'] ?? 0);
$where = "a.date BETWEEN ? AND ?"; $params = [$dateFrom, $dateTo];
if ($empFilter) { $where .= " AND a.employee_id=?"; $params[] = $empFilter; }
if ($isManager && !$isAdmin) { $where .= " AND e.department_id=?"; $params[] = $deptId; }
if (!$isAdmin && !$isManager) { $where .= " AND a.employee_id=?"; $params[] = $empId; }
$records = DB::fetchAll("SELECT a.*,e.first_name,e.last_name FROM attendance a JOIN employee e ON a.employee_id=e.id WHERE $where ORDER BY a.date DESC, e.first_name", $params);
$employees = ($isAdmin||$isManager) ? DB::fetchAll("SELECT id,first_name,last_name FROM employee WHERE status IN ('Regular','Probationary') ORDER BY first_name") : [];
require_once __DIR__ . '/../includes/layout_header.php';
?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <h1 style="font-size:24px;font-weight:700;margin:0;">Attendance</h1>
    <?php if(!$isAdmin&&!$isManager): ?>
    <form method="POST" style="display:flex;gap:8px;">
        <input type="hidden" name="_action" value="clockIn">
        <button type="submit" class="btn" style="background:#43a047;color:#fff;">Clock In</button>
    </form>
    <?php endif; ?>
</div>
<div class="hrms-card" style="margin-bottom:16px;padding:16px;">
    <form method="GET" action="" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="page" value="attendance">
        <div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">From</label><input type="date" name="from" value="<?php echo e($dateFrom); ?>" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
        <div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">To</label><input type="date" name="to" value="<?php echo e($dateTo); ?>" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
        <?php if($isAdmin||$isManager): ?><div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Employee</label><select name="emp" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="">All</option><?php foreach($employees as $em): ?><option value="<?php echo $em['id']; ?>" <?php echo $empFilter==$em['id']?'selected':''; ?>><?php echo e($em['first_name'].' '.$em['last_name']); ?></option><?php endforeach; ?></select></div><?php endif; ?>
        <button type="submit" class="btn btn-accent">Filter</button><a href="<?php echo url('attendance'); ?>" class="btn btn-outline">Reset</a>
    </form>
</div>
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;">
<thead><tr><th>Employee</th><th>Date</th><th>Clock In</th><th>Clock Out</th><th>Hours</th><th>Status</th><?php if($isAdmin): ?><th>Notes</th><?php endif; ?></tr></thead>
<tbody>
<?php foreach($records as $r): ?>
<tr>
    <td style="font-weight:600;"><?php echo e($r['first_name'].' '.$r['last_name']); ?></td>
    <td><?php echo date('M j, Y',strtotime($r['date'])); ?></td>
    <td><?php echo $r['clock_in']?date('h:i A',strtotime($r['clock_in'])):'—'; ?></td>
    <td><?php echo $r['clock_out']?date('h:i A',strtotime($r['clock_out'])):'—'; ?></td>
    <td><?php echo $r['total_hours']?number_format($r['total_hours'],1).'h':'—'; ?></td>
    <td><span class="badge <?php echo $r['status']==='On Time'?'badge-success':($r['status']==='Late'?'badge-warning':'badge-danger'); ?>"><?php echo e($r['status']); ?></span></td>
    <?php if($isAdmin): ?><td style="font-size:12px;color:var(--text-muted);"><?php echo e($r['notes']??''); ?></td><?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if(empty($records)): ?><tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted);">No attendance records found.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>

