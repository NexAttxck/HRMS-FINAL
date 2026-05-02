<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
Auth::check();
Auth::requireRole(["Super Admin", "Manager"]);
$pageTitle = "Audit Logs &mdash; " . APP_NAME;

// ── Filters ────────────────────────────────────────────────────────────────
$search   = trim($_GET["q"]      ?? "");
$module   = trim($_GET["module"] ?? "");
$userF    = trim($_GET["user"]   ?? "");
$dateFrom = $_GET["from"] ?? date("Y-m-01");
$dateTo   = $_GET["to"]   ?? date("Y-m-d");
$perPage  = 50;
$page     = max(1, (int)($_GET["p"] ?? 1));
$offset   = ($page - 1) * $perPage;

$where  = "DATE(FROM_UNIXTIME(al.created_at)) BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];
if ($search)  { $where .= " AND (u.username LIKE ? OR al.action LIKE ? OR al.data LIKE ?)"; $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; }
if ($module)  { $where .= " AND al.module = ?";       $params[] = $module; }
if ($userF)   { $where .= " AND al.user_id = ?";      $params[] = $userF; }

// ── CSV Export ──────────────────────────────────────────────────────────────
if (isset($_GET["export"])) {
    $rows = DB::fetchAll("SELECT al.*,u.username,u.role FROM audit_log al LEFT JOIN `user` u ON al.user_id=u.id WHERE $where ORDER BY al.created_at DESC LIMIT 5000", $params);
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=audit_log_" . date("Y-m-d") . ".csv");
    $f = fopen("php://output", "w");
    fputcsv($f, ["ID","Timestamp","User","Role","Action","Module","Record ID","Details","IP"]);
    foreach ($rows as $r) {
        fputcsv($f, [$r["id"], date("Y-m-d H:i:s", $r["created_at"]), $r["username"] ?? "System", $r["role"] ?? "", $r["action"] ?? "", $r["module"] ?? "", $r["model_id"] ?? "", $r["data"] ?? "", $r["ip"] ?? ""]);
    }
    fclose($f); exit;
}

// ── Stats ───────────────────────────────────────────────────────────────────
$totalAll   = DB::fetchScalar("SELECT COUNT(*) FROM audit_log");
$totalToday = DB::fetchScalar("SELECT COUNT(*) FROM audit_log WHERE DATE(FROM_UNIXTIME(created_at))=CURDATE()");
$totalWeek  = DB::fetchScalar("SELECT COUNT(*) FROM audit_log WHERE created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(),INTERVAL 7 DAY))");
$totalUsers = DB::fetchScalar("SELECT COUNT(DISTINCT user_id) FROM audit_log WHERE created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(),INTERVAL 7 DAY))");

// Module breakdown (last 30 days)
$modStats = DB::fetchAll("SELECT module, COUNT(*) as cnt FROM audit_log WHERE created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(),INTERVAL 30 DAY)) AND module IS NOT NULL GROUP BY module ORDER BY cnt DESC LIMIT 8");

// ── Data ────────────────────────────────────────────────────────────────────
$total  = (int)DB::fetchScalar("SELECT COUNT(*) FROM audit_log al LEFT JOIN `user` u ON al.user_id=u.id WHERE $where", $params);
$logs   = DB::fetchAll("SELECT al.*,u.username,u.role FROM audit_log al LEFT JOIN `user` u ON al.user_id=u.id WHERE $where ORDER BY al.created_at DESC LIMIT $perPage OFFSET $offset", $params);
$modules = DB::fetchAll("SELECT DISTINCT module FROM audit_log WHERE module IS NOT NULL ORDER BY module");
$users   = DB::fetchAll("SELECT id, username FROM `user` ORDER BY username");
$pages   = (int)ceil($total / $perPage);

