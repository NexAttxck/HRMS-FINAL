<?php
require_once __DIR__ . "/../config.php"; require_once __DIR__ . "/../includes/db.php"; require_once __DIR__ . "/../includes/auth.php";
Auth::check(); Auth::requireRole(["Super Admin", "Manager"]); $pageTitle="Personnel Actions &mdash; ".APP_NAME;
if($_SERVER["REQUEST_METHOD"]==="POST"){$a=$_POST["_action"]??"";
    if($a==="save"){DB::insert("INSERT INTO personnel_action (employee_id,type,effective_date,old_value,new_value,remarks,status,created_by,updated_at,created_at) VALUES (?,?,?,?,?,?,?,?,?,?)",[$_POST["employee_id"],$_POST["type"],$_POST["effective_date"],$_POST["old_value"]??null,$_POST["new_value"]??null,$_POST["remarks"]??null,"Approved",Auth::id(),time(),time()]);
        Auth::audit('Personnel Action: '.($_POST['type'] ?? ''), 'Personnel Action', (int)($_POST['employee_id'] ?? 0), ($_POST['type'] ?? '') . ' — ' . substr($_POST['remarks'] ?? '', 0, 80));
        Auth::flash("success","Personnel action recorded!"); header("Location: ".url("personnel_action")); exit;}
}
$actions=DB::fetchAll("SELECT pa.*,e.first_name,e.last_name,u.username as created_by_name FROM personnel_action pa JOIN employee e ON pa.employee_id=e.id LEFT JOIN `user` u ON pa.created_by=u.id ORDER BY pa.created_at DESC");
$employees=DB::fetchAll("SELECT id,first_name,last_name FROM employee ORDER BY first_name");
$action=$_GET["action"]??"index";
require_once __DIR__ . "/../includes/layout_header.php"; ?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;"><h1 style="font-size:24px;font-weight:700;margin:0;">Personnel Actions</h1><a href="<?php echo url("personnel_action",["action"=>"create"]); ?>" class="btn btn-accent">+ New Action</a></div>
<?php if($action==="create"): ?>
<div class="hrms-card"><div class="card-header"><h3>New Personnel Action</h3></div><div class="card-body">
<form method="POST"><input type="hidden" name="_action" value="save">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Employee</label><select name="employee_id" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="">&mdash; Select &mdash;</option><?php foreach($employees as $em): ?><option value="<?php echo $em["id"]; ?>"><?php echo e($em["first_name"]." ".$em["last_name"]); ?></option><?php endforeach; ?></select></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Action Type</label><select name="type" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><?php foreach(["Promotion","Demotion","Salary Adjustment","Transfer","Resignation","Termination","Other"] as $at): ?><option><?php echo $at; ?></option><?php endforeach; ?></select></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Effective Date</label><input type="date" name="effective_date" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Old Value</label><input type="text" name="old_value" placeholder="e.g. old salary or position" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">New Value</label><input type="text" name="new_value" placeholder="e.g. new salary or position" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div style="grid-column:1/-1;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Reason/Notes</label><textarea name="remarks" rows="3" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></textarea></div>
</div><div style="margin-top:16px;display:flex;gap:12px;"><button type="submit" class="btn btn-accent">Record Action</button><a href="<?php echo url("personnel_action"); ?>" class="btn btn-outline">Cancel</a></div>
</form></div></div>
<?php else: ?>
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;">
<thead><tr><th>Date</th><th>Employee</th><th>Action</th><th>Change</th><th>Reason</th><th>By</th></tr></thead><tbody>
<?php foreach($actions as $a2): $ac=["Promotion"=>"#66bb6a","Salary Adjustment"=>"#4da6ff","Demotion"=>"#fb8c00","Termination"=>"#ef5350","Resignation"=>"#ef5350"][$a2["type"]]??"#fff"; ?>
<tr><td style="font-size:12px;color:var(--text-muted);"><?php echo date("M j, Y",strtotime($a2["effective_date"])); ?></td>
<td style="font-weight:600;"><?php echo e($a2["first_name"]." ".$a2["last_name"]); ?></td>
<td><span style="color:<?php echo $ac; ?>;font-size:12px;font-weight:600;"><?php echo e($a2["type"]); ?></span></td>
<td style="font-size:12px;"><span style="text-decoration:line-through;color:var(--text-muted);"><?php echo e($a2["old_value"]??""); ?></span> &rarr; <?php echo e($a2["new_value"]??""); ?></td>
<td style="font-size:12px;color:var(--text-muted);"><?php echo e(substr($a2["remarks"]??"",0,50)); ?></td>
<td style="font-size:12px;"><?php echo e($a2["created_by_name"]??"System"); ?></td>
</tr><?php endforeach; if(empty($actions)): ?><tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">No personnel actions yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php endif; ?>
<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>
