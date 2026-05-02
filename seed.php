<?php
/**
 * HRMSV3 Database Seeder — CURA Corporation (2-week-old IT company)
 * Corrected to match actual DB schema column names.
 * Run: http://localhost/HRMSV3/seed.php?force=1
 */
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/includes/db.php";

$alreadySeeded = DB::fetchScalar("SELECT COUNT(*) FROM `user` WHERE role='Super Admin'");
if ($alreadySeeded > 0 && !isset($_GET["force"])) {
    echo "<div style='font-family:sans-serif;padding:40px;max-width:600px;margin:0 auto;'>
    <h2 style='color:#43a047;'>&#9989; Already Seeded</h2>
    <p>Append <code>?force=1</code> to re-seed.</p>
    <p><strong>admin@cura.ph</strong> / Password123 (Super Admin)<br>
    <strong>hr@cura.ph</strong> / Password123 (Manager)<br>
    <strong>juan.delacruz@cura.ph</strong> / Password123 (Employee)</p>
    <a href='/HRMSV3/index.php?page=login'>Go to Login &rarr;</a></div>";
    exit;
}

if (isset($_GET["force"])) {
    $tables = ["system_notification","audit_log","payslip","payroll","lms_enrollment","lms_module","onboarding_task","project_task","project_member","project","candidate","job_posting","employee_benefit","benefit","feedback","personnel_action","leave_request","attendance","shift","announcement","holiday","system_setting","position","employee","user","department"];
    foreach ($tables as $t) { try { DB::execute("DELETE FROM `$t`"); DB::execute("ALTER TABLE `$t` AUTO_INCREMENT=1"); } catch(Exception $e){} }
}

function pw($p){return md5($p);}
$now = time(); $nowDt = date("Y-m-d H:i:s");

echo "<div style='font-family:sans-serif;padding:40px;max-width:700px;margin:0 auto;'><h2>&#128161; Seeding HRMSV3 — CURA Corporation...</h2><ul style='line-height:2;'>";

// 1. SYSTEM SETTINGS
$settings = ["company_name"=>"CURA Corporation","company_email"=>"hr@cura.ph","leave_vacation"=>"15","leave_sick"=>"10","leave_personal"=>"3","leave_emergency"=>"3","payroll_cutoff"=>"15","work_hours_per_day"=>"8"];
foreach ($settings as $k=>$v) DB::execute("INSERT INTO system_setting (`key`,`value`,updated_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE `value`=?,updated_at=?",[$k,$v,$now,$v,$now]);
echo "<li>&#10003; System settings</li>";

// 2. DEPARTMENTS
$deptIds = [];
$depts = [
    ["Software Development","Builds and maintains all software products","Makati City",850000],
    ["QA & Testing","Ensures quality across all deliverables","Makati City",450000],
    ["DevOps & Infrastructure","Manages cloud, CI/CD, and server infrastructure","Makati City",600000],
    ["UI/UX Design","Creates user-centered designs and prototypes","BGC, Taguig",380000],
    ["Project Management","Oversees project delivery and timelines","Makati City",500000],
    ["HR & Administration","Handles people operations and compliance","Makati City",300000],
];
foreach ($depts as $d) {
    $deptIds[$d[0]] = DB::insert("INSERT INTO department (name,description,location,budget,created_at) VALUES (?,?,?,?,?)",[$d[0],$d[1],$d[2],$d[3],$now]);
}
echo "<li>&#10003; Departments (6)</li>";

// 3. POSITIONS — uses salary_min / salary_max per actual schema
$posData = [
    ["Chief Executive Officer",$deptIds["HR & Administration"],120000,200000],
    ["HR Manager",$deptIds["HR & Administration"],45000,75000],
    ["Senior Software Engineer",$deptIds["Software Development"],80000,120000],
    ["Software Engineer",$deptIds["Software Development"],45000,80000],
    ["Junior Developer",$deptIds["Software Development"],25000,45000],
    ["QA Engineer",$deptIds["QA & Testing"],40000,65000],
    ["DevOps Engineer",$deptIds["DevOps & Infrastructure"],60000,95000],
    ["UI/UX Designer",$deptIds["UI/UX Design"],40000,70000],
    ["Project Manager",$deptIds["Project Management"],60000,90000],
    ["Business Analyst",$deptIds["Project Management"],45000,75000],
];
foreach ($posData as $p) {
    DB::insert("INSERT INTO position (title,department_id,salary_min,salary_max,created_at) VALUES (?,?,?,?,?)",[$p[0],$p[1],$p[2],$p[3],$now]);
}
echo "<li>&#10003; Positions (10)</li>";

