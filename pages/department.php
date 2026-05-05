<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
Auth::check();
$pageTitle = 'Departments &mdash; ' . APP_NAME;
$isAdmin   = Auth::isAdmin();
$isManager = Auth::isManager();
$action    = $_GET['action'] ?? 'index';

// Resolve manager's department (with session fallback)
$managerDeptId = Auth::deptId();
if ($isManager && !$isAdmin && !$managerDeptId) {
    $managerDeptId = (int)DB::fetchScalar("SELECT department_id FROM employee WHERE user_id=? AND department_id IS NOT NULL LIMIT 1", [Auth::id()]);
}

if (!$isAdmin && !$isManager && $action !== 'index' && $action !== 'view') {
    Auth::flash('error', 'Access denied.'); header('Location: ' . url('department')); exit;
}
// Managers cannot create new departments or delete them
if ($isManager && !$isAdmin && in_array($action, ['create'])) {
    Auth::flash('error', 'Managers can only edit their own department.'); header('Location: ' . url('department')); exit;
}
// Managers can only edit their OWN department
$editId = (int)($_GET['id'] ?? 0);
if ($isManager && !$isAdmin && $action === 'edit' && $editId && $editId !== $managerDeptId) {
    Auth::flash('error', 'You can only edit your own department.'); header('Location: ' . url('department')); exit;
}

//  POST handlers 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['_action'] ?? '';
    if ($a === 'save' && ($isAdmin || $isManager)) {
        $id = (int)($_POST['id'] ?? 0);
        // Managers can only save their own department
        if ($isManager && !$isAdmin && $id && $id !== $managerDeptId) {
            Auth::flash('error', 'You can only edit your own department.'); header('Location: ' . url('department')); exit;
        }
        if ($id) {
            // Managers can only save employee assignments; protect name/budget for Admin only
            if ($isAdmin) {
                DB::execute("UPDATE department SET name=?,description=?,budget=?,location=? WHERE id=?",
                    [$_POST['name'], $_POST['description'] ?? null, $_POST['budget'] ?? 0, $_POST['location'] ?? null, $id]);
            }

            // ── Employee assignment (both Admin and Manager) ─────────────────
            $newIds  = array_filter(array_map('intval', $_POST['employee_ids'] ?? []));
            $oldRows = DB::fetchAll("SELECT id FROM employee WHERE department_id=?", [$id]);
            $oldIds  = array_column($oldRows, 'id');
            $remove  = array_diff($oldIds, $newIds);
            if ($remove) {
                $ph = implode(',', array_fill(0, count($remove), '?'));
                DB::execute("UPDATE employee SET department_id=NULL WHERE id IN ($ph)", array_values($remove));
                // Also clear user.department_id for unassigned employees
                DB::execute("UPDATE `user` u JOIN employee e ON e.user_id=u.id SET u.department_id=NULL WHERE e.id IN ($ph)", array_values($remove));
            }
            if ($newIds) {
                $ph = implode(',', array_fill(0, count($newIds), '?'));
                DB::execute("UPDATE employee SET department_id=? WHERE id IN ($ph)",
                    array_merge([$id], array_values($newIds)));
                // Sync user.department_id so User Management stays accurate
                DB::execute("UPDATE `user` u JOIN employee e ON e.user_id=u.id SET u.department_id=? WHERE e.id IN ($ph)",
                    array_merge([$id], array_values($newIds)));
            }
        } elseif ($isAdmin) {
            // Only Admin can create new departments
            $id = DB::insert("INSERT INTO department (name,description,budget,location,created_at) VALUES (?,?,?,?,?)",
                [$_POST['name'], $_POST['description'] ?? null, $_POST['budget'] ?? 0, $_POST['location'] ?? null, time()]);
        }
        Auth::audit($id ? 'Update Department' : 'Create Department', 'Department', $id ?: null, $_POST['name'] ?? '');
        Auth::flash('success', 'Department saved!'); header('Location: ' . url('department', ['action'=>'edit','id'=>$id])); exit;
    }
    if ($a === 'delete' && $isAdmin) {
        DB::execute("DELETE FROM department WHERE id=?", [(int)$_POST['id']]);
        Auth::audit('Delete Department', 'Department', (int)$_POST['id']);
        Auth::flash('success', 'Department deleted.'); header('Location: ' . url('department')); exit;
    }
}

