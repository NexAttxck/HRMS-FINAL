<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';
Auth::check();
$isAdmin=Auth::isAdmin(); $isManager=Auth::isManager(); $userId=Auth::id();
$action=$_GET['action']??'index'; $viewId=(int)($_GET['id']??0); $psId=(int)($_GET['ps']??0);
if(!$isAdmin&&!$isManager){header('Location:'.url('my_payslips'));exit;}

// ── POST ─────────────────────────────────────────────────────────────────────
if($_SERVER['REQUEST_METHOD']==='POST'){
    $a=$_POST['_action']??'';
    if($isAdmin&&$a==='create'){
        $pid=DB::insert("INSERT INTO payroll (period_label,period_start,period_end,pay_date,status,created_by,created_at,updated_at) VALUES (?,?,?,?,'Draft',?,NOW(),NOW())",
            [$_POST['period_label'],$_POST['period_start'],$_POST['period_end'],$_POST['pay_date'],$userId]);
        Auth::audit('Create Payroll Period','Payroll',(int)$pid,$_POST['period_label']??'');
        Auth::flash('success','Payroll period created.');
        header('Location:'.url('payroll',['action'=>'view','id'=>$pid]));exit;
    }
    if($isAdmin&&$a==='process'){
        $pid=(int)$_POST['payroll_id'];
        $emps=DB::fetchAll("SELECT * FROM employee WHERE status IN ('Regular','Probationary')");
        $cnt=0;
        foreach($emps as $em){
            if(DB::fetchScalar("SELECT COUNT(*) FROM payslip WHERE payroll_id=? AND employee_id=?",[$pid,$em['id']]))continue;
            $b=(float)($em['basic_salary']??0);
            $al=round($b*0.10,2); $ot=0;
            $sss=round(min($b*0.045,900),2); $ph=round(min($b*0.02,400),2); $pi=100.00;
            $tax=round(max(0,($b-20833)*0.20),2);
            $gross=$b+$al+$ot; $ded=$sss+$ph+$pi+$tax; $net=$gross-$ded;
            DB::insert("INSERT INTO payslip (payroll_id,employee_id,basic_salary,allowances,overtime_pay,gross_pay,sss,phil_health,pag_ibig,income_tax,total_deductions,net_pay,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'Processed',NOW())",
                [$pid,$em['id'],$b,$al,$ot,$gross,$sss,$ph,$pi,$tax,$ded,$net]);
            $cnt++;
        }
        DB::execute("UPDATE payroll SET status='Processed',updated_at=NOW() WHERE id=?",[$pid]);
        Auth::audit('Process Payroll','Payroll',$pid,"Generated {$cnt} payslips");
        Auth::flash('success',"Payroll processed — {$cnt} payslips generated.");
        header('Location:'.url('payroll',['action'=>'view','id'=>$pid]));exit;
    }
    if($isAdmin&&$a==='release'){
        $pid=(int)$_POST['payroll_id'];
        DB::execute("UPDATE payroll SET status='Released',updated_at=NOW() WHERE id=?",[$pid]);
        DB::execute("UPDATE payslip SET status='Released' WHERE payroll_id=?",[$pid]);
        Auth::audit('Release Payroll','Payroll',$pid,'Released to employees');
        Auth::flash('success','Payroll released — employees can now view their payslips.');
        header('Location:'.url('payroll',['action'=>'view','id'=>$pid]));exit;
    }
}

// ── DATA ─────────────────────────────────────────────────────────────────────
$payrolls=DB::fetchAll("SELECT p.*,u.username as creator,COUNT(ps.id) as slip_count,COALESCE(SUM(ps.gross_pay),0) as total_gross,COALESCE(SUM(ps.net_pay),0) as total_net FROM payroll p LEFT JOIN `user` u ON p.created_by=u.id LEFT JOIN payslip ps ON ps.payroll_id=p.id GROUP BY p.id ORDER BY p.id DESC");
$viewPayroll=null; $payslips=[]; $viewPs=null; $psEmp=null;

