<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
Auth::check();
$pageTitle = 'Shift Scheduling — ' . APP_NAME;
$isAdmin=Auth::isAdmin(); $isManager=Auth::isManager(); $empId=Auth::empId(); $deptId=Auth::deptId();
if ($isManager && !$isAdmin && !$deptId) {
    $deptId = (int)DB::fetchScalar("SELECT department_id FROM employee WHERE user_id=? AND department_id IS NOT NULL LIMIT 1", [Auth::id()]);
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $a=$_POST['_action']??'';
    if (($isAdmin||$isManager)&&$a==='save') {
        $id=(int)($_POST['id']??0);
        // Convert 'custom' work_rule_id to NULL (avoids INT column type mismatch)
        $wrId = (!empty($_POST['work_rule_id']) && $_POST['work_rule_id'] !== 'custom') ? (int)$_POST['work_rule_id'] : null;
        try {
            if ($id) {
                DB::execute("UPDATE shift SET employee_id=?,date=?,start_time=?,end_time=?,type='Onsite',shift_name=?,work_rule_id=?,break_start_time=?,break_end_time=?,status=? WHERE id=?",
                    [$_POST['employee_id'],$_POST['start_date']??$_POST['date'],$_POST['start_time'],$_POST['end_time'],$_POST['shift_name']??null,$wrId,$_POST['break_start_time']?:null,$_POST['break_end_time']?:null,$_POST['status']??'Scheduled',$id]);
            } else {
                $start = new DateTime($_POST['start_date']);
                $end   = new DateTime($_POST['end_date'] ?? $_POST['start_date']);
                $interval = new DateInterval('P1D');
                $period = new DatePeriod($start, $interval, (clone $end)->modify('+1 day'));
                $selectedDays = $_POST['days'] ?? ['1','2','3','4','5'];
                if ($_POST['start_date'] === ($_POST['end_date'] ?? $_POST['start_date'])) {
                    $selectedDays = [ (string)$start->format('w') ];
                }
                $count = 0;
                foreach ($period as $dt) {
                    $dow = (string)$dt->format('w');
                    if (in_array($dow, $selectedDays)) {
                        DB::execute("INSERT INTO shift (employee_id,date,start_time,end_time,type,shift_name,work_rule_id,break_start_time,break_end_time,status,published,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,1,?)",
                            [$_POST['employee_id'],$dt->format('Y-m-d'),$_POST['start_time'],$_POST['end_time'],'Onsite',$_POST['shift_name']??null,$wrId,$_POST['break_start_time']?:null,$_POST['break_end_time']?:null,$_POST['status']??'Scheduled',time()]);
                        $count++;
                    }
                }
                // Notify employee
                $sd = date('M j, Y', strtotime($_POST['start_date']));
                $ed = date('M j, Y', strtotime($_POST['end_date'] ?? $_POST['start_date']));
                $st = date('g:i A', strtotime($_POST['start_time'])).' – '.date('g:i A', strtotime($_POST['end_time']));
                DB::execute("INSERT INTO system_notification (user_id,type,title,message,link,is_read,created_at) SELECT u.id,'info','New Schedule Assigned',?,?,0,NOW() FROM employee e JOIN `user` u ON e.user_id=u.id WHERE e.id=?",
                    ['You have been assigned a schedule from '.$sd.' to '.$ed.' ('.$st.')',url('shift'),$_POST['employee_id']]);
            }
            // Redirect to the month containing the saved shift
            $saveDate = $_POST['start_date'] ?? $_POST['date'] ?? date('Y-m-d');
            Auth::flash('success','Shift(s) saved!');
            header('Location: '.url('shift',['month'=>(int)date('n',strtotime($saveDate)),'year'=>(int)date('Y',strtotime($saveDate))])); exit;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                Auth::flash('error', 'A shift already exists for this employee on that date!');
                header('Location: '.url('shift', ['action' => 'create'])); exit;
            }
            Auth::flash('error', 'Save failed: '.$e->getMessage());
            header('Location: '.url('shift', ['action' => 'create'])); exit;
        }
    }
    if ($isAdmin&&$a==='delete') { DB::execute("DELETE FROM shift WHERE id=?",[(int)$_POST['id']]); Auth::flash('success','Shift deleted.'); header('Location: '.url('shift')); exit; }
}

