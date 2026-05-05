<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
Auth::check(); Auth::requireRole(["Super Admin","Manager"]);
$pageTitle = "Onboarding — " . APP_NAME;
$isAdmin   = Auth::isAdmin();
$isManager = Auth::isManager();
$deptId    = Auth::deptId();
// Fallback: if manager's session dept is null, look it up from employee record
if ($isManager && !$isAdmin && !$deptId) {
    $deptId = (int)DB::fetchScalar("SELECT department_id FROM employee WHERE user_id=? AND department_id IS NOT NULL LIMIT 1", [Auth::id()]);
}

// Ensure checklist table exists
try { DB::execute("CREATE TABLE IF NOT EXISTS `employee_doc_checklist` (`id` INT AUTO_INCREMENT PRIMARY KEY,`employee_id` INT NOT NULL,`document_type` VARCHAR(100) NOT NULL,`status` ENUM('Pending','Submitted','Approved','Waived') DEFAULT 'Pending',`document_id` INT NULL,`notes` VARCHAR(255) NULL,`created_at` INT NOT NULL DEFAULT 0,`updated_at` INT NOT NULL DEFAULT 0,FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []); } catch (\Exception $e) {}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $a = $_POST["_action"] ?? "";
    // HR Approves / Waives / Resets a document
    if ($a === "update_checklist") {
        $clId     = (int)$_POST["checklist_id"];
        $clStatus = in_array($_POST["cl_status"]??'', ["Approved","Waived","Pending"]) ? $_POST["cl_status"] : "Approved";
        DB::execute("UPDATE employee_doc_checklist SET status=?,updated_at=? WHERE id=?", [$clStatus, time(), $clId]);
        Auth::audit("Checklist: $clStatus", "Onboarding", $clId);
        Auth::flash("success", "Document marked as $clStatus.");
        header("Location: " . url("onboarding", ["emp" => (int)$_POST["emp_id"]])); exit;
    }
    // Seed checklist for existing employee (if somehow missing)
    if ($isAdmin && $a === "seed_checklist") {
        $sEmpId = (int)$_POST["emp_id"];
        $existing = (int)DB::fetchScalar("SELECT COUNT(*) FROM employee_doc_checklist WHERE employee_id=?", [$sEmpId]);
        if (!$existing) {
            $docs = ['Employment Contract','SSS E1 Form / SS Card','PhilHealth MDR','Pag-IBIG MDF','BIR Form 2316 / TIN Card','NBI Clearance','Birth Certificate (PSA)','Medical Certificate','Diploma / Transcript of Records','2×2 ID Photo'];
            foreach ($docs as $doc) DB::insert("INSERT INTO employee_doc_checklist (employee_id,document_type,status,created_at,updated_at) VALUES (?,?,'Pending',?,?)", [$sEmpId, $doc, time(), time()]);
            Auth::flash("success", "Checklist created for employee.");
        } else { Auth::flash("error", "Checklist already exists."); }
        header("Location: " . url("onboarding", ["emp" => $sEmpId])); exit;
    }
}

// Determine view mode
$empFilter = (int)($_GET["emp"] ?? 0);

// Employees list for filter
$empWhere = "status IN ('Regular','Probationary')";
$empParams = [];
if ($isManager && !$isAdmin) { $empWhere .= " AND department_id=?"; $empParams[] = $deptId; }
$employees = DB::fetchAll("SELECT id, first_name, last_name, hire_date, department_id FROM employee WHERE $empWhere ORDER BY hire_date DESC, first_name", $empParams);

// Overview — per employee doc completion stats
$overviewData = [];
foreach ($employees as $em) {
    $total    = (int)DB::fetchScalar("SELECT COUNT(*) FROM employee_doc_checklist WHERE employee_id=?", [$em["id"]]);
    $done     = (int)DB::fetchScalar("SELECT COUNT(*) FROM employee_doc_checklist WHERE employee_id=? AND status IN ('Submitted','Approved')", [$em["id"]]);
    $approved = (int)DB::fetchScalar("SELECT COUNT(*) FROM employee_doc_checklist WHERE employee_id=? AND status='Approved'", [$em["id"]]);
    // PDS fields check
    $empRow   = DB::fetchOne("SELECT gender,date_of_birth,civil_status,address,phone,sss_no,philhealth_no,pagibig_no,tin_no,emergency_contact_name FROM employee WHERE id=?", [$em["id"]]);
    $pdsFields= ['gender','date_of_birth','civil_status','address','phone','sss_no','philhealth_no','pagibig_no','tin_no','emergency_contact_name'];
    $pdsFilled= $empRow ? count(array_filter($pdsFields, fn($f) => !empty($empRow[$f]))) : 0;
    $pdsPct   = round($pdsFilled / count($pdsFields) * 100);
    $overviewData[] = array_merge($em, ['total'=>$total,'done'=>$done,'approved'=>$approved,'pds_pct'=>$pdsPct,'has_checklist'=>$total>0]);
}

// Detail view for a specific employee
$detailEmp       = null;
$detailChecklist = [];
if ($empFilter) {
    $detailEmp = DB::fetchOne("SELECT e.*,d.name as dept_name FROM employee e LEFT JOIN department d ON e.department_id=d.id WHERE e.id=?", [$empFilter]);
    if ($detailEmp) {
        // Auto-seed if missing
        $clExisting = (int)DB::fetchScalar("SELECT COUNT(*) FROM employee_doc_checklist WHERE employee_id=?", [$empFilter]);
        if ($clExisting === 0) {
            $docs = ['Employment Contract','SSS E1 Form / SS Card','PhilHealth MDR (Member Data Record)','Pag-IBIG MDF (Membership Data Form)','BIR Form 2316 / TIN Card','NBI Clearance','Birth Certificate (PSA-authenticated)','Medical Certificate','Diploma / Transcript of Records','2×2 ID Photo (white background)'];
            foreach ($docs as $doc) DB::insert("INSERT INTO employee_doc_checklist (employee_id,document_type,status,created_at,updated_at) VALUES (?,?,'Pending',?,?)", [$empFilter, $doc, time(), time()]);
        }
        $detailChecklist = DB::fetchAll("SELECT cl.*,
            COALESCE(ed1.file_name, ed2.file_name) AS file_name,
            COALESCE(ed1.file_path, ed2.file_path) AS file_path
            FROM employee_doc_checklist cl
            LEFT JOIN employee_document ed1 ON cl.document_id = ed1.id
            LEFT JOIN employee_document ed2 ON ed2.employee_id = cl.employee_id AND ed2.document_type = cl.document_type AND cl.document_id IS NULL
            WHERE cl.employee_id=?
            ORDER BY FIELD(cl.status,'Pending','Submitted','Waived','Approved'), cl.document_type", [$empFilter]);
    }
}

require_once __DIR__ . "/../includes/layout_header.php";
?>
<div style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-size:26px;font-weight:700;margin:0;">Onboarding</h1>
        <p style="color:var(--text-muted);margin:4px 0 0;">Track document submissions and PDS completion for all employees.</p>
    </div>
    <?php if($empFilter): ?>
    <a href="<?php echo url('onboarding'); ?>" class="btn btn-outline">&#8592; Back to Overview</a>
    <?php endif; ?>
</div>

<?php if ($empFilter && $detailEmp): ?>
<!-- ── DETAIL VIEW: Single Employee ── -->
<?php
    $clTotal    = count($detailChecklist);
    $clDone     = count(array_filter($detailChecklist, fn($c) => in_array($c['status'],['Submitted','Approved'])));
    $clPending  = count(array_filter($detailChecklist, fn($c) => $c['status']==='Pending'));
    $clPct      = $clTotal>0 ? round($clDone/$clTotal*100) : 0;
?>
<div class="hrms-card" style="margin-bottom:20px;padding:20px;">
    <div style="display:flex;align-items:center;gap:16px;">
        <div style="width:50px;height:50px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0;">
            <?php echo strtoupper(substr($detailEmp['first_name'],0,1).substr($detailEmp['last_name'],0,1)); ?>
        </div>
        <div style="flex:1;">
            <div style="font-size:18px;font-weight:700;"><?php echo e($detailEmp['first_name'].' '.$detailEmp['last_name']); ?></div>
            <div style="font-size:12px;color:var(--text-muted);"><?php echo e($detailEmp['dept_name']??'No Department'); ?> · Hired <?php echo $detailEmp['hire_date']?date('M j, Y',strtotime($detailEmp['hire_date'])):'—'; ?></div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:24px;font-weight:700;"><?php echo $clPct; ?>%</div>
            <div style="font-size:12px;color:var(--text-muted);"><?php echo $clDone; ?>/<?php echo $clTotal; ?> docs submitted</div>
            <?php if($clPending>0): ?><span style="font-size:11px;color:#fb8c00;"><?php echo $clPending; ?> still pending</span><?php else: ?><span class="badge badge-success">All submitted</span><?php endif; ?>
        </div>
    </div>
    <div style="height:8px;background:rgba(255,255,255,0.08);border-radius:4px;overflow:hidden;margin-top:14px;">
        <div style="height:100%;width:<?php echo $clPct; ?>%;background:var(--accent);border-radius:4px;transition:width .4s;"></div>
    </div>
</div>

<?php if (empty($detailChecklist)): ?>
<div class="hrms-card" style="padding:40px;text-align:center;color:var(--text-muted);">
    <p style="margin:0;">No document checklist found. Please reload the page.</p>
</div>
<?php else: ?>
<div class="hrms-card">
    <div class="card-header"><h3>&#128203; Document Checklist</h3></div>
    <div class="card-body" style="padding:0;">
    <?php foreach ($detailChecklist as $cl):
        $clColor = ["Pending"=>"#fb8c00","Submitted"=>"#4da6ff","Approved"=>"#66bb6a","Waived"=>"var(--text-muted)"][$cl["status"]] ?? "#fff";
    ?>
    <div style="display:flex;align-items:center;gap:14px;padding:13px 20px;border-bottom:1px solid var(--border);">
        <div style="width:10px;height:10px;border-radius:50%;background:<?php echo $clColor; ?>;flex-shrink:0;"></div>
        <div style="flex:1;">
            <div style="font-size:13px;font-weight:600;"><?php echo e($cl["document_type"]); ?></div>
            <?php if($cl["file_name"]): ?><div style="font-size:11px;color:var(--text-muted);margin-top:2px;">&#128206; <?php echo e($cl["file_name"]); ?></div><?php endif; ?>
        </div>
        <span style="font-size:11px;padding:3px 10px;border-radius:12px;background:<?php echo $clColor; ?>22;color:<?php echo $clColor; ?>;font-weight:600;"><?php echo $cl["status"]; ?></span>
        <?php if($cl["file_name"]): ?>
            <a href="<?php echo BASE_URL.'/'.$cl['file_path']; ?>" target="_blank" class="btn btn-sm btn-accent" style="padding:4px 12px;font-size:11px;">👁 View File</a>
        <?php endif; ?>
        <form method="POST" style="display:flex;gap:4px;margin:0;">
            <input type="hidden" name="_action" value="update_checklist">
            <input type="hidden" name="checklist_id" value="<?php echo $cl['id']; ?>">
            <input type="hidden" name="emp_id" value="<?php echo $detailEmp['id']; ?>">
            <?php if($cl['status']!=='Approved'): ?><button type="submit" name="cl_status" value="Approved" class="btn btn-sm" style="background:#43a047;color:#fff;padding:3px 8px;font-size:11px;">Approve</button><?php endif; ?>
            <?php if($cl['status']==='Pending'): ?><button type="submit" name="cl_status" value="Waived" class="btn btn-sm btn-outline" style="padding:3px 8px;font-size:11px;">Waive</button><?php endif; ?>
            <?php if($cl['status']!=='Pending'): ?><button type="submit" name="cl_status" value="Pending" class="btn btn-sm btn-outline" style="padding:3px 8px;font-size:11px;color:#fb8c00;">Reset</button><?php endif; ?>
        </form>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- ── OVERVIEW: All Employees ── -->
<div class="hrms-card">
    <div class="card-header"><h3>Employee Onboarding Status</h3></div>
    <div class="card-body" style="padding:0;">
    <table class="table hrms-table" style="margin:0;">
    <thead><tr>
        <th>Employee</th><th>Hire Date</th>
        <th>PDS</th><th>Documents</th><th>Status</th><th>Action</th>
    </tr></thead>
    <tbody>
    <?php foreach($overviewData as $row):
        $docPct   = $row['total']>0 ? round($row['done']/$row['total']*100) : 0;
        $allOk    = $docPct===100 && $row['pds_pct']>=80;
        $hasIssue = $row['done'] < $row['total'];
    ?>
    <tr>
        <td style="font-weight:600;"><?php echo e($row['first_name'].' '.$row['last_name']); ?></td>
        <td style="font-size:12px;color:var(--text-muted);"><?php echo $row['hire_date']?date('M j, Y',strtotime($row['hire_date'])):'—'; ?></td>
        <td>
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:60px;height:5px;background:rgba(255,255,255,0.08);border-radius:3px;overflow:hidden;"><div style="height:100%;width:<?php echo $row['pds_pct']; ?>%;background:var(--accent);border-radius:3px;"></div></div>
                <span style="font-size:11px;"><?php echo $row['pds_pct']; ?>%</span>
            </div>
        </td>
        <td>
            <?php if(!$row['has_checklist']): ?>
            <span style="font-size:11px;color:var(--text-muted);">No checklist</span>
            <?php else: ?>
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:60px;height:5px;background:rgba(255,255,255,0.08);border-radius:3px;overflow:hidden;"><div style="height:100%;width:<?php echo $docPct; ?>%;background:<?php echo $hasIssue?'#fb8c00':'#66bb6a'; ?>;border-radius:3px;"></div></div>
                <span style="font-size:11px;"><?php echo $row['done']; ?>/<?php echo $row['total']; ?></span>
            </div>
            <?php endif; ?>
        </td>
        <td>
            <?php if($allOk): ?><span class="badge badge-success">Complete</span>
            <?php elseif($hasIssue||$row['pds_pct']<80): ?><span class="badge badge-warning">Pending</span>
            <?php else: ?><span class="badge badge-info">In Review</span><?php endif; ?>
        </td>
        <td><a href="<?php echo url('onboarding',['emp'=>$row['id']]); ?>" class="btn btn-sm btn-outline" style="padding:3px 10px;">Review</a></td>
    </tr>
    <?php endforeach;
    if(empty($overviewData)): ?><tr><td colspan="6" style="text-align:center;padding:28px;color:var(--text-muted);">No employees found.</td></tr><?php endif; ?>
    </tbody>
    </table>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>
