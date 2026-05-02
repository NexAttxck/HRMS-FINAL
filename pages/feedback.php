<?php
require_once __DIR__ . "/../config.php"; require_once __DIR__ . "/../includes/db.php"; require_once __DIR__ . "/../includes/auth.php";
Auth::check(); $pageTitle="Feedback — ".APP_NAME; $empId=Auth::empId(); $isAdmin=Auth::isAdmin();
if($_SERVER["REQUEST_METHOD"]==="POST"){$a=$_POST["_action"]??"";
    if($a==="send"){$anon=(int)($_POST["is_anonymous"]??0);DB::insert("INSERT INTO feedback (sender_id,receiver_id,message,category,is_anonymous,created_at) VALUES (?,?,?,?,?,?)",[$empId,(int)$_POST["receiver_id"],$_POST["message"]??null,$_POST["category"]??"positive",$anon,time()]);Auth::flash("success","Feedback sent!");header("Location: ".url("feedback"));exit;}
}
$received=$empId?DB::fetchAll("SELECT f.*,CASE WHEN f.is_anonymous=1 THEN 'Anonymous' ELSE CONCAT(e.first_name,' ',e.last_name) END as sender_name FROM feedback f LEFT JOIN employee e ON f.sender_id=e.id WHERE f.receiver_id=? ORDER BY f.created_at DESC",[$empId]):[];
$sent=$empId?DB::fetchAll("SELECT f.*,CONCAT(e.first_name,' ',e.last_name) as receiver_name FROM feedback f JOIN employee e ON f.receiver_id=e.id WHERE f.sender_id=? ORDER BY f.created_at DESC",[$empId]):[];
$all=$isAdmin?DB::fetchAll("SELECT f.*,CONCAT(se.first_name,' ',se.last_name) as sender_name,CONCAT(re.first_name,' ',re.last_name) as receiver_name FROM feedback f LEFT JOIN employee se ON f.sender_id=se.id LEFT JOIN employee re ON f.receiver_id=re.id ORDER BY f.created_at DESC"):[];
$employees=DB::fetchAll("SELECT id,first_name,last_name FROM employee WHERE status IN ('Regular','Probationary') AND id!=? ORDER BY first_name",[$empId??0]);
$catColors=["positive"=>"#66bb6a","constructive"=>"#fb8c00","suggestion"=>"#4da6ff"];
require_once __DIR__ . "/../includes/layout_header.php"; ?>
<div style="margin-bottom:20px;"><h1 style="font-size:24px;font-weight:700;margin:0;">Feedback</h1></div>
<div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;">
<div>
<div class="hrms-card" style="margin-bottom:16px;"><div class="card-header"><h3>Send Feedback</h3></div><div class="card-body">
<form method="POST"><input type="hidden" name="_action" value="send">
<div style="margin-bottom:12px;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">To</label><select name="receiver_id" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="">Select Employee</option><?php foreach($employees as $em): ?><option value="<?php echo $em["id"]; ?>"><?php echo e($em["first_name"]." ".$em["last_name"]); ?></option><?php endforeach; ?></select></div>
<div style="margin-bottom:12px;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Category</label><select name="category" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="positive">Positive</option><option value="constructive">Constructive</option><option value="suggestion">Suggestion</option></select></div>
<div style="margin-bottom:12px;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Message</label><textarea name="message" rows="4" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></textarea></div>
<div style="margin-bottom:12px;"><label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;"><input type="checkbox" name="is_anonymous" value="1"> Send Anonymously</label></div>
<button type="submit" class="btn btn-accent btn-block">Send Feedback</button>
</form></div></div>
</div>
<div>
<div class="hrms-card" style="margin-bottom:16px;"><div class="card-header"><h3><?php echo $isAdmin?"All Feedback":"Received Feedback"; ?></h3></div><div class="card-body" style="display:flex;flex-direction:column;gap:12px;max-height:400px;overflow-y:auto;">
<?php $list=$isAdmin?$all:$received; foreach($list as $f): $cc=$catColors[$f["category"]]??"#fff"; ?>
<div style="padding:14px;border-left:3px solid <?php echo $cc; ?>;background:rgba(255,255,255,0.03);border-radius:0 8px 8px 0;">
<div style="display:flex;justify-content:space-between;margin-bottom:6px;"><span style="font-size:12px;font-weight:600;color:<?php echo $cc; ?>;"><?php echo ucfirst($f["category"]); ?></span><span style="font-size:11px;color:var(--text-muted);"><?php echo date("M j, Y",strtotime($f["created_at"])); ?></span></div>
<p style="margin:0 0 6px;font-size:13px;"><?php echo nl2br(e($f["message"]??"")); ?></p>
<span style="font-size:11px;color:var(--text-muted);">From: <?php echo e($f["sender_name"]??"Anonymous"); ?><?php if($isAdmin): ?> → <?php echo e($f["receiver_name"]??"—"); ?><?php endif; ?></span>
</div><?php endforeach; if(empty($list)): ?><p style="text-align:center;color:var(--text-muted);padding:20px;">No feedback yet.</p><?php endif; ?>
</div></div>
<?php if(!$isAdmin&&!empty($sent)): ?><div class="hrms-card"><div class="card-header"><h3>Sent Feedback</h3></div><div class="card-body" style="display:flex;flex-direction:column;gap:10px;max-height:280px;overflow-y:auto;">
<?php foreach($sent as $f): $cc=$catColors[$f["category"]]??"#fff"; ?>
<div style="padding:12px;border-left:3px solid <?php echo $cc; ?>;background:rgba(255,255,255,0.03);border-radius:0 8px 8px 0;">
<div style="font-size:12px;font-weight:600;margin-bottom:4px;color:<?php echo $cc; ?>;"><?php echo ucfirst($f["category"]); ?> → <?php echo e($f["receiver_name"]); ?></div>
<p style="margin:0;font-size:12px;color:var(--text-muted);"><?php echo e(substr($f["message"]??"",0,80)); ?></p></div>
<?php endforeach; ?></div></div><?php endif; ?>
</div></div>
<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>

