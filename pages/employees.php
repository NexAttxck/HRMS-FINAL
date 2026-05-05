<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
Auth::check();
$pageTitle = "Employees &mdash; " . APP_NAME;
$action    = $_GET["action"] ?? "index";
$isAdmin   = Auth::isAdmin();
$isManager = Auth::isManager();

// Ensure document checklist table exists
try { DB::execute("CREATE TABLE IF NOT EXISTS `employee_doc_checklist` (`id` INT AUTO_INCREMENT PRIMARY KEY,`employee_id` INT NOT NULL,`document_type` VARCHAR(100) NOT NULL,`status` ENUM('Pending','Submitted','Approved','Waived') DEFAULT 'Pending',`document_id` INT NULL,`notes` VARCHAR(255) NULL,`created_at` INT NOT NULL DEFAULT 0,`updated_at` INT NOT NULL DEFAULT 0,FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []); } catch (\Exception $e) {}

if (!function_exists('seedDocChecklist')) {
    function seedDocChecklist(int $empId): void {
        $existing = (int)DB::fetchScalar("SELECT COUNT(*) FROM employee_doc_checklist WHERE employee_id=?", [$empId]);
        if ($existing > 0) return;
        $docs = ['Employment Contract','SSS E1 Form / SS Card','PhilHealth MDR','Pag-IBIG MDF','BIR Form 2316 / TIN Card','NBI Clearance','Birth Certificate (PSA)','Medical Certificate','Diploma / Transcript of Records','2x2 ID Photo'];
        foreach ($docs as $doc) DB::insert("INSERT INTO employee_doc_checklist (employee_id,document_type,status,created_at,updated_at) VALUES (?,?,'Pending',?,?)", [$empId, $doc, time(), time()]);
    }
}

//  POST HANDLERS 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $a = $_POST["_action"] ?? "";

    // CREATE
    if (($isAdmin || $isManager) && $a === "create") {
        // Always auto-generate — never accept from user input
        $year  = date("Y");
        $count = (int)DB::fetchScalar("SELECT COUNT(*) FROM employee") + 1;
        $empNo = sprintf("EMP-%s-%04d", $year, $count);
        // Ensure uniqueness if count collision
        while (DB::fetchScalar("SELECT COUNT(*) FROM employee WHERE employee_no=?", [$empNo])) {
            $count++;
            $empNo = sprintf("EMP-%s-%04d", $year, $count);
        }
        $uid = null;
        if (!empty($_POST["email"])) {
            $email = trim($_POST["email"]);
            $emailExists = DB::fetchScalar("SELECT id FROM `user` WHERE email = ?", [$email]);
            if ($emailExists) {
                Auth::flash("error", "The email address '$email' is already registered to another user.");
                header("Location: ".url("employees", ["action"=>"create"])); exit;
            }
            $uname = strtolower(str_replace(" ",".",trim($_POST["first_name"]??""))) . "." . strtolower(trim($_POST["last_name"]??""));
            $uid = DB::insert("INSERT INTO `user` (username,email,password,role,status,department_id,created_at) VALUES (?,?,?,?,?,?,?)",
                [$uname, $email, md5($_POST["password"] ?? "Password123"), "Employee", "Active", $_POST["department_id"] ?? null, time()]);
        }
        // Sync job_title from position if position_id provided
        if (!empty($_POST['position_id'])) {
            $posTitle = DB::fetchScalar("SELECT title FROM position WHERE id=?", [(int)$_POST['position_id']]);
            if ($posTitle) $_POST['job_title'] = $posTitle;
        }
        $newEmpId = DB::insert("INSERT INTO employee (user_id,employee_no,first_name,middle_name,last_name,suffix,gender,email,phone,department_id,job_title,position_id,employment_type,status,hire_date,basic_salary,civil_status,date_of_birth,place_of_birth,nationality,blood_type,highest_education,address,sss_no,philhealth_no,pagibig_no,tin_no,bank_name,bank_account_number,emergency_contact_name,emergency_contact_relationship,emergency_contact_phone,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$uid,$empNo,$_POST["first_name"]??null,$_POST["middle_name"]??null,$_POST["last_name"]??null,$_POST["suffix"]??null,$_POST["gender"]??null,$_POST["email"]??null,$_POST["phone"]??null,empty($_POST["department_id"])?null:$_POST["department_id"],$_POST["job_title"]??null,empty($_POST["position_id"])?null:$_POST["position_id"],$_POST["employment_type"]??"Full-Time",$_POST["status"]??"Regular",!empty($_POST["hire_date"])?$_POST["hire_date"]:date('Y-m-d'),$_POST["basic_salary"]??0,$_POST["civil_status"]??"Single",$_POST["date_of_birth"]??null,$_POST["place_of_birth"]??null,$_POST["nationality"]??"Filipino",$_POST["blood_type"]??null,$_POST["highest_education"]??null,$_POST["address"]??null,$_POST["sss_no"]??null,$_POST["philhealth_no"]??null,$_POST["pagibig_no"]??null,$_POST["tin_no"]??null,$_POST["bank_name"]??null,$_POST["bank_account_number"]??null,$_POST["emergency_contact_name"]??null,$_POST["emergency_contact_relationship"]??null,$_POST["emergency_contact_phone"]??null,time(),time()]
        );
        seedDocChecklist((int)$newEmpId);
        Auth::audit('Create Employee', 'Employee', (int)$newEmpId, ($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''));
        Auth::flash("success","Employee created successfully! You can now upload their 201 files.");
        header("Location: ".url("employees",["action"=>"edit","id"=>$newEmpId,"tab"=>"documents"])); exit;
    }

    // UPDATE — full 201 file
    if (($isAdmin || $isManager) && $a === "update") {
        $id = (int)$_POST["id"];
        $getPost = function($k) { return empty($_POST[$k]) ? null : $_POST[$k]; };
        // Sync job_title from position
        if (!empty($_POST['position_id'])) {
            $posTitle = DB::fetchScalar("SELECT title FROM position WHERE id=?", [(int)$_POST['position_id']]);
            if ($posTitle) $_POST['job_title'] = $posTitle;
        }
        DB::execute("UPDATE employee SET first_name=?,middle_name=?,last_name=?,suffix=?,gender=?,email=?,phone=?,department_id=?,job_title=?,position_id=?,employment_type=?,status=?,hire_date=?,basic_salary=?,civil_status=?,date_of_birth=?,place_of_birth=?,nationality=?,blood_type=?,highest_education=?,address=?,sss_no=?,philhealth_no=?,pagibig_no=?,tin_no=?,bank_name=?,bank_account_number=?,emergency_contact_name=?,emergency_contact_relationship=?,emergency_contact_phone=?,updated_at=? WHERE id=?",
            [$getPost("first_name"),$getPost("middle_name"),$getPost("last_name"),$getPost("suffix"),$getPost("gender"),$getPost("email"),$getPost("phone"),$getPost("department_id"),$_POST["job_title"]??$getPost("job_title"),$getPost("position_id"),$getPost("employment_type")?:"Full-Time",$getPost("status")?:"Regular",$getPost("hire_date"),$_POST["basic_salary"]??0,$getPost("civil_status")?:"Single",$getPost("date_of_birth"),$getPost("place_of_birth"),$getPost("nationality")?:"Filipino",$getPost("blood_type"),$getPost("highest_education"),$getPost("address"),$getPost("sss_no"),$getPost("philhealth_no"),$getPost("pagibig_no"),$getPost("tin_no"),$getPost("bank_name"),$getPost("bank_account_number"),$getPost("emergency_contact_name"),$getPost("emergency_contact_relationship"),$getPost("emergency_contact_phone"),time(),$id]
        );
        DB::execute("INSERT INTO audit_log (user_id,action,module,model_id,ip,created_at) VALUES (?,?,?,?,?,?)",
            [Auth::id(), 'Update Employee', 'Employee', $id, $_SERVER['REMOTE_ADDR']??'127.0.0.1', time()]);
        Auth::flash("success","Employee 201 file updated!");
        $returnTab = $_POST["_active_tab"] ?? "personal";
        header("Location: ".url("employees",["action"=>"edit","id"=>$id,"tab"=>$returnTab])); exit;
    }

    // DELETE
    if (($isAdmin || $isManager) && $a === "delete") {
        $delId = (int)$_POST["id"];
        DB::execute("INSERT INTO audit_log (user_id,action,module,model_id,ip,created_at) VALUES (?,?,?,?,?,?)",
            [Auth::id(), 'Delete Employee', 'Employee', $delId, $_SERVER['REMOTE_ADDR']??'127.0.0.1', time()]);
        DB::execute("DELETE FROM employee WHERE id=?",[$delId]);
        Auth::flash("success","Employee removed."); header("Location: ".url("employees")); exit;
    }

    // UPLOAD DOCUMENT (201 file attachment)
    if (($isAdmin || $isManager) && $a === "upload_doc") {
        $eid     = (int)$_POST["employee_id"];
        $docType = trim($_POST["document_type"] ?? "Other");
        $clId    = (int)($_POST["checklist_id"] ?? 0);
        if (!empty($_FILES["doc_file"]["name"]) && $_FILES["doc_file"]["error"] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . "/../uploads/employee_docs/$eid/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $origName   = basename($_FILES["doc_file"]["name"]);
            $safeName   = preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
            $uniqueName = time() . "_" . $safeName;
            $destPath   = $uploadDir . $uniqueName;
            if (move_uploaded_file($_FILES["doc_file"]["tmp_name"], $destPath)) {
                $newDocId = DB::insert("INSERT INTO employee_document (employee_id,document_type,file_name,file_path,uploaded_at) VALUES (?,?,?,?,?)",
                    [$eid, $docType, $origName, "uploads/employee_docs/$eid/$uniqueName", time()]);
                // Link to checklist item and mark Submitted
                if ($clId) {
                    DB::execute("UPDATE employee_doc_checklist SET document_id=?,status='Submitted',updated_at=? WHERE id=? AND employee_id=?",
                        [$newDocId, time(), $clId, $eid]);
                } else {
                    // Fallback: match by document_type
                    DB::execute("UPDATE employee_doc_checklist SET document_id=?,status='Submitted',updated_at=? WHERE employee_id=? AND document_type=? AND status='Pending' LIMIT 1",
                        [$newDocId, time(), $eid, $docType]);
                }
                Auth::flash("success", "Document uploaded: $origName");
            } else {
                Auth::flash("error", "Upload failed. Check folder permissions.");
            }
        } else {
            Auth::flash("error", "No file selected or upload error.");
        }
        header("Location: " . url("employees", ["action"=>"edit","id"=>$eid,"tab"=>"documents"])); exit;
    }

    // DELETE DOCUMENT
    if (($isAdmin || $isManager) && $a === "delete_doc") {
        $docId = (int)$_POST["doc_id"];
        $empId2 = (int)$_POST["employee_id"];
        $doc = DB::fetchOne("SELECT * FROM employee_document WHERE id=? AND employee_id=?", [$docId, $empId2]);
        if ($doc) {
            $fullPath = __DIR__ . "/../" . $doc["file_path"];
            if (file_exists($fullPath)) @unlink($fullPath);
            DB::execute("DELETE FROM employee_document WHERE id=?", [$docId]);
            Auth::flash("success", "Document deleted.");
        }
        header("Location: " . url("employees", ["action"=>"edit","id"=>$empId2,"tab"=>"documents"])); exit;
    }
    // HR: Approve or Waive a checklist item
    if (($isAdmin||$isManager) && $a === 'update_checklist') {
        $clId = (int)$_POST['checklist_id']; $clStatus = $_POST['cl_status'] ?? 'Approved';
        DB::execute("UPDATE employee_doc_checklist SET status=?,updated_at=? WHERE id=?", [$clStatus, time(), $clId]);
        Auth::flash('success', 'Document status updated.');
        header('Location: ' . url('employees', ['action'=>'edit','id'=>(int)$_POST['emp_id'],'tab'=>'documents'])); exit;
    }
} // end POST block
if (($isAdmin || $isManager) && $action === "delete") {
    $delId = (int)($_GET["id"]??0);
    DB::execute("INSERT INTO audit_log (user_id,action,module,model_id,ip,created_at) VALUES (?,?,?,?,?,?)",
        [Auth::id(), 'Delete Employee', 'Employee', $delId, $_SERVER['REMOTE_ADDR']??'127.0.0.1', time()]);
    DB::execute("DELETE FROM employee WHERE id=?",[$delId]);
    Auth::flash("success","Employee removed."); header("Location: ".url("employees")); exit;
}

$depts     = DB::fetchAll("SELECT id, name FROM department ORDER BY name");
$positions = DB::fetchAll("SELECT id, title, department_id FROM position ORDER BY title");
$search      = trim($_GET["q"] ?? "");
$deptFilter  = (int)($_GET["dept"] ?? 0);
$statusFilter = $_GET["status"] ?? "";
$where = "1=1"; $params = [];
if ($search)      { $where .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ? OR e.employee_no LIKE ?)"; $s="%$search%"; $params=array_merge($params,[$s,$s,$s,$s]); }
if ($deptFilter)  { $where .= " AND e.department_id=?"; $params[] = $deptFilter; }
if ($statusFilter){ $where .= " AND e.status=?"; $params[] = $statusFilter; }
// Managers can only see employees in their own department
// Fall back to employee table dept if user.department_id is not set
$managerDeptId = Auth::deptId();
if ($isManager && !$isAdmin) {
    if (!$managerDeptId) {
        // Try to get department from the employee record linked to this user
        $managerDeptId = DB::fetchScalar("SELECT department_id FROM employee WHERE user_id=? AND department_id IS NOT NULL LIMIT 1", [Auth::id()]);
    }
    if ($managerDeptId) {
        $where .= " AND e.department_id=?";
        $params[] = $managerDeptId;
    } else {
        // Manager has no department at all — return empty to avoid showing all data
        $where .= " AND 1=0";
    }
}
$employees = DB::fetchAll("SELECT e.*, d.name as dept_name, p.title as position_title FROM employee e LEFT JOIN department d ON e.department_id=d.id LEFT JOIN position p ON e.position_id=p.id WHERE $where ORDER BY e.first_name", $params);

$viewId = (int)($_GET["id"] ?? 0);
$editEmp = null;
if (($action === "edit" || $action === "view") && $viewId) {
    $editEmp = DB::fetchOne("SELECT e.*, d.name as dept_name, p.title as position_title FROM employee e LEFT JOIN department d ON e.department_id=d.id LEFT JOIN position p ON e.position_id=p.id WHERE e.id=?", [$viewId]);
}
// Load checklist for HR Documents tab
$empChecklist = [];
if (($action === 'edit') && $viewId) {
    $empChecklist = DB::fetchAll("SELECT cl.*,
        COALESCE(ed1.file_name, ed2.file_name) AS file_name,
        COALESCE(ed1.file_path, ed2.file_path) AS file_path
        FROM employee_doc_checklist cl
        LEFT JOIN employee_document ed1 ON cl.document_id = ed1.id
        LEFT JOIN employee_document ed2 ON ed2.employee_id = cl.employee_id AND ed2.document_type = cl.document_type AND cl.document_id IS NULL
        WHERE cl.employee_id=?
        ORDER BY FIELD(cl.status,'Pending','Submitted','Waived','Approved'), cl.document_type", [$viewId]);
}

// helper to print an input field
function fi(string $name, string $label, string $type, $val, bool $required=false, string $extra=""): string {
    $req = $required ? " required" : "";
    $v   = e((string)$val);
    return "<div class='form-group'>
        <label class='form-label'>".e($label).($required?" <span style='color:#ef5350;'>*</span>":"")."</label>
        <input type='$type' name='$name' value='$v' class='form-control'$req $extra>
    </div>";
}
function fs(string $name, string $label, array $options, $selected, bool $required=false, string $extra=""): string {
    $req = $required ? " required" : "";
    $html = "<div class='form-group'><label class='form-label'>".e($label).($required?" <span style='color:#ef5350;'>*</span>":"")."</label><select name='$name' class='form-control'$req $extra><option value=''>&mdash; Select &mdash;</option>";
    foreach ($options as $k=>$v) {
        $sel = ((string)$selected===(string)$k||(string)$selected===(string)$v) ? " selected" : "";
        $html .= "<option value='".e((string)$k)."'$sel>".e((string)$v)."</option>";
    }
    return $html."</select></div>";
}


// Load documents for 201 file
$empDocs = [];
if (($action === "edit" || $action === "view") && $viewId) {
    $empDocs = DB::fetchAll("SELECT * FROM employee_document WHERE employee_id=? ORDER BY uploaded_at DESC", [$viewId]);
}require_once __DIR__ . "/../includes/layout_header.php";
?>
<style>
.emp-tabs { display:flex; gap:0; border-bottom:2px solid var(--border); margin-bottom:24px; overflow-x:auto; }
.emp-tab  { padding:12px 20px; font-size:13px; font-weight:600; color:var(--text-muted); cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px; white-space:nowrap; transition:color .2s,border-color .2s; display:flex; align-items:center; gap:8px; }
.emp-tab:hover { color:var(--text); }
.emp-tab.active { color:var(--accent); border-bottom-color:var(--accent); }
.emp-panel { display:none; }
.emp-panel.active { display:block; }
.form-group { margin-bottom:18px; }
.form-label { display:block; font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
.form-control { width:100%; padding:10px 14px; background:rgba(255,255,255,0.06); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:13px; transition:border-color .2s,box-shadow .2s; box-sizing:border-box; }
.form-control:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(124,147,104,0.15); }
.form-section-title { font-size:13px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.8px; padding-bottom:10px; border-bottom:1px solid var(--border); margin-bottom:20px; display:flex; align-items:center; gap:10px; }
.form-section-title::before { content:""; width:3px; height:14px; background:var(--accent); border-radius:2px; display:inline-block; }
.emp-profile-photo { width:120px; height:120px; border-radius:50%; background:var(--accent); display:flex; align-items:center;justify-content:center; font-size:40px; font-weight:700; color:#fff; margin:0 auto 16px; border:3px solid var(--border); }
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:0 20px; }
.grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:0 20px; }
.grid-4 { display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:0 20px; }
@media(max-width:768px){.grid-2,.grid-3,.grid-4{grid-template-columns:1fr;}}
.govid-card { background:rgba(255,255,255,0.04); border:1px solid var(--border); border-radius:12px; padding:20px; }
.govid-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; margin-bottom:12px; }
</style>

