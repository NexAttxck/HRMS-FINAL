<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
Auth::check();
$pageTitle = 'Timelog Disputes — ' . APP_NAME;
$isAdmin = Auth::isAdmin(); $isManager = Auth::isManager(); $empId = Auth::empId(); $userId = Auth::id();
$action = $_GET['action'] ?? 'index';
$deptId = Auth::deptId();
// Fallback: if manager's session dept is null, look it up from employee record
if ($isManager && !$isAdmin && !$deptId) {
    $deptId = (int)DB::fetchScalar("SELECT department_id FROM employee WHERE user_id=? AND department_id IS NOT NULL LIMIT 1", [Auth::id()]);
}

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['_action'] ?? '';

    if ($a === 'file' && !$isAdmin) {
        $disputeDate = $_POST['dispute_date'];
        // Cannot dispute today or future
        if (strtotime($disputeDate) >= strtotime('today')) {
            Auth::flash('error', 'You can only dispute past dates.');
            header('Location: '.url('timelog_dispute',['action'=>'new'])); exit;
        }

        // Get original attendance
        $att = DB::fetchOne("SELECT * FROM attendance WHERE employee_id=? AND date=?", [$empId, $disputeDate]);
        $origIn = $att['clock_in'] ?? null;
        $origOut = $att['clock_out'] ?? null;

        // Handle document upload
        $docPath = null;
        if (!empty($_FILES['doc']['name']) && $_FILES['doc']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/disputes/' . $empId . '/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['doc']['name'], PATHINFO_EXTENSION);
            $uniqueName = 'dispute_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['doc']['tmp_name'], $uploadDir . $uniqueName)) {
                $docPath = 'uploads/disputes/' . $empId . '/' . $uniqueName;
            }
        }

        DB::execute("INSERT INTO timelog_dispute (employee_id, dispute_date, original_clock_in, original_clock_out, corrected_clock_in, corrected_clock_out, break_minutes, dispute_type, reason, doc_path, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,'Pending',?)",
            [$empId, $disputeDate, $origIn, $origOut, $_POST['corrected_clock_in'] ?: null, $_POST['corrected_clock_out'] ?: null, (int)($_POST['break_minutes'] ?? 60), $_POST['dispute_type'], $_POST['reason'] ?? null, $docPath, time()]);

        Auth::flash('success', 'Dispute filed successfully!');
        header('Location: '.url('timelog_dispute')); exit;
    }

    if (($isAdmin || $isManager) && in_array($a, ['approve','deny'])) {
        $id = (int)$_POST['id'];
        // Managers can only act on disputes within their department
        if ($isManager && !$isAdmin) {
            $dispDept = (int)DB::fetchScalar("SELECT e.department_id FROM timelog_dispute td JOIN employee e ON td.employee_id=e.id WHERE td.id=?", [$id]);
            if ($dispDept !== (int)$deptId) {
                Auth::flash('error', 'Access denied: this dispute is outside your department.');
                header('Location: '.url('timelog_dispute')); exit;
            }
        }
        $status = $a === 'approve' ? 'Approved' : 'Denied';
        DB::execute("UPDATE timelog_dispute SET status=?, reviewed_by=?, reviewed_at=? WHERE id=?", [$status, $userId, time(), $id]);

        $dispute = DB::fetchOne("SELECT * FROM timelog_dispute WHERE id=?", [$id]);
        if ($dispute) {
            // On approve, update or create the attendance record
            if ($a === 'approve') {
                $att = DB::fetchOne("SELECT * FROM attendance WHERE employee_id=? AND date=?", [$dispute['employee_id'], $dispute['dispute_date']]);
                $cin  = $dispute['corrected_clock_in'];
                $cout = $dispute['corrected_clock_out'];
                $breakH = ($dispute['break_minutes'] ?? 60) / 60;
                $totalH = 0;
                if ($cin && $cout) {
                    $totalH = round((strtotime($cout) - strtotime($cin)) / 3600 - $breakH, 2);
                    $totalH = max(0, $totalH);
                }

                if ($att) {
                    DB::execute("UPDATE attendance SET clock_in=?, clock_out=?, total_hours=?, notes=CONCAT(COALESCE(notes,''), ' [Corrected via dispute #$id]') WHERE id=?",
                        [$cin, $cout, $totalH, $att['id']]);
                } else {
                    DB::execute("INSERT INTO attendance (employee_id, date, clock_in, clock_out, status, total_hours, notes, created_at) VALUES (?,?,?,?,'On Time',?,?,?)",
                        [$dispute['employee_id'], $dispute['dispute_date'], $cin, $cout, $totalH, 'Created via dispute #'.$id, time()]);
                }
            }

            // Notify employee
            $statusMsg = $a === 'approve' ? 'approved and your timelog has been corrected' : 'denied';
            $notifTitle = 'Timelog Dispute ' . $status;
            $notifMsg = 'Your timelog dispute for ' . $dispute['dispute_date'] . ' has been ' . $statusMsg . '.';
            DB::execute("INSERT INTO system_notification (user_id, type, title, message, link, is_read, created_at) SELECT u.id, ?, ?, ?, ?, 0, NOW() FROM employee e JOIN user u ON e.user_id=u.id WHERE e.id=?",
                [$a === 'approve' ? 'success' : 'danger', $notifTitle, $notifMsg, url('timelog_dispute'), $dispute['employee_id']]);
        }

        Auth::flash('success', "Dispute $status.");
        header('Location: '.url('timelog_dispute')); exit;
    }
}