// 4. USERS + EMPLOYEES — status=Regular (schema enum), tin_no (not tin)
$userIds = []; $empIds = [];
$people = [
    ["admin","admin@cura.ph","Password123","Super Admin","HR & Administration","Chief Executive Officer","Carlos","Reyes",180000,"EMP-001","09171234561","Married","1985-03-15",-14,"Male"],
    ["hr.manager","hr@cura.ph","Password123","Manager","HR & Administration","HR Manager","Maria","Santos",65000,"EMP-002","09171234562","Single","1990-06-20",-14,"Female"],
    ["juan.delacruz","juan.delacruz@cura.ph","Password123","Employee","Software Development","Senior Software Engineer","Juan","De La Cruz",95000,"EMP-003","09171234563","Single","1993-04-12",-14,"Male"],
    ["ana.garcia","ana.garcia@cura.ph","Password123","Employee","Software Development","Software Engineer","Ana","Garcia",62000,"EMP-004","09171234564","Married","1996-07-08",-14,"Female"],
    ["mark.lim","mark.lim@cura.ph","Password123","Employee","Software Development","Junior Developer","Mark","Lim",28000,"EMP-005","09171234565","Single","1999-11-25",-12,"Male"],
    ["jenny.tan","jenny.tan@cura.ph","Password123","Employee","Software Development","Junior Developer","Jenny","Tan",27500,"EMP-006","09171234566","Single","2000-02-14",-12,"Female"],
    ["ryan.co","ryan.co@cura.ph","Password123","Employee","QA & Testing","QA Engineer","Ryan","Co",48000,"EMP-007","09171234567","Single","1994-09-30",-14,"Male"],
    ["lisa.ong","lisa.ong@cura.ph","Password123","Employee","QA & Testing","QA Engineer","Lisa","Ong",46000,"EMP-008","09171234568","Single","1997-12-05",-13,"Female"],
    ["ben.wu","ben.wu@cura.ph","Password123","Employee","DevOps & Infrastructure","DevOps Engineer","Ben","Wu",80000,"EMP-009","09171234569","Married","1991-08-18",-14,"Male"],
    ["claire.sy","claire.sy@cura.ph","Password123","Employee","UI/UX Design","UI/UX Designer","Claire","Sy",52000,"EMP-010","09171234570","Single","1995-05-22",-14,"Female"],
    ["kevin.ng","kevin.ng@cura.ph","Password123","Employee","UI/UX Design","UI/UX Designer","Kevin","Ng",50000,"EMP-011","09171234571","Single","1997-01-10",-12,"Male"],
    ["diana.go","diana.go@cura.ph","Password123","Employee","Project Management","Project Manager","Diana","Go",75000,"EMP-012","09171234572","Married","1989-03-28",-14,"Female"],
    ["joel.cruz","joel.cruz@cura.ph","Password123","Employee","Project Management","Business Analyst","Joel","Cruz",55000,"EMP-013","09171234573","Single","1992-10-15",-13,"Male"],
    ["grace.lee","grace.lee@cura.ph","Password123","Employee","Software Development","Software Engineer","Grace","Lee",64000,"EMP-014","09171234574","Single","1994-06-11",-13,"Female"],
    ["mike.chan","mike.chan@cura.ph","Password123","Employee","DevOps & Infrastructure","DevOps Engineer","Mike","Chan",78000,"EMP-015","09171234575","Married","1990-12-03",-13,"Male"],
    ["kim.ramos","kim.ramos@cura.ph","Password123","Employee","HR & Administration","HR & Admin Officer","Kim","Ramos",35000,"EMP-016","09171234576","Single","1998-04-19",-11,"Female"],
    ["dante.flores","dante.flores@cura.ph","Password123","Employee","Software Development","Junior Developer","Dante","Flores",28000,"EMP-017","09171234577","Single","2001-07-07",-10,"Male"],
];
foreach ($people as $p) {
    $dId = $deptIds[$p[4]] ?? null;
    $uid = DB::insert("INSERT INTO `user` (username,email,password,role,status,department_id,created_at) VALUES (?,?,?,?,?,?,?)",[$p[0],$p[1],pw($p[2]),$p[3],"Active",$dId,$now]);
    $userIds[$p[0]] = $uid;
    $hireDate = date("Y-m-d", strtotime($p[13]." days"));
    $eid = DB::insert("INSERT INTO employee (user_id,employee_no,first_name,last_name,email,phone,department_id,job_title,status,hire_date,basic_salary,civil_status,date_of_birth,nationality,sss_no,philhealth_no,pagibig_no,tin_no,address,gender,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
        [$uid,$p[9],$p[6],$p[7],$p[1],$p[10],$dId,$p[5],"Regular",$hireDate,$p[8],$p[11],$p[12],"Filipino","03-".$p[9]."-1","12-".$p[9]."-2","9876-".$p[9]."-3","111-".$p[9]."-4","Metro Manila, Philippines",$p[14],$now,$now]);
    $empIds[$p[0]] = $eid;
}
echo "<li>&#10003; Users + Employees (17)</li>";