<?php if ($action === "create" && ($isAdmin || $isManager)): ?>
<!-- Simple Create Employee Form -->
<div style="margin-bottom:24px;display:flex;align-items:center;gap:14px;">
    <a href="<?php echo url("employees"); ?>" class="btn btn-outline btn-sm" style="padding:6px 14px;">&#8592; Back</a>
    <h1 style="font-size:22px;font-weight:700;margin:0;">Add New Employee</h1>
</div>
<form method="POST" id="empCreateForm">
    <input type="hidden" name="_action" value="create">
    <div class="hrms-card" style="max-width:600px;margin:0 auto;padding:30px;">
        <div style="text-align:center;margin-bottom:24px;">
            <h3 style="margin:0 0 8px;font-size:18px;">Basic Information</h3>
            <p style="color:var(--text-muted);font-size:13px;margin:0;">Enter the essential details to create the employee's profile. You can upload their 201 files and documents afterward.</p>
        </div>
        <div class="grid-2">
            <?php echo fi("first_name","First Name","text","",true); ?>
            <?php echo fi("last_name","Last Name","text","",true); ?>
        </div>
        <div class="grid-2">
            <?php echo fi("email","Email Address","email","",true); ?>
            <div class="form-group">
                <label class="form-label">Department <span style="color:#ef5350;">*</span></label>
                <select name="department_id" id="deptSelectCreate" class="form-control" required onchange="filterPositions('deptSelectCreate', 'posSelectCreate')">
                    <option value="">&mdash; Select &mdash;</option>
                    <?php foreach($depts as $d): ?>
                    <option value="<?php echo $d["id"]; ?>"><?php echo e($d["name"]); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Position <span style="color:#ef5350;">*</span></label>
                <select name="position_id" id="posSelectCreate" class="form-control" required onchange="syncJobTitle(this, 'hiddenJobTitle')">
                    <option value="">&mdash; Select Department First &mdash;</option>
                    <?php foreach($positions as $p): ?>
                    <option value="<?php echo $p["id"]; ?>" data-dept="<?php echo $p["department_id"]; ?>" style="display:none;"><?php echo e($p["title"]); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php echo fs("status","Employment Status",["Probationary"=>"Probationary","Regular"=>"Regular","Contractual"=>"Contractual"],"Probationary",true); ?>
        </div>
        <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border);text-align:right;">
            <button type="submit" class="btn btn-accent" style="padding:10px 28px;font-size:14px;font-weight:600;">
                &#43; Create Profile &amp; Continue to 201 Files
            </button>
        </div>
    </div>
