<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
Auth::check();
$pageTitle="Benefits — ".APP_NAME; $isAdmin=Auth::isAdmin(); $empId=Auth::empId(); $action=$_GET["action"]??"index";
if($_SERVER["REQUEST_METHOD"]==="POST"){
    $a=$_POST["_action"]??"";
    if($isAdmin&&$a==="save"){$id=(int)($_POST["id"]??0);
        if($id)DB::execute("UPDATE benefit SET name=?,description=?,type=?,value=? WHERE id=?",[$_POST["name"],$_POST["description"]??null,$_POST["type"]??null,$_POST["value"]??0,$id]);
        else DB::insert("INSERT INTO benefit (name,description,type,value,created_at) VALUES (?,?,?,?,?)",[$_POST["name"],$_POST["description"]??null,$_POST["type"]??null,$_POST["value"]??0,time()]);
        Auth::flash("success","Benefit saved!");header("Location: ".url("benefit"));exit;}
    if($isAdmin&&$a==="enroll"){$eid=(int)$_POST["employee_id"];$bid=(int)$_POST["benefit_id"];$ex=DB::fetchScalar("SELECT COUNT(*) FROM employee_benefit WHERE employee_id=? AND benefit_id=?",[$eid,$bid]);if(!$ex){DB::execute("INSERT INTO employee_benefit (employee_id,benefit_id,enrolled_at) VALUES (?,?,CURDATE())",[$eid,$bid]);Auth::flash("success","Enrolled!");}header("Location: ".url("benefit"));exit;}
}
$benefits=DB::fetchAll("SELECT b.*,(SELECT COUNT(*) FROM employee_benefit eb WHERE eb.benefit_id=b.id) as enrolled FROM benefit b ORDER BY b.name");
$employees=$isAdmin?DB::fetchAll("SELECT id,first_name,last_name FROM employee WHERE status IN ('Regular','Probationary') ORDER BY first_name"):[];
$myBenefits=$empId?DB::fetchAll("SELECT b.* FROM benefit b JOIN employee_benefit eb ON b.id=eb.benefit_id WHERE eb.employee_id=?",[$empId]):[];
$editId=(int)($_GET["id"]??0);$es=$editId?DB::fetchOne("SELECT * FROM benefit WHERE id=?",[$editId]):null;
require_once __DIR__ . "/../includes/layout_header.php";
?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
<h1 style="font-size:24px;font-weight:700;margin:0;">Benefits</h1>
<?php if($isAdmin): ?><a href="<?php echo url("benefit",["action"=>"create"]); ?>" class="btn btn-accent">+ Add Benefit</a><?php endif; ?>
</div>
<?php if(($action==="create"||$action==="edit")&&$isAdmin): ?>
<div class="hrms-card"><div class="card-header"><h3><?php echo $es?"Edit":"New"; ?> Benefit</h3></div><div class="card-body">
<form method="POST"><input type="hidden" name="_action" value="save"><?php if($es): ?><input type="hidden" name="id" value="<?php echo $es["id"]; ?>"><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Name</label><input type="text" name="name" value="<?php echo e($es["name"]??""); ?>" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Type</label><input type="text" name="type" value="<?php echo e($es["type"]??""); ?>" placeholder="Health, Insurance..." style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Value</label><input type="number" name="value" value="<?php echo e((string)($es["value"]??0)); ?>" step="0.01" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div style="grid-column:1/-1;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Description</label><textarea name="description" rows="3" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><?php echo e($es["description"]??""); ?></textarea></div>
</div><div style="margin-top:16px;display:flex;gap:12px;"><button type="submit" class="btn btn-accent">Save</button><a href="<?php echo url("benefit"); ?>" class="btn btn-outline">Cancel</a></div>
</form></div></div>
<?php else: ?>
<?php if($isAdmin&&!empty($employees)&&!empty($benefits)): ?>
<div class="hrms-card" style="margin-bottom:16px;padding:16px;"><h3 style="margin:0 0 12px;font-size:14px;">Enroll Employee in Benefit</h3>
<form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;"><input type="hidden" name="_action" value="enroll">
<select name="employee_id" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="">Select Employee</option><?php foreach($employees as $em): ?><option value="<?php echo $em["id"]; ?>"><?php echo e($em["first_name"]." ".$em["last_name"]); ?></option><?php endforeach; ?></select>
<select name="benefit_id" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="">Select Benefit</option><?php foreach($benefits as $b): ?><option value="<?php echo $b["id"]; ?>"><?php echo e($b["name"]); ?></option><?php endforeach; ?></select>
<button type="submit" class="btn btn-accent">Enroll</button></form></div>
<?php endif; ?>
<?php if(Auth::isEmployee()&&!empty($myBenefits)): ?>
<div class="hrms-card" style="margin-bottom:16px;"><div class="card-header"><h3>My Benefits</h3></div><div class="card-body"><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
<?php foreach($myBenefits as $b): ?><div style="padding:14px;background:rgba(124,147,104,0.1);border:1px solid rgba(124,147,104,0.3);border-radius:8px;"><div style="font-weight:600;font-size:14px;margin-bottom:4px;"><?php echo e($b["name"]); ?></div><div style="font-size:12px;color:var(--text-muted);"><?php echo e($b["type"]??""); ?></div></div><?php endforeach; ?>
</div></div></div>
<?php endif; ?>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
<?php foreach($benefits as $b): ?>
<div class="hrms-card" style="padding:20px;"><div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;"><h3 style="margin:0;font-size:15px;font-weight:700;"><?php echo e($b["name"]); ?></h3><?php if($isAdmin): ?><a href="<?php echo url("benefit",["action"=>"edit","id"=>$b["id"]]); ?>" class="btn btn-sm btn-outline" style="padding:3px 10px;">Edit</a><?php endif; ?></div>
<p style="color:var(--text-muted);font-size:12px;margin:0 0 8px;"><?php echo e($b["description"]??"—"); ?></p>
<div style="display:flex;justify-content:space-between;font-size:12px;"><span style="color:var(--text-muted);"><?php echo e($b["type"]??"General"); ?></span><?php if($b["value"]>0): ?><span style="color:var(--accent);font-weight:600;">&#8369;<?php echo number_format($b["value"],2); ?></span><?php endif; ?></div>
<div style="margin-top:8px;font-size:11px;color:var(--text-muted);">&#128101; <?php echo $b["enrolled"]; ?> enrolled</div></div>
<?php endforeach; if(empty($benefits)): ?><div style="grid-column:1/-1;text-align:center;color:var(--text-muted);padding:40px;">No benefits configured.</div><?php endif; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>

