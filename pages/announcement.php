<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
Auth::check();
$pageTitle='Announcements — '.APP_NAME; $isAdmin=Auth::isAdmin(); $isManager=Auth::isManager(); $action=$_GET['action']??'index'; $userId=Auth::id(); $deptId=Auth::deptId();
if($_SERVER['REQUEST_METHOD']==='POST'){
    $a=$_POST['_action']??'';
    if(($isAdmin||$isManager)&&$a==='save'){
        $id=(int)($_POST['id']??0); $cw=(int)($_POST['is_company_wide']??0);
        if($id)DB::execute("UPDATE announcement SET title=?,content=?,priority=?,pinned=?,is_company_wide=?,department_id=?,updated_at=? WHERE id=?",[$_POST['title'],$_POST['content']??null,$_POST['priority']??'Medium',(int)($_POST['pinned']??0),$cw,$cw?null:($deptId??null),time(),$id]);
        else DB::insert("INSERT INTO announcement (title,content,posted_by,priority,pinned,is_company_wide,department_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)",[$_POST['title'],$_POST['content']??null,$userId,$_POST['priority']??'Medium',(int)($_POST['pinned']??0),$cw,$cw?null:($deptId??null),time(),time()]);
        Auth::flash('success','Announcement saved!');header('Location: '.url('announcement'));exit;
    }
    if($isAdmin&&$a==='delete'){DB::execute("DELETE FROM announcement WHERE id=?",[(int)$_POST['id']]);Auth::flash('success','Deleted.');header('Location: '.url('announcement'));exit;}
}
$editId=(int)($_GET['id']??0); $es=$editId?DB::fetchOne("SELECT * FROM announcement WHERE id=?",[$editId]):null;
$anns=DB::fetchAll("SELECT a.*,u.username as poster FROM announcement a LEFT JOIN `user` u ON a.posted_by=u.id ORDER BY a.pinned DESC, a.created_at DESC");
require_once __DIR__ . '/../includes/layout_header.php';
?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <h1 style="font-size:24px;font-weight:700;margin:0;">Announcements</h1>
    <?php if($isAdmin||$isManager): ?><a href="<?php echo url('announcement',['action'=>'create']);?>" class="btn btn-accent">+ Post Announcement</a><?php endif;?>
</div>
<?php if(($action==='create'||$action==='edit')&&($isAdmin||$isManager)): ?>
<div class="hrms-card"><div class="card-header"><h3><?php echo $es?'Edit':'New Announcement';?></h3></div><div class="card-body">
<form method="POST"><input type="hidden" name="_action" value="save"><?php if($es): ?><input type="hidden" name="id" value="<?php echo $es['id'];?>"><?php endif;?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
<div style="grid-column:1/-1;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Title</label><input type="text" name="title" value="<?php echo e($es['title']??'');?>" required style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Priority</label><select name="priority" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><?php foreach(['Low','Medium','High','Urgent'] as $p): ?><option value="<?php echo $p;?>" <?php echo ($es['priority']??'Medium')===$p?'selected':'';?>><?php echo $p;?></option><?php endforeach;?></select></div>
<div style="display:flex;align-items:center;gap:12px;padding-top:20px;"><label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;"><input type="checkbox" name="pinned" value="1" <?php echo ($es['pinned']??0)?'checked':'';?>> Pin this announcement</label><label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;"><input type="checkbox" name="is_company_wide" value="1" <?php echo ($es['is_company_wide']??1)?'checked':'';?>> Company-wide</label></div>
<div style="grid-column:1/-1;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Content</label><textarea name="content" rows="6" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><?php echo e($es['content']??'');?></textarea></div>
</div>
<div style="display:flex;gap:12px;"><button type="submit" class="btn btn-accent">Post Announcement</button><a href="<?php echo url('announcement');?>" class="btn btn-outline">Cancel</a></div>
</form></div></div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:16px;">
<?php $pColors=['Low'=>'var(--text-muted)','Medium'=>'#4da6ff','High'=>'#fb8c00','Urgent'=>'#ef5350'];
foreach($anns as $an): $pc=$pColors[$an['priority']]??'var(--text-muted)'; ?>
<div class="hrms-card" style="padding:20px;border-left:4px solid <?php echo $pc;?>;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
        <div style="display:flex;align-items:center;gap:10px;"><h3 style="margin:0;font-size:15px;font-weight:700;"><?php echo e($an['title']);?></h3><?php if($an['pinned']): ?><span style="font-size:12px;">&#128204;</span><?php endif;?></div>
        <div style="display:flex;align-items:center;gap:8px;"><span class="badge" style="background:<?php echo $pc;?>20;color:<?php echo $pc;?>;border:1px solid <?php echo $pc;?>40;"><?php echo $an['priority'];?></span>
        <?php if($isAdmin||$isManager): ?><a href="<?php echo url('announcement',['action'=>'edit','id'=>$an['id']]);?>" class="btn btn-sm btn-outline" style="padding:3px 10px;">Edit</a><?php if($isAdmin): ?><form method="POST" style="display:inline;"><input type="hidden" name="_action" value="delete"><input type="hidden" name="id" value="<?php echo $an['id'];?>"><button type="submit" class="btn btn-sm btn-outline text-danger" style="padding:3px 10px;" onclick="return confirm('Delete?')">Del</button></form><?php endif;?><?php endif;?>
        </div>
    </div>
    <p style="color:var(--text-muted);font-size:13px;margin:0 0 10px;line-height:1.6;"><?php echo nl2br(e($an['content']??''));?></p>
    <div style="font-size:11px;color:var(--text-muted);">Posted by <?php echo e($an['poster']??'System');?> &bull; <?php echo date('M j, Y g:i A',$an['created_at']);?> <?php if($an['is_company_wide']): ?>&bull; Company-wide<?php endif;?></div>
</div>
<?php endforeach; if(empty($anns)): ?><div class="hrms-card" style="padding:40px;text-align:center;color:var(--text-muted);">No announcements yet.</div><?php endif;?>
</div>
<?php endif;?>
<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
