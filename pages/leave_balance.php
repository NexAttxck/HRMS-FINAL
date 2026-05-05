<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/leave_policy.php';
Auth::check();
if (!Auth::isAdmin() && !Auth::isManager()) { header('Location: '.url('dashboard')); exit; }
$pageTitle = 'Leave Balances — ' . APP_NAME;
$isAdmin = Auth::isAdmin();

// Handle manual adjustment
if ($_SERVER['REQUEST_METHOD']==='POST' && $isAdmin) {
    $empId = (int)$_POST['emp_id'];
    $year  = (int)$_POST['year'];
    $field = $_POST['field'] ?? '';
    $val   = (float)$_POST['value'];
    $allowed = ['sil_accrued','sil_used','sil_carried_over'];
    if (in_array($field, $allowed) && $empId && $year) {
        // Ensure balance row exists
        LeavePolicy::getBalance($empId, $year);
        if ($field === 'sil_accrued') {
            DB::execute("UPDATE leave_balance SET $field=?, sil_manual_override=1, updated_at=? WHERE employee_id=? AND year=?", [$val, time(), $empId, $year]);
        } else {
            DB::execute("UPDATE leave_balance SET $field=?, updated_at=? WHERE employee_id=? AND year=?", [$val, time(), $empId, $year]);
        }
        Auth::flash('success', 'Balance updated!');
    }
    header('Location: '.url('leave_balance')); exit;
}

$year = (int)($_GET['year'] ?? date('Y'));
$prevYear = $year - 1;
$nextYear = $year + 1;