if($action==='view'&&$viewId){
    $viewPayroll=DB::fetchOne("SELECT * FROM payroll WHERE id=?",[$viewId]);
    if($viewPayroll) $payslips=DB::fetchAll("SELECT ps.*,e.first_name,e.last_name,e.employee_no,d.name as dept_name,pos.title as pos_title FROM payslip ps JOIN employee e ON ps.employee_id=e.id LEFT JOIN department d ON e.department_id=d.id LEFT JOIN position pos ON e.position_id=pos.id WHERE ps.payroll_id=? ORDER BY e.last_name,e.first_name",[$viewId]);
}
if($action==='payslip'&&$psId){
    $viewPs=DB::fetchOne("SELECT ps.*,p.period_label,p.pay_date,p.period_start,p.period_end FROM payslip ps JOIN payroll p ON ps.payroll_id=p.id WHERE ps.id=?",[$psId]);
    if($viewPs){
        $psEmp=DB::fetchOne("SELECT e.*,d.name as dept_name,pos.title as pos_title FROM employee e LEFT JOIN department d ON e.department_id=d.id LEFT JOIN position pos ON e.position_id=pos.id WHERE e.id=?",[$viewPs['employee_id']]);
        $viewPayroll=DB::fetchOne("SELECT * FROM payroll WHERE id=?",[$viewPs['payroll_id']]);
    }
}
// Index KPIs
$kpi=['periods'=>0,'employees_paid'=>0,'total_gross'=>0,'total_net'=>0];
if($action==='index'){
    $kpi['periods']=(int)DB::fetchScalar("SELECT COUNT(*) FROM payroll")?:0;
    $kpi['employees_paid']=(int)DB::fetchScalar("SELECT COUNT(DISTINCT employee_id) FROM payslip")?:0;
    $kpi['total_gross']=(float)(DB::fetchScalar("SELECT COALESCE(SUM(gross_pay),0) FROM payslip")?:0);
    $kpi['total_net']=(float)(DB::fetchScalar("SELECT COALESCE(SUM(net_pay),0) FROM payslip")?:0);
}
// View KPIs
$vkpi=['headcount'=>0,'gross'=>0,'deductions'=>0,'net'=>0];
if($action==='view'&&!empty($payslips)){
    foreach($payslips as $ps){$vkpi['headcount']++;$vkpi['gross']+=$ps['gross_pay'];$vkpi['deductions']+=$ps['total_deductions'];$vkpi['net']+=$ps['net_pay'];}
}

$pageTitle=($action==='payslip'&&$viewPs&&$psEmp)?e($psEmp['first_name'].' '.$psEmp['last_name']).' — Payslip — '.APP_NAME:(($action==='view'&&$viewPayroll)?'Payroll: '.e($viewPayroll['period_label']).' — '.APP_NAME:'Payroll Management — '.APP_NAME);
require_once __DIR__.'/../includes/layout_header.php';
?>
<?php if($action==='payslip'&&$viewPs&&$psEmp): ?>
<style>
@media print{.hrms-header,.hrms-sidebar,.no-print,.btn{display:none!important;}.hrms-body-row{display:block!important;}.hrms-main{padding:0!important;margin:0!important;}.payslip-print{box-shadow:none!important;border:none!important;max-width:100%!important;}}
</style>
<div class="no-print" style="margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <a href="<?php echo url('payroll',['action'=>'view','id'=>$viewPs['payroll_id']]);?>" class="btn btn-outline">&larr; Back to Payroll</a>
    <button onclick="window.print()" class="btn btn-accent">&#128424; Print Payslip</button>