// ── Module color map ─────────────────────────────────────────────────────────
$modColors = [
    "auth"             => ["#42a5f5","rgba(66,165,245,0.12)"],
    "employee"         => ["#7C9368","rgba(124,147,104,0.12)"],
    "department"       => ["#ab47bc","rgba(171,71,188,0.12)"],
    "position"         => ["#26a69a","rgba(38,166,154,0.12)"],
    "leave"            => ["#ffa726","rgba(255,167,38,0.12)"],
    "attendance"       => ["#ef5350","rgba(239,83,80,0.12)"],
    "payroll"          => ["#66bb6a","rgba(102,187,106,0.12)"],
    "personnel action" => ["#ff7043","rgba(255,112,67,0.12)"],
    "user management"  => ["#ec407a","rgba(236,64,122,0.12)"],
    "recruitment"      => ["#29b6f6","rgba(41,182,246,0.12)"],
    "onboarding"       => ["#8d6e63","rgba(141,110,99,0.12)"],
    "announcement"     => ["#ffee58","rgba(255,238,88,0.12)"],
];

function modBadge(string $m): string {
    global $modColors;
    $lookup = strtolower(trim($m));
    [$color,$bg] = $modColors[$lookup] ?? ["#8a8a7a","rgba(138,138,122,0.12)"];
    return "<span style='padding:2px 10px;background:$bg;color:$color;border-radius:12px;font-size:11px;font-weight:700;white-space:nowrap;'>$m</span>";
}

function actionIcon(string $action): string {
    if (stripos($action,'login')  !== false) return "&#128274;";
    if (stripos($action,'logout') !== false) return "&#128275;";
    if (stripos($action,'create') !== false) return "&#43;";
    if (stripos($action,'add')    !== false) return "&#43;";
    if (stripos($action,'update') !== false) return "&#9998;";
    if (stripos($action,'edit')   !== false) return "&#9998;";
    if (stripos($action,'delete') !== false) return "&#128465;";
    if (stripos($action,'clock')  !== false) return "&#128336;";
    if (stripos($action,'process')!== false) return "&#9654;";
    if (stripos($action,'approve')!== false) return "&#10003;";
    if (stripos($action,'deny')   !== false) return "&#10007;";
    return "&#9679;";
}

function actionColor(string $action): string {
    if (stripos($action,'login')  !== false) return "#42a5f5";
    if (stripos($action,'logout') !== false) return "#8a8a7a";
    if (stripos($action,'create') !== false || stripos($action,'add') !== false) return "#66bb6a";
    if (stripos($action,'update') !== false || stripos($action,'edit') !== false) return "#ffa726";
    if (stripos($action,'delete') !== false || stripos($action,'deny') !== false) return "#ef5350";
    if (stripos($action,'approve')!== false || stripos($action,'process') !== false) return "#7C9368";
    return "#8a8a7a";
}

require_once __DIR__ . "/../includes/layout_header.php";
?>

<!-- Page Header -->
<div style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-size:28px;font-weight:700;margin:0;">&#128203; Audit Logs</h1>
        <p style="color:var(--text-muted);margin:4px 0 0;">Complete activity trail for the entire system</p>
    </div>
    <a href="<?php echo url("audit") . "&export=1&from=" . urlencode($dateFrom) . "&to=" . urlencode($dateTo) . ($module?"&module=".urlencode($module):"") . ($search?"&q=".urlencode($search):""); ?>"
       class="btn btn-outline" style="display:flex;align-items:center;gap:6px;">
        &#8595; Export CSV
    </a>
</div>

<!-- Stats Cards -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <?php foreach([
        ["Today",         $totalToday, "&#128197;", "#42a5f5"],
        ["Last 7 Days",   $totalWeek,  "&#128197;", "#ffa726"],
        ["Active Users",  $totalUsers, "&#128100;", "#7C9368"],
        ["Total Events",  $totalAll,   "&#128203;", "#ab47bc"],
    ] as [$label,$val,$icon,$clr]): ?>
    <div class="hrms-card" style="padding:18px 20px;display:flex;align-items:center;gap:14px;">
        <div style="width:44px;height:44px;border-radius:10px;background:<?php echo $clr; ?>20;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;"><?php echo $icon; ?></div>
        <div>
            <div style="font-size:24px;font-weight:700;line-height:1;"><?php echo number_format($val); ?></div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?php echo $label; ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Module breakdown bar -->
