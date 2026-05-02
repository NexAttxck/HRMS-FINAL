<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';
Auth::check();
$pageTitle='My Payslips — '.APP_NAME;
$empId=Auth::empId();

$payslips=DB::fetchAll("SELECT ps.*,p.period_label,p.pay_date,p.period_start,p.period_end FROM payslip ps JOIN payroll p ON ps.payroll_id=p.id WHERE ps.employee_id=? AND ps.status='Released' ORDER BY p.pay_date DESC",[$empId]);
$viewId=(int)($_GET['id']??0); $viewPs=null; $emp=null;
if($viewId){
    $viewPs=DB::fetchOne("SELECT ps.*,p.period_label,p.pay_date,p.period_start,p.period_end FROM payslip ps JOIN payroll p ON ps.payroll_id=p.id WHERE ps.id=? AND ps.employee_id=?",[$viewId,$empId]);
    if($viewPs) $emp=DB::fetchOne("SELECT e.*,d.name as dept_name,pos.title as pos_title FROM employee e LEFT JOIN department d ON e.department_id=d.id LEFT JOIN position pos ON e.position_id=pos.id WHERE e.id=?",[$empId]);
}
require_once __DIR__.'/../includes/layout_header.php';
?>
<?php if($viewPs&&$emp): ?>
<style>
@media print{.hrms-header,.hrms-sidebar,.no-print,.btn{display:none!important;}.hrms-body-row{display:block!important;}.hrms-main{padding:0!important;margin:0!important;}.payslip-card{box-shadow:none!important;border:none!important;max-width:100%!important;}}
</style>
<div class="no-print" style="margin-bottom:18px;display:flex;align-items:center;gap:10px;">
    <a href="<?php echo url('my_payslips');?>" class="btn btn-outline">&larr; Back to My Payslips</a>
    <button onclick="window.print()" class="btn btn-accent">&#128424; Print / Save PDF</button>
</div>
<div class="hrms-card payslip-card" style="max-width:760px;margin:0 auto;padding:0;">
  <!-- Header Bar -->
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
      <div style="font-size:18px;font-weight:800;"><?php echo e($emp['first_name'].' '.$emp['last_name']);?></div>
      <div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?php echo e($emp['pos_title']??'—');?></div>
    </div>
    <div style="text-align:right;">
      <div style="font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;margin-bottom:4px;">Details</div>
      <div style="font-size:13px;"><strong>Employee ID:</strong> <?php echo e($emp['employee_no']??'—');?></div>
      <div style="font-size:13px;"><strong>Department:</strong> <?php echo e($emp['dept_name']??'—');?></div>
      <div style="font-size:13px;"><strong>Employment Status:</strong> <?php echo e($emp['status']??'—');?></div>
    </div>
  </div>
  <!-- Earnings & Deductions -->
  <div style="padding:24px 32px;display:grid;grid-template-columns:1fr 1fr;gap:32px;border-bottom:1px solid var(--border);">
    <div>
      <div style="font-size:11px;text-transform:uppercase;font-weight:700;color:var(--text-muted);margin-bottom:12px;letter-spacing:1px;">Earnings</div>
      <?php $ears=[['Basic Salary',$viewPs['basic_salary']],['Allowances',$viewPs['allowances']],['Overtime Pay',$viewPs['overtime_pay']]];
      foreach($ears as $r):if((float)$r[1]>0):?>
      <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(255,255,255,0.04);font-size:13px;">
        <span style="color:var(--text-muted);"><?php echo $r[0];?></span>
        <span style="font-weight:600;">&#8369;<?php echo number_format($r[1],2);?></span>
      </div>
      <?php endif;endforeach;?>
      <div style="display:flex;justify-content:space-between;padding:10px 0 0;font-size:14px;font-weight:800;border-top:2px solid var(--border);margin-top:4px;">
        <span>Gross Pay</span><span style="color:#66bb6a;">&#8369;<?php echo number_format($viewPs['gross_pay'],2);?></span>
      </div>
    </div>
    <div>
      <div style="font-size:11px;text-transform:uppercase;font-weight:700;color:var(--text-muted);margin-bottom:12px;letter-spacing:1px;">Deductions</div>
      <?php $deds=[['SSS Contribution',$viewPs['sss']],['PhilHealth',$viewPs['phil_health']],['Pag-IBIG Fund',$viewPs['pag_ibig']],['Withholding Tax',$viewPs['income_tax']]];
      foreach($deds as $r):if((float)$r[1]>0):?>
      <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(255,255,255,0.04);font-size:13px;">
        <span style="color:var(--text-muted);"><?php echo $r[0];?></span>
        <span style="font-weight:600;color:#ef5350;">&#8369;<?php echo number_format($r[1],2);?></span>
      </div>
      <?php endif;endforeach;?>
      <div style="display:flex;justify-content:space-between;padding:10px 0 0;font-size:14px;font-weight:800;border-top:2px solid var(--border);margin-top:4px;">
        <span>Total Deductions</span><span style="color:#ef5350;">&#8369;<?php echo number_format($viewPs['total_deductions'],2);?></span>
      </div>
    </div>
  </div>
  <!-- Net Pay Banner -->
  <div style="padding:24px 32px;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,var(--accent),#5a8a4a);">
    <div>
      <div style="color:rgba(255,255,255,0.8);font-size:12px;font-weight:600;letter-spacing:1px;text-transform:uppercase;">Net Pay</div>
      <div style="color:rgba(255,255,255,0.65);font-size:12px;margin-top:2px;">Amount credited to your account</div>
    </div>
    <div style="color:#fff;font-size:36px;font-weight:900;">&#8369;<?php echo number_format($viewPs['net_pay'],2);?></div>
  </div>
  <!-- Signature Block -->
  <div style="padding:28px 32px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;border-top:1px solid var(--border);">
    <?php foreach(['Prepared by','Verified by','Employee Signature'] as $s):?>
    <div style="text-align:center;">
      <div style="height:40px;border-bottom:1px solid var(--border);margin-bottom:8px;"></div>
      <div style="font-size:11px;color:var(--text-muted);"><?php echo $s;?></div>
    </div>
    <?php endforeach;?>
  </div>
  <div style="padding:10px 32px 18px;text-align:center;font-size:11px;color:var(--text-muted);">
    This is a computer-generated payslip. Generated on <?php echo date('F j, Y \a\t g:i A');?>.
  </div>
