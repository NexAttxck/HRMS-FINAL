<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
Auth::check();
$pageTitle = 'Shift Scheduling — ' . APP_NAME;
$isAdmin=Auth::isAdmin(); $isManager=Auth::isManager(); $empId=Auth::empId(); $deptId=Auth::deptId();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $a=$_POST['_action']??'';
    if (($isAdmin||$isManager)&&$a==='save') {
        $id=(int)($_POST['id']??0);
        if ($id) DB::execute("UPDATE shift SET employee_id=?,date=?,start_time=?,end_time=?,type=?,shift_name=?,status=? WHERE id=?",[$_POST['employee_id'],$_POST['date'],$_POST['start_time'],$_POST['end_time'],$_POST['type']??'Onsite',$_POST['shift_name']??null,$_POST['status']??'Scheduled',$id]);
        else DB::execute("INSERT INTO shift (employee_id,date,start_time,end_time,type,shift_name,status,published,created_at) VALUES (?,?,?,?,?,?,?,1,?)",[$_POST['employee_id'],$_POST['date'],$_POST['start_time'],$_POST['end_time'],$_POST['type']??'Onsite',$_POST['shift_name']??null,$_POST['status']??'Scheduled',time()]);
        Auth::flash('success','Shift saved!'); header('Location: '.url('shift')); exit;
    }
    if ($isAdmin&&$a==='delete') { DB::execute("DELETE FROM shift WHERE id=?",[  (int)$_POST['id']]); Auth::flash('success','Shift deleted.'); header('Location: '.url('shift')); exit; }
}
$dateFrom=$_GET['from']??date('Y-m-d'); $dateTo=$_GET['to']??date('Y-m-d',strtotime('+6 days'));
$where="s.date BETWEEN ? AND ?"; $params=[$dateFrom,$dateTo];
if ($isManager&&!$isAdmin) { $where.=" AND e.department_id=?"; $params[]=$deptId; }
if (!$isAdmin&&!$isManager) { $where.=" AND s.employee_id=?"; $params[]=$empId; }
$shifts=DB::fetchAll("SELECT s.*,e.first_name,e.last_name FROM shift s JOIN employee e ON s.employee_id=e.id WHERE $where ORDER BY s.date,s.start_time",$params);
$employees=($isAdmin||$isManager)?DB::fetchAll("SELECT id,first_name,last_name FROM employee WHERE status IN ('Regular','Probationary') ORDER BY first_name"):[];
$action=$_GET['action']??'index';
require_once __DIR__ . '/../includes/layout_header.php';
?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <h1 style="font-size:24px;font-weight:700;margin:0;">Shift Scheduling</h1>
    <?php if($isAdmin||$isManager): ?><a href="<?php echo url('shift',['action'=>'create']); ?>" class="btn btn-accent">+ Add Shift</a><?php endif; ?>
</div>
<?php if($action==='create'||$action==='edit'): $editId=(int)($_GET['id']??0); $es=$editId?DB::fetchOne("SELECT * FROM shift WHERE id=?",[$editId]):null; ?>
<div class="hrms-card"><div class="card-header"><h3><?php echo $es?'Edit Shift':'New Shift'; ?></h3></div><div class="card-body">
<form method="POST"><input type="hidden" name="_action" value="save"><?php if($es): ?><input type="hidden" name="id" value="<?php echo $es['id']; ?>"><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Employee</label><select name="employee_id" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="">— Select —</option><?php foreach($employees as $em): ?><option value="<?php echo $em['id']; ?>" <?php echo ($es['employee_id']??'')==$em['id']?'selected':''; ?>><?php echo e($em['first_name'].' '.$em['last_name']); ?></option><?php endforeach; ?></select></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Date</label><input type="date" name="date" value="<?php echo e($es['date']??date('Y-m-d')); ?>" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Start Time</label><input type="time" name="start_time" value="<?php echo e($es['start_time']??'08:00'); ?>" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">End Time</label><input type="time" name="end_time" value="<?php echo e($es['end_time']??'17:00'); ?>" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Type</label><select name="type" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="Onsite" <?php echo ($es['type']??'Onsite')==='Onsite'?'selected':''; ?>>Onsite</option><option value="Remote" <?php echo ($es['type']??'')==='Remote'?'selected':''; ?>>Remote</option></select></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Shift Name</label><input type="text" name="shift_name" value="<?php echo e($es['shift_name']??''); ?>" placeholder="e.g. Morning Shift" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
</div>
<div style="margin-top:16px;display:flex;gap:12px;"><button type="submit" class="btn btn-accent">Save Shift</button><a href="<?php echo url('shift'); ?>" class="btn btn-outline">Cancel</a></div>
</form></div></div>
<?php else: ?>
<div class="hrms-card" style="margin-bottom:16px;padding:16px;"><form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;"><input type="hidden" name="page" value="shift">
<div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">From</label><input type="date" name="from" value="<?php echo e($dateFrom); ?>" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">To</label><input type="date" name="to" value="<?php echo e($dateTo); ?>" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<button type="submit" class="btn btn-accent">Filter</button><a href="<?php echo url('shift'); ?>" class="btn btn-outline">Reset</a>
</form></div>
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;">
<thead><tr><th>Employee</th><th>Date</th><th>Shift</th><th>Time</th><th>Type</th><th>Status</th><?php if($isAdmin||$isManager): ?><th>Actions</th><?php endif; ?></tr></thead>
<tbody>
<?php foreach($shifts as $s): ?>
<tr><td style="font-weight:600;"><?php echo e($s['first_name'].' '.$s['last_name']); ?></td><td><?php echo date('M j, Y',strtotime($s['date'])); ?></td><td><?php echo e($s['shift_name']??'Regular'); ?></td><td style="font-size:12px;"><?php echo date('h:i A',strtotime($s['start_time'])); ?> – <?php echo date('h:i A',strtotime($s['end_time'])); ?></td><td><span class="badge <?php echo $s['type']==='Remote'?'badge-info':'badge-muted'; ?>"><?php echo $s['type']; ?></span></td><td><span class="badge <?php echo $s['status']==='Completed'?'badge-success':($s['status']==='Missed'?'badge-danger':'badge-muted'); ?>"><?php echo $s['status']; ?></span></td>
<?php if($isAdmin||$isManager): ?><td><a href="<?php echo url('shift',['action'=>'edit','id'=>$s['id']]); ?>" class="btn btn-sm btn-outline" style="padding:3px 10px;">Edit</a></td><?php endif; ?>
</tr>
<?php endforeach; if(empty($shifts)): ?><tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted);">No shifts found.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>