</form>

<?php elseif ($action === "edit" && $editEmp && ($isAdmin || $isManager)): ?>
<?php
$isEdit = true;
$empName = e($editEmp["first_name"]." ".$editEmp["last_name"]);
$initials = strtoupper(substr($editEmp["first_name"]??"U",0,1).substr($editEmp["last_name"]??"",0,1));
$activeTab = $_GET["tab"] ?? "personal";
?>
<!-- Page Header -->
<div style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:14px;">
        <a href="<?php echo url("employees"); ?>" class="btn btn-outline btn-sm" style="padding:6px 14px;">&#8592; Back</a>
        <div>
            <h1 style="font-size:22px;font-weight:700;margin:0;">Edit Employee &mdash; <?php echo $empName; ?></h1>
            <?php if($isEdit): ?><p style="margin:2px 0 0;font-size:13px;color:var(--text-muted);"><?php echo e($editEmp["employee_no"]??""); ?> &mdash; <?php echo e($editEmp["dept_name"]??""); ?></p><?php endif; ?>
        </div>
    </div>
    <?php if($isEdit&&($isAdmin||$isManager)): ?>
    <div style="display:flex;gap:8px;">
        <a href="<?php echo url("employees",["action"=>"view","id"=>$editEmp["id"]]); ?>" class="btn btn-outline btn-sm">View Profile</a>
    </div>
    <?php endif; ?>