//  Data 
$depts = DB::fetchAll("SELECT d.*,
    (SELECT COUNT(*) FROM employee e WHERE e.department_id=d.id AND e.status IN ('Regular','Probationary','Active')) as emp_count
    FROM department d ORDER BY d.name");

$editId        = $editId ?: (int)($_GET['id'] ?? 0);
$es            = $editId ? DB::fetchOne("SELECT * FROM department WHERE id=?", [$editId]) : null;
$allEmployees  = DB::fetchAll("SELECT id,first_name,last_name,job_title,department_id FROM employee ORDER BY first_name,last_name");
$deptEmpIds    = $editId ? array_column(DB::fetchAll("SELECT id FROM employee WHERE department_id=?", [$editId]), 'id') : [];

// Color palette matching legacy
$colors = ['#1e88e5','#43a047','#8e24aa','#fb8c00','#ef5350','#00acc1','#f4511e','#6d4c41'];

require_once __DIR__ . '/../includes/layout_header.php';
?>

<!--  Header  -->
<div style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
    <div>
        <h1 style="font-size:28px;font-weight:700;margin:0;">Departments</h1>
        <p style="color:var(--text-muted);margin:4px 0 0;">Manage organizational departments and teams</p>
    </div>
    <?php if ($isAdmin): ?>
    <a href="<?php echo url('department', ['action' => 'create']); ?>" class="btn btn-accent" style="display:flex;align-items:center;gap:8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Department
    </a>
    <?php endif; ?>
</div>

<?php if ($action === 'create' || $action === 'edit'): ?>
<!--  Create / Edit Form  -->
<div class="hrms-card">
    <div class="card-header"><h3><?php echo $es ? 'Edit Department' : 'New Department'; ?></h3></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="_action" value="save">
            <?php if ($es): ?><input type="hidden" name="id" value="<?php echo $es['id']; ?>"><?php endif; ?>
            <?php if ($isAdmin): ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <?php foreach ([['name','Department Name','text',$es['name']??''],['location','Location','text',$es['location']??''],['budget','Budget (&#8369;)','number',$es['budget']??'']] as $f): ?>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;"><?php echo $f[1]; ?></label>
                    <input type="<?php echo $f[2]; ?>" name="<?php echo $f[0]; ?>" value="<?php echo e((string)$f[3]); ?>"
                        style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                </div>
                <?php endforeach; ?>

                <div style="grid-column:1/-1;">
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Description</label>
                    <textarea name="description" rows="3"
                        style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><?php echo e($es['description'] ?? ''); ?></textarea>
                </div>
            </div>
            <?php endif; ?>

            <!-- Employee Assignment Panel -->
            <div style="margin-top:24px;border:1px solid var(--border);border-radius:10px;overflow:hidden;">
                <div style="padding:14px 18px;background:rgba(255,255,255,0.04);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <span style="font-size:14px;font-weight:700;">&#128101; Assign Employees</span>
                        <span style="font-size:12px;color:var(--text-muted);margin-left:10px;">Check employees to assign them to this department</span>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="button" onclick="toggleAllEmps(true)" style="font-size:11px;padding:4px 10px;border:1px solid var(--border);border-radius:6px;background:none;color:var(--text-muted);cursor:pointer;">Select All</button>
                        <button type="button" onclick="toggleAllEmps(false)" style="font-size:11px;padding:4px 10px;border:1px solid var(--border);border-radius:6px;background:none;color:var(--text-muted);cursor:pointer;">Clear All</button>
                    </div>
                </div>
                <!-- Search filter -->
                <div style="padding:10px 18px;border-bottom:1px solid var(--border);">
                    <input type="text" id="empSearch" placeholder="Search employees..." oninput="filterEmps(this.value)"
                        style="width:100%;padding:8px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                </div>
                <!-- Employee list -->
                <div id="empList" style="max-height:320px;overflow-y:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:0;">
                    <?php foreach ($allEmployees as $em):
                        $checked = in_array($em['id'], $deptEmpIds);
                        $inOther = $em['department_id'] && !in_array($em['id'], $deptEmpIds);
                    ?>
                    <label class="emp-row" data-name="<?php echo strtolower($em['first_name'].' '.$em['last_name']); ?>"
                        style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--border);cursor:pointer;transition:background .15s;"
                        onmouseenter="this.style.background='rgba(255,255,255,0.04)'" onmouseleave="this.style.background='';">
                        <input type="checkbox" name="employee_ids[]" value="<?php echo $em['id']; ?>"
                            <?php echo $checked ? 'checked' : ''; ?>
                            style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer;flex-shrink:0;">
                        <div style="min-width:0;">
                            <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?php echo e($em['first_name'].' '.$em['last_name']); ?>
                            </div>
                            <div style="font-size:11px;color:var(--text-muted);">
                                <?php echo e($em['job_title'] ?? 'No title'); ?>
                                <?php if ($inOther): ?>
                                <span style="color:#fb8c00;"> &bull; In another dept</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; if(empty($allEmployees)): ?>
                    <div style="padding:24px;text-align:center;color:var(--text-muted);">No employees found.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top:16px;display:flex;gap:12px;">
                <button type="submit" class="btn btn-accent">Save Department</button>
                <a href="<?php echo url('department'); ?>" class="btn btn-outline">Cancel</a>
            </div>

            <script>
            function toggleAllEmps(state) {
                document.querySelectorAll('#empList input[type=checkbox]').forEach(cb => cb.checked = state);
            }
            function filterEmps(q) {
                q = q.toLowerCase();
                document.querySelectorAll('.emp-row').forEach(row => {
                    row.style.display = row.dataset.name.includes(q) ? '' : 'none';
                });
            }
            </script>
        </form>
    </div>
