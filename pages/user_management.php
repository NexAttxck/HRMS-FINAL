<?php
require_once __DIR__ . "/../config.php"; require_once __DIR__ . "/../includes/db.php"; require_once __DIR__ . "/../includes/auth.php";
Auth::check(); Auth::requireRole(["Super Admin"]); $pageTitle="User Management — ".APP_NAME;
if($_SERVER["REQUEST_METHOD"]==="POST"){$a=$_POST["_action"]??"";
    if($a==="save"){$id=(int)($_POST["id"]??0);
        if($id){
            $deptId=!empty($_POST["department_id"])?$_POST["department_id"]:null;
            DB::execute("UPDATE `user` SET username=?,email=?,role=?,status=?,department_id=? WHERE id=?",[$_POST["username"],$_POST["email"],$_POST["role"],$_POST["status"],$deptId,$id]);
            if(!empty($_POST["password"]))DB::execute("UPDATE `user` SET password=? WHERE id=?",[md5($_POST["password"]),$id]);
            // Keep employee record in sync
            DB::execute("UPDATE employee SET email=?,department_id=? WHERE user_id=?",[$_POST["email"],$deptId,$id]);
        } else {
            $deptId=!empty($_POST["department_id"])?$_POST["department_id"]:null;
            $newUid=DB::insert("INSERT INTO `user` (username,email,password,role,status,department_id,created_at) VALUES (?,?,?,?,?,?,?)",[$_POST["username"],$_POST["email"],md5($_POST["password"]??"Password123"),$_POST["role"]??"Employee",$_POST["status"]??"Active",$deptId,time()]);
            // Auto-create skeleton employee record so user appears in all HR modules
            $parts=explode('.',$_POST["username"],2);
            $firstName=ucfirst($parts[0]??$_POST["username"]);
            $lastName=ucfirst($parts[1]??"");
            $newEmpRowId = DB::insert("INSERT INTO employee (user_id,first_name,last_name,email,department_id,status,employment_type,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)",[$newUid,$firstName,$lastName,$_POST["email"],$deptId,'Probationary','Full-Time',time(),time()]);
            // Seed required document checklist for new employee
            $existing=(int)DB::fetchScalar("SELECT COUNT(*) FROM employee_doc_checklist WHERE employee_id=?",[$newEmpRowId]);
            if(!$existing){$docs=['Employment Contract','SSS E1 Form / SS Card','PhilHealth MDR','Pag-IBIG MDF','BIR Form 2316 / TIN Card','NBI Clearance','Birth Certificate (PSA)','Medical Certificate','Diploma / Transcript of Records','2×2 ID Photo'];foreach($docs as $doc)DB::insert("INSERT INTO employee_doc_checklist (employee_id,document_type,status,created_at,updated_at) VALUES (?,?,'Pending',?,?)",[$newEmpRowId,$doc,time(),time()]);}
        }
        Auth::audit($id ? 'Update User' : 'Create User', 'User Management', $id ?: null, ($_POST['username'] ?? '') . ' (' . ($_POST['role'] ?? '') . ')');
        Auth::flash("success","User saved!"); header("Location: ".url("user_management")); exit;}
    if($a==="reset_password"){$rpId=(int)$_POST["id"];$newPw=trim($_POST["new_password"]??"");
        if($rpId&&strlen($newPw)>=6){
            DB::execute("UPDATE `user` SET password=? WHERE id=?",[md5($newPw),$rpId]);
            Auth::audit('Reset Password','User Management',$rpId,'Password reset by admin');
            Auth::flash("success","Password reset successfully.");
        } else { Auth::flash("error","Password must be at least 6 characters."); }
        header("Location: ".url("user_management")); exit;}
    if($a==="delete"){$delUid=(int)$_POST["id"];Auth::audit('Delete User','User Management',$delUid);
        // Remove linked employee record first to avoid FK orphans
        DB::execute("DELETE FROM employee WHERE user_id=?",[$delUid]);
        DB::execute("DELETE FROM `user` WHERE id=?",[$delUid]);
        Auth::flash("success","User deleted.");header("Location: ".url("user_management"));exit;}
}
$users=DB::fetchAll("SELECT u.*,d.name as dept_name FROM `user` u LEFT JOIN department d ON u.department_id=d.id ORDER BY u.role,u.username");
$depts=DB::fetchAll("SELECT id,name FROM department ORDER BY name");
$action=$_GET["action"]??"index"; $editId=(int)($_GET["id"]??0); $es=$editId?DB::fetchOne("SELECT * FROM `user` WHERE id=?",[$editId]):null;
require_once __DIR__ . "/../includes/layout_header.php"; ?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
<h1 style="font-size:24px;font-weight:700;margin:0;">User Management</h1>
<a href="<?php echo url("user_management",["action"=>"create"]); ?>" class="btn btn-accent">+ Add User</a>
</div>
<?php if($action==="create"||$action==="edit"): ?>
<div class="hrms-card"><div class="card-header"><h3><?php echo $es?"Edit":"New"; ?> User</h3></div><div class="card-body">
<form method="POST"><input type="hidden" name="_action" value="save"><?php if($es): ?><input type="hidden" name="id" value="<?php echo $es["id"]; ?>"><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Username</label><input type="text" name="username" value="<?php echo e($es["username"]??""); ?>" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Email</label><input type="email" name="email" value="<?php echo e($es["email"]??""); ?>" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Password <?php echo $es?"(blank=unchanged)":""; ?></label><input type="password" name="password" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Role</label><select name="role" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><?php foreach(["Super Admin","Manager","Employee"] as $r): ?><option value="<?php echo $r; ?>" <?php echo ($es["role"]??"")===$r?"selected":""; ?>><?php echo $r; ?></option><?php endforeach; ?></select></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Status</label><select name="status" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="Active" <?php echo ($es["status"]??"")==="Active"?"selected":""; ?>>Active</option><option value="Inactive" <?php echo ($es["status"]??"")==="Inactive"?"selected":""; ?>>Inactive</option></select></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Department</label><select name="department_id" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="">None</option><?php foreach($depts as $d): ?><option value="<?php echo $d["id"]; ?>" <?php echo ($es["department_id"]??"")==$d["id"]?"selected":""; ?>><?php echo e($d["name"]); ?></option><?php endforeach; ?></select></div>
</div><div style="margin-top:16px;display:flex;gap:12px;"><button type="submit" class="btn btn-accent">Save User</button><a href="<?php echo url("user_management"); ?>" class="btn btn-outline">Cancel</a></div>
</form></div></div>
<?php else: ?>
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;">
<thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Department</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead><tbody>
<?php foreach($users as $u): $rc=["Super Admin"=>"#ef5350","Manager"=>"#fb8c00","Employee"=>"#66bb6a"][$u["role"]]??"#fff"; ?>
<tr><td style="font-weight:600;"><?php echo e($u["username"]); ?></td><td><?php echo e($u["email"]); ?></td>
<td><span style="padding:2px 10px;background:<?php echo $rc; ?>20;color:<?php echo $rc; ?>;border-radius:12px;font-size:12px;font-weight:600;border:1px solid <?php echo $rc; ?>40;"><?php echo e($u["role"]); ?></span></td>
<td><?php echo e($u["dept_name"]??"—"); ?></td>
<td><span class="badge <?php echo $u["status"]==="Active"?"badge-success":"badge-danger"; ?>"><?php echo $u["status"]; ?></span></td>
<td style="text-align:right;display:flex;gap:6px;justify-content:flex-end;align-items:center;">
<a href="<?php echo url("user_management",["action"=>"edit","id"=>$u["id"]]); ?>" class="btn btn-sm btn-outline" style="padding:3px 10px;">Edit</a>
<button type="button" class="btn btn-sm btn-outline" style="padding:3px 10px;color:#4da6ff;" onclick="toggleReset(<?php echo $u['id']; ?>)">&#128273; Reset PW</button>
<form method="POST" style="display:inline;"><input type="hidden" name="_action" value="delete"><input type="hidden" name="id" value="<?php echo $u["id"]; ?>"><button type="submit" class="btn btn-sm btn-outline text-danger" style="padding:3px 10px;" onclick="return confirm('Delete this user and their employee record?')">Del</button></form>
</td></tr>
<!-- Inline Reset Password Row -->
<tr id="reset-row-<?php echo $u['id']; ?>" style="display:none;background:rgba(77,166,255,0.06);">
<td colspan="6" style="padding:12px 20px;">
    <form method="POST" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="hidden" name="_action" value="reset_password">
        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
        <span style="font-size:12px;font-weight:600;color:#4da6ff;">&#128273; Reset password for <strong><?php echo e($u['username']); ?></strong>:</span>
        <input type="password" name="new_password" placeholder="New password (min 6 chars)" required minlength="6" style="padding:7px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;min-width:220px;">
        <button type="submit" class="btn btn-sm" style="background:#4da6ff;color:#fff;padding:6px 14px;">Set Password</button>
        <button type="button" onclick="toggleReset(<?php echo $u['id']; ?>)" class="btn btn-sm btn-outline" style="padding:6px 10px;">Cancel</button>
    </form>
</td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php endif; ?>
<script>
function toggleReset(id) {
    var row = document.getElementById('reset-row-' + id);
    if (!row) return;
    var open = row.style.display !== 'none';
    // Close all open reset rows first
    document.querySelectorAll('[id^="reset-row-"]').forEach(function(r){ r.style.display='none'; });
    if (!open) { row.style.display = 'table-row'; row.querySelector('input[type=password]').focus(); }
}
</script>
<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>