</div>

<?php else: ?>
<div style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;">
  <h1 style="font-size:24px;font-weight:700;margin:0;">My Payslips</h1>
</div>
<?php if(empty($payslips)):?>
<div class="hrms-card" style="padding:60px;text-align:center;">
  <div style="font-size:48px;margin-bottom:12px;">&#128181;</div>
  <div style="font-size:16px;font-weight:600;margin-bottom:6px;">No payslips available yet</div>
  <div style="color:var(--text-muted);font-size:13px;">Your payslips will appear here once HR processes and releases payroll.</div>
</div>
<?php else:?>
<div class="hrms-card"><div class="card-body" style="padding:0;">
<table class="table hrms-table" style="margin:0;">
  <thead><tr><th>Pay Period</th><th>Pay Date</th><th>Gross Pay</th><th>Deductions</th><th>Net Pay</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($payslips as $ps):?>
  <tr>
    <td style="font-weight:700;"><?php echo e($ps['period_label']);?></td>
    <td style="font-size:12px;"><?php echo $ps['pay_date']?date('M j, Y',strtotime($ps['pay_date'])):'—';?></td>
    <td>&#8369;<?php echo number_format($ps['gross_pay'],2);?></td>
    <td style="color:#ef5350;">&#8369;<?php echo number_format($ps['total_deductions'],2);?></td>
    <td style="color:#66bb6a;font-weight:800;font-size:15px;">&#8369;<?php echo number_format($ps['net_pay'],2);?></td>
    <td><span class="badge badge-success"><?php echo e($ps['status']);?></span></td>
    <td><a href="<?php echo url('my_payslips',['id'=>$ps['id']]);?>" class="btn btn-sm btn-outline" style="padding:3px 10px;">View &amp; Print</a></td>
  </tr>
  <?php endforeach;?>
  </tbody>
</table>
</div></div>
<?php endif;?>
<?php endif;?>
<?php require_once __DIR__.'/../includes/layout_footer.php';?>
