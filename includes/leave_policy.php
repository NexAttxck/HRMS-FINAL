<?php
/**
 * Leave Policy Engine
 * Centralized leave credit accrual, eligibility, and validation logic.
 */
class LeavePolicy {

    // Policy constants
    const SIL_RATE        = 1.25;  // per month
    const SIL_MAX_YEAR    = 15;    // max per year
    const SIL_CONVERTIBLE = 5;     // max convertible in Feb
    const CARRYOVER_EXPIRY_MONTH = 4; // April 1
    const VACATION_ADVANCE_DAYS  = 14;
    const SICK_EMERGENCY_HOURS   = 48;

    const BIRTHDAY_LEAVE_DAYS     = 1;
    const BEREAVEMENT_IMMEDIATE   = 3;
    const BEREAVEMENT_SECONDARY   = 1;
    const PATERNITY_DAYS          = 7;
    const PATERNITY_EXTENDED      = 14;
    const MATERNITY_DAYS          = 105;
    const SOLO_PARENT_DAYS        = 7;
    const SPECIAL_WOMEN_DAYS      = 60;
    const VAWC_DAYS               = 10;

    // All leave types in the system
    const TYPES = [
        'Vacation Leave',
        'Sick Leave',
        'Emergency Leave',
        'Birthday Leave',
        'Bereavement Leave (Immediate Family)',
        'Bereavement Leave (Secondary Family)',
        'Paternity Leave',
        'Maternity Leave',
        'Solo Parent Leave',
        'Special Leave for Women Workers',
        'Violence Against Women and Children Leave',
        'Leave Without Pay',
    ];

    // Types that deduct from SIL
    const SIL_TYPES = ['Vacation Leave','Sick Leave','Emergency Leave'];

    // Types requiring document upload
    const DOC_REQUIRED = [
        'Sick Leave','Emergency Leave',
        'Bereavement Leave (Immediate Family)','Bereavement Leave (Secondary Family)',
        'Paternity Leave','Maternity Leave',
        'Special Leave for Women Workers','Violence Against Women and Children Leave',
    ];

    /**
     * Get or create the leave balance row for an employee for a given year.
     */
    public static function getBalance(int $empId, ?int $year = null): array {
        $year = $year ?? (int)date('Y');
        $bal = DB::fetchOne("SELECT * FROM leave_balance WHERE employee_id=? AND year=?", [$empId, $year]);
        if (!$bal) {
            DB::execute("INSERT INTO leave_balance (employee_id, year, updated_at) VALUES (?,?,?)", [$empId, $year, time()]);
            $bal = DB::fetchOne("SELECT * FROM leave_balance WHERE employee_id=? AND year=?", [$empId, $year]);
        }
        return $bal;
    }

    /**
     * Calculate how many SIL credits an employee has accrued this year.
     * Based on date_regularized (or hire_date + 6 months as fallback).
     */
    public static function calculateAccrued(array $emp, ?int $year = null): float {
        $year = $year ?? (int)date('Y');
        $regDate = $emp['date_regularized'] ?? null;

        // Fallback: hire_date + 6 months
        if (!$regDate && !empty($emp['hire_date'])) {
            $regDate = date('Y-m-d', strtotime($emp['hire_date'] . ' +6 months'));
        }
        if (!$regDate) return 0;

        $regTs = strtotime($regDate);
        $yearStart = mktime(0,0,0,1,1,$year);
        $now = time();
        $yearEnd = mktime(23,59,59,12,31,$year);
        $endTs = min($now, $yearEnd);

        // If not yet eligible this year
        if ($regTs > $endTs) return 0;

        // First eligible month in this year
        $startMonth = ($regTs >= $yearStart) ? (int)date('n', $regTs) : 1;
        $endMonth   = (int)date('n', $endTs);

        $months = max(0, $endMonth - $startMonth + 1);
        $accrued = round($months * self::SIL_RATE, 2);
        return min($accrued, self::SIL_MAX_YEAR);
    }

    /**
     * Get effective SIL remaining (accrued - used + valid carryover).
     */
    public static function silRemaining(int $empId, array $emp): float {
        $year = (int)date('Y');
        $bal = self::getBalance($empId, $year);
        $accrued = self::calculateAccrued($emp, $year);

        // Carryover expires April 1
        $carryover = (float)$bal['sil_carried_over'];
        $now = time();
        $expiryDate = mktime(0,0,0, self::CARRYOVER_EXPIRY_MONTH, 1, $year);
        if ($now >= $expiryDate) {
            $carryover = 0;
        }

        return round($accrued - (float)$bal['sil_used'] + $carryover, 2);
    }

    /**
     * Check if employee is eligible for SIL (6-month training or regularized).
     */
    public static function isSilEligible(array $emp): bool {
        if (!empty($emp['date_regularized'])) {
            return strtotime($emp['date_regularized']) <= time();
        }
        if (!empty($emp['hire_date'])) {
            $sixMonths = strtotime($emp['hire_date'] . ' +6 months');
            return $sixMonths <= time();
        }
        return false;
    }