<?php if ($modStats): ?>
<div class="hrms-card" style="padding:16px 20px;margin-bottom:20px;">
    <div style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Activity by Module (Last 30 Days)</div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
        <?php foreach ($modStats as $ms): 
            $isActive = ($module === $ms["module"]);
            $lookup = strtolower(trim($ms["module"]));
            [$baseColor, $baseBg] = $modColors[$lookup] ?? ["#8a8a7a","rgba(138,138,122,0.12)"];
            
            if ($isActive) {
                // Active state: vibrant color, solid border, glow
                $c = $baseColor;
                $bg = $baseColor . "40"; 
                $border = $baseColor;
                $shadow = "box-shadow: 0 0 8px {$baseColor}40;";
            } else {
                // Inactive state: completely greyed out
                $c = "#8a8a7a";
                $bg = "rgba(138,138,122,0.08)";
                $border = "rgba(138,138,122,0.2)";
                $shadow = "";
            }
        ?>
        <a href="<?php echo url("audit",["module"=>$isActive ? "" : $ms["module"],"from"=>$dateFrom,"to"=>$dateTo,"q"=>$search,"user"=>$userF]); ?>"
           style="display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:20px;text-decoration:none;
           background:<?php echo $bg; ?>; transition:all 0.2s;
           color:<?php echo $c; ?>;border:1px solid <?php echo $border; ?>;font-size:12px;font-weight:600;
           <?php echo $shadow; ?>">
            <?php echo e($ms["module"]); ?>
            <span style="background:rgba(255,255,255,0.1);padding:1px 7px;border-radius:10px;"><?php echo $ms["cnt"]; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="hrms-card" style="padding:16px 20px;margin-bottom:16px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="page" value="audit">
        <div>
            <label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px;">Search</label>
            <input type="text" name="q" value="<?php echo e($search); ?>" placeholder="Action, user, details..."
                style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;width:220px;">
        </div>
        <div>
            <label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px;">Module</label>
            <select name="module" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                <option value="">All Modules</option>
                <?php foreach ($modules as $m): ?>
                <option value="<?php echo e($m["module"]); ?>" <?php echo $module===$m["module"]?"selected":""; ?>><?php echo e($m["module"]); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px;">User</label>
            <select name="user" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                <option value="">All Users</option>
                <?php foreach ($users as $u): ?>
                <option value="<?php echo $u["id"]; ?>" <?php echo $userF==(string)$u["id"]?"selected":""; ?>><?php echo e($u["username"]); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px;">From</label>
            <input type="date" name="from" value="<?php echo e($dateFrom); ?>"
                style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
        </div>
        <div>
            <label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px;">To</label>
            <input type="date" name="to" value="<?php echo e($dateTo); ?>"
                style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
        </div>
        <button type="submit" class="btn btn-accent">Filter</button>
        <a href="<?php echo url("audit"); ?>" class="btn btn-outline">Reset</a>
    </form>
</div>

<!-- Results count -->
<div style="margin-bottom:10px;font-size:13px;color:var(--text-muted);">
    Showing <?php echo number_format(min($total, $perPage * $page - $perPage + 1)); ?>–<?php echo number_format(min($total, $perPage * $page)); ?> of <?php echo number_format($total); ?> events
    <?php if ($module || $search || $userF): ?>
    <span style="margin-left:8px;">
        <?php if ($module): ?><span style="padding:2px 8px;background:rgba(124,147,104,0.12);color:var(--accent);border-radius:6px;font-size:11px;font-weight:600;">Module: <?php echo e($module); ?></span><?php endif; ?>
        <?php if ($search): ?><span style="padding:2px 8px;background:rgba(66,165,245,0.12);color:#42a5f5;border-radius:6px;font-size:11px;font-weight:600;margin-left:4px;">Search: <?php echo e($search); ?></span><?php endif; ?>
    </span>
    <?php endif; ?>
</div>