// Fetch disputes
if ($isAdmin || $isManager) {
    $statusFilter = $_GET['status'] ?? '';
    $where = '1=1'; $params = [];
    if ($statusFilter) { $where .= " AND td.status=?"; $params[] = $statusFilter; }
    // Managers only see disputes from their own department
    if ($isManager && !$isAdmin && $deptId) {
        $where .= " AND e.department_id=?";
        $params[] = $deptId;
    }
    $disputes = DB::fetchAll("SELECT td.*, e.first_name, e.last_name, e.employee_no, e.department_id FROM timelog_dispute td JOIN employee e ON td.employee_id=e.id WHERE $where ORDER BY td.created_at DESC", $params);
} else {
    $disputes = DB::fetchAll("SELECT td.*, e.first_name, e.last_name FROM timelog_dispute td JOIN employee e ON td.employee_id=e.id WHERE td.employee_id=? ORDER BY td.created_at DESC", [$empId]);
}

// For new dispute: get recent attendance
$recentAtt = [];
if (!$isAdmin && $empId) {
    $recentAtt = DB::fetchAll("SELECT * FROM attendance WHERE employee_id=? AND date < CURDATE() ORDER BY date DESC LIMIT 14", [$empId]);
}

require_once __DIR__ . '/../includes/layout_header.php';
$inputStyle = "width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;";
$labelStyle = "display:block;font-size:11px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;";
?>
<style>
.dispute-card{background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:18px;transition:all .15s}
.dispute-card:hover{border-color:rgba(255,255,255,0.12)}
.dt-badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase}
.dt-correction{background:rgba(30,136,229,0.15);color:#42a5f5}
.dt-schedule{background:rgba(251,140,0,0.15);color:#ffa726}
.dt-generic{background:rgba(171,71,188,0.15);color:#ce93d8}
.time-compare{display:flex;align-items:center;gap:12px;margin:10px 0;font-size:12px}
.time-compare .old{color:#ef5350;text-decoration:line-through;opacity:.7}
.time-compare .new{color:var(--accent);font-weight:700}
.time-compare .arrow{color:var(--text-muted);font-size:16px}
.att-quick{padding:8px 12px;border:1px solid var(--border);border-radius:8px;cursor:pointer;transition:all .15s;font-size:12px}
.att-quick:hover{border-color:var(--accent);background:rgba(124,147,104,0.06)}
.att-quick.selected{border-color:var(--accent);background:rgba(124,147,104,0.1)}
</style>

<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-size:24px;font-weight:700;margin:0;">Timelog Disputes</h1>
        <p style="color:var(--text-muted);margin:4px 0 0;font-size:13px;"><?php echo ($isAdmin||$isManager) ? 'Review and manage employee timelog disputes' : 'Correct your attendance records'; ?></p>
    </div>
    <div style="display:flex;gap:8px;">
        <?php if($isAdmin||$isManager): ?>
        <a href="<?php echo url('timelog_dispute'); ?>" class="btn btn-sm <?php echo !isset($_GET['status'])?'btn-accent':'btn-outline'; ?>">All</a>
        <a href="<?php echo url('timelog_dispute',['status'=>'Pending']); ?>" class="btn btn-sm <?php echo ($_GET['status']??'')==='Pending'?'btn-accent':'btn-outline'; ?>">Pending</a>
        <a href="<?php echo url('timelog_dispute',['status'=>'Approved']); ?>" class="btn btn-sm <?php echo ($_GET['status']??'')==='Approved'?'btn-accent':'btn-outline'; ?>">Approved</a>
        <a href="<?php echo url('timelog_dispute',['status'=>'Denied']); ?>" class="btn btn-sm <?php echo ($_GET['status']??'')==='Denied'?'btn-accent':'btn-outline'; ?>">Denied</a>
        <?php endif; ?>
        <?php if(!$isAdmin): ?>
        <a href="<?php echo url('timelog_dispute',['action'=>'new']); ?>" class="btn btn-accent">+ File Dispute</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'new' && !$isAdmin): ?>
<!-- File New Dispute -->
<div class="hrms-card" style="margin-bottom:24px;">
    <div class="card-header"><h3 style="margin:0;">File a Timelog Dispute</h3></div>
    <div class="card-body" style="padding:24px;">
        <?php if (!empty($recentAtt)): ?>
        <div style="margin-bottom:20px;">
            <label style="<?php echo $labelStyle; ?>">Quick Select — Recent Attendance</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php foreach($recentAtt as $ra): ?>
                <div class="att-quick" onclick="selectDate('<?php echo $ra['date']; ?>','<?php echo $ra['clock_in']??''; ?>','<?php echo $ra['clock_out']??''; ?>')">
                    <div style="font-weight:600;"><?php echo date('M j', strtotime($ra['date'])); ?></div>
                    <div style="color:var(--text-muted);font-size:10px;"><?php echo $ra['clock_in'] ? date('g:i A', strtotime($ra['clock_in'])) : '—'; ?> – <?php echo $ra['clock_out'] ? date('g:i A', strtotime($ra['clock_out'])) : '—'; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="_action" value="file">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label style="<?php echo $labelStyle; ?>">Dispute Date</label>
                    <input type="date" name="dispute_date" id="disputeDate" required max="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" style="<?php echo $inputStyle; ?>">
                </div>
                <div>
                    <label style="<?php echo $labelStyle; ?>">Dispute Type</label>
                    <select name="dispute_type" style="<?php echo $inputStyle; ?>">
                        <option value="Timelog Correction">Timelog Correction</option>
                        <option value="Schedule Issue">Schedule Issue</option>
                        <option value="Generic">Generic Issue</option>
                    </select>
                </div>
                <div>
                    <label style="<?php echo $labelStyle; ?>">Corrected Clock In</label>
                    <input type="time" name="corrected_clock_in" id="corrIn" style="<?php echo $inputStyle; ?>">
                </div>
                <div>
                    <label style="<?php echo $labelStyle; ?>">Corrected Clock Out</label>
                    <input type="time" name="corrected_clock_out" id="corrOut" style="<?php echo $inputStyle; ?>">
                </div>
                <div>
                    <label style="<?php echo $labelStyle; ?>">Break Duration (minutes)</label>
                    <input type="number" name="break_minutes" value="60" min="0" max="240" style="<?php echo $inputStyle; ?>">
                </div>
                <div>
                    <label style="<?php echo $labelStyle; ?>">Supporting Document</label>
                    <input type="file" name="doc" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="<?php echo $inputStyle; ?>padding:7px 12px;">
                </div>
            </div>
            <div style="margin-top:16px;">
                <label style="<?php echo $labelStyle; ?>">Reason / Explanation</label>
                <textarea name="reason" rows="3" required placeholder="Explain why you need this correction..." style="<?php echo $inputStyle; ?>resize:vertical;"></textarea>
            </div>
            <div style="margin-top:16px;display:flex;gap:12px;">
                <button type="submit" class="btn btn-accent">Submit Dispute</button>
                <a href="<?php echo url('timelog_dispute'); ?>" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
function selectDate(date, cin, cout) {
    document.getElementById('disputeDate').value = date;
    if (cin) document.getElementById('corrIn').value = cin;
    if (cout) document.getElementById('corrOut').value = cout;
    document.querySelectorAll('.att-quick').forEach(el => el.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
}
</script>
<?php endif; ?>

<!-- Disputes List -->
<div style="display:flex;flex-direction:column;gap:12px;">
<?php if(empty($disputes)): ?>
    <div class="hrms-card" style="text-align:center;padding:60px;">
        <p style="color:var(--text-muted);font-size:14px;">No timelog disputes found.</p>
        <?php if(!$isAdmin): ?>
        <a href="<?php echo url('timelog_dispute',['action'=>'new']); ?>" class="btn btn-accent" style="margin-top:12px;">+ File Your First Dispute</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php foreach($disputes as $d):
    $typeClass = match($d['dispute_type']) { 'Timelog Correction'=>'dt-correction','Schedule Issue'=>'dt-schedule',default=>'dt-generic' };
    $statusBadge = match($d['status']) { 'Approved'=>'badge-success','Denied'=>'badge-danger',default=>'badge-warning' };
?>
<div class="dispute-card">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                <?php if($isAdmin||$isManager): ?>
                <div style="width:30px;height:30px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;">
                    <?php echo strtoupper(substr($d['first_name'],0,1).substr($d['last_name'],0,1)); ?>
                </div>
                <div>
                    <div style="font-weight:600;font-size:13px;"><?php echo e($d['first_name'].' '.$d['last_name']); ?></div>
                    <div style="font-size:11px;color:var(--text-muted);"><?php echo e($d['employee_no'] ?? ''); ?></div>
                </div>
                <?php endif; ?>
                <span class="dt-badge <?php echo $typeClass; ?>"><?php echo $d['dispute_type']; ?></span>
                <span class="badge <?php echo $statusBadge; ?>" style="font-size:10px;"><?php echo $d['status']; ?></span>
            </div>

            <div style="font-size:13px;font-weight:600;margin-bottom:4px;">
                <?php echo date('l, F j, Y', strtotime($d['dispute_date'])); ?>
            </div>

            <div class="time-compare">
                <span class="old"><?php echo $d['original_clock_in'] ? date('g:i A', strtotime($d['original_clock_in'])) : 'No record'; ?> – <?php echo $d['original_clock_out'] ? date('g:i A', strtotime($d['original_clock_out'])) : 'No record'; ?></span>
                <span class="arrow">→</span>
                <span class="new"><?php echo $d['corrected_clock_in'] ? date('g:i A', strtotime($d['corrected_clock_in'])) : '—'; ?> – <?php echo $d['corrected_clock_out'] ? date('g:i A', strtotime($d['corrected_clock_out'])) : '—'; ?></span>
                <span style="font-size:10px;color:var(--text-muted);">(<?php echo $d['break_minutes']; ?>m break)</span>
            </div>

            <?php if($d['reason']): ?>
            <p style="font-size:12px;color:var(--text-muted);margin:6px 0 0;line-height:1.5;"><?php echo e($d['reason']); ?></p>
            <?php endif; ?>

            <?php if($d['doc_path']): ?>
            <a href="<?php echo e('/' . ltrim($d['doc_path'], '/')); ?>" target="_blank" style="font-size:11px;color:var(--accent);display:inline-flex;align-items:center;gap:4px;margin-top:6px;">📎 View Document</a>
            <?php endif; ?>
        </div>

        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
            <span style="font-size:10px;color:var(--text-muted);"><?php echo date('M j, g:i A', $d['created_at']); ?></span>
            <?php if(($isAdmin||$isManager) && $d['status'] === 'Pending'): ?>
            <div style="display:flex;gap:6px;">
                <form method="POST" style="display:inline;"><input type="hidden" name="_action" value="approve"><input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-accent" style="padding:5px 14px;font-size:11px;">Approve</button>
                </form>
                <form method="POST" style="display:inline;"><input type="hidden" name="_action" value="deny"><input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline" style="padding:5px 14px;font-size:11px;color:#ef5350;border-color:#ef5350;">Deny</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
