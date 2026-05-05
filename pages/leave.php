<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/leave_policy.php';
Auth::check();
$pageTitle = 'Leave Management — ' . APP_NAME;
$isAdmin=Auth::isAdmin(); $isManager=Auth::isManager(); $empId=Auth::empId(); $userId=Auth::id(); $deptId=Auth::deptId();
if ($isManager && !$isAdmin && !$deptId) {
    $deptId = (int)DB::fetchScalar("SELECT department_id FROM employee WHERE user_id=? AND department_id IS NOT NULL LIMIT 1", [$userId]);
}
$action=$_GET['action']??'index';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $a=$_POST['_action']??'';
    if ($a==='apply') {
        if ($isAdmin) { Auth::flash('error', 'Super Admins cannot apply for leave.'); header('Location: ' . url('leave')); exit; }
        $start = new DateTime($_POST['start_date']);
        $end = new DateTime($_POST['end_date']);
        $days = 0;
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
        foreach ($period as $dt) {
            $curr = $dt->format('N');
            if ($curr != 6 && $curr != 7) {
                $days++;
            }
        }
        $days = max(1, $days);

        // Backend limit validation
        $empData = DB::fetchOne("SELECT * FROM employee WHERE id=?", [$empId]);
        $eligibility = LeavePolicy::checkEligibility($empData, $empId);
        $leaveType = $_POST['leave_type'];
        $maxDays = $eligibility[$leaveType]['max_days'] ?? 0;
        if ($days > $maxDays && $leaveType !== 'Leave Without Pay') {
            Auth::flash('error', "Limit Exceeded: You only have $maxDays days available for $leaveType.");
            header('Location: ' . url('leave', ['action'=>'apply'])); exit;
        }

        // Handle file upload for supporting documents
        $docPath = null;
        if (!empty($_FILES['support_doc']['name']) && $_FILES['support_doc']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/leave_docs/' . $empId . '/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['support_doc']['name'], PATHINFO_EXTENSION);
            $uniqueName = 'leave_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['support_doc']['tmp_name'], $uploadDir . $uniqueName)) {
                $docPath = 'uploads/leave_docs/' . $empId . '/' . $uniqueName;
            }
        }
        DB::execute("INSERT INTO leave_request (employee_id,leave_type,start_date,end_date,days,reason,doc_path,deducted_from,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,'Pending',?,?)",[$empId,$_POST['leave_type'],$_POST['start_date'],$_POST['end_date'],$days,$_POST['reason']??null,$docPath,null,time(),time()]);
        Auth::audit('Leave Request Submitted', 'Leave', $empId, ($_POST['leave_type'] ?? '') . ': ' . ($_POST['start_date'] ?? '') . ' to ' . ($_POST['end_date'] ?? ''));
        Auth::flash('success','Leave request submitted!'); header('Location: '.url('leave')); exit;
    }
    if (($isAdmin||$isManager) && in_array($a,['approve','deny','undo'])) {
        $id=(int)$_POST['id']; 
        $lr=DB::fetchOne("SELECT * FROM leave_request WHERE id=?",[$id]);
        $status = 'Pending';
        if ($a==='approve') $status='Approved';
        if ($a==='deny') $status='Denied';
        
        // Deduct credits on approve, reverse on undo
        $deductedFrom = $lr['deducted_from'] ?? null;
        if ($a==='approve' && $lr) {
            $deductedFrom = LeavePolicy::deductCredits($lr['employee_id'], $lr['leave_type'], (float)$lr['days']);
            DB::execute("UPDATE leave_request SET status=?,approved_by=?,deducted_from=?,updated_at=? WHERE id=?",[$status,$userId,$deductedFrom,time(),$id]);
        } elseif ($a==='undo' && $lr && $lr['status']==='Approved' && $deductedFrom) {
            LeavePolicy::reverseCredits($lr['employee_id'], $deductedFrom, (float)$lr['days']);
            DB::execute("UPDATE leave_request SET status='Pending',approved_by=NULL,deducted_from=NULL,updated_at=? WHERE id=?",[time(),$id]);
        } else {
            DB::execute("UPDATE leave_request SET status=?,approved_by=?,updated_at=? WHERE id=?",[$status,($status==='Pending'?null:$userId),time(),$id]);
        }
        
        if ($lr && $a!=='undo') {
            DB::execute("INSERT INTO system_notification (user_id,type,title,message,link,is_read,created_at) SELECT u.id,?,?,?,?,0,NOW() FROM employee e JOIN `user` u ON e.user_id=u.id WHERE e.id=?",[$status==='Approved'?'success':'danger',"Leave Request $status","Your leave request has been $status.",url('my_leave'),$lr['employee_id']]);
        }
        $msg = $a==='undo' ? 'reverted to Pending' : strtolower($status);
        Auth::audit('Leave Request ' . $status, 'Leave', $id, 'Status: ' . $status);
        Auth::flash('success',"Leave request $msg!"); header('Location: '.url('leave')); exit;
    }
}

