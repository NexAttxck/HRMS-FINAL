<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
Auth::check();
$pageTitle = 'Attendance — ' . APP_NAME;
$isAdmin = Auth::isAdmin(); $isManager = Auth::isManager();
$empId = Auth::empId();
$deptId = Auth::deptId();
// Fallback: if manager's session dept is null, look it up from employee record
if ($isManager && !$isAdmin && !$deptId) {
    $deptId = (int)DB::fetchScalar("SELECT department_id FROM employee WHERE user_id=? AND department_id IS NOT NULL LIMIT 1", [Auth::id()]);
}

// Load Wi-Fi restriction settings
$sys = [];
foreach (DB::fetchAll("SELECT `key`,`value` FROM system_setting") as $s) $sys[$s['key']] = $s['value'];
$wifiEnabled  = ($sys['wifi_restriction_enabled'] ?? '0') === '1';
$wifiStrict   = ($sys['wifi_strict_mode'] ?? '1') === '1';

// Legacy form-based clock-out (no SSID check needed)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['_action'] ?? '';
    if ($a === 'clockOut' && $empId) {
        DB::execute("UPDATE attendance SET clock_out=NOW(),total_hours=TIMESTAMPDIFF(MINUTE,clock_in,NOW())/60,updated_at=? WHERE employee_id=? AND date=CURDATE()", [time(),$empId]);
        Auth::audit('Clock Out', 'Attendance', $empId);
        Auth::flash('success','Clocked out!'); header('Location: '.url('attendance',['action'=>'my'])); exit;
    }
    if (($isAdmin||$isManager) && $a === 'save') {
        $id = (int)($_POST['id']??0);
        if ($id) {
            DB::execute("UPDATE attendance SET clock_in=?,clock_out=?,status=?,notes=?,updated_at=? WHERE id=?", [$_POST['clock_in'],$_POST['clock_out'],$_POST['status'],$_POST['notes']??null,time(),$id]);
        } else {
            DB::execute("INSERT INTO attendance (employee_id,date,clock_in,clock_out,status,notes,created_at) VALUES (?,?,?,?,?,?,?)", [$_POST['employee_id'],$_POST['date'],$_POST['clock_in'],$_POST['clock_out'],$_POST['status'],$_POST['notes']??null,time()]);
        }
        Auth::audit($id ? 'Update Attendance' : 'Log Attendance', 'Attendance', $id ?: (int)($_POST['employee_id'] ?? 0));
        Auth::flash('success','Attendance saved!'); header('Location: '.url('attendance')); exit;
    }
}

$dateFrom  = $_GET['from'] ?? date('Y-m-d', strtotime('-1 month'));
$dateTo    = $_GET['to']   ?? date('Y-m-d');
$empFilter = (int)($_GET['emp'] ?? 0);
$where = "a.date BETWEEN ? AND ?"; $params = [$dateFrom, $dateTo];
if ($empFilter) { $where .= " AND a.employee_id=?"; $params[] = $empFilter; }
if ($isManager && !$isAdmin) { $where .= " AND e.department_id=?"; $params[] = $deptId; }
if (!$isAdmin && !$isManager) { $where .= " AND a.employee_id=?"; $params[] = $empId; }
$records   = DB::fetchAll("SELECT a.*,e.first_name,e.last_name FROM attendance a JOIN employee e ON a.employee_id=e.id WHERE $where ORDER BY a.date DESC, a.id DESC, e.first_name", $params);
$employees = ($isAdmin||$isManager) ? DB::fetchAll("SELECT id,first_name,last_name FROM employee WHERE status IN ('Regular','Probationary')" . ($isManager&&!$isAdmin?" AND department_id=".(int)$deptId:"") . " ORDER BY first_name") : [];
$todayAtt  = null;
if (!$isAdmin && !$isManager && $empId) {
    $todayAtt = DB::fetchOne("SELECT * FROM attendance WHERE employee_id=? AND date=CURDATE() ORDER BY id DESC", [$empId]);
}
require_once __DIR__ . '/../includes/layout_header.php';
?>