    /**
     * Check eligibility for each leave type, returning an array of [type => [eligible, reason]].
     */
    public static function checkEligibility(array $emp, int $empId): array {
        $year = (int)date('Y');
        $bal = self::getBalance($empId, $year);
        $silOk = self::isSilEligible($emp);
        $silRem = $silOk ? self::silRemaining($empId, $emp) : 0;
        $gender = strtolower($emp['gender'] ?? '');
        $isMale = in_array($gender, ['male','m']);
        $isFemale = in_array($gender, ['female','f']);

        $result = [];

        // SIL types
        foreach (self::SIL_TYPES as $t) {
            if (!$silOk) {
                $result[$t] = ['eligible' => false, 'reason' => 'Complete 6-month training first', 'max_days' => 0];
            } elseif ($silRem <= 0) {
                $result[$t] = ['eligible' => false, 'reason' => 'No SIL credits remaining', 'max_days' => 0];
            } else {
                $result[$t] = ['eligible' => true, 'reason' => number_format($silRem,2).' SIL available', 'max_days' => $silRem];
            }
        }

        // Birthday Leave
        $bdayOk = false; $bdayReason = 'Not eligible';
        if (!empty($emp['date_of_birth']) && !empty($emp['hire_date'])) {
            $oneYear = strtotime($emp['hire_date'] . ' +1 year');
            if (time() >= $oneYear) {
                $bday = date('m-d', strtotime($emp['date_of_birth']));
                $bdayThisYear = strtotime(date('Y') . '-' . $bday);
                $weekBefore = $bdayThisYear - (3 * 86400);
                $weekAfter  = $bdayThisYear + (7 * 86400);
                $now = time();
                if ($now >= $weekBefore && $now <= $weekAfter && !$bal['birthday_used']) {
                    $bdayOk = true;
                    $bdayReason = 'Available — birthday week';
                } elseif ($bal['birthday_used']) {
                    $bdayReason = 'Already used this year';
                } else {
                    $bdayReason = 'Only usable in your birthday week';
                }
            } else {
                $bdayReason = 'Requires 1 year of service';
            }
        }
        $result['Birthday Leave'] = ['eligible' => $bdayOk, 'reason' => $bdayReason, 'max_days' => $bdayOk ? 1 : 0];

        // Bereavement
        $result['Bereavement Leave (Immediate Family)'] = ['eligible' => true, 'reason' => 'Up to 3 days (+ SIL if available)', 'max_days' => 3 + $silRem];
        $result['Bereavement Leave (Secondary Family)'] = ['eligible' => true, 'reason' => 'Up to 1 day (+ SIL if available)', 'max_days' => 1 + $silRem];

        // Paternity
        if ($isMale) {
            $pUsed = (float)$bal['paternity_used'];
            $pRem = self::PATERNITY_EXTENDED - $pUsed;
            $result['Paternity Leave'] = ['eligible' => $pRem > 0, 'reason' => $pRem > 0 ? $pRem.' days remaining' : 'Fully used', 'max_days' => max(0, $pRem)];
        } else {
            $result['Paternity Leave'] = ['eligible' => false, 'reason' => 'Male employees only', 'max_days' => 0];
        }

        // Maternity
        if ($isFemale) {
            $mUsed = (int)$bal['maternity_used'];
            $mRem = self::MATERNITY_DAYS - $mUsed;
            $result['Maternity Leave'] = ['eligible' => $mRem > 0, 'reason' => $mRem > 0 ? $mRem.' days remaining' : 'Fully used', 'max_days' => max(0, $mRem)];
        } else {
            $result['Maternity Leave'] = ['eligible' => false, 'reason' => 'Female employees only', 'max_days' => 0];
        }

        // Solo Parent
        $hasSoloId = !empty($emp['solo_parent_id']);
        $spUsed = (float)$bal['solo_parent_used'];
        $spRem = self::SOLO_PARENT_DAYS - $spUsed;
        if ($hasSoloId && $spRem > 0) {
            $result['Solo Parent Leave'] = ['eligible' => true, 'reason' => $spRem.' days remaining', 'max_days' => $spRem];
        } else {
            $result['Solo Parent Leave'] = ['eligible' => false, 'reason' => $hasSoloId ? 'Fully used' : 'Requires DSWD Solo Parent ID', 'max_days' => 0];
        }

        // Special Leave Women (RA 9710)
        if ($isFemale && !empty($emp['hire_date'])) {
            $sixMo = strtotime($emp['hire_date'] . ' +6 months');
            $swUsed = (int)$bal['special_women_used'];
            $swRem = self::SPECIAL_WOMEN_DAYS - $swUsed;
            if (time() >= $sixMo && $swRem > 0) {
                $result['Special Leave for Women Workers'] = ['eligible' => true, 'reason' => $swRem.' days remaining', 'max_days' => $swRem];
            } else {
                $result['Special Leave for Women Workers'] = ['eligible' => false, 'reason' => time() < $sixMo ? '6 months continuous service required' : 'Fully used', 'max_days' => 0];
            }
        } else {
            $result['Special Leave for Women Workers'] = ['eligible' => false, 'reason' => 'Female employees only', 'max_days' => 0];
        }

        // VAWC (RA 9262)
        $vUsed = (float)$bal['vawc_used'];
        $vRem = self::VAWC_DAYS - $vUsed;
        if ($vRem > 0) {
            $result['Violence Against Women and Children Leave'] = ['eligible' => true, 'reason' => $vRem.' days remaining', 'max_days' => $vRem];
        } else {
            $result['Violence Against Women and Children Leave'] = ['eligible' => false, 'reason' => 'Fully used', 'max_days' => 0];
        }

        // Leave Without Pay — always available
        $result['Leave Without Pay'] = ['eligible' => true, 'reason' => 'No credits deducted — requires supervisor approval', 'max_days' => 999];

        return $result;
    }