</div>

<?php elseif ($action === 'view' && $editId): ?>
<!--  Department View  -->
<?php
$dept = DB::fetchOne("SELECT d.*,
    (SELECT COUNT(*) FROM employee e WHERE e.department_id=d.id AND e.status IN ('Regular','Probationary','Active')) as emp_count
    FROM department d WHERE d.id=?", [$editId]);
$deptEmployees = DB::fetchAll("SELECT * FROM employee WHERE department_id=? ORDER BY first_name", [$editId]);
$deptColor = $colors[($editId - 1) % count($colors)];
?>
<div style="margin-bottom:20px;display:flex;align-items:center;gap:16px;">
    <a href="<?php echo url('department'); ?>" class="btn btn-outline" style="display:flex;align-items:center;gap:6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back
    </a>
    <h2 style="margin:0;font-size:22px;font-weight:700;"><?php echo e($dept['name']); ?></h2>
    <?php if ($isAdmin || ($isManager && $editId == $managerDeptId)): ?>
    <a href="<?php echo url('department', ['action' => 'edit', 'id' => $editId]); ?>" class="btn btn-outline" style="margin-left:auto;">Edit</a>
    <?php endif; ?>
</div>
<div class="hrms-card" style="margin-bottom:20px;">
    <div class="card-body" style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;">
        <div><div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;">Location</div><div style="font-weight:600;"><?php echo e($dept['location'] ?: ''); ?></div></div>
        <div><div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;">Budget</div><div style="font-weight:600;"><?php echo $dept['budget'] > 0 ? '&#8369;'.number_format($dept['budget'],0) : ''; ?></div></div>
        <div><div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;">Employees</div><div style="font-weight:600;"><?php echo $dept['emp_count']; ?></div></div>
        <div><div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;">Description</div><div style="font-size:13px;color:var(--text-muted);"><?php echo e($dept['description'] ?: 'No description.'); ?></div></div>
    </div>
</div>
<div class="hrms-card">
    <div class="card-header"><h3>Employees in <?php echo e($dept['name']); ?></h3></div>
    <div class="card-body" style="padding:0;">
        <table class="table hrms-table" style="margin:0;">
            <thead><tr><th>Name</th><th>Job Title</th><th>Status</th><th>Email</th></tr></thead>
            <tbody>
            <?php foreach ($deptEmployees as $emp): ?>
            <tr>
                <td><a href="<?php echo url('employees', ['action' => 'view', 'id' => $emp['id']]); ?>" style="color:var(--accent);"><?php echo e($emp['first_name'] . ' ' . $emp['last_name']); ?></a></td>
                <td><?php echo e($emp['job_title'] ?? ''); ?></td>
                <td><span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $emp['status'] ?? '')); ?>"><?php echo e($emp['status'] ?? ''); ?></span></td>
                <td><?php echo e($emp['email'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($deptEmployees)): ?>
            <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted);">No employees in this department.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!--  Summary Stats  -->