<!-- ── Wi-Fi Clock-In Modal ──────────────────────────────────────────────── -->
<div id="wifiClockInModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:9999;justify-content:center;align-items:center;">
  <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:20px;width:380px;max-width:95vw;padding:36px 32px;box-shadow:0 24px 64px rgba(0,0,0,0.5);text-align:center;">
    <!-- Checking state -->
    <div id="wifiStateChecking">
      <div style="width:72px;height:72px;border-radius:50%;background:rgba(124,147,104,0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" class="wifi-spin"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1" fill="var(--accent)"/></svg>
      </div>
      <h3 style="margin:0 0 8px;font-size:18px;font-weight:700;">Verifying Wi-Fi</h3>
      <p style="color:var(--text-muted);font-size:13px;margin:0;">Checking your network connection…</p>
    </div>
    <!-- Error states -->
    <div id="wifiStateError" style="display:none;">
      <div id="wifiErrorIcon" style="width:72px;height:72px;border-radius:50%;background:rgba(239,83,80,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;"></div>
      <h3 id="wifiErrorTitle" style="margin:0 0 10px;font-size:18px;font-weight:700;"></h3>
      <p id="wifiErrorMsg" style="color:var(--text-muted);font-size:13px;margin:0 0 24px;line-height:1.6;"></p>
      <button id="wifiErrorBtn" onclick="closeWifiModal()" class="btn btn-accent" style="width:100%;padding:12px;">Try Again</button>
    </div>
    <!-- Success state -->
    <div id="wifiStateSuccess" style="display:none;">
      <div style="width:72px;height:72px;border-radius:50%;background:rgba(67,160,71,0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#43a047" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <h3 style="margin:0 0 8px;font-size:18px;font-weight:700;">Clocked In!</h3>
      <p id="wifiSuccessMsg" style="color:var(--text-muted);font-size:13px;margin:0 0 24px;"></p>
      <button onclick="location.reload()" class="btn btn-accent" style="width:100%;padding:12px;">Done</button>
    </div>
  </div>
</div>

<style>
@keyframes wifiPulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.9)} }
.wifi-spin { animation: wifiPulse 1.4s ease-in-out infinite; }
</style>

<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <h1 style="font-size:24px;font-weight:700;margin:0;">Attendance</h1>
    <?php if(!$isAdmin&&!$isManager): ?>
        <?php if(!$todayAtt): ?>
        <button id="clockInBtn" onclick="startClockIn()" class="btn" style="background:linear-gradient(135deg,#43a047,#2e7d32);color:#fff;display:flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;font-weight:600;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Clock In
        </button>
        <?php elseif(!$todayAtt['clock_out']): ?>
        <form method="POST" style="display:flex;gap:8px;align-items:center;">
            <span style="font-size:12px;color:var(--text-muted);">In: <?php echo date('h:i A', strtotime($todayAtt['clock_in'])); ?></span>
            <input type="hidden" name="_action" value="clockOut">
            <button type="submit" class="btn" style="background:#fb8c00;color:#fff;">Clock Out</button>
        </form>
        <?php else: ?>
        <div style="font-size:12px;color:var(--text-muted);display:flex;gap:8px;">
            <span class="badge badge-success">Completed</span>
            <span>In: <?php echo date('h:i A', strtotime($todayAtt['clock_in'])); ?></span>
            <span>Out: <?php echo date('h:i A', strtotime($todayAtt['clock_out'])); ?></span>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="hrms-card" style="margin-bottom:16px;padding:16px;">
    <form method="GET" action="" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="page" value="attendance">
        <div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">From</label><input type="date" name="from" value="<?php echo e($dateFrom); ?>" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
        <div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">To</label><input type="date" name="to" value="<?php echo e($dateTo); ?>" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"></div>
        <?php if($isAdmin||$isManager): ?><div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Employee</label><select name="emp" style="padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;"><option value="">All</option><?php foreach($employees as $em): ?><option value="<?php echo $em['id']; ?>" <?php echo $empFilter==$em['id']?'selected':''; ?>><?php echo e($em['first_name'].' '.$em['last_name']); ?></option><?php endforeach; ?></select></div><?php endif; ?>
        <button type="submit" class="btn btn-accent">Filter</button><a href="<?php echo url('attendance'); ?>" class="btn btn-outline">Reset</a>
    </form>
