<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
Auth::check(); Auth::requireRole(["Super Admin"]); $pageTitle = "Positions &mdash; " . APP_NAME;

// ── POST handlers ──────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $a = $_POST["_action"] ?? "";
    if ($a === "save") {
        $id = (int)($_POST["id"] ?? 0);
        if ($id) {
            DB::execute("UPDATE position SET title=?,code=?,department_id=?,level=?,salary_min=?,salary_max=?,description=? WHERE id=?",
                [$_POST["title"], $_POST["code"] ?? null, $_POST["department_id"] ?? null, (int)($_POST["level"] ?? 1),
                 $_POST["salary_min"] ?? 0, $_POST["salary_max"] ?? 0, $_POST["description"] ?? null, $id]);
        } else {
            DB::insert("INSERT INTO position (title,code,department_id,level,salary_min,salary_max,description,created_at) VALUES (?,?,?,?,?,?,?,?)",
                [$_POST["title"], $_POST["code"] ?? null, $_POST["department_id"] ?? null, (int)($_POST["level"] ?? 1),
                 $_POST["salary_min"] ?? 0, $_POST["salary_max"] ?? 0, $_POST["description"] ?? null, time()]);
        }
        Auth::audit($id ? 'Update Position' : 'Create Position', 'Position', $id ?: null, $_POST['title'] ?? null);
        Auth::flash("success", "Position saved!"); header("Location: " . url("position")); exit;
    }
    if ($a === "delete") {
        $delId = (int)($_POST["id"] ?? 0);
        Auth::audit('Delete Position', 'Position', $delId);
        DB::execute("DELETE FROM position WHERE id=?", [$delId]);
        Auth::flash("success", "Position deleted."); header("Location: " . url("position")); exit;
    }
}

// ── Filters ────────────────────────────────────────────────────────────────
$deptFilter = (int)($_GET["dept"] ?? 0);
$where = "1=1"; $params = [];
if ($deptFilter) { $where .= " AND p.department_id=?"; $params[] = $deptFilter; }