</div>

<form method="POST" id="emp201Form" enctype="multipart/form-data" novalidate>
<input type="hidden" name="_action" value="update">
<input type="hidden" name="id" value="<?php echo $editEmp["id"]; ?>">
<input type="hidden" name="_active_tab" id="activeTabInput" value="<?php echo e($activeTab); ?>">

<div style="display:grid;grid-template-columns:260px 1fr;gap:24px;align-items:flex-start;">

<!--  LEFT PANEL: photo + quick info  -->
<div>
    <div class="hrms-card" style="padding:24px;text-align:center;margin-bottom:16px;">
        <div class="emp-profile-photo"><?php echo $initials; ?></div>
        <p style="font-size:13px;color:var(--text-muted);margin:0 0 12px;">Profile Photo</p>
        <label style="cursor:pointer;display:inline-block;" class="btn btn-outline btn-sm" style="padding:6px 14px;">
            <input type="file" name="photo" accept="image/*" style="display:none;" onchange="previewPhoto(this)">
            &#128247; Upload Photo
        </label>
    </div>

    <div class="hrms-card" style="padding:0;overflow:hidden;">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border);">
            <p style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin:0 0 6px;">Employment Status</p>
            <?php echo fs("status","Status",["Probationary"=>"Probationary","Regular"=>"Regular","On Leave"=>"On Leave","Resigned"=>"Resigned","Terminated"=>"Terminated"],$editEmp["status"]??"Regular",true); ?>
        </div>
        <div style="padding:14px 16px;border-bottom:1px solid var(--border);">
            <?php echo fs("employment_type","Employment Type",["Full-Time"=>"Full-Time","Part-Time"=>"Part-Time","Contractual"=>"Contractual","Intern"=>"Intern"],$editEmp["employment_type"]??"Full-Time"); ?>
        </div>
    </div>
</div>