// 5. ATTENDANCE
$workdays = [];
for ($d=0;$d<=13;$d++){$ts=strtotime("-$d days");if(date("N",$ts)<=5)$workdays[]=date("Y-m-d",$ts);}
foreach ($empIds as $uname=>$eid){
    foreach ($workdays as $wd){
        $skip=($uname==="mark.lim"&&$wd===date("Y-m-d",strtotime("-3 days")));
        $skip2=($uname==="lisa.ong"&&$wd===date("Y-m-d",strtotime("-1 days")));
        if($skip||$skip2)continue;
        $isLate=(rand(0,10)===0);
        $ci=date("H:i:s",mktime(8,$isLate?rand(15,45):rand(0,10),0));
        $co=date("H:i:s",mktime(17,rand(0,30),0));
        DB::execute("INSERT IGNORE INTO attendance (employee_id,date,clock_in,clock_out,status,total_hours,created_at) VALUES (?,?,?,?,?,?,?)",[$eid,$wd,$ci,$co,$isLate?"Late":"On Time",9.0,$now]);
    }
}
echo "<li>&#10003; Attendance (2-week history)</li>";

// 6. SHIFTS
$shiftTypes=["Morning Shift","Regular Shift","Afternoon Shift"];
foreach($empIds as $uname=>$eid){
    for($d=0;$d<=6;$d++){$ts=strtotime("+$d days");if(date("N",$ts)>5)continue;$sdate=date("Y-m-d",$ts);
        $idx=array_search($uname,array_keys($empIds))%3;
        $starts=["08:00:00","08:00:00","13:00:00"][$idx];$ends=["17:00:00","17:00:00","22:00:00"][$idx];
        $type=(strpos($uname,"wu")!==false||strpos($uname,"chan")!==false)?"Remote":"Onsite";
        DB::execute("INSERT IGNORE INTO shift (employee_id,date,start_time,end_time,shift_name,type,status,published,created_at) VALUES (?,?,?,?,?,?,?,1,?)",[$eid,$sdate,$starts,$ends,$shiftTypes[$idx],$type,"Scheduled",$now]);
    }
}
echo "<li>&#10003; Shifts (7-day schedule)</li>";

// 7. LEAVE REQUESTS — status enum: Pending/Approved/Denied
$leaveData=[
    ["mark.lim","Sick Leave",date("Y-m-d",strtotime("-3 days")),date("Y-m-d",strtotime("-3 days")),1,"Fever and flu","Approved"],
    ["lisa.ong","Personal Leave",date("Y-m-d",strtotime("-1 days")),date("Y-m-d",strtotime("-1 days")),1,"Personal errand","Approved"],
    ["jenny.tan","Vacation Leave",date("Y-m-d",strtotime("+3 days")),date("Y-m-d",strtotime("+7 days")),5,"Family vacation","Pending"],
    ["ryan.co","Sick Leave",date("Y-m-d",strtotime("+1 days")),date("Y-m-d",strtotime("+1 days")),1,"Medical appointment","Pending"],
    ["claire.sy","Vacation Leave",date("Y-m-d",strtotime("-10 days")),date("Y-m-d",strtotime("-8 days")),3,"Rest","Approved"],
    ["diana.go","Emergency Leave",date("Y-m-d",strtotime("-5 days")),date("Y-m-d",strtotime("-5 days")),1,"Family emergency","Approved"],
];
$approverUid=$userIds["hr.manager"]??1;
foreach($leaveData as $l){$eid=$empIds[$l[0]]??null;if(!$eid)continue;
    DB::insert("INSERT INTO leave_request (employee_id,leave_type,start_date,end_date,days,reason,status,approved_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)",[$eid,$l[1],$l[2],$l[3],$l[4],$l[5],$l[6],$l[6]==="Approved"?$approverUid:null,$now,$now]);
}
echo "<li>&#10003; Leave requests (6)</li>";