// Employees always see only their own requests (except view)
if (!$isAdmin && !$isManager && $action !== 'apply' && $action !== 'new' && $action !== 'view') {
    $action = 'my';
}

// Individual view (admin sees any, employee sees only their own)
$viewRequest = null;
if ($action === 'view') {
    $vid = (int)($_GET['id'] ?? 0);
    if ($isAdmin || $isManager) {
        $viewRequest = DB::fetchOne("SELECT lr.*, e.first_name, e.last_name, e.employee_no, e.email, d.name as dept_name FROM leave_request lr JOIN employee e ON lr.employee_id=e.id LEFT JOIN department d ON e.department_id=d.id WHERE lr.id=?", [$vid]);
    } else {
        $viewRequest = DB::fetchOne("SELECT lr.*, e.first_name, e.last_name, e.employee_no, e.email, d.name as dept_name FROM leave_request lr JOIN employee e ON lr.employee_id=e.id LEFT JOIN department d ON e.department_id=d.id WHERE lr.id=? AND lr.employee_id=?", [$vid, $empId]);
    }
}

if ($action==='my') {
    $records=DB::fetchAll("SELECT * FROM leave_request WHERE employee_id=? ORDER BY created_at DESC",[$empId]);
} else {
    $where='1=1'; $params=[];
    if (!$isAdmin&&!$isManager) { $where.=" AND lr.employee_id=?"; $params[]=$empId; }
    // Managers can only see leave requests for their own department
    if ($isManager&&!$isAdmin) { $where.=" AND e.department_id=?"; $params[]=$deptId; }
    $statusF=$_GET['status']??'';
    if ($statusF) { $where.=" AND lr.status=?"; $params[]=$statusF; }
    $records=DB::fetchAll("SELECT lr.*,e.first_name,e.last_name FROM leave_request lr JOIN employee e ON lr.employee_id=e.id WHERE $where ORDER BY lr.created_at DESC",$params);
}
$leaveTypes = LeavePolicy::TYPES;
// Load employee data + eligibility for the apply form
$empData = null; $eligibility = []; $silRemaining = 0;
if (!$isAdmin && $empId) {
    $empData = DB::fetchOne("SELECT * FROM employee WHERE id=?", [$empId]);
    if ($empData) {
        LeavePolicy::syncAccrued($empId, $empData);
        $eligibility = LeavePolicy::checkEligibility($empData, $empId);
        $silRemaining = LeavePolicy::silRemaining($empId, $empData);
    }
}
require_once __DIR__ . '/../includes/layout_header.php';
?>
<style>
/* Leave Application Styles */
.leave-radio-grid { display:grid; grid-template-columns:1fr 1fr; gap:0; }
.leave-radio-item {
    display:flex; align-items:center; gap:10px; padding:12px 16px;
    cursor:pointer; transition:background .15s; border-radius:8px;
}
.leave-radio-item:hover { background:rgba(255,255,255,0.04); }
.leave-radio-item input[type="radio"] {
    width:18px; height:18px; accent-color:var(--accent); cursor:pointer; flex-shrink:0;
    appearance:none; -webkit-appearance:none; border:2px solid var(--border);
    border-radius:50%; position:relative; background:transparent;
}
.leave-radio-item input[type="radio"]:checked {
    border-color:var(--accent); background:var(--accent);
    box-shadow:inset 0 0 0 3px var(--card-bg);
}
.leave-radio-item .leave-label { font-size:13px; font-weight:500; color:var(--text); }