<!--  RIGHT PANEL: tabbed 201 sections  -->
<div class="hrms-card" style="padding:0;overflow:hidden;">
    <!-- Tabs -->
    <div class="emp-tabs" style="padding:0 20px;">
        <div class="emp-tab <?php echo $activeTab==="personal"?"active":""; ?>" onclick="switchTab('personal',this)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            Personal
        </div>
        <div class="emp-tab <?php echo $activeTab==="employment"?"active":""; ?>" onclick="switchTab('employment',this)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
            Employment
        </div>
        <div class="emp-tab <?php echo $activeTab==="emergency"?"active":""; ?>" onclick="switchTab('emergency',this)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.21h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 9a16 16 0 0 0 6 6l1.95-1.95a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 15z"></path></svg>
            Emergency
        </div>
        <div class="emp-tab <?php echo $activeTab==="documents"?"active":""; ?>" onclick="switchTab('documents',this)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            Documents
        </div>
    </div>

    <div style="padding:24px;">

    <!--  TAB: PERSONAL INFORMATION  -->
    <div class="emp-panel <?php echo $activeTab==="personal"?"active":""; ?>" id="tab-personal">
        <div class="form-section-title">Personal Information</div>
        <div class="grid-4">
            <?php echo fi("first_name","First Name","text",$editEmp["first_name"]??"",true); ?>
            <?php echo fi("middle_name","Middle Name","text",$editEmp["middle_name"]??"",$false??false); ?>
            <?php echo fi("last_name","Last Name","text",$editEmp["last_name"]??"",true); ?>
            <?php echo fi("suffix","Suffix (Jr./Sr./III)","text",$editEmp["suffix"]??"",$false??false); ?>
        </div>
        <div class="grid-3">
            <?php echo fs("gender","Gender",["Male"=>"Male","Female"=>"Female","Other"=>"Other"],$editEmp["gender"]??"",$true??true); ?>
            <?php echo fi("date_of_birth","Date of Birth","date",$editEmp["date_of_birth"]??"",$false??false); ?>
            <?php echo fi("place_of_birth","Place of Birth","text",$editEmp["place_of_birth"]??"",$false??false); ?>
        </div>
        <div class="grid-3">
            <?php echo fs("civil_status","Civil Status",["Single"=>"Single","Married"=>"Married","Widowed"=>"Widowed","Separated"=>"Separated"],$editEmp["civil_status"]??"Single"); ?>
            <?php echo fi("nationality","Nationality","text",$editEmp["nationality"]??"Filipino"); ?>
            <?php echo fs("blood_type","Blood Type",["A+"=>"A+","A-"=>"A-","B+"=>"B+","B-"=>"B-","AB+"=>"AB+","AB-"=>"AB-","O+"=>"O+","O-"=>"O-"],$editEmp["blood_type"]??"",$false??false); ?>
        </div>

        <div class="form-section-title" style="margin-top:24px;">Contact Information</div>
        <div class="grid-2">
            <?php echo fi("email","Email Address","email",$editEmp["email"]??"",$true??true); ?>
            <?php echo fi("phone","Mobile / Phone","text",$editEmp["phone"]??"",$false??false); ?>
        </div>
        <div class="form-group">
            <label class="form-label">Home Address</label>
            <textarea name="address" class="form-control" rows="3" style="resize:vertical;"><?php echo e($editEmp["address"]??"",$false??false); ?></textarea>
        </div>
    </div>

    <!--  TAB: EMPLOYMENT INFORMATION  -->
    <div class="emp-panel <?php echo $activeTab==="employment"?"active":""; ?>" id="tab-employment">
        <div class="form-section-title">Employment Details</div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Employee No.</label>
                <div style="padding:10px 14px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:8px;font-size:13px;font-weight:700;color:var(--accent);letter-spacing:.5px;">
                    <?php echo e($editEmp["employee_no"] ?? ''); ?>
                    <span style="font-size:10px;color:var(--text-muted);font-weight:400;margin-left:8px;">Auto-generated · read-only</span>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Department <span style="color:#ef5350;">*</span></label>
                <select name="department_id" id="deptSelectEdit" class="form-control" onchange="filterPositions('deptSelectEdit', 'posSelectEdit')">
                    <option value="">&mdash; Select &mdash;</option>
                    <?php foreach($depts as $d): ?>
                    <option value="<?php echo $d["id"]; ?>" <?php echo ($editEmp["department_id"]??"")==$d["id"]?"selected":""; ?>><?php echo e($d["name"]); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Position</label>
                <select name="position_id" id="posSelectEdit" class="form-control" onchange="syncJobTitle(this, 'hiddenJobTitle')">
                    <option value="">&mdash; Select &mdash;</option>
                    <?php foreach($positions as $p): ?>
                    <option value="<?php echo $p["id"]; ?>" data-dept="<?php echo $p["department_id"]; ?>" <?php echo ($editEmp["position_id"]??"")==$p["id"]?"selected":""; ?> style="display:none;"><?php echo e($p["title"]); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid-2">
            <input type="hidden" name="job_title" id="hiddenJobTitle" value="<?php echo e($editEmp['position_title'] ?? $editEmp['job_title'] ?? ''); ?>">
            <?php echo fi("basic_salary","Basic Monthly Salary (&#8369;)","number",$editEmp["basic_salary"]??"",$false??false,'step="0.01" min="0"'); ?>
        </div>
        <div class="grid-2">
            <?php echo fi("hire_date","Date Hired","date",$editEmp["hire_date"]??"",$false??false); ?>
            <?php echo fs("highest_education","Highest Educational Attainment",["High School"=>"High School","Vocational"=>"Vocational / TESDA","Bachelor&apos;s Degree"=>"Bachelor&apos;s Degree","Master&apos;s Degree"=>"Master&apos;s Degree","Doctorate"=>"Doctorate / PhD"],$editEmp["highest_education"]??"",$false??false); ?>
        </div>
    </div>

    <!--  TAB: EMERGENCY CONTACT  -->
    <div class="emp-panel <?php echo $activeTab==="emergency"?"active":""; ?>" id="tab-emergency">
        <div class="form-section-title">Emergency Contact Person</div>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:20px;">Person to notify in case of emergency. Must not be the same as the employee.</p>
        <div class="grid-3">
            <?php echo fi("emergency_contact_name","Full Name","text",$editEmp["emergency_contact_name"]??"",$false??false); ?>
            <?php echo fi("emergency_contact_relationship","Relationship","text",$editEmp["emergency_contact_relationship"]??"",$false??false,'placeholder="e.g. Spouse, Parent, Sibling"'); ?>
            <?php echo fi("emergency_contact_phone","Contact Number","text",$editEmp["emergency_contact_phone"]??"",$false??false,'placeholder="e.g. 09XXXXXXXXX"'); ?>
        </div>
    </div>

    </form> <!-- END OF emp201Form -->

    <!--  TAB: DOCUMENTS (201 File Attachments)  -->
    <div class="emp-panel <?php echo $activeTab==="documents"?"active":""; ?>" id="tab-documents">
        <div class="form-section-title">Government IDs</div>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:20px;">Required for statutory compliance and government reporting.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
            <div class="govid-card">
                <div class="govid-icon" style="background:rgba(30,136,229,0.12);">&#127959;</div>
                <div style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--text);">SSS &mdash; Social Security System</div>
                <?php echo fi("sss_no","SSS Number","text",$editEmp["sss_no"]??"",false,'placeholder="XX-XXXXXXX-X" form="emp201Form"'); ?>
            </div>
            <div class="govid-card">
                <div class="govid-icon" style="background:rgba(67,160,71,0.12);">&#127973;</div>
                <div style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--text);">PhilHealth</div>
                <?php echo fi("philhealth_no","PhilHealth Number","text",$editEmp["philhealth_no"]??"",false,'placeholder="XX-XXXXXXXXX-X" form="emp201Form"'); ?>
            </div>
            <div class="govid-card">
                <div class="govid-icon" style="background:rgba(142,36,170,0.12);">&#127968;</div>
                <div style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--text);">Pag-IBIG / HDMF</div>
                <?php echo fi("pagibig_no","Pag-IBIG Number","text",$editEmp["pagibig_no"]??"",false,'placeholder="XXXX-XXXX-XXXX" form="emp201Form"'); ?>
            </div>
            <div class="govid-card">
                <div class="govid-icon" style="background:rgba(251,140,0,0.12);">&#128196;</div>
                <div style="font-size:13px;font-weight:700;margin-bottom:10px;color:var(--text);">BIR &mdash; TIN</div>
                <?php echo fi("tin_no","Tax Identification Number","text",$editEmp["tin_no"]??"",false,'placeholder="XXX-XXX-XXX-XXX" form="emp201Form"'); ?>
            </div>
        </div>

        <div class="form-section-title">Bank Account Information</div>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:20px;">Bank details for financial record-keeping and HR documentation.</p>
        <div class="grid-2" style="margin-bottom:24px;">
            <?php echo fs("bank_name","Bank Name",["BDO"=>"BDO Unibank","BPI"=>"Bank of the Philippine Islands (BPI)","Metrobank"=>"Metrobank","UnionBank"=>"UnionBank","Landbank"=>"Land Bank of the Philippines","PNB"=>"Philippine National Bank","Security Bank"=>"Security Bank","RCBC"=>"RCBC","Eastwest"=>"EastWest Bank","GCash"=>"GCash (Maya/PayMaya)","Other"=>"Other"],$editEmp["bank_name"]??"",false,'form="emp201Form"'); ?>
            <?php echo fi("bank_account_number","Account Number","text",$editEmp["bank_account_number"]??"",false,'placeholder="Account number" form="emp201Form"'); ?>
        </div>

        <div class="form-section-title">201 Files &amp; Pre-employment Requirements</div>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:20px;">This is where the requirements that you need to submit are placed (e.g. Resume, NBI Clearance).</p>

        <?php if (!empty($empChecklist)): ?>
        <div class="hrms-card" style="margin-bottom:24px;">
            <div class="card-body" style="padding:0;">
            <?php foreach($empChecklist as $cl):
                $clColor=['Pending'=>'#fb8c00','Submitted'=>'#4da6ff','Approved'=>'#66bb6a','Waived'=>'var(--text-muted)'][$cl['status']]??'var(--text-muted)';
            ?>
            <div style="display:flex;align-items:center;gap:14px;padding:13px 20px;border-bottom:1px solid var(--border);">
                <div style="width:10px;height:10px;border-radius:50%;background:<?php echo $clColor; ?>;flex-shrink:0;"></div>
                <div style="flex:1;">
                    <div style="font-size:13px;font-weight:600;"><?php echo e($cl['document_type']); ?></div>
                    <?php if($cl['file_name']): ?><div style="font-size:11px;color:var(--text-muted);margin-top:2px;">&#128206; <?php echo e($cl['file_name']); ?></div><?php endif; ?>
                </div>
                <span style="font-size:11px;padding:3px 10px;border-radius:12px;background:<?php echo $clColor; ?>22;color:<?php echo $clColor; ?>;font-weight:600;"><?php echo $cl['status']; ?></span>
                
                <?php if($cl['file_name']): ?>
                    <a href="<?php echo BASE_URL.'/'.$cl['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline" style="padding:3px 8px;font-size:11px;">👁 View</a>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data" style="margin:0;" onchange="this.submit()">
                        <input type="hidden" name="_action" value="upload_doc">
                        <input type="hidden" name="employee_id" value="<?php echo $editEmp['id']; ?>">
                        <input type="hidden" name="document_type" value="<?php echo e($cl['document_type']); ?>">
                        <input type="hidden" name="checklist_id" value="<?php echo $cl['id']; ?>">
                        <label class="btn btn-sm btn-outline" style="padding:3px 8px;font-size:11px;cursor:pointer;margin:0;">
                            Upload <input type="file" name="doc_file" style="display:none;" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        </label>
                    </form>
                <?php endif; ?>

                <?php if($isAdmin||$isManager): ?>
                    <form method="POST" style="display:flex;gap:4px;margin:0;">
                        <input type="hidden" name="_action" value="update_checklist">
                        <input type="hidden" name="checklist_id" value="<?php echo $cl['id']; ?>">
                        <input type="hidden" name="emp_id" value="<?php echo $editEmp['id']; ?>">
                        <?php if($cl['status']==='Submitted'): ?>
                            <button type="submit" name="cl_status" value="Approved" class="btn btn-sm" style="background:#43a047;color:#fff;padding:3px 8px;font-size:11px;">Approve</button>
                        <?php endif; ?>
                        <?php if($cl['status']==='Pending'): ?>
                            <button type="submit" name="cl_status" value="Waived" class="btn btn-sm btn-outline" style="padding:3px 8px;font-size:11px;">Waive</button>
                        <?php endif; ?>
                        <?php if($cl['status']!=='Pending'): ?>
                            <button type="submit" name="cl_status" value="Pending" class="btn btn-sm btn-outline" style="padding:3px 8px;font-size:11px;color:#fb8c00;">Reset</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Extra Uploads -->
        <div class="form-section-title">Upload Additional Documents</div>
        <div style="background:rgba(255,255,255,0.03);border:1px dashed var(--border);border-radius:12px;padding:20px;margin-bottom:24px;">
            <form method="POST" enctype="multipart/form-data" id="docUploadForm">
                <input type="hidden" name="_action" value="upload_doc">
                <input type="hidden" name="employee_id" value="<?php echo $editEmp['id']; ?>">
                <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:flex-end;">
                    <div>
                        <label class="form-label">Custom Document Name</label>
                        <input type="text" name="document_type" class="form-control" placeholder="e.g. Certifications, Valid ID" required>
                    </div>
                    <div>
                        <label class="form-label">File</label>
                        <input type="file" name="doc_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-accent" style="padding:10px 20px;">Upload</button>
                </div>
            </form>
        </div>

        <?php
        // Filter out docs that are already in the checklist to avoid duplicating them in the "Other Docs" list
        $checklistTypes = array_column($empChecklist, 'document_type');
        $otherDocs = array_filter($empDocs, function($d) use ($checklistTypes) {
            return !in_array($d['document_type'], $checklistTypes);
        });
        ?>
        <?php if (!empty($otherDocs)): ?>
        <div class="form-section-title">Other Uploaded Documents</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));gap:16px;">
        <?php foreach ($otherDocs as $doc): ?>
            <div class="hrms-card" style="padding:16px;background:rgba(255,255,255,0.02);border:1px solid var(--border);display:flex;align-items:center;gap:14px;">
                <div style="font-size:28px;color:var(--text-muted);">&#128206;</div>
                <div style="flex:1;min-width:0;">
                    <h4 style="margin:0 0 2px;font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($doc['document_type']); ?></h4>
                    <div style="font-size:11px;color:var(--text-muted);"><?php echo e($doc['file_name']); ?></div>
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <a href="<?php echo BASE_URL.'/'.$doc['file_path']; ?>" target="_blank" class="btn btn-sm btn-accent" style="padding:4px 12px;font-size:11px;">View</a>
                    <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this document?')">
                        <input type="hidden" name="_action" value="delete_doc">
                        <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                        <input type="hidden" name="employee_id" value="<?php echo $doc['employee_id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline" style="padding:4px 12px;font-size:11px;color:#ef5350;">Del</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- FORM ACTIONS -->
    <div style="margin-top:28px;padding-top:20px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div style="font-size:12px;color:var(--text-muted);">
            <?php if(isset($editEmp["updated_at"])): ?>
            Last updated: <?php echo date("M j, Y H:i",$editEmp["updated_at"]); ?>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:12px;">
            <a href="<?php echo url("employees"); ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" form="emp201Form" class="btn btn-accent" style="padding:10px 28px;font-size:14px;font-weight:600;">
                &#10003; Save Changes
            </button>
        </div>
    </div>

    </div><!-- /padded inner -->