// 8. ANNOUNCEMENTS — uses target_audience (not is_company_wide)
$annData=[
    ["Welcome to CURA Corporation!","We are thrilled to officially launch CURA Corporation! As a brand-new IT company, we are building something amazing together.",$userIds["admin"],"Urgent","All"],
    ["Company Onboarding Week","All new hires must complete their onboarding checklist by end of Week 2. Please coordinate with HR.",$userIds["hr.manager"],"High","All"],
    ["Office Wi-Fi Credentials","SSID: CURA-Office-5G | Password: CuraConnect2026! Do not share outside.",$userIds["admin"],"Medium","All"],
    ["Health Card Enrollment","PhilHealth and HMO enrollment forms are due this Friday. Submit to HR.",$userIds["hr.manager"],"High","All"],
    ["First Sprint Kickoff","Software Development team: our first sprint starts Monday. Jira boards are live.",$userIds["diana.go"]??$userIds["admin"],"Medium","Managers"],
];
foreach($annData as $a){
    DB::insert("INSERT INTO announcement (title,content,posted_by,priority,pinned,target_audience,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)",[$a[0],$a[1],$a[2],$a[3],($a[3]==="Urgent"||$a[3]==="High")?1:0,$a[4],$now,$now]);
}
echo "<li>&#10003; Announcements (5)</li>";

// 9. HOLIDAYS — is_recurring (not recurring), valid type enum
$holidayData=[
    ["New Year Day","2026-01-01","Regular",1],
    ["EDSA People Power Revolution","2026-02-25","Special Non-Working",1],
    ["Maundy Thursday","2026-04-02","Regular",0],
    ["Good Friday","2026-04-03","Regular",0],
    ["Araw ng Kagitingan","2026-04-09","Regular",1],
    ["Labor Day","2026-05-01","Regular",1],
    ["Independence Day","2026-06-12","Regular",1],
    ["Ninoy Aquino Day","2026-08-21","Special Non-Working",1],
    ["National Heroes Day","2026-08-31","Regular",1],
    ["All Saints Day","2026-11-01","Special Non-Working",1],
    ["Bonifacio Day","2026-11-30","Regular",1],
    ["Feast of Immaculate Conception","2026-12-08","Special Non-Working",1],
    ["Christmas Day","2026-12-25","Regular",1],
    ["Rizal Day","2026-12-30","Regular",1],
];
foreach($holidayData as $h) DB::insert("INSERT INTO holiday (name,date,type,is_recurring,created_at) VALUES (?,?,?,?,?)",[$h[0],$h[1],$h[2],$h[3],$now]);
echo "<li>&#10003; Holidays (14)</li>";

// 10. BENEFITS + ENROLLMENTS
$benefitData=[
    ["HMO Coverage","Comprehensive health maintenance organization coverage","Health",5000],
    ["SSS","Social Security System mandatory contribution","Government",900],
    ["PhilHealth","Philippine Health Insurance contribution","Government",400],
    ["Pag-IBIG Fund","Home Development Mutual Fund contribution","Government",100],
    ["13th Month Pay","Mandatory 13th month pay","Bonus",0],
    ["Meal Allowance","Daily meal allowance for onsite work","Allowance",150],
    ["Transportation Allowance","Monthly transportation subsidy","Allowance",2000],
    ["Internet Allowance","Monthly internet allowance for WFH days","Allowance",1500],
];
$benefitIds=[];
foreach($benefitData as $b) $benefitIds[$b[0]]=DB::insert("INSERT INTO benefit (name,description,type,value,created_at) VALUES (?,?,?,?,?)",[$b[0],$b[1],$b[2],$b[3],$now]);
foreach($empIds as $uname=>$eid){ foreach($benefitIds as $bname=>$bid){ DB::execute("INSERT IGNORE INTO employee_benefit (employee_id,benefit_id,enrolled_at) VALUES (?,?,CURDATE())",[$eid,$bid]); } }
echo "<li>&#10003; Benefits (8) + all employees enrolled</li>";