</div>

<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;">
<thead><tr><th>Employee</th><th>Date</th><th>Clock In</th><th>Clock Out</th><th>Hours</th><th>Status</th><?php if($isAdmin): ?><th>Notes</th><?php endif; ?></tr></thead>
<tbody>
<?php foreach($records as $r): ?>
<tr>
    <td style="font-weight:600;"><?php echo e($r['first_name'].' '.$r['last_name']); ?></td>
    <td><?php echo date('M j, Y',strtotime($r['date'])); ?></td>
    <td><?php echo $r['clock_in']?date('h:i A',strtotime($r['clock_in'])):'—'; ?></td>
    <td><?php echo $r['clock_out']?date('h:i A',strtotime($r['clock_out'])):'—'; ?></td>
    <td><?php echo $r['total_hours']?number_format($r['total_hours'],1).'h':'—'; ?></td>
    <td><span class="badge <?php echo strpos($r['status'],'On Time')!==false?'badge-success':(strpos($r['status'],'Late')!==false?'badge-warning':'badge-danger'); ?>"><?php echo e($r['status']); ?></span></td>
    <?php if($isAdmin): ?><td style="font-size:12px;color:var(--text-muted);"><?php echo e($r['notes']??''); ?></td><?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if(empty($records)): ?><tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted);">No attendance records found.</td></tr><?php endif; ?>
</tbody></table></div></div>

<?php if(!$isAdmin && !$isManager && $empId): ?>
<script>
// Config passed from PHP
const WIFI_ENABLED = <?php echo $wifiEnabled ? 'true' : 'false'; ?>;
const WIFI_STRICT  = <?php echo $wifiStrict  ? 'true' : 'false'; ?>;
const EMP_ID       = <?php echo (int)$empId; ?>;
const API_BASE     = '<?php echo BASE_URL; ?>/api/wifi.php';

// ── SSID cache (5 min) ────────────────────────────────────────────────────────
let _ssidCache = null, _ssidCacheAt = 0;
async function fetchAllowedSSIDs() {
    const now = Date.now();
    if (_ssidCache && now - _ssidCacheAt < 300000) return _ssidCache;
    const r = await fetch(API_BASE + '?action=allowed-ssids');
    const d = await r.json();
    _ssidCache   = d.ssids || [];
    _ssidCacheAt = now;
    return _ssidCache;
}

// ── Modal helpers ─────────────────────────────────────────────────────────────
const modal = document.getElementById('wifiClockInModal');
function showModal()  { modal.style.display = 'flex'; }
function closeWifiModal() { modal.style.display = 'none'; }

function setState(state) {
    ['Checking','Error','Success'].forEach(s =>
        document.getElementById('wifiState'+s).style.display = s===state ? 'block' : 'none'
    );
}

const ICONS = {
    ssid_mismatch: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ef5350" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
    no_wifi:        `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ef5350" stroke-width="2"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.56 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>`,
    permission_denied: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ef5350" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>`,
};

function showError(reason, detectedSSID) {
    setState('Error');
    document.getElementById('wifiErrorIcon').innerHTML = ICONS[reason] || ICONS.no_wifi;
    const titles = {
        ssid_mismatch:     'Wrong network detected',
        no_wifi:           'No Wi-Fi connection',
        permission_denied: 'Location permission required',
    };
    const msgs = {
        ssid_mismatch:     `You're connected to '<strong>${detectedSSID}</strong>'. Please connect to the company Wi-Fi and try again.`,
        no_wifi:           `Clock-in requires a Wi-Fi connection. Please connect to the company network and try again.`,
        permission_denied: `Wi-Fi verification needs location access. Please enable it in <strong>Settings → Privacy → Location</strong>.`,
    };
    const btnLabels = { ssid_mismatch:'Try Again', no_wifi:'Try Again', permission_denied:'Close' };
    document.getElementById('wifiErrorTitle').textContent = titles[reason] || 'Verification Failed';
    document.getElementById('wifiErrorMsg').innerHTML    = msgs[reason] || 'Clock-in failed.';
    document.getElementById('wifiErrorBtn').textContent  = btnLabels[reason] || 'Try Again';
}

