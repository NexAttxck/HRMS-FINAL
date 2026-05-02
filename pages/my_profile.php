<?php
require_once __DIR__ . "/../config.php"; require_once __DIR__ . "/../includes/db.php"; require_once __DIR__ . "/../includes/auth.php";
Auth::check(); $pageTitle="My Profile — ".APP_NAME; $empId=Auth::empId(); $userId=Auth::id();
if($_SERVER["REQUEST_METHOD"]==="POST"){
    if($empId)DB::execute("UPDATE employee SET phone=?,address=?,emergency_contact_name=?,updated_at=? WHERE id=?",[$_POST["phone"]??null,$_POST["address"]??null,$_POST["emergency_contact"]??null,time(),$empId]);
    if(!empty($_POST["new_password"])&&$_POST["new_password"]===$_POST["confirm_password"])DB::execute("UPDATE `user` SET password=? WHERE id=?",[md5($_POST["new_password"]),$userId]);
    Auth::flash("success","Profile updated!"); header("Location: ".url("my_profile")); exit;
}
$emp=$empId?DB::fetchOne("SELECT e.*,d.name as dept_name FROM employee e LEFT JOIN department d ON e.department_id=d.id WHERE e.id=?",[$empId]):null;
$user=DB::fetchOne("SELECT * FROM `user` WHERE id=?",[$userId]);
require_once __DIR__ . "/../includes/layout_header.php"; ?>
<div style="margin-bottom:20px;"><h1 style="font-size:24px;font-weight:700;margin:0;">My Profile</h1></div>
<div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;">
<div class="hrms-card" style="padding:24px;text-align:center;height:fit-content;">
    <div style="width:80px;height:80px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:#fff;margin:0 auto 16px;">
        <?php echo strtoupper(substr($emp["first_name"]??Auth::name(),0,1).substr($emp["last_name"]??"",0,1)); ?>
    </div>
    <h3 style="margin:0 0 4px;"><?php echo $emp?e($emp["first_name"]." ".$emp["last_name"]):e(Auth::name()); ?></h3>
    <p style="color:var(--text-muted);font-size:13px;margin:0 0 6px;"><?php echo e($emp["job_title"]??"—"); ?></p>
    <p style="color:var(--text-muted);font-size:12px;margin:0 0 12px;"><?php echo e($emp["dept_name"]??"—"); ?></p>
    <span class="badge badge-success"><?php echo e($emp["status"]??"Active"); ?></span>
    <div style="margin-top:16px;border-top:1px solid var(--border);padding-top:16px;text-align:left;">
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;">Employee No.</div>
        <div style="font-weight:600;"><?php echo e($emp["employee_no"]??"—"); ?></div>
        <div style="font-size:12px;color:var(--text-muted);margin:8px 0 4px;">Hire Date</div>
        <div style="font-weight:600;"><?php echo $emp["hire_date"]?date("M j, Y",strtotime($emp["hire_date"])):"—"; ?></div>
        <div style="font-size:12px;color:var(--text-muted);margin:8px 0 4px;">Email</div>
        <div style="font-weight:600;font-size:12px;"><?php echo e($user["email"]??"—"); ?></div>
    </div>
</div>
<div>
    <div class="hrms-card" style="margin-bottom:16px;"><div class="card-header"><h3>Government IDs</h3></div><div class="card-body">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
    <?php $govIds=[["SSS No.",$emp["sss_no"]??"—"],["PhilHealth No.",$emp["philhealth_no"]??"—"],["Pag-IBIG No.",$emp["pagibig_no"]??"—"],["TIN",$emp["tin_no"]??"—"]];
    foreach($govIds as $g): ?><div><p style="font-size:11px;color:var(--text-muted);margin:0 0 2px;text-transform:uppercase;"><?php echo $g[0]; ?></p><p style="font-size:14px;font-weight:600;margin:0;"><?php echo e($g[1]); ?></p></div><?php endforeach; ?>
    </div></div></div>
    <div class="hrms-card"><div class="card-header"><h3>Update Contact Info</h3></div><div class="card-body">
    <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
    <div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Phone</label><input type="text" name="phone" value="<?php echo e($emp["phone"]??""); ?>" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
    <div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Emergency Contact</label><input type="text" name="emergency_contact" value="<?php echo e($emp["emergency_contact_name"]??""); ?>" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
    <div style="grid-column:1/-1;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Address</label><textarea name="address" rows="2" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><?php echo e($emp["address"]??""); ?></textarea></div>
    <div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">New Password</label><input type="password" name="new_password" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
    <div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Confirm Password</label><input type="password" name="confirm_password" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
    </div><div style="margin-top:16px;"><button type="submit" class="btn btn-accent">Save Changes</button></div>
    </form></div></div>
</div></div>
<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>
