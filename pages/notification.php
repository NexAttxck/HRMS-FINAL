<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
Auth::check();
if (isset($_GET['action']) && $_GET['action'] === 'markRead') {
    DB::execute("UPDATE system_notification SET is_read = 1 WHERE user_id = ?", [Auth::id()]);
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? url("dashboard")));
    exit;
}

$pageTitle = "Notifications — " . APP_NAME;
$notifications = DB::fetchAll("SELECT * FROM system_notification WHERE user_id = ? ORDER BY created_at DESC", [Auth::id()]);
DB::execute("UPDATE system_notification SET is_read = 1 WHERE user_id = ?", [Auth::id()]);

require_once __DIR__ . "/../includes/layout_header.php";
?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;">
    <h1 style="font-size:24px;font-weight:700;margin:0;">Notifications</h1>
</div>
<div class="hrms-card">
    <div class="card-body" style="padding:0;">
        <?php if(empty($notifications)): ?>
            <div style="padding:36px 24px;text-align:center;color:var(--text-muted);">
                <p style="margin:0;font-size:14px;">No notifications found.</p>
            </div>
        <?php else: ?>
            <table class="table hrms-table" style="margin:0;">
                <tbody>
                <?php foreach($notifications as $n):
                    $ic = $n['type']==='success'?'#66bb6a':($n['type']==='warning'?'#E07A45':($n['type']==='danger'?'#ef5350':'var(--accent)'));
                    $ip = $n['type']==='success'?'<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>':'<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>';
                ?>
                <tr>
                    <td style="width:50px;text-align:center;">
                        <div style="width:34px;height:34px;border-radius:50%;background:<?php echo $ic; ?>20;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="<?php echo $ic; ?>" stroke-width="2"><?php echo $ip; ?></svg>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:14px;font-weight:600;color:var(--text);"><?php echo e($n['title']); ?></div>
                        <div style="font-size:13px;color:var(--text-muted);margin-top:2px;"><?php echo e($n['message']); ?></div>
                    </td>
                    <td style="text-align:right;font-size:12px;color:var(--text-muted);width:150px;">
                        <?php echo date('M j, Y g:i A', strtotime($n['created_at'])); ?>
                    </td>
                    <td style="text-align:right;width:80px;">
                        <?php if($n['link']): ?>
                        <a href="<?php echo e($n['link']); ?>" class="btn btn-sm btn-outline">View</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>