</div>
<div class="hrms-card payslip-print" style="max-width:760px;margin:0 auto;padding:0;">
  <!-- Header -->
  <div style="background:var(--accent);padding:28px 32px;border-radius:12px 12px 0 0;display:flex;justify-content:space-between;align-items:center;">
    <div>
      <div style="font-size:26px;font-weight:900;letter-spacing:3px;color:#fff;">STAFFORA</div>
      <div style="font-size:12px;color:rgba(255,255,255,0.75);margin-top:2px;">by CURA Corporation &bull; Official Payslip</div>
    </div>
    <div style="text-align:right;">
      <div style="font-size:13px;font-weight:700;color:#fff;"><?php echo e($viewPs['period_label']);?></div>
      <div style="font-size:12px;color:rgba(255,255,255,0.75);">Pay Date: <?php echo date('F j, Y',strtotime($viewPs['pay_date']));?></div>
      <div style="font-size:12px;color:rgba(255,255,255,0.75);">Period: <?php echo date('M j',strtotime($viewPs['period_start']));?> – <?php echo date('M j, Y',strtotime($viewPs['period_end']));?></div>
    </div>
  </div>
  <!-- Employee Info -->
  <div style="padding:20px 32px;border-bottom:1px solid var(--border);display:grid;grid-template-columns:1fr 1fr;gap:12px;">
    <div>
      <div style="font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;margin-bottom:4px;">Employee</div>
      <div style="font-size:18px;font-weight:800;"><?php echo e($psEmp['first_name'].' '.$psEmp['last_name']);?></div>
      <div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?php echo e($psEmp['pos_title']??'—');?></div>
    </div>
    <div style="text-align:right;">
      <div style="font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;margin-bottom:4px;">Details</div>
      <div style="font-size:13px;"><strong>ID:</strong> <?php echo e($psEmp['employee_no']??'—');?></div>
      <div style="font-size:13px;"><strong>Department:</strong> <?php echo e($psEmp['dept_name']??'—');?></div>
      <div style="font-size:13px;"><strong>Status:</strong> <?php echo e($psEmp['status']??'—');?></div>
    </div>
  </div>
  <!-- Earnings & Deductions -->
  <div style="padding:24px 32px;display:grid;grid-template-columns:1fr 1fr;gap:32px;border-bottom:1px solid var(--border);">
    <div>
      <div style="font-size:11px;text-transform:uppercase;font-weight:700;color:var(--text-muted);margin-bottom:12px;letter-spacing:1px;">Earnings</div>
      <?php $ears=[['Basic Salary',$viewPs['basic_salary']],['Allowances',$viewPs['allowances']],['Overtime Pay',$viewPs['overtime_pay']]];
      foreach($ears as $r):if((float)$r[1]>0):?>
      <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid rgba(255,255,255,0.05);font-size:13px;">
        <span style="color:var(--text-muted);"><?php echo $r[0];?></span><span style="font-weight:600;">&#8369;<?php echo number_format($r[1],2);?></span>
      </div>
      <?php endif;endforeach;?>
      <div style="display:flex;justify-content:space-between;padding:10px 0 0;font-size:14px;font-weight:800;">
        <span>Gross Pay</span><span style="color:#66bb6a;">&#8369;<?php echo number_format($viewPs['gross_pay'],2);?></span>
      </div>
    </div>
    <div>
      <div style="font-size:11px;text-transform:uppercase;font-weight:700;color:var(--text-muted);margin-bottom:12px;letter-spacing:1px;">Deductions</div>
      <?php $deds=[['SSS',$viewPs['sss']],['PhilHealth',$viewPs['phil_health']],['Pag-IBIG',$viewPs['pag_ibig']],['Income Tax',$viewPs['income_tax']]];
      foreach($deds as $r):if((float)$r[1]>0):?>
      <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid rgba(255,255,255,0.05);font-size:13px;">
        <span style="color:var(--text-muted);"><?php echo $r[0];?></span><span style="font-weight:600;color:#ef5350;">&#8369;<?php echo number_format($r[1],2);?></span>
      </div>
      <?php endif;endforeach;?>
      <div style="display:flex;justify-content:space-between;padding:10px 0 0;font-size:14px;font-weight:800;">
        <span>Total Deductions</span><span style="color:#ef5350;">&#8369;<?php echo number_format($viewPs['total_deductions'],2);?></span>
      </div>
    </div>
  </div>
  <!-- Net Pay -->
  <div style="padding:24px 32px;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,var(--accent),#5a8a4a);border-radius:0 0 12px 12px;">
    <div style="color:rgba(255,255,255,0.85);font-size:14px;font-weight:600;">NET PAY</div>
    <div style="color:#fff;font-size:34px;font-weight:900;">&#8369;<?php echo number_format($viewPs['net_pay'],2);?></div>
  </div>
  <!-- Signatures -->
  <div style="padding:28px 32px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;border-top:1px solid var(--border);">
    <?php foreach(['Prepared by','Approved by','Received by'] as $sig):?>
    <div style="text-align:center;">
      <div style="border-top:1px solid var(--border);padding-top:8px;font-size:11px;color:var(--text-muted);margin-top:36px;"><?php echo $sig;?></div>
    </div>
    <?php endforeach;?>
  </div>
</div>

<?php elseif($action==='create'&&$isAdmin): ?>
<div style="margin-bottom:20px;"><h1 style="font-size:24px;font-weight:700;margin:0;">Create Payroll Period</h1></div>
<div class="hrms-card"><div class="card-body">
<form method="POST">
<input type="hidden" name="_action" value="create">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
  <div style="grid-column:1/-1;"><label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:5px;">Period Label</label><input type="text" name="period_label" placeholder="e.g. May 2026 – Semi-Monthly 1" required class="form-control" style="background:rgba(255,255,255,0.06);border-color:var(--border);color:var(--text);"></div>
  <div><label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:5px;">Period Start</label><input type="date" name="period_start" required class="form-control" style="background:rgba(255,255,255,0.06);border-color:var(--border);color:var(--text);"></div>
  <div><label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:5px;">Period End</label><input type="date" name="period_end" required class="form-control" style="background:rgba(255,255,255,0.06);border-color:var(--border);color:var(--text);"></div>
  <div><label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:5px;">Pay Date</label><input type="date" name="pay_date" required class="form-control" style="background:rgba(255,255,255,0.06);border-color:var(--border);color:var(--text);"></div>