// 11. LMS MODULES + ENROLLMENTS
$lmsData=[
    ["Company Culture & Values","Introduction to CURA values, vision and mission","document",45,$deptIds["HR & Administration"]],
    ["Information Security Basics","Mandatory cybersecurity awareness training","document",60,null],
    ["Git & Version Control","Practical guide to Git workflows and PRs","video",90,$deptIds["Software Development"]],
    ["Agile & Scrum Fundamentals","Introduction to Agile methodology and Scrum","document",120,null],
    ["Figma for Designers","Hands-on Figma fundamentals and prototyping","video",180,$deptIds["UI/UX Design"]],
    ["Docker & Kubernetes Basics","Container orchestration essentials","video",150,$deptIds["DevOps & Infrastructure"]],
    ["QA Best Practices","Test planning, bug reporting, regression testing","document",90,$deptIds["QA & Testing"]],
    ["Data Privacy Act Compliance","Understanding RA 10173 in the workplace","document",60,null],
];
$lmsIds=[];
$adminUid=$userIds["admin"]??1;
foreach($lmsData as $m) $lmsIds[]=DB::insert("INSERT INTO lms_module (title,description,type,duration_mins,department_id,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)",[$m[0],$m[1],$m[2],$m[3],$m[4],$adminUid,$now,$now]);
foreach($empIds as $uname=>$eid){
    foreach($lmsIds as $idx=>$mid){
        $prog=rand(0,100); $status=$prog>=100?"Completed":($prog>0?"In Progress":"Not Started"); $comp=$prog>=100?$now:null;
        DB::execute("INSERT IGNORE INTO lms_enrollment (module_id,employee_id,progress,status,completed_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?)",[$mid,$eid,$prog,$status,$comp,$now,$now]);
    }
}
echo "<li>&#10003; LMS modules (8) + enrollments</li>";

// 12. ONBOARDING TASKS
$onboardTasks=[
    ["Submit government ID copies","Pre-boarding","Completed"],
    ["Sign employment contract","Pre-boarding","Completed"],
    ["Complete personal data sheet","Pre-boarding","Completed"],
    ["Setup company email and accounts","Week 1","Completed"],
    ["Meet department team members","Week 1","Completed"],
    ["Complete Info Security training","Week 1","In Progress"],
    ["Setup development environment","Week 1","In Progress"],
    ["Read company handbook","Week 1","Completed"],
    ["Meet with direct supervisor","Week 2","Completed"],
    ["Complete first project assignment","Week 2","In Progress"],
    ["Submit SSS/PhilHealth/Pag-IBIG forms","Week 2","Not Started"],
    ["Complete HMO enrollment","Integration","Not Started"],
];
$newHires=["mark.lim","jenny.tan","ryan.co","kim.ramos","dante.flores"];
foreach($empIds as $uname=>$eid){
    $subset=in_array($uname,$newHires)?$onboardTasks:array_slice($onboardTasks,0,8);
    foreach($subset as $t) DB::insert("INSERT INTO onboarding_task (employee_id,task_name,stage,status,due_date,created_at,updated_at) VALUES (?,?,?,?,?,?,?)",[$eid,$t[0],$t[1],$t[2],date("Y-m-d",strtotime("+".rand(1,14)." days")),$now,$now]);
}
echo "<li>&#10003; Onboarding tasks</li>";