// Calendar month
$calMonth = (int)($_GET['month'] ?? date('n'));
$calYear  = (int)($_GET['year'] ?? date('Y'));
if ($calMonth < 1) { $calMonth = 12; $calYear--; }
if ($calMonth > 12) { $calMonth = 1; $calYear++; }
$firstDay    = mktime(0,0,0,$calMonth,1,$calYear);
$daysInMonth = (int)date('t', $firstDay);
$startDow    = (int)date('w', $firstDay);
$monthLabel  = date('F Y', $firstDay);
$dateFrom    = date('Y-m-d', $firstDay);
$dateTo      = date('Y-m-d', mktime(0,0,0,$calMonth,$daysInMonth,$calYear));

$empFilter = ($isAdmin && !empty($_GET['emp_id'])) ? (int)$_GET['emp_id'] : 0;
$where="s.date BETWEEN ? AND ?"; $params=[$dateFrom,$dateTo];
if (!$isAdmin&&!$isManager) { $where.=" AND s.employee_id=?"; $params[]=$empId; }
// Managers can only see shifts for their own department
if ($isManager&&!$isAdmin&&$deptId) { $where.=" AND e.department_id=?"; $params[]=$deptId; }
// HR Admin: optional employee filter
if ($isAdmin && $empFilter) { $where.=" AND s.employee_id=?"; $params[]=$empFilter; }
$shifts=DB::fetchAll("SELECT s.*,e.first_name,e.last_name,e.department_id FROM shift s JOIN employee e ON s.employee_id=e.id WHERE $where ORDER BY s.date,s.start_time",$params);

$shiftsByDate = [];
foreach ($shifts as $s) { $shiftsByDate[$s['date']][] = $s; }