<?php
$totalEmp = array_sum(array_column($depts, 'emp_count'));
$avgTeam  = count($depts) > 0 ? round($totalEmp / count($depts), 1) : 0;
?>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:24px;">
    <div class="hrms-card" style="border-left:4px solid #1e88e5;padding:20px;">
        <p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;text-transform:uppercase;">Total Departments</p>
        <p style="font-size:32px;font-weight:700;margin:0;"><?php echo count($depts); ?></p>
    </div>
    <div class="hrms-card" style="border-left:4px solid #43a047;padding:20px;">
        <p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;text-transform:uppercase;">Total Employees</p>
        <p style="font-size:32px;font-weight:700;margin:0;"><?php echo $totalEmp; ?></p>
    </div>
    <div class="hrms-card" style="border-left:4px solid #fb8c00;padding:20px;">
        <p style="font-size:12px;color:var(--text-muted);margin:0 0 4px;text-transform:uppercase;">Avg Team Size</p>
        <p style="font-size:32px;font-weight:700;margin:0;"><?php echo $avgTeam; ?></p>
    </div>
</div>

<!--  Department Cards  -->
<?php if (empty($depts)): ?>
<div class="hrms-card" style="text-align:center;padding:48px;">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" style="margin-bottom:16px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    <p style="color:var(--text-muted);font-size:16px;margin:0;">No departments yet. <a href="<?php echo url('department', ['action' => 'create']); ?>" style="color:var(--accent);">Add one now</a>.</p>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;">
<?php foreach ($depts as $i => $d):
    $color = $colors[$i % count($colors)];
    $hex22 = $color . '22'; // 13% opacity
?>
<div class="hrms-card" style="overflow:hidden;transition:transform .2s,box-shadow .2s;cursor:pointer;"
     onmouseenter="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 32px rgba(0,0,0,0.28)';"
     onmouseleave="this.style.transform='';this.style.boxShadow='';">
    <!-- Colour accent bar -->
    <div style="height:5px;background:<?php echo $color; ?>;"></div>
    <div class="card-body" style="padding:24px;">
        <!-- Icon + action buttons row -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;">
            <div style="width:48px;height:48px;border-radius:10px;background:<?php echo $hex22; ?>;display:flex;align-items:center;justify-content:center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="<?php echo $color; ?>" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <?php if ($isAdmin || ($isManager && $d['id'] == $managerDeptId)): ?>
            <div style="display:flex;gap:8px;">
                <a href="<?php echo url('department', ['action' => 'edit', 'id' => $d['id']]); ?>"
                   style="width:32px;height:32px;border:1px solid var(--border);border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none;" title="Edit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </a>
                <?php if ($isAdmin): ?>
                <form method="POST" style="margin:0;" onsubmit="return confirm('Delete <?php echo e($d['name']); ?>?');">
                    <input type="hidden" name="_action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                    <button type="submit" style="width:32px;height:32px;border:1px solid var(--border);border-radius:6px;display:flex;align-items:center;justify-content:center;color:#ef5350;background:none;cursor:pointer;" title="Delete">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Name & description -->
        <h3 style="font-size:18px;font-weight:700;margin:0 0 6px;"><?php echo e($d['name']); ?></h3>
        <?php if ($d['location']): ?>
        <span style="font-size:10px;background:rgba(255,255,255,0.08);padding:2px 6px;border-radius:4px;border:1px solid var(--border);color:var(--text-muted);display:inline-block;margin-bottom:8px;">
             <?php echo e($d['location']); ?>
        </span>
        <?php endif; ?>
        <p style="font-size:13px;color:var(--text-muted);margin:0 0 16px;"><?php echo e($d['description'] ?: 'No description provided.'); ?></p>

        <!-- Stats row -->
        <div style="display:flex;gap:16px;margin-bottom:20px;">
            <div style="flex:1;text-align:center;padding:12px;background:rgba(255,255,255,0.03);border-radius:8px;border:1px solid var(--border);">
                <div style="font-size:22px;font-weight:700;color:<?php echo $color; ?>;"><?php echo $d['emp_count']; ?></div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">Employees</div>
            </div>
            <div style="flex:1;text-align:center;padding:12px;background:rgba(255,255,255,0.03);border-radius:8px;border:1px solid var(--border);">
                <div style="font-size:22px;font-weight:700;">&#8369;<?php 
                    if ($d['budget'] >= 1000000) {
                        echo number_format($d['budget'] / 1000000, 1) . 'm';
                    } elseif ($d['budget'] > 0) {
                        echo number_format($d['budget'] / 1000, 0) . 'k';
                    } else {
                        echo 'N/A';
                    }
                ?></div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">Budget</div>
            </div>
        </div>

        <!-- View button -->
        <a href="<?php echo url('department', ['action' => 'view', 'id' => $d['id']]); ?>"
           class="btn btn-outline" style="width:100%;justify-content:center;display:flex;align-items:center;gap:6px;">
            View Department
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
