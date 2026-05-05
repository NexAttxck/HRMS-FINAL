<?php
require_once __DIR__ . "/../config.php"; require_once __DIR__ . "/../includes/db.php"; require_once __DIR__ . "/../includes/auth.php";
Auth::check(); $pageTitle="Recruitment — ".APP_NAME; $isAdmin=Auth::isAdmin(); $isManager=Auth::isManager(); $userId=Auth::id(); $action=$_GET["action"]??"index";
if($_SERVER["REQUEST_METHOD"]==="POST"){$a=$_POST["_action"]??"";
    if(($isAdmin||$isManager)&&$a==="saveJob"){$id=(int)($_POST["id"]??0);
        if($id)DB::execute("UPDATE job_posting SET title=?,description=?,requirements=?,salary_min=?,salary_max=?,location=?,type=?,status=?,department_id=?,updated_at=? WHERE id=?",[$_POST["title"],$_POST["description"]??null,$_POST["requirements"]??null,$_POST["salary_min"]??0,$_POST["salary_max"]??0,$_POST["location"]??null,$_POST["type"]??null,$_POST["status"]??"Draft",$_POST["department_id"]??null,time(),$id]);
        else DB::insert("INSERT INTO job_posting (title,department_id,description,requirements,salary_min,salary_max,location,type,status,posted_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",[$_POST["title"],$_POST["department_id"]??null,$_POST["description"]??null,$_POST["requirements"]??null,$_POST["salary_min"]??0,$_POST["salary_max"]??0,$_POST["location"]??null,$_POST["type"]??null,$_POST["status"]??"Draft",$userId,time(),time()]);
        Auth::flash("success","Job posting saved!"); header("Location: ".url("recruitment")); exit;}
    if($a==="saveCandidate"){$id=(int)($_POST["id"]??0);
        if($id)DB::execute("UPDATE candidate SET stage=?,notes=?,updated_at=? WHERE id=?",[$_POST["stage"],$_POST["notes"]??null,time(),$id]);
        else DB::insert("INSERT INTO candidate (job_id,name,email,phone,stage,expected_salary,notes,applied_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,CURDATE(),?,?)",[$_POST["job_id"],$_POST["name"],$_POST["email"]??null,$_POST["phone"]??null,$_POST["stage"]??"Applied",$_POST["expected_salary"]??0,$_POST["notes"]??null,time(),time()]);
        Auth::flash("success","Candidate saved!"); header("Location: ".url("recruitment",["action"=>"candidates","job"=>$_POST["job_id"]])); exit;}
}
$depts=DB::fetchAll("SELECT id,name FROM department ORDER BY name");
$jobId=(int)($_GET["job"]??0); $viewId=(int)($_GET["id"]??0);
if($action==="candidates"&&$jobId){$job=DB::fetchOne("SELECT * FROM job_posting WHERE id=?",[$jobId]);$candidates=DB::fetchAll("SELECT * FROM candidate WHERE job_id=? ORDER BY applied_at DESC",[$jobId]);}
else{$jobs=DB::fetchAll("SELECT jp.*,d.name as dept_name,(SELECT COUNT(*) FROM candidate WHERE job_id=jp.id) as cand_count FROM job_posting jp LEFT JOIN department d ON jp.department_id=d.id ORDER BY jp.created_at DESC");}
require_once __DIR__ . "/../includes/layout_header.php"; ?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
<h1 style="font-size:24px;font-weight:700;margin:0;"><?php echo $action==="candidates"&&isset($job)?"Candidates: ".e($job["title"]):"Recruitment"; ?></h1>
<div style="display:flex;gap:8px;"><?php if($action==="candidates"): ?><a href="<?php echo url("recruitment"); ?>" class="btn btn-outline">&larr; Jobs</a><?php if($isAdmin||$isManager): ?><a href="<?php echo url("recruitment",["action"=>"addCandidate","job"=>$jobId]); ?>" class="btn btn-accent">+ Add Candidate</a><?php endif; ?><?php else: ?><?php if($isAdmin||$isManager): ?><a href="<?php echo url("recruitment",["action"=>"createJob"]); ?>" class="btn btn-accent">+ Post Job</a><?php endif; ?><?php endif; ?></div>
</div>
<?php if($action==="createJob"&&($isAdmin||$isManager)): ?>
<div class="hrms-card"><div class="card-header"><h3>New Job Posting</h3></div><div class="card-body">
<form method="POST"><input type="hidden" name="_action" value="saveJob">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
<div style="grid-column:1/-1;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Job Title</label><input type="text" name="title" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Department</label><select name="department_id" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="">Any</option><?php foreach($depts as $d): ?><option value="<?php echo $d["id"]; ?>"><?php echo e($d["name"]); ?></option><?php endforeach; ?></select></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Status</label><select name="status" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="Open">Open</option><option value="Draft">Draft</option><option value="Closed">Closed</option></select></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Min Salary</label><input type="number" name="salary_min" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Max Salary</label><input type="number" name="salary_max" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Location</label><input type="text" name="location" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Type</label><input type="text" name="type" placeholder="Full-time, Part-time..." style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div style="grid-column:1/-1;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Description</label><textarea name="description" rows="3" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></textarea></div>
<div style="grid-column:1/-1;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Requirements</label><textarea name="requirements" rows="3" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></textarea></div>
</div><div style="margin-top:16px;display:flex;gap:12px;"><button type="submit" class="btn btn-accent">Post Job</button><a href="<?php echo url("recruitment"); ?>" class="btn btn-outline">Cancel</a></div>
</form></div></div>
<?php elseif($action==="candidates"&&isset($job)): ?>
<?php if(($isAdmin||$isManager)&&$action==="addCandidate"||isset($_GET["addcand"])): ?>
<div class="hrms-card" style="margin-bottom:16px;"><div class="card-header"><h3>Add Candidate</h3></div><div class="card-body">
<form method="POST"><input type="hidden" name="_action" value="saveCandidate"><input type="hidden" name="job_id" value="<?php echo $jobId; ?>">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Name</label><input type="text" name="name" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Email</label><input type="email" name="email" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Phone</label><input type="text" name="phone" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Stage</label><select name="stage" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><?php foreach(["Applied","Screening","Interview","Offer","Hired","Rejected"] as $s): ?><option><?php echo $s; ?></option><?php endforeach; ?></select></div>
</div><div style="margin-top:16px;display:flex;gap:12px;"><button type="submit" class="btn btn-accent">Add Candidate</button></div>
</form></div></div>
<?php endif; ?>
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;"><thead><tr><th>Name</th><th>Email</th><th>Stage</th><th>Applied</th><?php if($isAdmin||$isManager): ?><th>Update Stage</th><?php endif; ?></tr></thead><tbody>
<?php $stageColors=["Applied"=>"badge-muted","Screening"=>"badge-info","Interview"=>"badge-warning","Offer"=>"badge-success","Hired"=>"badge-success","Rejected"=>"badge-danger"];
foreach($candidates as $c): ?><tr><td style="font-weight:600;"><?php echo e($c["name"]); ?></td><td><?php echo e($c["email"]??""); ?></td><td><span class="badge <?php echo $stageColors[$c["stage"]]??"badge-muted"; ?>"><?php echo $c["stage"]; ?></span></td><td style="color:var(--text-muted);"><?php echo $c["applied_at"]?date("M j, Y",strtotime($c["applied_at"])):"—"; ?></td>
<?php if($isAdmin||$isManager): ?><td><form method="POST" style="display:flex;gap:6px;"><input type="hidden" name="_action" value="saveCandidate"><input type="hidden" name="id" value="<?php echo $c["id"]; ?>"><input type="hidden" name="job_id" value="<?php echo $jobId; ?>"><select name="stage" style="padding:4px 8px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;"><?php foreach(["Applied","Screening","Interview","Offer","Hired","Rejected"] as $s): ?><option value="<?php echo $s; ?>" <?php echo $c["stage"]===$s?"selected":""; ?>><?php echo $s; ?></option><?php endforeach; ?></select><button type="submit" class="btn btn-sm btn-accent" style="padding:4px 8px;">&#10003;</button></form></td><?php endif; ?>
</tr><?php endforeach; if(empty($candidates)): ?><tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted);">No candidates yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php else: ?>
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;"><thead><tr><th>Title</th><th>Department</th><th>Type</th><th>Salary</th><th>Status</th><th>Candidates</th><th>Actions</th></tr></thead><tbody>
<?php foreach($jobs as $j): $sc=$j["status"]==="Open"?"badge-success":($j["status"]==="Closed"?"badge-danger":"badge-muted"); ?>
<tr><td style="font-weight:600;"><?php echo e($j["title"]); ?></td><td><?php echo e($j["dept_name"]??""); ?></td><td><?php echo e($j["type"]??""); ?></td>
<td style="font-size:12px;">&#8369;<?php echo number_format($j["salary_min"],0); ?>–<?php echo number_format($j["salary_max"],0); ?></td>
<td><span class="badge <?php echo $sc; ?>"><?php echo $j["status"]; ?></span></td>
<td><?php echo $j["cand_count"]; ?></td>
<td><a href="<?php echo url("recruitment",["action"=>"candidates","job"=>$j["id"]]); ?>" class="btn btn-sm btn-outline" style="padding:3px 10px;">Candidates</a></td>
</tr><?php endforeach; if(empty($jobs)): ?><tr><td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted);">No job postings yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php endif; ?>
<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>
