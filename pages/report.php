<?php
require_once __DIR__ . "/../config.php"; require_once __DIR__ . "/../includes/db.php"; require_once __DIR__ . "/../includes/auth.php";
Auth::check(); Auth::requireRole(["Super Admin","Manager"]); $pageTitle="Reports — ".APP_NAME; $isAdmin=Auth::isAdmin(); $deptId=Auth::deptId();
if (!$isAdmin && !$deptId) {
    $deptId = (int)DB::fetchScalar("SELECT department_id FROM employee WHERE user_id=? AND department_id IS NOT NULL LIMIT 1", [Auth::id()]);
}
$type=$_GET["type"]??"attendance"; $month=$_GET["month"]??date("Y-m"); $empFilter=(int)($_GET["emp"]??0);
$depts=DB::fetchAll("SELECT id,name FROM department ORDER BY name");
$employees=DB::fetchAll("SELECT id,first_name,last_name FROM employee WHERE status IN ('Regular','Probationary') ORDER BY first_name");
$data=[]; $summary=[];
if($type==="attendance"){
    $where="DATE_FORMAT(a.date,'%Y-%m')=?"; $params=[$month];
    if($empFilter){$where.=" AND a.employee_id=?";$params[]=$empFilter;}
    if(!$isAdmin){$where.=" AND e.department_id=?";$params[]=$deptId;}
    $data=DB::fetchAll("SELECT e.first_name,e.last_name,COUNT(*) as days,SUM(CASE WHEN a.status='On Time' THEN 1 ELSE 0 END) as on_time,SUM(CASE WHEN a.status='Late' THEN 1 ELSE 0 END) as late,COALESCE(SUM(a.total_hours),0) as total_hours FROM attendance a JOIN employee e ON a.employee_id=e.id WHERE $where GROUP BY a.employee_id,e.first_name,e.last_name ORDER BY e.first_name",$params);
} elseif($type==="leave"){
    $where="YEAR(lr.start_date)=?"; $params=[substr($month,0,4)];
    if(!$isAdmin){$where.=" AND e.department_id=?";$params[]=$deptId;}
    $data=DB::fetchAll("SELECT e.first_name,e.last_name,lr.leave_type,COUNT(*) as requests,SUM(lr.days) as total_days,SUM(CASE WHEN lr.status='Approved' THEN lr.days ELSE 0 END) as approved_days FROM leave_request lr JOIN employee e ON lr.employee_id=e.id WHERE $where GROUP BY lr.employee_id,e.first_name,e.last_name,lr.leave_type ORDER BY e.first_name",$params);
// payroll report type REMOVED — payroll module dropped
}
require_once __DIR__ . "/../includes/layout_header.php"; ?>
<div style="margin-bottom:20px;"><h1 style="font-size:24px;font-weight:700;margin:0;">Reports & Analytics</h1></div>
<div class="hrms-card" style="margin-bottom:16px;padding:16px;"><form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;"><input type="hidden" name="page" value="report">
<div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Report Type</label><select name="type" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;" onchange="this.form.submit()">
<option value="attendance" <?php echo $type==="attendance"?"selected":""; ?>>Attendance Summary</option>
<option value="leave" <?php echo $type==="leave"?"selected":""; ?>>Leave Summary</option>
<?php /* Payroll Summary report option REMOVED */ ?>
</select></div>
<div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;"><?php echo $type==="leave"?"Year":"Month"; ?></label><input type="month" name="month" value="<?php echo e($month); ?>" onchange="this.form.submit()" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Employee</label><select name="emp" onchange="this.form.submit()" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="">All</option><?php foreach($employees as $em): ?><option value="<?php echo $em["id"]; ?>" <?php echo $empFilter==$em["id"]?"selected":""; ?>><?php echo e($em["first_name"]." ".$em["last_name"]); ?></option><?php endforeach; ?></select></div>
<button type="submit" style="display:none;"></button>
</form></div>
<?php if($type==="attendance"&&!empty($data)): ?>
<div class="hrms-card" style="margin-bottom:16px;"><div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;padding:20px;">
<?php $totDays=array_sum(array_column($data,"days")); $totOT=array_sum(array_column($data,"on_time")); $totLate=array_sum(array_column($data,"late")); $totH=array_sum(array_column($data,"total_hours")); $kpis=[["Total Days",$totDays,"#4da6ff"],["On Time",$totOT,"#66bb6a"],["Late",$totLate,"#fb8c00"],["Total Hours",round($totH,0),"#a78bfa"]];
foreach($kpis as $k): ?><div style="text-align:center;"><div style="font-size:28px;font-weight:700;color:<?php echo $k[2]; ?>;"><?php echo $k[1]; ?></div><div style="font-size:12px;color:var(--text-muted);"><?php echo $k[0]; ?></div></div><?php endforeach; ?>
</div></div>
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;">
<thead><tr><th>Employee</th><th>Days Present</th><th>On Time</th><th>Late</th><th>Total Hours</th><th>Rate</th></tr></thead><tbody>
<?php foreach($data as $r): ?><tr><td style="font-weight:600;"><?php echo e($r["first_name"]." ".$r["last_name"]); ?></td><td><?php echo $r["days"]; ?></td><td><?php echo $r["on_time"]; ?></td><td><?php echo $r["late"]>0?"<span style='color:#fb8c00;'>".$r["late"]."</span>":"0"; ?></td><td><?php echo round($r["total_hours"],1); ?>h</td>
<td><?php $rate=$r["days"]>0?round($r["on_time"]/$r["days"]*100):0; ?><div style="display:flex;align-items:center;gap:8px;"><div style="flex:1;height:6px;background:rgba(255,255,255,0.08);border-radius:3px;"><div style="height:100%;width:<?php echo $rate; ?>%;background:#66bb6a;border-radius:3px;"></div></div><span style="font-size:12px;"><?php echo $rate; ?>%</span></div></td>
</tr><?php endforeach; ?></tbody></table></div></div>
<?php elseif($type==="leave"&&!empty($data)): ?>
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;">
<thead><tr><th>Employee</th><th>Leave Type</th><th>Requests</th><th>Days Applied</th><th>Days Approved</th></tr></thead><tbody>
<?php foreach($data as $r): ?><tr><td style="font-weight:600;"><?php echo e($r["first_name"]." ".$r["last_name"]); ?></td><td><?php echo e($r["leave_type"]); ?></td><td><?php echo $r["requests"]; ?></td><td><?php echo $r["total_days"]; ?></td><td style="color:#66bb6a;font-weight:600;"><?php echo $r["approved_days"]; ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php /* payroll report results block REMOVED */ ?>
<?php else: ?><div class="hrms-card" style="padding:40px;text-align:center;color:var(--text-muted);">No data found for the selected filters.</div><?php endif; ?>
<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>