// Load all active employees with their balances
$employees = DB::fetchAll("SELECT e.id, e.first_name, e.last_name, e.employee_no, e.gender, e.hire_date, e.date_regularized, e.date_of_birth, e.solo_parent_id, d.name as dept_name
    FROM employee e LEFT JOIN department d ON e.department_id=d.id
    WHERE e.status IN ('Regular','Probationary')
    ORDER BY e.first_name, e.last_name");

// Sync and load all balances
$balances = [];
foreach ($employees as $emp) {
    LeavePolicy::syncAccrued($emp['id'], $emp, $year);
    $bal = LeavePolicy::getBalance($emp['id'], $year);
    $balances[$emp['id']] = $bal;
}

require_once __DIR__ . '/../includes/layout_header.php';
?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-size:24px;font-weight:700;margin:0;">Leave Balances</h1>
        <p style="color:var(--text-muted);margin:4px 0 0;font-size:13px;">View and manage employee leave credits</p>
    </div>
    <!-- Year nav -->
    <div style="display:flex;align-items:center;gap:10px;">
        <a href="<?php echo url('leave_balance',['year'=>$prevYear]); ?>" class="btn btn-outline btn-sm">‹ <?php echo $prevYear; ?></a>
        <span style="font-size:18px;font-weight:700;padding:0 8px;"><?php echo $year; ?></span>
        <a href="<?php echo url('leave_balance',['year'=>$nextYear]); ?>" class="btn btn-outline btn-sm"><?php echo $nextYear; ?> ›</a>
    </div>
</div>

<!-- Policy Summary Card -->
<div class="hrms-card" style="margin-bottom:20px;padding:20px;">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;">
        <div style="padding:14px;background:rgba(255,255,255,0.03);border-radius:10px;border:1px solid var(--border);">
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;">Accrual Rate</div>
            <div style="font-size:20px;font-weight:700;color:var(--accent);"><?php echo LeavePolicy::SIL_RATE; ?> <span style="font-size:12px;font-weight:400;">SIL/month</span></div>
        </div>
        <div style="padding:14px;background:rgba(255,255,255,0.03);border-radius:10px;border:1px solid var(--border);">
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;">Annual Cap</div>
            <div style="font-size:20px;font-weight:700;color:var(--accent);"><?php echo LeavePolicy::SIL_MAX_YEAR; ?> <span style="font-size:12px;font-weight:400;">days max</span></div>
        </div>
        <div style="padding:14px;background:rgba(255,255,255,0.03);border-radius:10px;border:1px solid var(--border);">
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;">Convertible (Feb)</div>
            <div style="font-size:20px;font-weight:700;color:#fb8c00;"><?php echo LeavePolicy::SIL_CONVERTIBLE; ?> <span style="font-size:12px;font-weight:400;">days max</span></div>
        </div>
        <div style="padding:14px;background:rgba(255,255,255,0.03);border-radius:10px;border:1px solid var(--border);">
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;">Carryover Expires</div>
            <div style="font-size:20px;font-weight:700;color:#ef5350;">Apr 1 <span style="font-size:12px;font-weight:400;">annually</span></div>
        </div>
    </div>
</div>

<!-- Balance Table -->
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;">
<thead><tr>
    <th>Employee</th>
    <th>Department</th>
    <th style="text-align:center;">Eligible</th>
    <th style="text-align:center;">Accrued SIL</th>
    <th style="text-align:center;">Used SIL</th>
    <th style="text-align:center;">Carried Over</th>
    <th style="text-align:center;">Remaining</th>
    <th style="text-align:center;">Birthday</th>
    <?php if($isAdmin): ?><th style="text-align:center;">Adjust</th><?php endif; ?>
</tr></thead>
<tbody>
<?php foreach($employees as $emp):
    $bal = $balances[$emp['id']] ?? [];
    $eligible = LeavePolicy::isSilEligible($emp);
    $accrued = (float)($bal['sil_accrued'] ?? 0);
    $used    = (float)($bal['sil_used'] ?? 0);
    // Check carryover expiry
    $carryover = (float)($bal['sil_carried_over'] ?? 0);
    if (time() >= mktime(0,0,0,4,1,$year)) $carryover = 0;
    $remaining = round($accrued - $used + $carryover, 2);
    $bdayUsed = (bool)($bal['birthday_used'] ?? 0);
?>
<tr>
    <td>
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                <?php echo strtoupper(substr($emp['first_name'],0,1).substr($emp['last_name'],0,1)); ?>
            </div>
            <div>
                <div style="font-weight:600;font-size:13px;"><?php echo e($emp['first_name'].' '.$emp['last_name']); ?></div>
                <div style="font-size:11px;color:var(--text-muted);"><?php echo e($emp['employee_no']??''); ?></div>
            </div>
        </div>
    </td>
    <td style="font-size:12px;color:var(--text-muted);"><?php echo e($emp['dept_name']??''); ?></td>
    <td style="text-align:center;">
        <?php if($eligible): ?>
        <span class="badge badge-success" style="font-size:10px;">Eligible</span>
        <?php else: ?>
        <span class="badge badge-warning" style="font-size:10px;">Pending</span>
        <?php endif; ?>
    </td>
    <td style="text-align:center;font-weight:600;"><?php echo number_format($accrued,2); ?></td>
    <td style="text-align:center;color:#ef5350;font-weight:600;"><?php echo number_format($used,2); ?></td>
    <td style="text-align:center;color:#fb8c00;"><?php echo number_format($carryover,2); ?></td>
    <td style="text-align:center;">
        <span style="font-weight:700;color:<?php echo $remaining>0?'var(--accent)':'#ef5350'; ?>;">
            <?php echo number_format($remaining,2); ?>
        </span>
    </td>
    <td style="text-align:center;">
        <?php if($bdayUsed): ?>
        <span class="badge badge-muted" style="font-size:10px;">Used</span>
        <?php else: ?>
        <span class="badge badge-success" style="font-size:10px;">Available</span>
        <?php endif; ?>
    </td>
    <?php if($isAdmin): ?>
    <td style="text-align:center;">
        <button onclick="openAdjust(<?php echo $emp['id']; ?>, '<?php echo e($emp['first_name'].' '.$emp['last_name']); ?>', <?php echo $accrued; ?>, <?php echo $used; ?>, <?php echo $carryover; ?>)"
            class="btn btn-sm btn-outline" style="padding:3px 10px;font-size:11px;">Adjust</button>
    </td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if(empty($employees)): ?>
<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">No employees found.</td></tr>
<?php endif; ?>
</tbody></table></div></div>

<?php if($isAdmin): ?>
<!-- Adjust Modal -->
<div id="adjustModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:9999;justify-content:center;align-items:center;">
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:16px;width:400px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,0.5);">
        <h3 style="margin:0 0 4px;font-size:18px;">Adjust Leave Credits</h3>
        <p id="adjustName" style="margin:0 0 20px;font-size:13px;color:var(--text-muted);"></p>
        <form method="POST">
            <input type="hidden" name="emp_id" id="adjEmpId">
            <input type="hidden" name="year" value="<?php echo $year; ?>">
            <div style="margin-bottom:14px;">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:6px;text-transform:uppercase;">Field to Adjust</label>
                <select name="field" id="adjField" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                    <option value="sil_accrued">SIL Accrued</option>
                    <option value="sil_used">SIL Used</option>
                    <option value="sil_carried_over">Carried Over</option>
                </select>
            </div>
            <div style="margin-bottom:20px;">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:6px;text-transform:uppercase;">New Value (days)</label>
                <input type="number" name="value" id="adjValue" step="0.25" min="0" max="60" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-accent" style="flex:1;">Save Changes</button>
                <button type="button" onclick="closeAdjust()" class="btn btn-outline" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script>
function openAdjust(empId, name, accrued, used, carryover) {
    document.getElementById('adjEmpId').value = empId;
    document.getElementById('adjustName').textContent = name;
    document.getElementById('adjValue').value = accrued;
    document.getElementById('adjustModal').style.display = 'flex';
    document.getElementById('adjField').onchange = function() {
        var v = this.value==='sil_accrued'?accrued:(this.value==='sil_used'?used:carryover);
        document.getElementById('adjValue').value = v;
    };
}
function closeAdjust() { document.getElementById('adjustModal').style.display = 'none'; }
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