/* Upload zone */
.leave-upload-zone {
    width:120px; height:100px; border:2px dashed var(--border); border-radius:12px;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    cursor:pointer; transition:all .2s; gap:6px; position:relative; overflow:hidden;
}
.leave-upload-zone:hover { border-color:var(--accent); background:rgba(124,147,104,0.06); }
.leave-upload-zone.has-file { border-color:var(--accent); border-style:solid; background:rgba(124,147,104,0.08); }
.leave-upload-zone svg { color:var(--accent); }
.leave-upload-zone span { font-size:11px; font-weight:700; color:var(--accent); text-transform:uppercase; letter-spacing:0.5px; }
.leave-upload-zone input { position:absolute; inset:0; opacity:0; cursor:pointer; }

/* Date chips */
.leave-date-chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
.leave-date-chip {
    padding:8px 14px; border:1.5px solid var(--accent); border-radius:8px;
    font-size:12px; text-align:center; color:var(--accent); font-weight:600;
    background:rgba(124,147,104,0.06); line-height:1.3; min-width:72px;
    animation:chipIn .25s ease-out;
}
.leave-date-chip .chip-day { font-size:10px; font-weight:400; color:var(--text-muted); display:block; margin-top:1px; }
@keyframes chipIn { from { opacity:0; transform:scale(0.85); } to { opacity:1; transform:scale(1); } }

/* Section titles */
.leave-section-title {
    font-size:16px; font-weight:700; color:var(--text); margin:0 0 12px; display:flex; align-items:center; gap:8px;
}
.leave-section-sub { font-size:12px; color:var(--text-muted); margin:0 0 12px; font-style:italic; }
.leave-divider { border:none; border-top:1px solid var(--border); margin:24px 0; }
.leave-ineligible { opacity:0.5; cursor:not-allowed; }
.leave-ineligible input { cursor:not-allowed; }
.leave-ineligible:hover { background:transparent !important; }
</style>

<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-size:24px;font-weight:700;margin:0;">Leave Management</h1>
        <div style="display:flex;gap:8px;margin-top:8px;">
            <?php if($isAdmin||$isManager): ?>
            <a href="<?php echo url('leave'); ?>" class="btn btn-sm <?php echo ($action!=='my'&&$action!=='apply'&&$action!=='view')?'btn-accent':'btn-outline'; ?>">All Requests</a>
            <?php endif; ?>
            <?php if(!$isAdmin): ?>
            <a href="<?php echo url('leave',['action'=>'my']); ?>" class="btn btn-sm <?php echo $action==='my'?'btn-accent':'btn-outline'; ?>">My Leaves</a>
            <a href="<?php echo url('leave',['action'=>'apply']); ?>" class="btn btn-sm btn-outline">+ File a Leave</a>
            <?php endif; ?>
        </div>
    </div>
    <?php if($isManager): ?><a href="<?php echo url('leave',['action'=>'apply']); ?>" class="btn btn-accent">+ New Leave</a><?php endif; ?>
