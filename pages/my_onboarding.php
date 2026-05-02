<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
Auth::check();
$pageTitle = "My Onboarding — " . APP_NAME;
$empId  = Auth::empId();
$userId = Auth::id();

// Ensure checklist table exists
try { DB::execute("CREATE TABLE IF NOT EXISTS `employee_doc_checklist` (`id` INT AUTO_INCREMENT PRIMARY KEY,`employee_id` INT NOT NULL,`document_type` VARCHAR(100) NOT NULL,`status` ENUM('Pending','Submitted','Approved','Waived') DEFAULT 'Pending',`document_id` INT NULL,`notes` VARCHAR(255) NULL,`created_at` INT NOT NULL DEFAULT 0,`updated_at` INT NOT NULL DEFAULT 0,FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", []); } catch (\Exception $e) {}

// Auto-seed checklist if this employee doesn't have one yet (handles existing & new employees)
if ($empId) {
    $existing = (int)DB::fetchScalar("SELECT COUNT(*) FROM employee_doc_checklist WHERE employee_id=?", [$empId]);
    if ($existing === 0) {
        $docs = [
            'Employment Contract',
            'SSS E1 Form / SS Card',
            'PhilHealth MDR (Member Data Record)',
            'Pag-IBIG MDF (Membership Data Form)',
            'BIR Form 2316 / TIN Card',
            'NBI Clearance',
            'Birth Certificate (PSA-authenticated)',
            'Medical Certificate',
            'Diploma / Transcript of Records',
            '2×2 ID Photo (white background)',
        ];
        foreach ($docs as $doc) {
            DB::insert("INSERT INTO employee_doc_checklist (employee_id,document_type,status,created_at,updated_at) VALUES (?,?,'Pending',?,?)", [$empId, $doc, time(), time()]);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $a = $_POST["_action"] ?? "";

    // Save Personal Data Sheet
    if ($a === "save_pds" && $empId) {
        DB::execute(
            "UPDATE employee SET middle_name=?,suffix=?,gender=?,date_of_birth=?,place_of_birth=?,civil_status=?,nationality=?,blood_type=?,highest_education=?,address=?,phone=?,sss_no=?,philhealth_no=?,pagibig_no=?,tin_no=?,emergency_contact_name=?,emergency_contact_relationship=?,emergency_contact_phone=?,updated_at=? WHERE id=?",
            [$_POST["middle_name"]??null,$_POST["suffix"]??null,$_POST["gender"]??null,$_POST["date_of_birth"]??null,$_POST["place_of_birth"]??null,$_POST["civil_status"]??null,$_POST["nationality"]??"Filipino",$_POST["blood_type"]??null,$_POST["highest_education"]??null,$_POST["address"]??null,$_POST["phone"]??null,$_POST["sss_no"]??null,$_POST["philhealth_no"]??null,$_POST["pagibig_no"]??null,$_POST["tin_no"]??null,$_POST["emergency_contact_name"]??null,$_POST["emergency_contact_relationship"]??null,$_POST["emergency_contact_phone"]??null,time(),$empId]
        );
        Auth::flash("success", "Personal Data Sheet saved!");
        header("Location: " . url("my_onboarding") . "#pds"); exit;
    }

    // Upload document for checklist item
    if ($a === "submit_checklist_doc" && $empId) {
        $clId = (int)$_POST["checklist_id"];
        if (!empty($_FILES["doc_file"]["name"]) && $_FILES["doc_file"]["error"] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . "/../uploads/employee_docs/$empId/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $origName   = basename($_FILES["doc_file"]["name"]);
            $safeName   = preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
            $uniqueName = time() . "_" . $safeName;
            if (move_uploaded_file($_FILES["doc_file"]["tmp_name"], $uploadDir . $uniqueName)) {
                $cl = DB::fetchOne("SELECT * FROM employee_doc_checklist WHERE id=? AND employee_id=?", [$clId, $empId]);
                if ($cl) {
                    $docId = DB::insert("INSERT INTO employee_document (employee_id,document_type,file_name,file_path,uploaded_at) VALUES (?,?,?,?,?)",
                        [$empId, $cl["document_type"], $origName, "uploads/employee_docs/$empId/$uniqueName", time()]);
                    DB::execute("UPDATE employee_doc_checklist SET status='Submitted',document_id=?,updated_at=? WHERE id=?", [$docId, time(), $clId]);
                    Auth::flash("success", "Submitted: " . e($origName));
                }
            } else { Auth::flash("error", "Upload failed — check folder permissions."); }
        } else { Auth::flash("error", "No file selected."); }
        header("Location: " . url("my_onboarding") . "#docs"); exit;
    }
}

// Fetch employee data
$emp       = $empId ? DB::fetchOne("SELECT e.*,d.name as dept_name FROM employee e LEFT JOIN department d ON e.department_id=d.id WHERE e.id=?", [$empId]) : null;
$checklist = $empId ? DB::fetchAll("SELECT cl.*, ed.file_name, ed.file_path FROM employee_doc_checklist cl LEFT JOIN employee_document ed ON cl.document_id=ed.id WHERE cl.employee_id=? ORDER BY FIELD(cl.status,'Pending','Submitted','Waived','Approved'), cl.document_type", [$empId]) : [];

// PDS completion score
$pdsFields = ['middle_name','gender','date_of_birth','place_of_birth','civil_status','nationality','blood_type','highest_education','address','phone','sss_no','philhealth_no','pagibig_no','tin_no','emergency_contact_name','emergency_contact_relationship','emergency_contact_phone'];
$pdsFilled = $emp ? count(array_filter($pdsFields, fn($f) => !empty($emp[$f]))) : 0;
$pdsPct    = count($pdsFields) > 0 ? round($pdsFilled / count($pdsFields) * 100) : 0;

// Doc stats
$totalDocs     = count($checklist);
$submittedDocs = count(array_filter($checklist, fn($c) => in_array($c["status"], ["Submitted","Approved"])));
$pendingDocs   = count(array_filter($checklist, fn($c) => $c["status"] === "Pending"));
$docPct        = $totalDocs > 0 ? round($submittedDocs / $totalDocs * 100) : 0;

require_once __DIR__ . "/../includes/layout_header.php";
?>
<div style="margin-bottom:24px;">
    <h1 style="font-size:26px;font-weight:700;margin:0;">My Onboarding</h1>
    <p style="color:var(--text-muted);margin:4px 0 0;">Complete your Personal Data Sheet and submit all required documents below.</p>
</div>

<!-- Progress Cards -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:28px;">
    <div class="hrms-card" style="padding:20px;border-left:4px solid var(--accent);">
        <p style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin:0 0 6px;">Personal Data Sheet</p>
        <p style="font-size:28px;font-weight:700;margin:0;"><?php echo $pdsPct; ?>%</p>
        <div style="height:6px;background:rgba(255,255,255,0.08);border-radius:3px;margin-top:8px;overflow:hidden;">
            <div style="height:100%;width:<?php echo $pdsPct; ?>%;background:var(--accent);border-radius:3px;"></div>
        </div>
        <p style="font-size:12px;color:var(--text-muted);margin:6px 0 0;"><?php echo $pdsFilled; ?>/<?php echo count($pdsFields); ?> fields completed</p>
    </div>
    <div class="hrms-card" style="padding:20px;border-left:4px solid <?php echo $pendingDocs>0?'#fb8c00':'#66bb6a'; ?>;">
        <p style="font-size:11px;color:var(--text-muted);text-transform:uppercase;margin:0 0 6px;">Document Submission</p>
        <p style="font-size:28px;font-weight:700;margin:0;"><?php echo $docPct; ?>%</p>
        <div style="height:6px;background:rgba(255,255,255,0.08);border-radius:3px;margin-top:8px;overflow:hidden;">
            <div style="height:100%;width:<?php echo $docPct; ?>%;background:<?php echo $pendingDocs>0?'#fb8c00':'#66bb6a'; ?>;border-radius:3px;"></div>
        </div>
        <p style="font-size:12px;color:var(--text-muted);margin:6px 0 0;"><?php echo $submittedDocs; ?>/<?php echo $totalDocs; ?> documents submitted <?php if($pendingDocs===0&&$totalDocs>0): ?>✓<?php endif; ?></p>
    </div>
</div>

<!-- ── SECTION 1: Personal Data Sheet ── -->
<div class="hrms-card" style="margin-bottom:28px;" id="pds">
    <div class="card-header"><h3>&#128203; Personal Data Sheet</h3></div>
    <div class="card-body">
    <form method="POST">
    <input type="hidden" name="_action" value="save_pds">
    <?php $e2 = fn($f) => htmlspecialchars((string)($emp[$f]??''), ENT_QUOTES, 'UTF-8'); ?>

    <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:14px;letter-spacing:.5px;">I. Personal Information</div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px;">
        <?php foreach([['Middle Name','middle_name','text'],['Suffix','suffix','text'],['Gender','gender','select'],['Date of Birth','date_of_birth','date'],['Place of Birth','place_of_birth','text'],['Civil Status','civil_status','select'],['Nationality','nationality','text'],['Blood Type','blood_type','select'],['Highest Education','highest_education','select']] as [$lbl,$fld,$typ]): ?>
        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;"><?php echo $lbl; ?></label>
            <?php if($typ==='select'&&$fld==='gender'): ?>
            <select name="gender" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                <?php foreach(['Male','Female','Non-binary','Prefer not to say'] as $opt): ?><option value="<?php echo $opt; ?>" <?php echo ($emp['gender']??'')===$opt?'selected':''; ?>><?php echo $opt; ?></option><?php endforeach; ?>
            </select>
            <?php elseif($typ==='select'&&$fld==='civil_status'): ?>
            <select name="civil_status" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                <?php foreach(['Single','Married','Widowed','Separated','Divorced'] as $opt): ?><option value="<?php echo $opt; ?>" <?php echo ($emp['civil_status']??'')===$opt?'selected':''; ?>><?php echo $opt; ?></option><?php endforeach; ?>
            </select>
            <?php elseif($typ==='select'&&$fld==='blood_type'): ?>
            <select name="blood_type" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                <option value="">— Select —</option>
                <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $opt): ?><option value="<?php echo $opt; ?>" <?php echo ($emp['blood_type']??'')===$opt?'selected':''; ?>><?php echo $opt; ?></option><?php endforeach; ?>
            </select>
            <?php elseif($typ==='select'&&$fld==='highest_education'): ?>
            <select name="highest_education" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
                <option value="">— Select —</option>
                <?php foreach(['High School','Vocational / Technical','Associate Degree','Bachelor\'s Degree','Master\'s Degree','Doctoral Degree'] as $opt): ?><option value="<?php echo $opt; ?>" <?php echo ($emp['highest_education']??'')===$opt?'selected':''; ?>><?php echo $opt; ?></option><?php endforeach; ?>
            </select>
            <?php else: ?>
            <input type="<?php echo $typ; ?>" name="<?php echo $fld; ?>" value="<?php echo $e2($fld); ?>" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:14px;letter-spacing:.5px;">II. Contact & Address</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;">Phone Number</label>
            <input type="text" name="phone" value="<?php echo $e2('phone'); ?>" placeholder="09XXXXXXXXX" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
        </div>
        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;">Complete Address</label>
            <input type="text" name="address" value="<?php echo $e2('address'); ?>" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
        </div>
    </div>

    <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:14px;letter-spacing:.5px;">III. Government ID Numbers</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
        <?php foreach([['SSS No.','sss_no'],['PhilHealth No.','philhealth_no'],['Pag-IBIG No.','pagibig_no'],['TIN','tin_no']] as [$lbl,$fld]): ?>
        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;"><?php echo $lbl; ?></label>
            <input type="text" name="<?php echo $fld; ?>" value="<?php echo $e2($fld); ?>" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
        </div>
        <?php endforeach; ?>
    </div>

    <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:14px;letter-spacing:.5px;">IV. Emergency Contact</div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px;">
        <?php foreach([['Full Name','emergency_contact_name','text'],['Relationship','emergency_contact_relationship','text'],['Contact Number','emergency_contact_phone','text']] as [$lbl,$fld,$typ]): ?>
        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:4px;"><?php echo $lbl; ?></label>
            <input type="<?php echo $typ; ?>" name="<?php echo $fld; ?>" value="<?php echo $e2($fld); ?>" style="width:100%;padding:9px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;">
        </div>
        <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-accent" style="padding:10px 28px;">Save Personal Data Sheet</button>
    </form>
    </div>
</div>

<!-- ── SECTION 2: Document Checklist ── -->
<div class="hrms-card" id="docs">
    <div class="card-header">
        <h3>&#128228; Required Document Submissions</h3>
        <?php if($pendingDocs>0): ?><span style="font-size:12px;color:#fb8c00;font-weight:600;"><?php echo $pendingDocs; ?> pending</span><?php else: ?><span class="badge badge-success">All Submitted ✓</span><?php endif; ?>
    </div>
    <?php if (empty($checklist)): ?>
    <div class="card-body" style="text-align:center;padding:40px;color:var(--text-muted);">No documents assigned. Please contact HR.</div>
    <?php else: ?>
    <div class="card-body" style="padding:0;">
    <?php foreach ($checklist as $cl):
        $clColor = ["Pending"=>"#fb8c00","Submitted"=>"#4da6ff","Approved"=>"#66bb6a","Waived"=>"var(--text-muted)"][$cl["status"]] ?? "#fff";
    ?>
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:10px;height:10px;border-radius:50%;background:<?php echo $clColor; ?>;flex-shrink:0;"></div>
            <div style="flex:1;">
                <div style="font-size:13px;font-weight:600;"><?php echo e($cl["document_type"]); ?></div>
                <?php if($cl["file_name"]): ?><div style="font-size:11px;color:var(--text-muted);margin-top:2px;">&#128206; <?php echo e($cl["file_name"]); ?></div><?php endif; ?>
            </div>
            <span style="font-size:11px;padding:3px 10px;border-radius:12px;background:<?php echo $clColor; ?>22;color:<?php echo $clColor; ?>;font-weight:600;"><?php echo $cl["status"]; ?></span>
            <?php if($cl["file_name"]): ?>
            <a href="<?php echo BASE_URL.'/'.$cl['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline" style="padding:4px 10px;font-size:11px;">View</a>
            <?php endif; ?>
            <?php if(in_array($cl["status"],["Pending","Submitted"])): ?>
            <button type="button" onclick="toggleUpload(<?php echo $cl['id']; ?>)" class="btn btn-sm btn-accent" style="padding:4px 10px;font-size:11px;">
                <?php echo $cl["status"]==="Submitted"?"Re-upload":"&#128228; Upload"; ?>
            </button>
            <?php endif; ?>
        </div>
        <!-- Inline upload panel -->
        <?php if(in_array($cl["status"],["Pending","Submitted"])): ?>
        <div id="upload-<?php echo $cl['id']; ?>" style="display:none;margin-top:12px;padding:14px;background:rgba(124,147,104,0.06);border-radius:8px;border:1px solid var(--border);">
            <form method="POST" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <input type="hidden" name="_action" value="submit_checklist_doc">
                <input type="hidden" name="checklist_id" value="<?php echo $cl['id']; ?>">
                <input type="file" name="doc_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required
                    style="padding:6px 10px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:12px;flex:1;min-width:200px;">
                <button type="submit" class="btn btn-accent" style="padding:7px 16px;font-size:12px;">Submit</button>
                <button type="button" onclick="toggleUpload(<?php echo $cl['id']; ?>)" class="btn btn-outline" style="padding:7px 12px;font-size:12px;">Cancel</button>
            </form>
            <p style="font-size:11px;color:var(--text-muted);margin:6px 0 0;">Accepted: PDF, DOC, DOCX, JPG, PNG · Max 10MB</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleUpload(id) {
    var el = document.getElementById('upload-' + id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
<?php require_once __DIR__ . "/../includes/layout_footer.php"; ?>