<!-- Log Table -->
<div class="hrms-card">
    <div class="card-body" style="padding:0;">
        <table class="table hrms-table" style="margin:0;">
            <thead>
                <tr>
                    <th style="width:140px;">Time</th>
                    <th style="width:130px;">User</th>
                    <th>Action</th>
                    <th style="width:140px;">Module</th>
                    <th>Details</th>
                    <th style="width:110px;">IP Address</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $l):
                $aColor = actionColor($l["action"] ?? "");
                $aIcon  = actionIcon($l["action"] ?? "");
            ?>
            <tr style="transition:background .12s;" onmouseenter="this.style.background='rgba(255,255,255,0.03)'" onmouseleave="this.style.background=''">
                <td style="font-size:11px;color:var(--text-muted);white-space:nowrap;padding:10px 14px;">
                    <div style="font-weight:600;color:var(--text);font-size:12px;"><?php echo date("M j, Y", $l["created_at"]); ?></div>
                    <div><?php echo date("h:i:s A", $l["created_at"]); ?></div>
                </td>
                <td style="padding:10px 14px;">
                    <?php if ($l["username"]): ?>
                    <div style="display:flex;align-items:center;gap:7px;">
                        <div style="width:28px;height:28px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0;">
                            <?php echo strtoupper(substr($l["username"], 0, 2)); ?>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:12px;"><?php echo e($l["username"]); ?></div>
                            <?php if ($l["role"]): ?>
                            <div style="font-size:10px;color:var(--text-muted);"><?php echo e($l["role"]); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <span style="color:var(--text-muted);font-size:12px;">System</span>
                    <?php endif; ?>
                </td>
                <td style="padding:10px 14px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="width:26px;height:26px;border-radius:50%;background:<?php echo $aColor; ?>20;color:<?php echo $aColor; ?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;"><?php echo $aIcon; ?></span>
                        <span style="font-weight:600;font-size:13px;color:<?php echo $aColor; ?>;"><?php echo e($l["action"] ?? "&mdash;"); ?></span>
                    </div>
                </td>
                <td style="padding:10px 14px;"><?php echo modBadge($l["module"] ?? ""); ?></td>
                <td style="padding:10px 14px;font-size:12px;color:var(--text-muted);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?php if ($l["data"]): ?>
                    <span title="<?php echo e($l["data"]); ?>"><?php echo e(substr($l["data"], 0, 80)) . (strlen($l["data"] ?? "") > 80 ? "…" : ""); ?></span>
                    <?php elseif ($l["model_id"]): ?>
                    <span style="color:var(--text-muted);">ID #<?php echo $l["model_id"]; ?></span>
                    <?php else: ?>
                    <span style="color:rgba(255,255,255,0.2);">&mdash;</span>
                    <?php endif; ?>
                </td>
                <td style="padding:10px 14px;font-family:monospace;font-size:11px;color:var(--text-muted);"><?php echo e($l["ip"] ?? "&mdash;"); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
            <tr><td colspan="6" style="text-align:center;padding:48px;color:var(--text-muted);">
                <div style="font-size:32px;margin-bottom:10px;">&#128203;</div>
                No audit events found for the selected filters.
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div style="display:flex;gap:6px;justify-content:center;margin-top:20px;flex-wrap:wrap;">
    <?php if ($page > 1): ?>
    <a href="<?php echo url("audit",["from"=>$dateFrom,"to"=>$dateTo,"q"=>$search,"module"=>$module,"user"=>$userF,"p"=>$page-1]); ?>" class="btn btn-outline btn-sm">&larr; Prev</a>
    <?php endif; ?>
    <?php
    $start = max(1, $page - 3);
    $end   = min($pages, $page + 3);
    for ($i = $start; $i <= $end; $i++):
    ?>
    <a href="<?php echo url("audit",["from"=>$dateFrom,"to"=>$dateTo,"q"=>$search,"module"=>$module,"user"=>$userF,"p"=>$i]); ?>"
       class="btn btn-sm <?php echo $i === $page ? 'btn-accent' : 'btn-outline'; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
    <?php if ($page < $pages): ?>
    <a href="<?php echo url("audit",["from"=>$dateFrom,"to"=>$dateTo,"q"=>$search,"module"=>$module,"user"=>$userF,"p"=>$page+1]); ?>" class="btn btn-outline btn-sm">Next &rarr;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>