// 13. PROJECTS + TASKS + MEMBERS
$projData=[
    ["CURAConnect Web App","Main company product","Software Development","Active",date("Y-m-d",strtotime("-14 days")),date("Y-m-d",strtotime("+90 days"))],
    ["Company Website Redesign","Redesign corporate website","UI/UX Design","Active",date("Y-m-d",strtotime("-10 days")),date("Y-m-d",strtotime("+30 days"))],
    ["CI/CD Pipeline Setup","Implement automated CI/CD","DevOps & Infrastructure","Active",date("Y-m-d",strtotime("-12 days")),date("Y-m-d",strtotime("+14 days"))],
    ["QA Test Framework","Build automated test suite","QA & Testing","Active",date("Y-m-d",strtotime("-7 days")),date("Y-m-d",strtotime("+45 days"))],
];
$projIds=[];
foreach($projData as $p){
    $dId=$deptIds[$p[2]]??null; $ownerEid=$empIds["diana.go"]??array_values($empIds)[0];
    $pid=DB::insert("INSERT INTO project (name,owner_id,department_id,description,start_date,deadline,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)",[$p[0],$ownerEid,$dId,$p[1],$p[3],$p[4],$p[5],$now,$now]);
    $projIds[$p[0]]=$pid;
}
$devTeam=["juan.delacruz","ana.garcia","mark.lim","jenny.tan","grace.lee","dante.flores"];
foreach($devTeam as $u){if(isset($empIds[$u]))DB::execute("INSERT IGNORE INTO project_member (project_id,employee_id) VALUES (?,?)",[$projIds["CURAConnect Web App"],$empIds[$u]]);}
$tasks=[
    [$projIds["CURAConnect Web App"],$empIds["juan.delacruz"]??null,"Setup project structure","High","Completed",100,date("Y-m-d",strtotime("+5 days"))],
    [$projIds["CURAConnect Web App"],$empIds["ana.garcia"]??null,"Implement auth module","High","In Progress",60,date("Y-m-d",strtotime("+7 days"))],
    [$projIds["CURAConnect Web App"],$empIds["mark.lim"]??null,"Build dashboard UI","Medium","To Do",0,date("Y-m-d",strtotime("+10 days"))],
    [$projIds["CURAConnect Web App"],$empIds["grace.lee"]??null,"Database schema design","High","Completed",100,date("Y-m-d",strtotime("+3 days"))],
    [$projIds["Company Website Redesign"],$empIds["claire.sy"]??null,"Wireframes","High","Completed",100,date("Y-m-d",strtotime("+5 days"))],
    [$projIds["Company Website Redesign"],$empIds["kevin.ng"]??null,"High-fidelity mockups","Medium","In Progress",50,date("Y-m-d",strtotime("+12 days"))],
    [$projIds["CI/CD Pipeline Setup"],$empIds["ben.wu"]??null,"GitHub Actions setup","High","Completed",100,date("Y-m-d",strtotime("+2 days"))],
    [$projIds["CI/CD Pipeline Setup"],$empIds["mike.chan"]??null,"Docker containerization","High","In Progress",70,date("Y-m-d",strtotime("+7 days"))],
    [$projIds["QA Test Framework"],$empIds["ryan.co"]??null,"Select testing framework","Medium","Completed",100,date("Y-m-d",strtotime("+3 days"))],
    [$projIds["QA Test Framework"],$empIds["lisa.ong"]??null,"Write smoke tests","High","In Progress",30,date("Y-m-d",strtotime("+14 days"))],
];
foreach($tasks as $t) if($t[1]) DB::insert("INSERT INTO project_task (project_id,employee_id,title,priority,status,progress,due_date,created_at) VALUES (?,?,?,?,?,?,?,?)",[$t[0],$t[1],$t[2],$t[3],$t[4],$t[5],$t[6],$now]);
echo "<li>&#10003; Projects (4) + tasks + members</li>";