// ── Detect SSID via navigator.connection (best-effort in browsers) ──────────
// NOTE: Browsers cannot natively read SSID; this flow simulates the check
// for a web app context. In a real mobile wrapper (Capacitor / Cordova),
// replace getDetectedSSID() with the native plugin call.
async function getDetectedSSID() {
    // Try Network Information API (available in some Android WebViews)
    if (navigator.connection && navigator.connection.type === 'wifi') {
        return navigator.connection.ssid || '__WIFI_NO_SSID__';
    }
    // Fallback: if online, return a sentinel so the backend decides
    if (navigator.onLine) return '__WIFI_ONLINE__';
    return null; // offline
}

// ── Main clock-in flow ────────────────────────────────────────────────────────
async function startClockIn() {
    showModal();
    setState('Checking');

    // If Wi-Fi restriction is OFF — just clock in directly
    if (!WIFI_ENABLED) {
        await doClockIn('', '');
        return;
    }

    // Step 1 — Detect SSID
    let detectedSSID = null;
    try { detectedSSID = await getDetectedSSID(); }
    catch(e) { detectedSSID = null; }

    if (!detectedSSID) {
        // No Wi-Fi at all
        await logFailed('no_wifi', '');
        if (!WIFI_STRICT) { await doClockIn('UNVERIFIED', ''); return; }
        showError('no_wifi', '');
        return;
    }

    // Strip Android-style quoted SSID e.g. "OfficeNet" → OfficeNet
    const cleaned = detectedSSID.replace(/^"|"$/g, '').trim();

    // Step 2 — Fetch approved list
    let allowed = [];
    try { allowed = await fetchAllowedSSIDs(); }
    catch(e) {
        // If we can't fetch the list and strict mode is off — allow
        if (!WIFI_STRICT) { await doClockIn(cleaned, ''); return; }
        showError('ssid_mismatch', cleaned);
        return;
    }

    // Step 3 — Compare (case-insensitive)
    const sentinels = ['__WIFI_NO_SSID__', '__WIFI_ONLINE__'];
    const isMatch = allowed.length === 0
        ? true // no SSIDs configured → open
        : allowed.some(s => s.ssid_name.toLowerCase() === cleaned.toLowerCase())
          || sentinels.includes(cleaned); // browser can't read real SSID → trust online status

    if (isMatch) {
        await doClockIn(cleaned, navigator.userAgent.substring(0, 100));
    } else {
        await logFailed('ssid_mismatch', cleaned);
        if (!WIFI_STRICT) { await doClockIn(cleaned + ' (unverified)', ''); return; }
        showError('ssid_mismatch', cleaned);
    }
}

async function doClockIn(detectedSSID, deviceId) {
    const fd = new FormData();
    fd.append('detectedSSID', detectedSSID);
    fd.append('deviceId',     deviceId);
    try {
        const r = await fetch(API_BASE + '?action=clock-in', { method:'POST', body: fd });
        const d = await r.json();
        if (d.ok) {
            document.getElementById('wifiSuccessMsg').textContent =
                detectedSSID && !detectedSSID.startsWith('UNVERIFIED')
                    ? `Connected via: ${detectedSSID}`
                    : 'Successfully recorded.';
            setState('Success');
        } else {
            setState('Error');
            document.getElementById('wifiErrorTitle').textContent = 'Clock-In Failed';
            document.getElementById('wifiErrorMsg').textContent   = d.error || 'Please try again.';
            document.getElementById('wifiErrorIcon').innerHTML    = ICONS.ssid_mismatch;
        }
    } catch(e) {
        setState('Error');
        document.getElementById('wifiErrorTitle').textContent = 'Network Error';
        document.getElementById('wifiErrorMsg').textContent   = 'Could not reach the server. Please try again.';
        document.getElementById('wifiErrorIcon').innerHTML    = ICONS.no_wifi;
    }
}

async function logFailed(reason, detectedSSID) {
    const fd = new FormData();
    fd.append('reason',       reason);
    fd.append('detectedSSID', detectedSSID);
    try { await fetch(API_BASE + '?action=log-failed', { method:'POST', body: fd }); }
    catch(e) { /* fail silently */ }
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