</div><!-- /right card -->
</div><!-- /grid -->



<?php elseif ($action === "view" && $editEmp): ?>
<!--  PROFILE VIEW (201 read-only)  -->
<div style="margin-bottom:20px;display:flex;align-items:center;gap:14px;">
    <a href="<?php echo url("employees"); ?>" class="btn btn-outline btn-sm">&#8592; Back</a>
    <h1 style="font-size:22px;font-weight:700;margin:0;"><?php echo e($editEmp["first_name"]." ".$editEmp["last_name"]); ?></h1>
    <span class="badge <?php echo in_array($editEmp["status"],["Regular","Probationary"])?"badge-success":"badge-danger"; ?>" style="font-size:12px;"><?php echo e($editEmp["status"]??"-"); ?></span>
    <?php if($isAdmin||$isManager): ?><a href="<?php echo url("employees",["action"=>"edit","id"=>$editEmp["id"]]); ?>" class="btn btn-accent btn-sm" style="margin-left:auto;">Edit 201 File</a><?php endif; ?>
</div>
<div style="display:grid;grid-template-columns:260px 1fr;gap:20px;align-items:flex-start;">
    <div>
        <div class="hrms-card" style="padding:24px;text-align:center;">
            <div style="width:90px;height:90px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;color:#fff;margin:0 auto 14px;"><?php echo strtoupper(substr($editEmp["first_name"]??"U",0,1).substr($editEmp["last_name"]??"",0,1)); ?></div>
            <h3 style="margin:0 0 4px;font-size:16px;"><?php echo e($editEmp["first_name"]." ".($editEmp["middle_name"]?$editEmp["middle_name"]." ":"").$editEmp["last_name"]." ".($editEmp["suffix"]??"")); ?></h3>
            <p style="color:var(--accent);font-size:13px;font-weight:600;margin:0 0 4px;"><?php echo e($editEmp["position_title"] ?? $editEmp["job_title"] ?? ""); ?></p>
            <p style="color:var(--text-muted);font-size:12px;margin:0 0 12px;"><?php echo e($editEmp["dept_name"]??""); ?></p>
            <p style="font-size:12px;color:var(--text-muted);margin:0;">&#128203; <?php echo e($editEmp["employee_no"]??""); ?></p>
        </div>
        <div class="hrms-card" style="padding:16px;margin-top:14px;">
            <?php $qs=[["Hire Date",$editEmp["hire_date"]?date("M j, Y",strtotime($editEmp["hire_date"])):""],["Employment",$editEmp["employment_type"]??""],["Email",$editEmp["email"]??""],["Phone",$editEmp["phone"]??""]]; foreach($qs as $q): ?>
            <div style="margin-bottom:12px;"><p style="font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin:0 0 2px;"><?php echo $q[0]; ?></p><p style="font-size:13px;font-weight:600;margin:0;word-break:break-all;"><?php echo e($q[1]); ?></p></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div>
        <?php
        $sections = [
            "Personal Information" => [
                ["Gender",$editEmp["gender"]??""],["Date of Birth",$editEmp["date_of_birth"]?date("M j, Y",strtotime($editEmp["date_of_birth"])):""],["Place of Birth",$editEmp["place_of_birth"]??""],["Civil Status",$editEmp["civil_status"]??""],["Nationality",$editEmp["nationality"]??""],["Blood Type",$editEmp["blood_type"]??""],
                ["Address",$editEmp["address"]??""],
            ],
            "Employment" => [
                ["Department",$editEmp["dept_name"]??""],["Position",$editEmp["position_title"] ?? $editEmp["job_title"] ?? ""],["Employment Type",$editEmp["employment_type"]??""],["Highest Education",$editEmp["highest_education"]??""],["Basic Salary","&#8369;".number_format($editEmp["basic_salary"]??0,2)],
            ],
            "Government IDs" => [
                ["SSS No.",$editEmp["sss_no"]??""],["PhilHealth No.",$editEmp["philhealth_no"]??""],["Pag-IBIG No.",$editEmp["pagibig_no"]??""],["TIN",$editEmp["tin_no"]??""],
            ],
            "Banking" => [
                ["Bank",$editEmp["bank_name"]??""],["Account No.",$editEmp["bank_account_number"]??""],
            ],
            "Emergency Contact" => [
                ["Name",$editEmp["emergency_contact_name"]??""],["Relationship",$editEmp["emergency_contact_relationship"]??""],["Phone",$editEmp["emergency_contact_phone"]??""],
            ],
        ];
        foreach($sections as $secTitle=>$fields): ?>
        <div class="hrms-card" style="margin-bottom:16px;">
            <div class="card-header" style="padding:14px 20px;"><h3 style="font-size:13px;font-weight:700;"><?php echo $secTitle; ?></h3></div>
            <div class="card-body"><div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <?php foreach($fields as $f): ?>
            <div><p style="font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin:0 0 3px;"><?php echo $f[0]; ?></p><p style="font-size:13px;font-weight:600;margin:0;"><?php echo $f[1]; ?></p></div>
            <?php endforeach; ?>
            </div></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php else: ?>