$employees=($isAdmin||$isManager)?DB::fetchAll("SELECT id,first_name,last_name FROM employee WHERE status IN ('Regular','Probationary')" . ($isManager&&!$isAdmin ? " AND department_id=".(int)$deptId : "") . " ORDER BY first_name"):[];
$workRules=($isAdmin||$isManager)?DB::fetchAll("SELECT * FROM work_rule ORDER BY is_default DESC, name"):[];
$action=$_GET['action']??'index';
require_once __DIR__ . '/../includes/layout_header.php';
?>
<style>
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:1px;background:var(--border);border:1px solid var(--border);border-radius:12px;overflow:hidden}
.cal-hdr{padding:10px 4px;text-align:center;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);background:var(--card-bg);letter-spacing:.5px}
.cal-cell{background:var(--card-bg);min-height:110px;padding:6px;position:relative;transition:background .15s}
.cal-cell:hover{background:rgba(255,255,255,0.03)}
.cal-cell.today{background:rgba(124,147,104,0.08)}
.cal-cell.out{opacity:.3}
.cal-day{font-size:12px;font-weight:700;color:var(--text-muted);margin-bottom:4px;padding:2px 6px;display:inline-block;border-radius:6px}
.cal-cell.today .cal-day{background:var(--accent);color:#fff;border-radius:50%;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;padding:0}
.cal-sh{display:block;padding:4px 6px;margin-bottom:3px;border-radius:6px;font-size:10px;line-height:1.3;text-decoration:none;color:var(--text);transition:all .15s;border-left:3px solid}
.cal-sh:hover{transform:translateX(2px);filter:brightness(1.15)}
.cal-sh.t-on{background:rgba(30,136,229,0.12);border-color:#1e88e5}
.cal-sh.t-re{background:rgba(124,147,104,0.15);border-color:var(--accent)}
.cal-sh.t-hy{background:rgba(251,140,0,0.12);border-color:#fb8c00}
.cal-sh .sn{font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cal-sh .st{color:var(--text-muted);font-size:9px}
.cal-add{position:absolute;top:4px;right:4px;width:18px;height:18px;border-radius:50%;background:rgba(255,255,255,0.06);border:1px solid var(--border);color:var(--text-muted);font-size:14px;line-height:16px;text-align:center;text-decoration:none;opacity:0;transition:opacity .15s}
.cal-cell:hover .cal-add{opacity:1}
.cal-add:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
.cal-nav{display:flex;align-items:center;gap:12px}
.cal-nav a.nav-arrow{width:36px;height:36px;border-radius:8px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none;transition:all .15s}
.cal-nav a.nav-arrow:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
</style>

<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-size:24px;font-weight:700;margin:0;"><?php echo ($isAdmin||$isManager)?'Shift Scheduling':'My Schedule'; ?></h1>
        <p style="color:var(--text-muted);margin:4px 0 0;font-size:13px;"><?php echo ($isAdmin||$isManager)?'Manage employee shifts and schedules':'View your upcoming shifts'; ?></p>
    </div>
    <?php if($isAdmin||$isManager): ?><a href="<?php echo url('shift',['action'=>'create']); ?>" class="btn btn-accent">+ Add Shift</a><?php endif; ?>
</div>

<?php if($isAdmin): ?>
<div class="hrms-card" style="margin-bottom:16px;padding:14px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <span style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Filter by Employee</span>
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex:1;">
        <input type="hidden" name="page" value="shift">
        <input type="hidden" name="month" value="<?php echo $calMonth; ?>">
        <input type="hidden" name="year" value="<?php echo $calYear; ?>">
        <select name="emp_id" onchange="this.form.submit()" style="padding:7px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;min-width:200px;">
            <option value="">— All Employees —</option>
            <?php foreach($employees as $em): ?>
            <option value="<?php echo $em['id']; ?>" <?php echo $empFilter===$em['id']?'selected':''; ?>><?php echo e($em['first_name'].' '.$em['last_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <?php if($empFilter): ?><a href="<?php echo url('shift',['month'=>$calMonth,'year'=>$calYear]); ?>" class="btn btn-sm btn-outline">Clear</a><?php endif; ?>
    </form>
</div>
<?php endif; ?>

<?php if($action==='create'||$action==='edit'):
    $editId=(int)($_GET['id']??0);
    $es=$editId?DB::fetchOne("SELECT * FROM shift WHERE id=?",[$editId]):null;
?>
<style>
.custom-sched-box { background:var(--card-bg); border:1px solid var(--border); border-radius:12px; padding:24px; box-shadow:0 8px 24px rgba(0,0,0,0.1); margin-top:20px; color:var(--text); display:none; }
.custom-sched-box.active { display:block; }
.day-btn { display:inline-block; padding:8px 16px; border:1px solid var(--border); background:rgba(255,255,255,0.02); color:var(--text-muted); font-size:13px; font-weight:600; cursor:pointer; margin-right:4px; border-radius:6px; user-select:none; transition:all 0.2s; }
.day-btn input { display:none; }
.day-btn:has(input:checked) { background:var(--accent); border-color:var(--accent); color:#fff; }
.sched-label { display:block; font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px; }
.sched-input { width:100%; padding:10px 14px; border:1px solid var(--border); border-radius:8px; font-size:14px; color:var(--text); background:rgba(255,255,255,0.04); transition:border-color 0.2s; }
.sched-input:focus { outline:none; border-color:var(--accent); }
.sched-section { margin-bottom:20px; }
.sched-divider { height:1px; background:var(--border); margin:20px 0; }
</style>

<div class="hrms-card"><div class="card-header"><h3><?php echo $es?'Edit Shift':'New Schedule'; ?></h3></div><div class="card-body">
<form method="POST"><input type="hidden" name="_action" value="save"><?php if($es): ?><input type="hidden" name="id" value="<?php echo $es['id']; ?>"><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
<?php
$inputStyle = "width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;";
?>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Employee</label>
<select name="employee_id" required style="<?php echo $inputStyle; ?>"><option value="">— Select —</option>
<?php foreach($employees as $em): ?><option value="<?php echo $em['id']; ?>" <?php echo ($es['employee_id']??'')==$em['id']?'selected':''; ?>><?php echo e($em['first_name'].' '.$em['last_name']); ?></option><?php endforeach; ?>
</select></div>

<?php if($es || isset($_GET['date'])): ?>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Date</label>
<input type="date" name="start_date" value="<?php echo e($es['date'] ?? $_GET['date']); ?>" required style="<?php echo $inputStyle; ?>">
<input type="hidden" name="end_date" value="<?php echo e($es['date'] ?? $_GET['date']); ?>">
</div>
<?php else: ?>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Start Date</label>
<input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required style="<?php echo $inputStyle; ?>"></div>
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">End Date</label>
<input type="date" name="end_date" value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>" required style="<?php echo $inputStyle; ?>"></div>
<?php endif; ?>

<input type="hidden" name="type" value="Onsite">
<div><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Shift Name</label>
<input type="text" name="shift_name" value="<?php echo e($es['shift_name']??''); ?>" placeholder="e.g. Mid Shift" style="<?php echo $inputStyle; ?>"></div>

<div style="grid-column:1/-1;"><label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;">Work Rule</label>
<select name="work_rule_id" id="workRuleSelect" onchange="toggleCustomSchedule()" style="<?php echo $inputStyle; ?>">
<option value="custom">— Custom Schedule —</option>
<?php foreach($workRules as $wr): ?>
<option value="<?php echo $wr['id']; ?>" data-start="<?php echo $wr['start_time']; ?>" data-end="<?php echo $wr['end_time']; ?>" data-days="<?php echo $wr['working_days']; ?>" <?php echo ($es['work_rule_id']??'')==$wr['id']?'selected':''; ?>><?php echo e($wr['name']); ?> (<?php echo $wr['schedule_type']; ?>)<?php echo $wr['is_default']?' ⭐':''; ?></option>
<?php endforeach; ?>
</select></div>
</div>

<!-- Custom Schedule UI (White Background) -->
<div id="customSchedBox" class="custom-sched-box">
    <div class="sched-section" <?php if($es || isset($_GET['date'])) echo 'style="display:none;"'; ?>>
        <label class="sched-label">Days</label>
        <div>
            <?php 
            $dayLabels = ['Sun','Mon','Tues','Wed','Thurs','Fri','Sat'];
            for($i=0; $i<7; $i++): ?>
            <label class="day-btn">
                <input type="checkbox" name="days[]" value="<?php echo $i; ?>" class="day-cb" <?php echo ($i>=1 && $i<=5)?'checked':''; ?>>
                <?php echo $dayLabels[$i]; ?>
            </label>
            <?php endfor; ?>
        </div>
    </div>
    
    <div class="sched-section">
        <label class="sched-label">Shift</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <span style="font-size:12px;color:#777;margin-bottom:4px;display:block;">Shift starts (Core hours starts)</span>
                <input type="time" name="start_time" id="stTime" value="<?php echo e($es['start_time']??'11:00'); ?>" class="sched-input">
            </div>
            <div>
                <span style="font-size:12px;color:#777;margin-bottom:4px;display:block;">Shift ends</span>
                <input type="time" name="end_time" id="enTime" value="<?php echo e($es['end_time']??'20:00'); ?>" class="sched-input">
            </div>
        </div>
    </div>
    
    <div class="sched-section">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <label class="sched-label" style="margin:0;">Breaks <span style="display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;border-radius:50%;background:#ccc;color:#fff;font-size:10px;margin-left:4px;">i</span></label>
            <span style="font-size:12px;color:#ef5350;">0 min remaining</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:16px;align-items:end;margin-top:8px;">
            <div>
                <span style="font-size:12px;color:#777;margin-bottom:4px;display:block;">Start Time</span>
                <input type="time" name="break_start_time" value="<?php echo e($es['break_start_time']??'16:00'); ?>" class="sched-input">
            </div>
            <div>
                <span style="font-size:12px;color:#777;margin-bottom:4px;display:block;">End Time</span>
                <input type="time" name="break_end_time" value="<?php echo e($es['break_end_time']??'17:00'); ?>" class="sched-input">
            </div>
            <div>
                <button type="button" style="padding:10px 16px;background:#f8d7da;color:#d32f2f;border:none;border-radius:4px;font-weight:700;cursor:pointer;">🗑 DELETE</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCustomSchedule(){
    var sel = document.getElementById('workRuleSelect');
    var box = document.getElementById('customSchedBox');
    var opt = sel.options[sel.selectedIndex];
    
    if(opt.value === 'custom') {
        box.classList.add('active');
    } else {
        box.classList.add('active'); // Keep it visible to show the pre-made rule details!
        // Fill data from rule
        var st = opt.getAttribute('data-start');
        var en = opt.getAttribute('data-end');
        var days = opt.getAttribute('data-days');
        
        if(st) document.getElementById('stTime').value = st;
        if(en) document.getElementById('enTime').value = en;
        
        if(days) {
            var daysArr = days.split(',');
            document.querySelectorAll('.day-cb').forEach(function(cb) {
                cb.checked = daysArr.includes(cb.value) || (cb.value==='0' && daysArr.includes('7')); // handle Sunday=7 vs 0
            });
        }
    }
}
// Run on load
toggleCustomSchedule();
</script>
<div style="margin-top:16px;display:flex;gap:12px;">
    <button type="submit" class="btn btn-accent">Save Shift</button>
    <a href="<?php echo url('shift'); ?>" class="btn btn-outline">Cancel</a>
    <?php if($es && $isAdmin): ?>
    <form method="POST" style="margin-left:auto;" onsubmit="return confirm('Delete this shift?');"><input type="hidden" name="_action" value="delete"><input type="hidden" name="id" value="<?php echo $es['id']; ?>">
        <button type="submit" class="btn btn-outline" style="color:#ef5350;border-color:#ef5350;">Delete</button>
    </form>
    <?php endif; ?>
</div>
</form></div></div>

<?php else: ?>
<!-- Month Navigation -->
<div class="hrms-card" style="margin-bottom:16px;padding:16px;display:flex;align-items:center;justify-content:space-between;">
    <div class="cal-nav">
        <a class="nav-arrow" href="<?php echo url('shift',['month'=>$calMonth-1,'year'=>$calYear]); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <h2 style="margin:0;font-size:20px;font-weight:700;min-width:180px;text-align:center;"><?php echo $monthLabel; ?></h2>
        <a class="nav-arrow" href="<?php echo url('shift',['month'=>$calMonth+1,'year'=>$calYear]); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 6 15 12 9 18"/></svg>
        </a>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <a href="<?php echo url('shift',['month'=>date('n'),'year'=>date('Y')]); ?>" class="btn btn-outline btn-sm">Today</a>
        <span style="font-size:12px;color:var(--text-muted);"><?php echo count($shifts); ?> shift<?php echo count($shifts)!==1?'s':''; ?></span>
    </div>
</div>

<!-- Calendar -->
<div class="cal-grid">
    <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dn): ?>
    <div class="cal-hdr"><?php echo $dn; ?></div>
    <?php endforeach; ?>

    <?php
    $today = date('Y-m-d');
    for ($i = 0; $i < $startDow; $i++): ?>
    <div class="cal-cell out"></div>
    <?php endfor; ?>

    <?php for ($day = 1; $day <= $daysInMonth; $day++):
        $dateStr = sprintf('%04d-%02d-%02d', $calYear, $calMonth, $day);
        $isToday = ($dateStr === $today);
        $dayShifts = $shiftsByDate[$dateStr] ?? [];
    ?>
    <div class="cal-cell<?php echo $isToday?' today':''; ?>">
        <div class="cal-day"><?php echo $day; ?></div>
        <?php if($isAdmin||$isManager): ?>
        <a href="<?php echo url('shift',['action'=>'create','date'=>$dateStr]); ?>" class="cal-add" title="Add shift">+</a>
        <?php endif; ?>
        <?php foreach($dayShifts as $sh):
            $tcClass = 't-on';
            $timeStr = date('g:iA',strtotime($sh['start_time'])).'–'.date('g:iA',strtotime($sh['end_time']));
            $label = ($isAdmin||$isManager) ? substr($sh['first_name'],0,1).'. '.$sh['last_name'] : ($sh['shift_name']?:$sh['type']);
        ?>
        <?php if($isAdmin||$isManager): ?>
        <a href="<?php echo url('shift',['action'=>'edit','id'=>$sh['id']]); ?>" class="cal-sh <?php echo $tcClass; ?>">
            <div class="sn"><?php echo e($label); ?></div>
            <div class="st"><?php echo $timeStr; ?></div>
        </a>
        <?php else: ?>
        <div class="cal-sh <?php echo $tcClass; ?>" style="cursor:default;">
            <div class="sn"><?php echo e($label); ?></div>
            <div class="st"><?php echo $timeStr; ?></div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endfor; ?>

    <?php $endDow = (int)date('w', mktime(0,0,0,$calMonth,$daysInMonth,$calYear));
    for ($i = $endDow + 1; $i < 7; $i++): ?>
    <div class="cal-cell out"></div>
    <?php endfor; ?>
</div>

<!-- Legend -->
<div style="margin-top:16px;display:flex;gap:20px;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-muted);"><span style="width:12px;height:12px;border-radius:3px;background:rgba(30,136,229,0.25);border-left:3px solid #1e88e5;display:inline-block;"></span> Onsite</div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