</div>
<div style="margin-top:20px;display:flex;gap:10px;">
  <button type="submit" class="btn btn-accent">&#10003; Create Payroll Period</button>
  <a href="<?php echo url('payroll');?>" class="btn btn-outline">Cancel</a>
</div>
</form>
</div></div>

<?php elseif($action==='view'&&$viewPayroll): ?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
  <div><a href="<?php echo url('payroll');?>" style="font-size:12px;color:var(--text-muted);text-decoration:none;">&larr; All Payroll Periods</a>
  <h1 style="font-size:22px;font-weight:800;margin:4px 0 0;"><?php echo e($viewPayroll['period_label']);?></h1></div>
  <div style="display:flex;gap:8px;">
    <?php if($isAdmin&&$viewPayroll['status']==='Draft'):?>
    <form method="POST" style="display:inline;" onsubmit="return confirm('Generate payslips for all active employees?')">
      <input type="hidden" name="_action" value="process"><input type="hidden" name="payroll_id" value="<?php echo $viewPayroll['id'];?>">
      <button type="submit" class="btn btn-accent">&#9654; Process Payroll</button>
    </form>
    <?php elseif($isAdmin&&$viewPayroll['status']==='Processed'):?>
    <form method="POST" style="display:inline;" onsubmit="return confirm('Release payslips to employees?')">
      <input type="hidden" name="_action" value="release"><input type="hidden" name="payroll_id" value="<?php echo $viewPayroll['id'];?>">
      <button type="submit" class="btn btn-accent" style="background:#5a8a4a;">&#10003; Release to Employees</button>
    </form>
    <?php endif;?>
  </div>
</div>
<!-- Period info bar -->
<div class="hrms-card" style="padding:16px 20px;margin-bottom:16px;">
  <div style="display:flex;gap:28px;flex-wrap:wrap;align-items:center;font-size:13px;">
    <span><strong>Period:</strong> <?php echo date('M j',strtotime($viewPayroll['period_start']));?> – <?php echo date('M j, Y',strtotime($viewPayroll['period_end']));?></span>
    <span><strong>Pay Date:</strong> <?php echo date('M j, Y',strtotime($viewPayroll['pay_date']));?></span>
    <span><strong>Status:</strong> <span class="badge <?php echo $viewPayroll['status']==='Released'?'badge-success':($viewPayroll['status']==='Processed'?'badge-info':'badge-muted');?>"><?php echo $viewPayroll['status'];?></span></span>
  </div>
</div>
<!-- KPI row -->
<?php if(!empty($payslips)):?>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">
  <?php $vkpis=[['Employees Paid',$vkpi['headcount'],'','#7CB9E8'],['Total Gross','&#8369;'.number_format($vkpi['gross'],2),'','#66bb6a'],['Total Deductions','&#8369;'.number_format($vkpi['deductions'],2),'','#ef5350'],['Total Net Pay','&#8369;'.number_format($vkpi['net'],2),'','var(--accent)']];
  foreach($vkpis as $k):?>
  <div class="hrms-card" style="padding:16px 18px;border-left:3px solid <?php echo $k[3];?>;">
    <div style="font-size:11px;text-transform:uppercase;font-weight:600;color:var(--text-muted);margin-bottom:6px;"><?php echo $k[0];?></div>
    <div style="font-size:20px;font-weight:800;color:<?php echo $k[3];?>;"><?php echo $k[1];?></div>
  </div>
  <?php endforeach;?>