<!--  EMPLOYEE DIRECTORY LIST  -->
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <h1 style="font-size:24px;font-weight:700;margin:0;">Employee Directory</h1>
    <?php if($isAdmin||$isManager): ?><a href="<?php echo url("employees",["action"=>"create"]); ?>" class="btn btn-accent">+ Add Employee</a><?php endif; ?>
</div>
<div class="hrms-card" style="margin-bottom:16px;padding:16px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="page" value="employees">
        <div style="flex:1;min-width:200px;"><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Search</label><input type="text" name="q" value="<?php echo e($search); ?>" placeholder="Name, email, employee no..." class="form-control" style="padding:9px 12px;"></div>
        <?php if($isAdmin): ?>
        <div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Department</label><select name="dept" class="form-control" style="padding:9px 12px;width:auto;"><option value="">All Departments</option><?php foreach($depts as $d): ?><option value="<?php echo $d["id"]; ?>" <?php echo $deptFilter==$d["id"]?"selected":""; ?>><?php echo e($d["name"]); ?></option><?php endforeach; ?></select></div>
        <?php endif; ?>
        <div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Status</label><select name="status" class="form-control" style="padding:9px 12px;width:auto;"><option value="">All</option><?php foreach(["Probationary","Regular","On Leave","Resigned","Terminated"] as $st): ?><option value="<?php echo $st; ?>" <?php echo $statusFilter===$st?"selected":""; ?>><?php echo $st; ?></option><?php endforeach; ?></select></div>
        <button type="submit" class="btn btn-accent">Filter</button>
        <a href="<?php echo url("employees"); ?>" class="btn btn-outline">Reset</a>
    </form>