// 14. RECRUITMENT
$jobPostings=[
    ["Full-Stack Developer",$deptIds["Software Development"],"We are looking for a talented Full-Stack Developer.","3+ years in React, Node.js or PHP. Strong SQL.",60000,120000,"Makati City","Full-time","Open"],
    ["QA Automation Engineer",$deptIds["QA & Testing"],"Build and maintain automated test suites.","2+ years in Selenium/Cypress. CI/CD knowledge.",45000,75000,"Makati City","Full-time","Open"],
    ["Product Designer",$deptIds["UI/UX Design"],"Create beautiful user-centered designs.","Portfolio required. Figma proficiency. 2+ years.",40000,70000,"BGC, Taguig","Full-time","Open"],
];
$jobIds=[];
foreach($jobPostings as $j) $jobIds[]=DB::insert("INSERT INTO job_posting (title,department_id,description,requirements,salary_min,salary_max,location,type,status,posted_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",[$j[0],$j[1],$j[2],$j[3],$j[4],$j[5],$j[6],$j[7],$j[8],$adminUid,$now,$now]);
$candidateData=[
    [$jobIds[0],"Paolo Villanueva","paolo.v@email.com","09189999001","Interview",70000],
    [$jobIds[0],"Christine Mendoza","chris.m@email.com","09189999002","Applied",65000],
    [$jobIds[0],"Erwin Buenaventura","erwin.b@email.com","09189999003","Screening",75000],
    [$jobIds[1],"Sarah Quiambao","sarah.q@email.com","09189999004","Applied",50000],
    [$jobIds[2],"Marco Herrera","marco.h@email.com","09189999005","Interview",55000],
];
foreach($candidateData as $c) DB::insert("INSERT INTO candidate (job_id,name,email,phone,stage,expected_salary,applied_at,created_at,updated_at) VALUES (?,?,?,?,?,?,CURDATE(),?,?)",[$c[0],$c[1],$c[2],$c[3],$c[4],$c[5],$now,$now]);
echo "<li>&#10003; Job postings (3) + candidates (5)</li>";

// 15. PAYROLL + PAYSLIPS
$payrollId=DB::insert("INSERT INTO payroll (period_label,period_start,period_end,pay_date,status,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)",["April 2026 – Semi-monthly","2026-04-01","2026-04-15","2026-04-15","Processed",$adminUid,$now,$now]);
$salaries=["admin"=>180000,"hr.manager"=>65000,"juan.delacruz"=>95000,"ana.garcia"=>62000,"mark.lim"=>28000,"jenny.tan"=>27500,"ryan.co"=>48000,"lisa.ong"=>46000,"ben.wu"=>80000,"claire.sy"=>52000,"kevin.ng"=>50000,"diana.go"=>75000,"joel.cruz"=>55000,"grace.lee"=>64000,"mike.chan"=>78000,"kim.ramos"=>35000,"dante.flores"=>28000];
foreach($empIds as $uname=>$eid){
    $monthly=$salaries[$uname]??30000; $semi=$monthly/2;
    $allow=round($semi*0.10); $ot=rand(0,1)*rand(200,800);
    $sss=min(round($semi*0.045),450); $ph=min(round($semi*0.02),200); $pi=50; $tax=max(0,round(($semi-10417)*0.20));
    $gross=$semi+$allow+$ot; $deduct=$sss+$ph+$pi+$tax; $net=$gross-$deduct;
    DB::insert("INSERT INTO payslip (payroll_id,employee_id,basic_salary,allowances,overtime_pay,gross_pay,sss,phil_health,pag_ibig,income_tax,total_deductions,net_pay,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",[$payrollId,$eid,$semi,$allow,$ot,$gross,$sss,$ph,$pi,$tax,$deduct,$net,"Processed",$now]);
}
echo "<li>&#10003; Payroll + payslips (17)</li>";

// 16. FEEDBACK
$fbData=[
    [$empIds["juan.delacruz"]??0,$empIds["ana.garcia"]??0,"Great work on the auth module!","positive",0],
    [$empIds["diana.go"]??0,$empIds["mark.lim"]??0,"You are progressing well. Keep pushing!","constructive",0],
    [$empIds["ben.wu"]??0,$empIds["mike.chan"]??0,"Docker setup was flawless. Very efficient.","positive",0],
    [$empIds["hr.manager"]??0,$empIds["kim.ramos"]??0,"Please submit government forms before deadline.","constructive",0],
    [0,$empIds["admin"]??0,"Suggestion: provide standing desks for the dev team.","suggestion",1],
];
foreach($fbData as $f) if($f[0]!==null&&$f[1]) DB::insert("INSERT INTO feedback (sender_id,receiver_id,message,category,is_anonymous,created_at) VALUES (?,?,?,?,?,?)",[$f[0]?:null,$f[1],$f[2],$f[3],$f[4],$now]);
echo "<li>&#10003; Feedback entries (5)</li>";