</div>
<?php endif;?>
<!-- Payslips table -->
<div class="hrms-card"><div class="card-body" style="padding:0;">
<table class="table hrms-table" style="margin:0;">
  <thead><tr><th>Employee</th><th>Position / Dept</th><th>Basic Salary</th><th>Gross Pay</th><th>Deductions</th><th style="color:#66bb6a;">Net Pay</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($payslips as $ps):?>
  <tr>
    <td><span style="font-weight:700;"><?php echo e($ps['first_name'].' '.$ps['last_name']);?></span><br><span style="font-size:11px;color:var(--text-muted);"><?php echo e($ps['employee_no']??'');?></span></td>
    <td><span style="font-size:12px;"><?php echo e($ps['pos_title']??'—');?></span><br><span style="font-size:11px;color:var(--text-muted);"><?php echo e($ps['dept_name']??'—');?></span></td>
    <td>&#8369;<?php echo number_format($ps['basic_salary'],2);?></td>
    <td style="font-weight:600;color:#66bb6a;">&#8369;<?php echo number_format($ps['gross_pay'],2);?></td>
    <td style="color:#ef5350;">&#8369;<?php echo number_format($ps['total_deductions'],2);?></td>
    <td style="font-weight:800;font-size:15px;color:#66bb6a;">&#8369;<?php echo number_format($ps['net_pay'],2);?></td>
    <td><span class="badge <?php echo $ps['status']==='Released'?'badge-success':($ps['status']==='Processed'?'badge-info':'badge-muted');?>"><?php echo $ps['status'];?></span></td>
    <td><a href="<?php echo url('payroll',['action'=>'payslip','ps'=>$ps['id'],'id'=>$viewId]);?>" class="btn btn-sm btn-outline" style="padding:3px 10px;">View Slip</a></td>
  </tr>
  <?php endforeach; if(empty($payslips)):?>
  <tr><td colspan="8" style="text-align:center;padding:36px;color:var(--text-muted);">No payslips yet. Click <strong>Process Payroll</strong> to generate.</td></tr>
  <?php endif;?>
  </tbody>
</table>
</div></div>

<?php else: /* INDEX */ ?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;">
  <h1 style="font-size:24px;font-weight:700;margin:0;">Payroll Management</h1>
  <?php if($isAdmin):?><a href="<?php echo url('payroll',['action'=>'create']);?>" class="btn btn-accent">+ New Period</a><?php endif;?>
</div>
<!-- Index KPIs -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;">
  <?php $ikpis=[['Payroll Periods',$kpi['periods'],'&#128197;','#7CB9E8'],['Employees Paid',$kpi['employees_paid'],'&#128101;','var(--accent)'],['Total Gross Pay','&#8369;'.number_format($kpi['total_gross'],2),'&#128200;','#66bb6a'],['Total Net Pay','&#8369;'.number_format($kpi['total_net'],2),'&#128181;','#E07A45']];
  foreach($ikpis as $k):?>
  <div class="hrms-card kpi-card" style="border-left:3px solid <?php echo $k[3];?>;">
    <div style="font-size:22px;margin-bottom:6px;"><?php echo $k[2];?></div>
    <div class="kpi-value" style="color:<?php echo $k[3];?>;"><?php echo $k[1];?></div>
    <div class="kpi-label"><?php echo $k[0];?></div>
  </div>
  <?php endforeach;?>
</div>
<!-- Payroll list -->
<div class="hrms-card"><div class="card-header"><h3>Payroll Periods</h3></div><div class="card-body" style="padding:0;">
<table class="table hrms-table" style="margin:0;">
  <thead><tr><th>Period</th><th>Start</th><th>End</th><th>Pay Date</th><th>Employees</th><th>Total Gross</th><th>Total Net</th><th>Status</th><th>Created By</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($payrolls as $p):?>
  <tr>
    <td style="font-weight:700;"><?php echo e($p['period_label']);?></td>
    <td style="font-size:12px;"><?php echo $p['period_start']?date('M j, Y',strtotime($p['period_start'])):'—';?></td>
    <td style="font-size:12px;"><?php echo $p['period_end']?date('M j, Y',strtotime($p['period_end'])):'—';?></td>
    <td style="font-size:12px;"><?php echo $p['pay_date']?date('M j, Y',strtotime($p['pay_date'])):'—';?></td>
    <td style="font-weight:600;"><?php echo (int)$p['slip_count'];?></td>
    <td style="color:#66bb6a;">&#8369;<?php echo number_format((float)$p['total_gross'],2);?></td>
    <td style="font-weight:700;color:#66bb6a;">&#8369;<?php echo number_format((float)$p['total_net'],2);?></td>
    <td><span class="badge <?php echo $p['status']==='Released'?'badge-success':($p['status']==='Processed'?'badge-info':'badge-muted');?>"><?php echo $p['status'];?></span></td>
    <td style="font-size:12px;color:var(--text-muted);"><?php echo e($p['creator']??'System');?></td>
    <td><a href="<?php echo url('payroll',['action'=>'view','id'=>$p['id']]);?>" class="btn btn-sm btn-outline" style="padding:3px 10px;">Open</a></td>
  </tr>
  <?php endforeach; if(empty($payrolls)):?>
  <tr><td colspan="10" style="text-align:center;padding:36px;color:var(--text-muted);">No payroll periods yet. Click <strong>+ New Period</strong> to get started.</td></tr>
  <?php endif;?>
  </tbody>
</table>
</div></div>
<?php endif;?>
<?php require_once __DIR__.'/../includes/layout_footer.php';?>
