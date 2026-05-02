<?php
require_once __DIR__ . "/../config.php"; require_once __DIR__ . "/../includes/db.php"; require_once __DIR__ . "/../includes/auth.php";
Auth::check(); $pageTitle="Holidays — ".APP_NAME; $isAdmin=Auth::isAdmin();
if($isAdmin&&$_SERVER["REQUEST_METHOD"]==="POST"){$a=$_POST["_action"]??"";
    if($a==="save"){$id=(int)($_POST["id"]??0);
        if($id)DB::execute("UPDATE holiday SET name=?,date=?,type=?,recurring=? WHERE id=?",[$_POST["name"],$_POST["date"],$_POST["type"]??"Regular",(int)($_POST["recurring"]??0),$id]);
        else DB::insert("INSERT INTO holiday (name,date,type,recurring,created_at) VALUES (?,?,?,?,?)",[$_POST["name"],$_POST["date"],$_POST["type"]??"Regular",(int)($_POST["recurring"]??0),time()]);
        Auth::flash("success","Holiday saved!"); header("Location: ".url("holiday")); exit;}
    if($a==="delete"){DB::execute("DELETE FROM holiday WHERE id=?",[(int)$_POST["id"]]);Auth::flash("success","Deleted.");header("Location: ".url("holiday"));exit;}
}
$year=(int)($_GET["year"]??date("Y"));
$holidays=DB::fetchAll("SELECT * FROM holiday WHERE YEAR(date)=? OR recurring=1 ORDER BY date",[$year]);
$action=$_GET["action"]??"index"; $editId=(int)($_GET["id"]??0); $es=$editId?DB::fetchOne("SELECT * FROM holiday WHERE id=?",[$editId]):null;
require_once __DIR__ . "/../includes/layout_header.php"; ?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
<h1 style="font-size:24px;font-weight:700;margin:0;">Holidays <?php echo $year; ?></h1>
<div style="display:flex;gap:8px;align-items:center;">
<a href="<?php echo url("holiday",["year"=>$year-1]); ?>" class="btn btn-sm btn-outline">&larr; <?php echo $year-1; ?></a>
<a href="<?php echo url("holiday",["year"=>$year+1]); ?>" class="btn btn-sm btn-outline"><?php echo $year+1; ?> &rarr;</a>
<?php if($isAdmin): ?><a href="<?php echo url("holiday",["action"=>"create"]); ?>" class="btn btn-accent">+ Add Holiday</a><?php endif; ?>
</div></div>
<?php if(($action==="create"||$action==="edit")&&$isAdmin): ?>
<div class="hrms-card"><div class="card-header"><h3><?php echo $es?"Edit":"New"; ?> Holiday</h3></div><div class="card-body">
<form method="POST"><input type="hidden" name="_action" value="save"><?php if($es): ?><input type="hidden" name="id" value="<?php echo $es["id"]; ?>"><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Holiday Name</label><input type="text" name="name" value="<?php echo e($es["name"]??""); ?>" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Date</label><input type="date" name="date" value="<?php echo e($es["date"]??""); ?>" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Type</label><select name="type" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="Regular" <?php echo ($es["type"]??"")==="Regular"?"selected":""; ?>>Regular Holiday</option><option value="Special" <?php echo ($es["type"]??"")==="Special"?"selected":""; ?>>Special Holiday</option></select></div>
<div style="display:flex;align-items:center;padding-top:20px;"><label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;"><input type="checkbox" name="recurring" value="1" <?php echo ($es["recurring"]??0)?"checked":""; ?>> Recurring annually</label></div>
</div><div style="margin-top:16px;display:flex;gap:12px;"><button type="submit" class="btn btn-accent">Save</button><a href="<?php echo url("holiday"); ?>" class="btn btn-outline">Cancel</a></div>
</form></div></div>
<?php else: ?>
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;">
<thead><tr><th>Date</th><th>Holiday</th><th>Type</th><th>Recurring</th><?php if($isAdmin): ?><th>Actions</th><?php endif; ?></tr></thead><tbody>
<?php foreach($holidays as $h): ?>
<tr><td style="font-weight:600;"><?php echo date("M j, Y",strtotime($h["date"])); ?></td><td><?php echo e($h["name"]); ?></td>
<td><span class="badge <?php echo $h["type"]==="Regular"?"badge-danger":"badge-warning"; ?>"><?php echo $h["type"]; ?></span></td>
<td><?php echo $h["recurring"]?"&#9989; Yes":"No"; ?></td>
<?php if($isAdmin): ?><td style="display:flex;gap:6px;">
<a href="<?php echo url("holiday",["action"=>"edit","id"=>$h["id"]]); ?>" class="btn btn-sm btn-outline" style="padding:3px 8px;">Edit</a>
<form method="POST" style="display:inline;"><input type="hidden" name="_action" value="delete"><input type="hidden" name="id" value="<?php echo $h["id"]; ?>"><button type="submit" class="btn btn-sm btn-outline text-danger" style="padding:3px 8px;" onclick="return confirm('Delete?')">Del</button></form>
</td><?php endif; ?></tr>
<?php endforeach; if(empty($holidays)): ?><tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted);">No holidays found for <?php echo $year; ?>.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php endif; ?>
<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>
