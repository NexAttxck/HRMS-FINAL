<?php
require_once __DIR__ . "/../config.php"; require_once __DIR__ . "/../includes/db.php"; require_once __DIR__ . "/../includes/auth.php";
Auth::check(); $pageTitle="Projects — ".APP_NAME; $isAdmin=Auth::isAdmin(); $isManager=Auth::isManager(); $empId=Auth::empId(); $userId=Auth::id(); $action=$_GET["action"]??"index";
if($_SERVER["REQUEST_METHOD"]==="POST"){$a=$_POST["_action"]??"";
    if(($isAdmin||$isManager)&&$a==="saveProject"){$id=(int)($_POST["id"]??0);
        if($id)DB::execute("UPDATE project SET name=?,description=?,department_id=?,start_date=?,deadline=?,status=?,updated_at=? WHERE id=?",[$_POST["name"],$_POST["description"]??null,$_POST["department_id"]??null,$_POST["start_date"]??null,$_POST["deadline"]??null,$_POST["status"]??"Active",time(),$id]);
        else{$pid=DB::insert("INSERT INTO project (name,owner_id,department_id,description,start_date,deadline,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)",[$_POST["name"],$empId,$_POST["department_id"]??null,$_POST["description"]??null,$_POST["start_date"]??null,$_POST["deadline"]??null,$_POST["status"]??"Active",time(),time()]);
            if(!empty($_POST["members"])){foreach((array)$_POST["members"] as $mid){DB::execute("INSERT IGNORE INTO project_member (project_id,employee_id) VALUES (?,?)",[$pid,$mid]);}}}
        Auth::flash("success","Project saved!"); header("Location: ".url("project")); exit;}
    if(($isAdmin||$isManager)&&$a==="saveTask"){
        DB::insert("INSERT INTO project_task (project_id,employee_id,title,description,priority,status,progress,due_date,created_at) VALUES (?,?,?,?,?,?,0,?,?)",[$_POST["project_id"],$_POST["employee_id"]??null,$_POST["title"],$_POST["description"]??null,$_POST["priority"]??"Medium",$_POST["status"]??"To Do",$_POST["due_date"]??null,time()]);
        Auth::flash("success","Task added!"); header("Location: ".url("project",["action"=>"view","id"=>$_POST["project_id"]])); exit;}
    if($a==="updateTask"){DB::execute("UPDATE project_task SET status=?,progress=?,updated_at=? WHERE id=?",[$_POST["status"],$_POST["progress"]??0,time(),(int)$_POST["id"]]);Auth::flash("success","Task updated!");header("Location: ".url("project",["action"=>"view","id"=>$_POST["project_id"]]));exit;}
}
$depts=DB::fetchAll("SELECT id,name FROM department ORDER BY name");
$employees=DB::fetchAll("SELECT id,first_name,last_name FROM employee WHERE status IN ('Regular','Probationary') ORDER BY first_name");
$viewId=(int)($_GET["id"]??0);
if($action==="view"&&$viewId){
    $project=DB::fetchOne("SELECT p.*,d.name as dept_name FROM project p LEFT JOIN department d ON p.department_id=d.id WHERE p.id=?",[$viewId]);
    $tasks=DB::fetchAll("SELECT pt.*,e.first_name,e.last_name FROM project_task pt LEFT JOIN employee e ON pt.employee_id=e.id WHERE pt.project_id=? ORDER BY FIELD(pt.status,'To Do','In Progress','Completed','Blocked'),pt.due_date",[$viewId]);
    $members=DB::fetchAll("SELECT e.* FROM project_member pm JOIN employee e ON pm.employee_id=e.id WHERE pm.project_id=?",[$viewId]);
} else {
    $projects=DB::fetchAll("SELECT p.*,d.name as dept_name,(SELECT COUNT(*) FROM project_task WHERE project_id=p.id) as task_count,(SELECT COUNT(*) FROM project_member WHERE project_id=p.id) as member_count FROM project p LEFT JOIN department d ON p.department_id=d.id ORDER BY p.status,p.deadline");
}
require_once __DIR__ . "/../includes/layout_header.php"; ?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
<h1 style="font-size:24px;font-weight:700;margin:0;"><?php echo ($action==="view"&&isset($project))?e($project["name"]):"Projects"; ?></h1>
<div style="display:flex;gap:8px;"><?php if($action==="view"): ?><a href="<?php echo url("project"); ?>" class="btn btn-outline">&larr; Back</a><?php else: ?><?php if($isAdmin||$isManager): ?><a href="<?php echo url("project",["action"=>"create"]); ?>" class="btn btn-accent">+ New Project</a><?php endif; ?><?php endif; ?></div>
</div>
<?php if(($action==="create"||$action==="edit")&&($isAdmin||$isManager)):$editId=(int)($_GET["id"]??0);$es=$editId?DB::fetchOne("SELECT * FROM project WHERE id=?",[$editId]):null; ?>
<div class="hrms-card"><div class="card-header"><h3><?php echo $es?"Edit":"New"; ?> Project</h3></div><div class="card-body">
<form method="POST"><input type="hidden" name="_action" value="saveProject"><?php if($es): ?><input type="hidden" name="id" value="<?php echo $es["id"]; ?>"><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
<div style="grid-column:1/-1;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Project Name</label><input type="text" name="name" value="<?php echo e($es["name"]??""); ?>" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Department</label><select name="department_id" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="">All</option><?php foreach($depts as $d): ?><option value="<?php echo $d["id"]; ?>" <?php echo ($es["department_id"]??"")==$d["id"]?"selected":""; ?>><?php echo e($d["name"]); ?></option><?php endforeach; ?></select></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Status</label><select name="status" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><?php foreach(["Active","On Hold","Completed"] as $s): ?><option value="<?php echo $s; ?>" <?php echo ($es["status"]??"Active")===$s?"selected":""; ?>><?php echo $s; ?></option><?php endforeach; ?></select></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Start Date</label><input type="date" name="start_date" value="<?php echo e($es["start_date"]??date("Y-m-d")); ?>" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Deadline</label><input type="date" name="deadline" value="<?php echo e($es["deadline"]??""); ?>" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div style="grid-column:1/-1;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Description</label><textarea name="description" rows="3" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><?php echo e($es["description"]??""); ?></textarea></div>
</div><div style="margin-top:16px;display:flex;gap:12px;"><button type="submit" class="btn btn-accent">Save Project</button><a href="<?php echo url("project"); ?>" class="btn btn-outline">Cancel</a></div>
</form></div></div>
<?php elseif($action==="view"&&isset($project)): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
<div class="hrms-card" style="padding:20px;"><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Status</p><span class="badge <?php echo $project["status"]==="Active"?"badge-success":($project["status"]==="Completed"?"badge-info":"badge-warning"); ?>"><?php echo $project["status"]; ?></span><p style="font-size:12px;color:var(--text-muted);margin:12px 0 4px;">Deadline</p><p style="font-weight:700;margin:0;"><?php echo $project["deadline"]?date("M j, Y",strtotime($project["deadline"])):"—"; ?></p></div>
<div class="hrms-card" style="padding:20px;"><p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;">Members</p><div style="display:flex;flex-wrap:wrap;gap:6px;"><?php foreach($members as $m): ?><span style="padding:3px 10px;background:rgba(124,147,104,0.15);border-radius:12px;font-size:12px;"><?php echo e($m["first_name"]." ".$m["last_name"]); ?></span><?php endforeach; if(empty($members)): ?><span style="color:var(--text-muted);font-size:12px;">No members</span><?php endif; ?></div></div>
</div>
<?php if($isAdmin||$isManager): ?>
<div class="hrms-card" style="margin-bottom:16px;"><div class="card-header"><h3>Add Task</h3></div><div class="card-body">
<form method="POST" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:12px;align-items:flex-end;"><input type="hidden" name="_action" value="saveTask"><input type="hidden" name="project_id" value="<?php echo $viewId; ?>">
<div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Task Title</label><input type="text" name="title" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Assignee</label><select name="employee_id" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="">Unassigned</option><?php foreach($employees as $em): ?><option value="<?php echo $em["id"]; ?>"><?php echo e($em["first_name"]." ".$em["last_name"]); ?></option><?php endforeach; ?></select></div>
<div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Priority</label><select name="priority" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option>Low</option><option selected>Medium</option><option>High</option></select></div>
<div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Due Date</label><input type="date" name="due_date" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><button type="submit" class="btn btn-accent" style="white-space:nowrap;">Add Task</button></div>
</form></div></div>
<?php endif; ?>
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;">
<thead><tr><th>Task</th><th>Assignee</th><th>Priority</th><th>Status</th><th>Due</th><?php if($isAdmin||$isManager): ?><th>Update</th><?php endif; ?></tr></thead><tbody>
<?php $pc=["High"=>"#ef5350","Medium"=>"#fb8c00","Low"=>"#66bb6a"]; foreach($tasks as $t): ?>
<tr><td style="font-weight:600;"><?php echo e($t["title"]); ?></td>
<td><?php echo $t["first_name"]?e($t["first_name"]." ".$t["last_name"]):"—"; ?></td>
<td><span style="color:<?php echo $pc[$t["priority"]]??"#fff"; ?>;font-size:12px;font-weight:600;"><?php echo $t["priority"]; ?></span></td>
<td><span class="badge <?php echo $t["status"]==="Completed"?"badge-success":($t["status"]==="In Progress"?"badge-warning":($t["status"]==="Blocked"?"badge-danger":"badge-muted")); ?>"><?php echo $t["status"]; ?></span></td>
<td style="font-size:12px;color:var(--text-muted);"><?php echo $t["due_date"]?date("M j",strtotime($t["due_date"])):"—"; ?></td>
<?php if($isAdmin||$isManager): ?><td><form method="POST" style="display:flex;gap:6px;"><input type="hidden" name="_action" value="updateTask"><input type="hidden" name="id" value="<?php echo $t["id"]; ?>"><input type="hidden" name="project_id" value="<?php echo $viewId; ?>"><select name="status" style="padding:4px 8px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;"><?php foreach(["To Do","In Progress","Completed","Blocked"] as $s): ?><option value="<?php echo $s; ?>" <?php echo $t["status"]===$s?"selected":""; ?>><?php echo $s; ?></option><?php endforeach; ?></select><button type="submit" class="btn btn-sm btn-accent" style="padding:4px 8px;">&#10003;</button></form></td><?php endif; ?>
</tr><?php endforeach; if(empty($tasks)): ?><tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">No tasks yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
<?php foreach($projects as $p): $sc=$p["status"]==="Active"?"badge-success":($p["status"]==="Completed"?"badge-info":"badge-warning"); ?>
<div class="hrms-card" style="padding:20px;">
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;"><h3 style="margin:0;font-size:14px;font-weight:700;"><?php echo e($p["name"]); ?></h3><span class="badge <?php echo $sc; ?>"><?php echo $p["status"]; ?></span></div>
<p style="color:var(--text-muted);font-size:12px;margin:0 0 10px;"><?php echo e(substr($p["description"]??"",0,80)); ?></p>
<div style="display:flex;gap:16px;font-size:11px;color:var(--text-muted);margin-bottom:12px;"><span>&#128196; <?php echo $p["task_count"]; ?> tasks</span><span>&#128101; <?php echo $p["member_count"]; ?> members</span><?php if($p["deadline"]): ?><span>&#128197; <?php echo date("M j",strtotime($p["deadline"])); ?></span><?php endif; ?></div>
<a href="<?php echo url("project",["action"=>"view","id"=>$p["id"]]); ?>" class="btn btn-sm btn-outline btn-block">View Project</a>
</div>
<?php endforeach; if(empty($projects)): ?><div style="grid-column:1/-1;text-align:center;color:var(--text-muted);padding:40px;">No projects yet.</div><?php endif; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>