</div>
<?php if (($action==='apply'||$action==='new') && !$isAdmin): ?>
<div class="hrms-card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3 style="margin:0;">File a Leave</h3>
        <div style="font-size:13px;color:var(--text-muted);">SIL Credits: <span style="color:<?php echo $silRemaining > 0 ? 'var(--accent)' : '#ef5350'; ?>;font-weight:700;"><?php echo number_format($silRemaining,2); ?></span> days remaining</div>
    </div>
    <div class="card-body" style="padding:28px 32px;">
        <form method="POST" enctype="multipart/form-data" id="leaveForm">
            <input type="hidden" name="_action" value="apply">

            <!-- Section 1: Leave Type -->
            <div class="leave-section-title">What type of leave?</div>
            <div class="leave-radio-grid">
                <?php
                $docRequired = LeavePolicy::DOC_REQUIRED;
                $firstEligible = true;
                foreach($leaveTypes as $lt):
                    $elig = $eligibility[$lt] ?? ['eligible'=>true,'reason'=>''];
                    $isElig = $elig['eligible'];
                    $reason = $elig['reason'];
                    $needsDoc = in_array($lt, $docRequired);
                ?>
                <label class="leave-radio-item<?php echo !$isElig?' leave-ineligible':''; ?>" title="<?php echo e($reason); ?>">
                    <input type="radio" name="leave_type" value="<?php echo $lt; ?>"
                        <?php if($isElig && $firstEligible): $firstEligible=false; echo 'checked'; endif; ?>
                        <?php echo !$isElig ? 'disabled' : ''; ?> required onchange="renderDateChips()">
                    <span class="leave-label">
                        <?php echo $lt; ?>
                        <?php if($needsDoc): ?><span style="font-size:9px;color:#fb8c00;margin-left:4px;">📎 docs</span><?php endif; ?>
                        <?php if(!$isElig): ?><span style="display:block;font-size:10px;color:#ef5350;font-weight:400;margin-top:1px;"><?php echo e($reason); ?></span><?php endif; ?>
                        <?php if($isElig && $reason): ?><span style="display:block;font-size:10px;color:var(--text-muted);font-weight:400;margin-top:1px;"><?php echo e($reason); ?></span><?php endif; ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>

            <hr class="leave-divider">

            <!-- Section 2: Supporting Document Upload -->
            <div class="leave-section-title">Please upload supporting documents</div>
            <p class="leave-section-sub">(Medical Certificate, etc. — optional)</p>
            <div class="leave-upload-zone" id="uploadZone" onclick="document.getElementById('supportDoc').click();">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    <line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="12" y2="12"/><line x1="15" y1="15" x2="12" y2="12"/>
                </svg>
                <span>Upload</span>
                <input type="file" name="support_doc" id="supportDoc" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" onchange="handleLeaveFile(this)">
            </div>
            <p id="leaveFileName" style="display:none;margin:8px 0 0;font-size:12px;color:var(--accent);">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg>
                <span></span>
            </p>

            <hr class="leave-divider">

            <!-- Section 3: Date Range -->
            <div class="leave-section-title">How long are you gone?</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:520px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px;">Start Date</label>
                    <input type="date" name="start_date" id="leaveStart" required
                        style="width:100%;padding:10px 14px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"
                        onchange="renderDateChips()">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px;">End Date</label>
                    <input type="date" name="end_date" id="leaveEnd" required
                        style="width:100%;padding:10px 14px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"
                        onchange="renderDateChips()">
                </div>
            </div>
            <!-- Date Chips Preview -->
            <div style="margin-top:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;">Selected Dates</label>
                <div class="leave-date-chips" id="dateChips">
                    <span style="font-size:12px;color:var(--text-muted);font-style:italic;">Select a date range above to preview leave dates</span>
                </div>
            </div>

            <hr class="leave-divider">

            <!-- Section 4: Note / Reason -->
            <div class="leave-section-title">Note</div>
            <textarea name="reason" rows="4" placeholder="Briefly describe why you're filing this leave..."
                style="width:100%;padding:12px 16px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:13px;resize:vertical;"></textarea>

            <!-- Submit -->
            <div style="margin-top:24px;display:flex;gap:12px;">
                <button type="submit" class="btn btn-accent" style="padding:11px 28px;font-size:14px;">Submit Leave Request</button>
                <a href="<?php echo url('leave'); ?>" class="btn btn-outline" style="padding:11px 20px;font-size:14px;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
var leaveLimits = <?php echo json_encode($eligibility ?? []); ?>;

// File upload display
function handleLeaveFile(input) {
    var zone = document.getElementById('uploadZone');
    var label = document.getElementById('leaveFileName');
    if (input.files && input.files[0]) {
        zone.classList.add('has-file');
        label.style.display = 'block';
        label.querySelector('span').textContent = input.files[0].name + ' (' + (input.files[0].size/1024).toFixed(1) + ' KB)';
    } else {
        zone.classList.remove('has-file');
        label.style.display = 'none';
    }
}