</div>
<div class="hrms-card"><div class="card-body" style="padding:0;"><table class="table hrms-table" style="margin:0;">
<thead><tr><th>Employee</th><th>Position / Department</th><th>Status</th><th>Hire Date</th><th style="text-align:right;">Actions</th></tr></thead>
<tbody>
<?php foreach($employees as $e2): ?>
<tr>
    <td><div style="display:flex;align-items:center;gap:10px;">
        <div style="width:38px;height:38px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0;"><?php echo strtoupper(substr($e2["first_name"],0,1).substr($e2["last_name"],0,1)); ?></div>
        <div><div style="font-weight:600;font-size:13px;"><?php echo e($e2["first_name"]." ".$e2["last_name"]); ?></div>
        <div style="font-size:11px;color:var(--text-muted);"><?php echo e($e2["employee_no"]??""); ?> &bull; <?php echo e($e2["email"]??"",$false??false); ?></div></div>
    </div></td>
    <td><div style="font-size:13px;"><?php echo e($e2["position_title"] ?? $e2["job_title"] ?? ""); ?></div><div style="font-size:11px;color:var(--text-muted);"><?php echo e($e2["dept_name"] ?? ""); ?></div></td>
    <td><span class="badge <?php echo in_array($e2["status"],["Regular","Probationary"])?"badge-success":($e2["status"]==="On Leave"?"badge-warning":"badge-danger"); ?>"><?php echo e($e2["status"]); ?></span></td>
    <td style="color:var(--text-muted);font-size:13px;"><?php echo $e2["hire_date"]?date("M j, Y",strtotime($e2["hire_date"])):""; ?></td>
    <td style="text-align:right;display:flex;gap:6px;justify-content:flex-end;padding:10px 16px;">
        <a href="<?php echo url("employees",["action"=>"view","id"=>$e2["id"]]); ?>" class="btn btn-sm btn-outline" style="padding:4px 12px;">View</a>
        <?php if($isAdmin||$isManager): ?>
        <a href="<?php echo url("employees",["action"=>"edit","id"=>$e2["id"]]); ?>" class="btn btn-sm btn-accent" style="padding:4px 12px;">Edit 201</a>
        <a href="<?php echo url("employees",["action"=>"delete","id"=>$e2["id"]]); ?>" class="btn btn-sm btn-outline text-danger" style="padding:4px 12px;" onclick="return confirm('Delete this employee and all their records?')">Del</a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if(empty($employees)): ?><tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted);">No employees found.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php endif; ?>

<script>
function switchTab(name, el) {
    document.querySelectorAll(".emp-tab").forEach(t => t.classList.remove("active"));
    document.querySelectorAll(".emp-panel").forEach(p => p.classList.remove("active"));
    var panel = document.getElementById("tab-"+name);
    if (panel) panel.classList.add("active");
    if (el) el.classList.add("active");
    // Track active tab so redirect preserves it
    var inp = document.getElementById('activeTabInput');
    if (inp) inp.value = name;
}
function showFileName(input) {
    var p = document.getElementById('selectedFileName');
    if (input.files && input.files[0]) {
        p.style.display = 'block';
        p.querySelector('span').textContent = input.files[0].name + ' (' + (input.files[0].size/1024).toFixed(1) + ' KB)';
    } else { p.style.display = 'none'; }
}
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var ph = document.querySelector(".emp-profile-photo");
            ph.style.background = "url("+e.target.result+") center/cover";
            ph.textContent = "";
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function filterPositions(deptId, posId) {
    var dept = document.getElementById(deptId);
    var pos = document.getElementById(posId);
    if(!dept || !pos) return;
    
    var selectedDept = dept.value;
    var options = pos.options;
    var hasValidOptions = false;
    
    var currentVal = pos.value;
    var isCurrentValValid = false;

    for (var i = 1; i < options.length; i++) {
        var opt = options[i];
        if (opt.getAttribute("data-dept") === selectedDept) {
            opt.style.display = "";
            hasValidOptions = true;
            if (opt.value === currentVal) isCurrentValValid = true;
        } else {
            opt.style.display = "none";
        }
    }
    
    if (!isCurrentValValid) {
        pos.value = "";
    }
    
    options[0].text = selectedDept ? (hasValidOptions ? "\u2014 Select Position \u2014" : "\u2014 No positions in this department \u2014") : "\u2014 Select Department First \u2014";
}

function syncJobTitle(selectEl, targetId) {
    var opt = selectEl.options[selectEl.selectedIndex];
    var field = document.getElementById(targetId);
    if (field) field.value = opt.value ? opt.text.trim() : '';
}

document.addEventListener('DOMContentLoaded', function() {
    if(document.getElementById('deptSelectEdit')) filterPositions('deptSelectEdit', 'posSelectEdit');
    if(document.getElementById('deptSelectCreate')) filterPositions('deptSelectCreate', 'posSelectCreate');
});
</script>

<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>




