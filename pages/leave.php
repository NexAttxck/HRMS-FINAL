<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
Auth::check();
$pageTitle = 'Leave Management — ' . APP_NAME;
$isAdmin=Auth::isAdmin(); $isManager=Auth::isManager(); $empId=Auth::empId(); $userId=Auth::id(); $deptId=Auth::deptId();
$action=$_GET['action']??'index';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $a=$_POST['_action']??'';
    if ($a==='apply') {
        $days=max(1,(int)ceil((strtotime($_POST['end_date'])-strtotime($_POST['start_date']))/86400)+1);
        DB::execute("INSERT INTO leave_request (employee_id,leave_type,start_date,end_date,days,reason,status,created_at,updated_at) VALUES (?,?,?,?,?,?,'Pending',?,?)",[$empId,$_POST['leave_type'],$_POST['start_date'],$_POST['end_date'],$days,$_POST['reason']??null,time(),time()]);
        Auth::audit('Leave Request Submitted', 'Leave', $empId, ($_POST['leave_type'] ?? '') . ': ' . ($_POST['start_date'] ?? '') . ' to ' . ($_POST['end_date'] ?? ''));
        Auth::flash('success','Leave request submitted!'); header('Location: '.url('leave',['action'=>'my'])); exit;
    }
    if (($isAdmin||$isManager) && in_array($a,['approve','deny'])) {
        $id=(int)$_POST['id']; $status=$a==='approve'?'Approved':'Denied';
        DB::execute("UPDATE leave_request SET status=?,approved_by=?,rejection_note=?,updated_at=? WHERE id=?",[$status,$userId,$_POST['rejection_note']??null,time(),$id]);
        // notify
        $lr=DB::fetchOne("SELECT * FROM leave_request WHERE id=?",[$id]);
        if ($lr) DB::execute("INSERT INTO system_notification (user_id,type,title,message,link,is_read,created_at) SELECT u.id,?,?,?,?,0,NOW() FROM employee e JOIN `user` u ON e.user_id=u.id WHERE e.id=?",[$status==='Approved'?'success':'danger',"Leave Request $status","Your leave request has been $status.",url('my_leave'),$lr['employee_id']]);
        Auth::audit('Leave Request ' . $status, 'Leave', $id, 'Status: ' . $status);
        Auth::flash('success',"Leave request $status!"); header('Location: '.url('leave')); exit;
    }
}

if ($action==='my') {
    $records=DB::fetchAll("SELECT * FROM leave_request WHERE employee_id=? ORDER BY created_at DESC",[$empId]);
} else {
    $where='1=1'; $params=[];
    if ($isManager&&!$isAdmin) { $where.=" AND e.department_id=?"; $params[]=$deptId; }
    if (!$isAdmin&&!$isManager) { $where.=" AND lr.employee_id=?"; $params[]=$empId; }
    $statusF=$_GET['status']??'';
    if ($statusF) { $where.=" AND lr.status=?"; $params[]=$statusF; }
    $records=DB::fetchAll("SELECT lr.*,e.first_name,e.last_name FROM leave_request lr JOIN employee e ON lr.employee_id=e.id WHERE $where ORDER BY lr.created_at DESC",$params);
}
$leaveTypes=['Vacation Leave','Sick Leave','Personal Leave','Emergency Leave','Maternity Leave','Paternity Leave'];
require_once __DIR__ . '/../includes/layout_header.php';
?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div><h1 style="font-size:24px;font-weight:700;margin:0;">Leave Management</h1><div style="display:flex;gap:8px;margin-top:8px;"><a href="<?php echo url('leave'); ?>" class="btn btn-sm <?php echo $action!=='my'?'btn-accent':'btn-outline'; ?>">All Requests</a><?php if(!$isAdmin&&!$isManager): ?><a href="<?php echo url('leave',['action'=>'my']); ?>" class="btn btn-sm <?php echo $action==='my'?'btn-accent':'btn-outline'; ?>">My Leaves</a><a href="<?php echo url('leave',['action'=>'apply']); ?>" class="btn btn-sm btn-outline">+ Apply</a><?php endif; ?></div></div>
    <?php if($isAdmin||$isManager): ?><a href="<?php echo url('leave',['action'=>'apply']); ?>" class="btn btn-accent">+ New Leave</a><?php endif; ?>
</div>
<?php if ($action==='apply'||$action==='new'): ?>
<div class="hrms-card"><div class="card-header"><h3>Apply for Leave</h3></div><div class="card-body">
<form method="POST">
<input type="hidden" name="_action" value="apply">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Leave Type</label><select name="leave_type" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><?php foreach($leaveTypes as $lt): ?><option><?php echo $lt; ?></option><?php endforeach; ?></select></div>
<div></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Start Date</label><input type="date" name="start_date" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">End Date</label><input type="date" name="end_date" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div style="grid-column:1/-1;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Reason</label><textarea name="reason" rows="3" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></textarea></div>
</div>
<div style="margin-top:16px;display:flex;gap:12px;"><button type="submit" class="btn btn-accent">Submit Request</button><a href="<?php echo url('leave'); ?>" class="btn btn-outline">Cancel</a></div>
</form></div></div>
<?php else: ?>
<?php if($isAdmin||$isManager): ?>
<div class="hrms-card" style="margin-bottom:16px;padding:16px;"><form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <input type="hidden" name="page" value="leave">
    <div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Status</label><select name="status" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="">All</option><?php foreach(['Pending','Approved','Denied'] as $s): ?><option value="<?php echo $s; ?>" <?php echo ($_GET['status']??'')===$s?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?></select></div>
    <button type="submit" class="btn btn-accent">Filter</button><a href="<?php echo url('leave'); ?>" class="btn btn-outline">Reset</a>
</form></div>
<?php endif; ?>
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;">
<thead><tr><?php if($isAdmin||$isManager): ?><th>Employee</th><?php endif; ?><th>Type</th><th>Period</th><th>Days</th><th>Status</th><th>Reason</th><?php if($isAdmin||$isManager): ?><th>Actions</th><?php endif; ?></tr></thead>
<tbody>
<?php foreach($records as $r): ?>
<tr>
    <?php if($isAdmin||$isManager): ?><td style="font-weight:600;"><?php echo e(($r['first_name']??'').' '.($r['last_name']??'')); ?></td><?php endif; ?>
    <td><?php echo e($r['leave_type']); ?></td>
    <td style="font-size:12px;"><?php echo date('M j',strtotime($r['start_date'])); ?> – <?php echo date('M j, Y',strtotime($r['end_date'])); ?></td>
    <td><?php echo $r['days']; ?></td>
    <td><span class="badge <?php echo $r['status']==='Approved'?'badge-success':($r['status']==='Pending'?'badge-warning':'badge-danger'); ?>"><?php echo $r['status']; ?></span></td>
    <td style="font-size:12px;color:var(--text-muted);"><?php echo e(substr($r['reason']??'',0,40)); ?></td>
    <?php if($isAdmin||$isManager): ?><td>
        <?php if($r['status']==='Pending'): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="_action" value="approve"><input type="hidden" name="id" value="<?php echo $r['id']; ?>">
            <button type="submit" class="btn btn-sm" style="background:#43a047;color:#fff;padding:3px 10px;font-size:11px;">Approve</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="_action" value="deny"><input type="hidden" name="id" value="<?php echo $r['id']; ?>">
            <button type="submit" class="btn btn-sm btn-outline" style="padding:3px 10px;font-size:11px;">Deny</button>
        </form>
        <?php else: echo '—'; endif; ?>
    </td><?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if(empty($records)): ?><tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);">No leave requests found.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>