// Render date chips between start and end
function renderDateChips() {
    var start = document.getElementById('leaveStart').value;
    var end   = document.getElementById('leaveEnd').value;
    var container = document.getElementById('dateChips');
    container.innerHTML = '';

    if (!start || !end) {
        container.innerHTML = '<span style="font-size:12px;color:var(--text-muted);font-style:italic;">Select a date range above to preview leave dates</span>';
        return;
    }

    var s = new Date(start + 'T00:00:00');
    var e = new Date(end + 'T00:00:00');
    if (e < s) {
        container.innerHTML = '<span style="font-size:12px;color:#ef5350;">End date must be after start date</span>';
        return;
    }

    var dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    var monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var count = 0;
    var maxChips = 60; // safety cap

    for (var d = new Date(s); d <= e && count < maxChips; d.setDate(d.getDate()+1)) {
        var dayOfWeek = d.getDay();
        // Skip weekends (Sat=6, Sun=0)
        if (dayOfWeek === 0 || dayOfWeek === 6) continue;

        var chip = document.createElement('div');
        chip.className = 'leave-date-chip';
        chip.innerHTML = monthNames[d.getMonth()] + ' ' + String(d.getDate()).padStart(2,'0')
            + '<span class="chip-day">' + dayNames[dayOfWeek] + '</span>';
        chip.style.animationDelay = (count * 0.03) + 's';
        container.appendChild(chip);
        count++;
    }

    if (count === 0) {
        container.innerHTML = '<span style="font-size:12px;color:var(--text-muted);font-style:italic;">No working days in selected range</span>';
    } else {
        // Show summary
        var summary = document.createElement('div');
        summary.style.cssText = 'width:100%;margin-top:4px;font-size:12px;color:var(--text-muted);';
        summary.textContent = count + ' working day' + (count>1?'s':'') + ' total';
        container.appendChild(summary);

        // Limit Check
        var selectedType = document.querySelector('input[name="leave_type"]:checked');
        var submitBtn = document.querySelector('#leaveForm button[type="submit"]');
        if (selectedType && leaveLimits[selectedType.value]) {
            var maxDays = parseFloat(leaveLimits[selectedType.value].max_days);
            if (count > maxDays) {
                var warn = document.createElement('div');
                warn.style.cssText = 'width:100%;margin-top:8px;font-size:12px;color:#ef5350;font-weight:600;padding:8px 12px;background:rgba(239,83,80,0.1);border-radius:6px;';
                warn.innerHTML = '⚠️ Limit Exceeded: You only have ' + maxDays + ' days available for ' + selectedType.value + '.';
                container.appendChild(warn);
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                submitBtn.style.cursor = 'not-allowed';
            } else {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }
        }
    }
}
</script><?php elseif ($action === 'view' && $viewRequest): ?>
<!-- ── Individual Request Detail View ── -->
<?php $vr = $viewRequest; $badgeClass = $vr['status']==='Approved'?'badge-success':($vr['status']==='Pending'?'badge-warning':'badge-danger'); ?>
<div style="margin-bottom:16px;">
    <a href="<?php echo url('leave'); ?>" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> Back to All Requests
    </a>