    /**
     * Deduct leave credits after an approved leave request.
     */
    public static function deductCredits(int $empId, string $leaveType, float $days, int $year = null): string {
        $year = $year ?? (int)date('Y');
        $bal = self::getBalance($empId, $year);
        $deductedFrom = $leaveType;

        if (in_array($leaveType, self::SIL_TYPES)) {
            DB::execute("UPDATE leave_balance SET sil_used = sil_used + ?, updated_at=? WHERE employee_id=? AND year=?", [$days, time(), $empId, $year]);
            $deductedFrom = 'SIL';
        } elseif ($leaveType === 'Birthday Leave') {
            DB::execute("UPDATE leave_balance SET birthday_used=1, updated_at=? WHERE employee_id=? AND year=?", [time(), $empId, $year]);
            $deductedFrom = 'Birthday';
        } elseif (strpos($leaveType, 'Bereavement') !== false) {
            DB::execute("UPDATE leave_balance SET bereavement_used = bereavement_used + ?, updated_at=? WHERE employee_id=? AND year=?", [$days, time(), $empId, $year]);
            $deductedFrom = 'Bereavement';
        } elseif ($leaveType === 'Paternity Leave') {
            DB::execute("UPDATE leave_balance SET paternity_used = paternity_used + ?, updated_at=? WHERE employee_id=? AND year=?", [$days, time(), $empId, $year]);
            $deductedFrom = 'Paternity';
        } elseif ($leaveType === 'Maternity Leave') {
            DB::execute("UPDATE leave_balance SET maternity_used = maternity_used + ?, updated_at=? WHERE employee_id=? AND year=?", [$days, time(), $empId, $year]);
            $deductedFrom = 'Maternity';
        } elseif ($leaveType === 'Solo Parent Leave') {
            DB::execute("UPDATE leave_balance SET solo_parent_used = solo_parent_used + ?, updated_at=? WHERE employee_id=? AND year=?", [$days, time(), $empId, $year]);
            $deductedFrom = 'Solo Parent';
        } elseif ($leaveType === 'Special Leave for Women Workers') {
            DB::execute("UPDATE leave_balance SET special_women_used = special_women_used + ?, updated_at=? WHERE employee_id=? AND year=?", [$days, time(), $empId, $year]);
            $deductedFrom = 'Special Women';
        } elseif (strpos($leaveType, 'Violence') !== false) {
            DB::execute("UPDATE leave_balance SET vawc_used = vawc_used + ?, updated_at=? WHERE employee_id=? AND year=?", [$days, time(), $empId, $year]);
            $deductedFrom = 'VAWC';
        } else {
            $deductedFrom = 'Without Pay';
        }

        return $deductedFrom;
    }

    /**
     * Reverse credit deduction (for undo action).
     */
    public static function reverseCredits(int $empId, string $deductedFrom, float $days, int $year = null): void {
        $year = $year ?? (int)date('Y');
        $map = [
            'SIL'           => 'sil_used',
            'Birthday'      => null,
            'Bereavement'   => 'bereavement_used',
            'Paternity'     => 'paternity_used',
            'Maternity'     => 'maternity_used',
            'Solo Parent'   => 'solo_parent_used',
            'Special Women' => 'special_women_used',
            'VAWC'          => 'vawc_used',
        ];

        if ($deductedFrom === 'Birthday') {
            DB::execute("UPDATE leave_balance SET birthday_used=0, updated_at=? WHERE employee_id=? AND year=?", [time(), $empId, $year]);
        } elseif (isset($map[$deductedFrom]) && $map[$deductedFrom]) {
            $col = $map[$deductedFrom];
            DB::execute("UPDATE leave_balance SET $col = GREATEST(0, $col - ?), updated_at=? WHERE employee_id=? AND year=?", [$days, time(), $empId, $year]);
        }
    }

    /**
     * Sync accrued SIL into the balance table.
     */
    public static function syncAccrued(int $empId, array $emp, ?int $year = null): void {
        $year = $year ?? (int)date('Y');
        $accrued = self::calculateAccrued($emp, $year);
        $bal = self::getBalance($empId, $year);
        if (isset($bal['sil_manual_override']) && $bal['sil_manual_override'] == 1) {
            return; // Skip sync if manually overridden
        }
        if ((float)$bal['sil_accrued'] != $accrued) {
            DB::execute("UPDATE leave_balance SET sil_accrued=?, updated_at=? WHERE employee_id=? AND year=?", [$accrued, time(), $empId, $year]);
        }
    }
}