// 17. SYSTEM NOTIFICATIONS — created_at is DATETIME, not int
$nowDt=date("Y-m-d H:i:s");
foreach($empIds as $uname=>$eid){
    $uid=$userIds[$uname]??null; if(!$uid)continue;
    DB::insert("INSERT INTO system_notification (user_id,type,title,message,link,is_read,created_at) VALUES (?,?,?,?,?,?,?)",[$uid,"success","Welcome to Staffora HRMS!","Your account is set up. Explore your dashboard.","/HRMSV3/index.php?page=dashboard",0,$nowDt]);
}
DB::insert("INSERT INTO system_notification (user_id,type,title,message,link,is_read,created_at) VALUES (?,?,?,?,?,?,?)",[$userIds["admin"]??"","warning","2 Pending Leave Requests","Jenny Tan and Ryan Co have pending leave requests.","/HRMSV3/index.php?page=leave",0,$nowDt]);
DB::insert("INSERT INTO system_notification (user_id,type,title,message,link,is_read,created_at) VALUES (?,?,?,?,?,?,?)",[$userIds["hr.manager"]??"","info","Onboarding Reminder","5 employees have incomplete onboarding tasks.","/HRMSV3/index.php?page=onboarding",0,$nowDt]);
echo "<li>&#10003; System notifications</li>";

// 18. AUDIT LOG — uses 'data' column (not 'details')
$auditEntries=[
    [$userIds["admin"],"Login","Auth","Super Admin logged in","127.0.0.1"],
    [$userIds["admin"],"Created Payroll","payroll","Created April 2026 payroll period","127.0.0.1"],
    [$userIds["hr.manager"],"Login","Auth","HR Manager logged in","127.0.0.1"],
    [$userIds["hr.manager"],"Approved Leave","leave","Approved leave for Claire Sy","127.0.0.1"],
    [$userIds["admin"],"Posted Announcement","announcement","Posted Welcome announcement","127.0.0.1"],
];
foreach($auditEntries as $a) DB::insert("INSERT INTO audit_log (user_id,action,module,data,ip,created_at) VALUES (?,?,?,?,?,?)",[$a[0],$a[1],$a[2],$a[3],$a[4],$now]);
echo "<li>&#10003; Audit log entries (5)</li>";

// 19. PERSONNEL ACTIONS — column is 'type' (not action_type), 'remarks' (not reason), valid enum values
$paData=[
    [$empIds["juan.delacruz"]??0,"Promotion",date("Y-m-d"),"Software Engineer","Senior Software Engineer","Promoted after 6-month review",$userIds["admin"]],
    [$empIds["diana.go"]??0,"Salary Adjustment",date("Y-m-d"),"70000","75000","Performance-based increase for Q1 excellence",$userIds["admin"]],
];
foreach($paData as $p) if($p[0]) DB::insert("INSERT INTO personnel_action (employee_id,type,effective_date,old_value,new_value,remarks,status,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,'Approved',?,?,?)",[$p[0],$p[1],$p[2],$p[3],$p[4],$p[5],$p[6],$now,$now]);
echo "<li>&#10003; Personnel actions (2)</li>";

echo "</ul>";
echo "<div style='background:#e8f5e9;border:1px solid #a5d6a7;border-radius:8px;padding:24px;margin-top:20px;'>";
echo "<h3 style='color:#2e7d32;margin:0 0 12px;'>&#9989; Seeding Complete — CURA Corporation</h3>";
echo "<table style='border-collapse:collapse;font-size:14px;margin-bottom:16px;'>";
echo "<tr><td style='padding:4px 16px 4px 0;color:#555;'>Super Admin:</td><td><strong>admin@cura.ph</strong> / Password123</td></tr>";
echo "<tr><td style='padding:4px 16px 4px 0;color:#555;'>HR Manager:</td><td><strong>hr@cura.ph</strong> / Password123</td></tr>";
echo "<tr><td style='padding:4px 16px 4px 0;color:#555;'>Employee:</td><td><strong>juan.delacruz@cura.ph</strong> / Password123</td></tr>";
echo "</table>";
echo "<a href='/HRMSV3/index.php?page=login' style='background:#2e7d32;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:600;font-size:15px;'>Go to Login &rarr;</a>";
echo "</div></div>";