</div>
<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;">
    <!-- Left: Request Details -->
    <div class="hrms-card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;">Leave Request #<?php echo $vr['id']; ?></h3>
            <span class="badge <?php echo $badgeClass; ?>" style="font-size:12px;padding:5px 14px;"><?php echo $vr['status']; ?></span>
        </div>
        <div class="card-body" style="padding:24px;">
            <!-- Employee Info -->
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border);">
                <div style="width:48px;height:48px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0;">
                    <?php echo strtoupper(substr($vr['first_name'],0,1).substr($vr['last_name'],0,1)); ?>
                </div>
                <div>
                    <div style="font-size:16px;font-weight:700;"><?php echo e($vr['first_name'].' '.$vr['last_name']); ?></div>
                    <div style="font-size:12px;color:var(--text-muted);"><?php echo e($vr['employee_no']??''); ?> &bull; <?php echo e($vr['dept_name']??'No Department'); ?></div>
                </div>
            </div>
            <!-- Details Grid -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                <?php
                $details = [
                    ['Leave Type', $vr['leave_type'], '📋'],
                    ['Duration', $vr['days'].' day'.($vr['days']>1?'s':''), '📅'],
                    ['Start Date', date('F j, Y', strtotime($vr['start_date'])), '▶️'],
                    ['End Date', date('F j, Y', strtotime($vr['end_date'])), '⏹️'],
                    ['Filed On', date('M j, Y g:i A', $vr['created_at']), '🕐'],
                    ['Email', $vr['email']??'', '✉️'],
                ];
                foreach($details as $d):
                ?>
                <div style="padding:14px 16px;background:rgba(255,255,255,0.03);border-radius:10px;border:1px solid var(--border);">
                    <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;"><?php echo $d[2].' '.$d[0]; ?></div>
                    <div style="font-size:14px;font-weight:600;"><?php echo e($d[1]); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Reason -->
            <div style="margin-bottom:20px;">
                <div style="font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px;">Reason / Note</div>
                <div style="padding:16px;background:rgba(255,255,255,0.03);border-radius:10px;border:1px solid var(--border);font-size:13px;line-height:1.6;color:var(--text);min-height:60px;">
                    <?php echo $vr['reason'] ? nl2br(e($vr['reason'])) : '<span style="color:var(--text-muted);font-style:italic;">No reason provided</span>'; ?>
                </div>
            </div>
            <!-- Supporting Document -->
            <?php if(!empty($vr['doc_path'])): ?>
            <div>
                <div style="font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px;">📎 Supporting Document</div>
                <div style="padding:16px;background:rgba(255,255,255,0.03);border-radius:10px;border:1px solid var(--border);display:flex;align-items:center;gap:12px;">
                    <?php
                    $ext = strtolower(pathinfo($vr['doc_path'], PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                    ?>
                    <?php if($isImage): ?>
                    <img src="<?php echo BASE_URL.'/'.$vr['doc_path']; ?>" style="max-width:200px;max-height:160px;border-radius:8px;border:1px solid var(--border);">
                    <?php else: ?>
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <?php endif; ?>
                    <div>
                        <div style="font-size:13px;font-weight:600;"><?php echo e(basename($vr['doc_path'])); ?></div>
                        <a href="<?php echo BASE_URL.'/'.$vr['doc_path']; ?>" target="_blank" style="font-size:12px;color:var(--accent);text-decoration:none;">View / Download →</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Right: Action Panel -->
    <div>
        <div class="hrms-card" style="position:sticky;top:20px;">
            <div class="card-header"><h3 style="margin:0;">Actions</h3></div>
            <div class="card-body" style="padding:20px;">
                <?php if($vr['status']==='Pending'): ?>
                <form method="POST" style="margin-bottom:10px;">
                    <input type="hidden" name="_action" value="approve"><input type="hidden" name="id" value="<?php echo $vr['id']; ?>">
                    <button type="submit" class="btn" style="width:100%;background:#43a047;color:#fff;padding:12px;font-size:14px;font-weight:600;border-radius:10px;display:flex;align-items:center;justify-content:center;gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Approve Leave
                    </button>
                </form>
                <form method="POST">
                    <input type="hidden" name="_action" value="deny"><input type="hidden" name="id" value="<?php echo $vr['id']; ?>">
                    <button type="submit" class="btn btn-outline" style="width:100%;padding:12px;font-size:14px;font-weight:600;border-radius:10px;color:#ef5350;border-color:#ef5350;display:flex;align-items:center;justify-content:center;gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Deny Leave
                    </button>
                </form>
                <?php else: ?>
                <div style="text-align:center;padding:12px 0 16px;">
                    <span class="badge <?php echo $badgeClass; ?>" style="font-size:14px;padding:8px 20px;"><?php echo $vr['status']; ?></span>
                    <p style="font-size:12px;color:var(--text-muted);margin:10px 0 0;">This request has been <?php echo strtolower($vr['status']); ?>.</p>
                </div>
                <form method="POST" onsubmit="return confirm('Revert this leave request back to Pending?');">
                    <input type="hidden" name="_action" value="undo"><input type="hidden" name="id" value="<?php echo $vr['id']; ?>">
                    <button type="submit" class="btn btn-outline" style="width:100%;padding:12px;font-size:14px;font-weight:600;border-radius:10px;display:flex;align-items:center;justify-content:center;gap:8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg> Undo — Revert to Pending
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ── List View ── -->
<?php
// Summary stats for admin/manager
if ($isAdmin||$isManager) {
    $pending = 0; $approved = 0; $denied = 0;
    foreach($records as $rc) {
        if ($rc['status']==='Pending') $pending++;
        elseif ($rc['status']==='Approved') $approved++;
        else $denied++;
    }
}
?>
<?php if($isAdmin||$isManager): ?>
<!-- Status Summary Cards -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
    <a href="<?php echo url('leave',['status'=>'Pending']); ?>" class="hrms-card" style="padding:20px;text-decoration:none;border-left:4px solid #fb8c00;transition:transform .15s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;">Pending</div>
        <div style="font-size:28px;font-weight:700;color:#fb8c00;"><?php echo $pending; ?></div>
    </a>
    <a href="<?php echo url('leave',['status'=>'Approved']); ?>" class="hrms-card" style="padding:20px;text-decoration:none;border-left:4px solid #43a047;transition:transform .15s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;">Approved</div>
        <div style="font-size:28px;font-weight:700;color:#43a047;"><?php echo $approved; ?></div>
    </a>
    <a href="<?php echo url('leave',['status'=>'Denied']); ?>" class="hrms-card" style="padding:20px;text-decoration:none;border-left:4px solid #ef5350;transition:transform .15s;" onmouseenter="this.style.transform='translateY(-2px)'" onmouseleave="this.style.transform=''">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;">Denied</div>
        <div style="font-size:28px;font-weight:700;color:#ef5350;"><?php echo $denied; ?></div>
    </a>
</div>
<!-- Filter Bar -->
<div class="hrms-card" style="margin-bottom:16px;padding:14px 18px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="page" value="leave">
        <div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Status</label>
        <select name="status" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;min-width:120px;">
            <option value="">All</option>
            <?php foreach(['Pending','Approved','Denied'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo ($_GET['status']??'')===$s?'selected':''; ?>><?php echo $s; ?></option>
            <?php endforeach; ?>
        </select></div>
        <button type="submit" class="btn btn-accent btn-sm">Filter</button>
        <a href="<?php echo url('leave'); ?>" class="btn btn-outline btn-sm">Reset</a>
    </form>
</div>
<?php endif; ?>

<!-- Request Table -->
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;">
<thead><tr>
    <?php if($isAdmin||$isManager): ?><th>Employee</th><?php endif; ?>
    <th>Type</th><th>Period</th><th>Days</th><th>Status</th>
    <?php if(!$isAdmin&&!$isManager): ?><th>Reason</th><?php endif; ?>
    <th style="text-align:right;">Actions</th>
</tr></thead>
<tbody>
<?php foreach($records as $r): ?>
<tr style="transition:background .12s;" onmouseenter="this.style.background='rgba(255,255,255,0.03)'" onmouseleave="this.style.background=''">
    <?php if($isAdmin||$isManager): ?>
    <td>
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">
                <?php echo strtoupper(substr($r['first_name']??'',0,1).substr($r['last_name']??'',0,1)); ?>
            </div>
            <div style="font-weight:600;font-size:13px;"><?php echo e(($r['first_name']??'').' '.($r['last_name']??'')); ?></div>
        </div>
    </td>
    <?php endif; ?>
    <td style="font-size:13px;"><?php echo e($r['leave_type']); ?></td>
    <td style="font-size:12px;color:var(--text-muted);"><?php echo date('M j',strtotime($r['start_date'])); ?> – <?php echo date('M j, Y',strtotime($r['end_date'])); ?></td>
    <td style="font-weight:600;"><?php echo $r['days']; ?></td>
    <td><span class="badge <?php echo $r['status']==='Approved'?'badge-success':($r['status']==='Pending'?'badge-warning':'badge-danger'); ?>"><?php echo $r['status']; ?></span></td>
    <?php if(!$isAdmin&&!$isManager): ?>
    <td style="font-size:12px;color:var(--text-muted);"><?php echo e(substr($r['reason']??'',0,40)); ?></td>
    <?php endif; ?>
    <td style="text-align:right;">
        <div style="display:flex;gap:6px;justify-content:flex-end;">
            <a href="<?php echo url('leave',['action'=>'view','id'=>$r['id']]); ?>" class="btn btn-sm btn-outline" style="padding:4px 12px;font-size:11px;">View</a>
            <?php if($isAdmin||$isManager): ?>
            <?php if($r['status']==='Pending'): ?>
            <form method="POST" style="display:inline;"><input type="hidden" name="_action" value="approve"><input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button type="submit" class="btn btn-sm" style="background:#43a047;color:#fff;padding:4px 12px;font-size:11px;">Approve</button></form>
            <form method="POST" style="display:inline;"><input type="hidden" name="_action" value="deny"><input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button type="submit" class="btn btn-sm" style="background:#ef5350;color:#fff;padding:4px 12px;font-size:11px;">Deny</button></form>
            <?php else: ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Revert to Pending?');"><input type="hidden" name="_action" value="undo"><input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                <button type="submit" class="btn btn-sm btn-outline" style="padding:4px 12px;font-size:11px;">Undo</button></form>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
<?php if(empty($records)): ?><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">No leave requests found.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