$positions = DB::fetchAll(
    "SELECT p.*, d.name as dept_name,
     (SELECT COUNT(*) FROM employee e WHERE e.position_id=p.id AND e.status IN ('Regular','Probationary')) as filled_count
     FROM position p LEFT JOIN department d ON p.department_id=d.id
     WHERE $where ORDER BY p.title", $params);

$depts    = DB::fetchAll("SELECT id,name FROM department ORDER BY name");
$action   = $_GET["action"] ?? "index";
$editId   = (int)($_GET["id"] ?? 0);
$es       = $editId ? DB::fetchOne("SELECT * FROM position WHERE id=?", [$editId]) : null;

// ── Stats ──────────────────────────────────────────────────────────────────
$allPos         = DB::fetchAll("SELECT p.id,(SELECT COUNT(*) FROM employee e WHERE e.position_id=p.id AND e.status IN ('Regular','Probationary')) as fc FROM position p");
$totalPositions = count($allPos);
$filledCount    = count(array_filter($allPos, fn($p) => $p['fc'] > 0));
$vacantCount    = $totalPositions - $filledCount;
$deptCount      = (int)DB::fetchScalar("SELECT COUNT(DISTINCT department_id) FROM position WHERE department_id IS NOT NULL");

// ── Level & colour maps ────────────────────────────────────────────────────
$levelMap    = [1=>'Entry', 2=>'Mid', 3=>'Senior', 4=>'Lead', 5=>'Executive'];
$levelColors = [
    1 => ['#4caf50','rgba(76,175,80,0.13)'],
    2 => ['#42a5f5','rgba(66,165,245,0.13)'],
    3 => ['#ffa726','rgba(255,167,38,0.13)'],
    4 => ['#ab47bc','rgba(171,71,188,0.13)'],
    5 => ['#ef5350','rgba(239,83,80,0.13)'],
];
$deptPalette = ['#7C9368','#42a5f5','#ab47bc','#ffa726','#ef5350','#26a69a','#ec407a','#29b6f6','#ff7043','#66bb6a'];

// Build dept→colour index
$deptColorMap = [];
foreach ($depts as $i => $d) { $deptColorMap[$d['id']] = $deptPalette[$i % count($deptPalette)]; }

require_once __DIR__ . "/../includes/layout_header.php";
?>

<?php if ($action === "create" || $action === "edit"): ?>
<!-- ═══════════════════════ CREATE / EDIT FORM ═══════════════════════════ -->
<div style="margin-bottom:24px;display:flex;align-items:center;gap:14px;">
    <a href="<?php echo url("position"); ?>" class="btn btn-outline btn-sm" style="padding:6px 14px;">&#8592; Back</a>
    <div>
        <h1 style="font-size:22px;font-weight:700;margin:0;"><?php echo $es ? 'Edit Position' : 'New Position'; ?></h1>
        <p style="margin:2px 0 0;font-size:13px;color:var(--text-muted);">Define job title, salary band and organisational level</p>
    </div>
</div>

<div class="hrms-card">
    <div class="card-header" style="padding:16px 24px;border-bottom:1px solid var(--border);">
        <h3 style="margin:0;font-size:15px;font-weight:700;"><?php echo $es ? 'Edit' : 'New'; ?> Position</h3>
    </div>
    <div class="card-body" style="padding:24px;">
        <form method="POST">
            <input type="hidden" name="_action" value="save">
            <?php if ($es): ?><input type="hidden" name="id" value="<?php echo $es['id']; ?>"><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <!-- Title -->
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Job Title <span style="color:#ef5350;">*</span></label>
                    <input type="text" name="title" value="<?php echo e($es['title'] ?? ''); ?>" required
                        style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                </div>
                <!-- Code -->
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Position Code</label>
                    <input type="text" name="code" value="<?php echo e($es['code'] ?? ''); ?>" placeholder="e.g. ENG-001"
                        style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                </div>
                <!-- Department -->
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Department</label>
                    <select name="department_id" style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                        <option value="">&mdash; None &mdash;</option>
                        <?php foreach ($depts as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($es['department_id'] ?? '') == $d['id'] ? 'selected' : ''; ?>><?php echo e($d['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Level -->
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Level</label>
                    <select name="level" style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                        <?php foreach ($levelMap as $lv => $lname): ?>
                        <option value="<?php echo $lv; ?>" <?php echo ($es['level'] ?? 1) == $lv ? 'selected' : ''; ?>><?php echo $lname; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Salary Min -->
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Min Salary (&#8369;)</label>
                    <input type="number" name="salary_min" value="<?php echo e((string)($es['salary_min'] ?? '')); ?>" min="0" step="500"
                        style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                </div>
                <!-- Salary Max -->
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Max Salary (&#8369;)</label>
                    <input type="number" name="salary_max" value="<?php echo e((string)($es['salary_max'] ?? '')); ?>" min="0" step="500"
                        style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                </div>
                <!-- Description -->
                <div style="grid-column:1/-1;">
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Description</label>
                    <textarea name="description" rows="3"
                        style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;resize:vertical;"><?php echo e($es['description'] ?? ''); ?></textarea>
                </div>
            </div>

            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn-accent" style="padding:10px 28px;font-weight:600;">
                    &#10003; <?php echo $es ? 'Save Changes' : 'Create Position'; ?>
                </button>
                <a href="<?php echo url("position"); ?>" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ═══════════════════════════ INDEX VIEW ═══════════════════════════════ -->

<!-- Page Header -->
<div style="margin-bottom:24px;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-size:28px;font-weight:700;margin:0;">Positions</h1>
        <p style="color:var(--text-muted);margin:4px 0 0;font-size:13px;">Define job titles, salary bands, and organisational levels</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <!-- Department filter -->
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="page" value="position">
            <select name="dept" onchange="this.form.submit()"
                style="padding:8px 14px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;min-width:160px;">
                <option value="">All Departments</option>
                <?php foreach ($depts as $d): ?>
                <option value="<?php echo $d['id']; ?>" <?php echo $deptFilter == $d['id'] ? 'selected' : ''; ?>><?php echo e($d['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="<?php echo url("position", ["action" => "create"]); ?>" class="btn btn-accent" style="display:flex;align-items:center;gap:6px;padding:9px 18px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Position
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <?php foreach([
        ['Total Positions', $totalPositions, '#1e88e5', '&#128203;'],
        ['Filled',          $filledCount,    '#43a047', '&#10003;'],
        ['Vacant',          $vacantCount,    '#fb8c00', '&#9711;'],
        ['Departments',     $deptCount,      '#8e24aa', '&#127968;'],
    ] as [$label,$val,$clr,$icon]): ?>
    <div class="hrms-card" style="border-left:4px solid <?php echo $clr; ?>;padding:0;">
        <div style="padding:18px 20px;">
            <p style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin:0 0 6px;"><?php echo $label; ?></p>
            <p style="font-size:36px;font-weight:800;margin:0;line-height:1;color:<?php echo $clr; ?>;"><?php echo $val; ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Table -->
<div class="hrms-card">
    <div class="card-body" style="padding:0;">
        <table class="table hrms-table" style="margin:0;">
            <thead>
                <tr>
                    <th style="width:80px;font-size:11px;">CODE</th>
                    <th style="font-size:11px;">TITLE</th>
                    <th style="font-size:11px;">DEPARTMENT</th>
                    <th style="width:90px;font-size:11px;">LEVEL</th>
                    <th style="width:200px;font-size:11px;">SALARY RANGE</th>
                    <th style="width:70px;text-align:center;font-size:11px;">FILLED</th>
                    <th style="width:100px;text-align:right;font-size:11px;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($positions as $p):
                $lv     = (int)($p['level'] ?? 1);
                [$lc,$lb] = $levelColors[$lv] ?? ['#8a8a7a','rgba(138,138,122,0.12)'];
                $lname  = $levelMap[$lv] ?? 'Entry';
                $deptId = (int)($p['department_id'] ?? 0);
                $dc     = $deptColorMap[$deptId] ?? '#7C9368';
                $filled = (int)$p['filled_count'];
            ?>
            <tr style="transition:background .12s;" onmouseenter="this.style.background='rgba(255,255,255,0.03)'" onmouseleave="this.style.background=''">
                <!-- Code -->
                <td style="padding:12px 16px;">
                    <?php if ($p['code']): ?>
                    <span style="font-size:11px;font-family:monospace;background:rgba(255,255,255,0.07);padding:3px 8px;border-radius:5px;color:var(--text-muted);"><?php echo e($p['code']); ?></span>
                    <?php else: ?>
                    <span style="color:rgba(255,255,255,0.15);font-size:12px;">&mdash;</span>
                    <?php endif; ?>
                </td>
                <!-- Title -->
                <td style="padding:12px 16px;font-weight:600;font-size:14px;">
                    <a href="<?php echo url("position", ["action"=>"edit","id"=>$p['id']]); ?>" style="color:var(--text);text-decoration:none;"
                       onmouseenter="this.style.color='var(--accent)'" onmouseleave="this.style.color='var(--text)'">
                        <?php echo e($p['title']); ?>
                    </a>
                </td>
                <!-- Department badge -->
                <td style="padding:12px 16px;">
                    <?php if ($p['dept_name']): ?>
                    <span style="padding:3px 10px;background:<?php echo $dc; ?>1a;color:<?php echo $dc; ?>;border-radius:12px;font-size:12px;font-weight:600;white-space:nowrap;">
                        <?php echo e($p['dept_name']); ?>
                    </span>
                    <?php else: ?>
                    <span style="color:rgba(255,255,255,0.2);font-size:12px;">&mdash;</span>
                    <?php endif; ?>
                </td>
                <!-- Level badge -->
                <td style="padding:12px 16px;">
                    <span style="padding:3px 10px;background:<?php echo $lb; ?>;color:<?php echo $lc; ?>;border-radius:12px;font-size:11px;font-weight:700;">
                        <?php echo $lname; ?>
                    </span>
                </td>
                <!-- Salary range -->
                <td style="padding:12px 16px;font-size:13px;font-weight:500;white-space:nowrap;">
                    <?php if ($p['salary_min'] > 0 || $p['salary_max'] > 0): ?>
                    <span style="color:var(--text-muted);">&#8369;</span><?php echo number_format($p['salary_min'], 0); ?>
                    <span style="color:var(--text-muted);margin:0 4px;">&mdash;</span>
                    <span style="color:var(--text-muted);">&#8369;</span><?php echo number_format($p['salary_max'], 0); ?>
                    <?php else: ?>
                    <span style="color:rgba(255,255,255,0.2);">&mdash;</span>
                    <?php endif; ?>
                </td>
                <!-- Filled count -->
                <td style="padding:12px 16px;text-align:center;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;min-width:26px;height:26px;padding:0 6px;
                        border-radius:50px;font-size:12px;font-weight:700;
                        background:<?php echo $filled > 0 ? 'rgba(124,147,104,0.18)' : 'rgba(255,255,255,0.06)'; ?>;
                        color:<?php echo $filled > 0 ? 'var(--accent)' : 'var(--text-muted)'; ?>;">
                        <?php echo $filled; ?>
                    </span>
                </td>
                <!-- Actions -->
                <td style="padding:12px 16px;text-align:right;">
                    <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                        <a href="<?php echo url("position", ["action"=>"edit","id"=>$p['id']]); ?>"
                           class="btn btn-sm btn-outline" style="padding:4px 12px;font-size:12px;">Edit</a>
                        <form method="POST" style="margin:0;" onsubmit="return confirm('Delete &quot;<?php echo e($p['title']); ?>&quot;?');">
                            <input type="hidden" name="_action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <button type="submit" style="padding:4px 12px;font-size:12px;border-radius:6px;
                                background:rgba(239,83,80,0.12);color:#ef5350;border:1px solid rgba(239,83,80,0.3);
                                cursor:pointer;font-weight:600;">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($positions)): ?>
            <tr><td colspan="7" style="text-align:center;padding:48px;color:var(--text-muted);">
                <div style="font-size:32px;margin-bottom:10px;">&#128203;</div>
                No positions found<?php echo $deptFilter ? ' for the selected department.' : '.'; ?>
                <a href="<?php echo url('position',['action'=>'create']); ?>" style="display:block;margin-top:8px;color:var(--accent);">+ Add the first position</a>
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>
