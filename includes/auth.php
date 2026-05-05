<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

class Auth {
    public static function check(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }
        // Re-verify employee is not terminated (catches mid-session terminations)
        $empId = $_SESSION['employee_id'] ?? null;
        if ($empId) {
            $empStatus = DB::fetchScalar("SELECT status FROM employee WHERE id = ?", [$empId]);
            if ($empStatus && in_array($empStatus, ['Terminated', 'Resigned', 'Inactive'])) {
                // Log forced logout
                DB::execute("INSERT INTO audit_log (user_id, action, module, data, ip, created_at) VALUES (?, 'Forced Logout', 'Auth', ?, ?, ?)", [
                    $_SESSION['user_id'],
                    'Account blocked: employee status is ' . $empStatus,
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    time()
                ]);
                session_destroy();
                header('Location: ' . BASE_URL . '/index.php?page=login&blocked=1');
                exit;
            }
        }
    }

    public static function requireRole(array $roles): void {
        self::check();
        if (!in_array($_SESSION['role'], $roles)) {
            header('Location: ' . BASE_URL . '/index.php?page=dashboard');
            exit;
        }
    }

    public static function login(string $email, string $password): bool {
        $user = DB::fetchOne(
            "SELECT u.*, e.id as employee_id, e.status as emp_status
             FROM `user` u
             LEFT JOIN employee e ON e.user_id = u.id
             WHERE u.email = ? AND u.status = 'Active'",
            [$email]
        );
        if (!$user || $user['password'] !== md5($password)) {
            return false;
        }
        // Block terminated / resigned / inactive employees
        if ($user['emp_status'] && in_array($user['emp_status'], ['Terminated', 'Resigned', 'Inactive'])) {
            return false;
        }
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['username']    = $user['username'];
        $_SESSION['email']       = $user['email'];
        $_SESSION['role']        = $user['role'];
        $_SESSION['dept_id']     = $user['department_id'];
        $_SESSION['employee_id'] = $user['employee_id'];
        DB::execute("INSERT INTO audit_log (user_id, action, module, ip, created_at) VALUES (?, 'Login', 'Auth', ?, ?)", [
            $user['id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', time()
        ]);
        return true;
    }

    public static function audit(string $action, string $module, ?int $modelId = null, ?string $data = null): void {
        try {
            DB::execute(
                "INSERT INTO audit_log (user_id,action,module,model_id,data,ip,created_at) VALUES (?,?,?,?,?,?,?)",
                [self::id() ?? null, $action, $module, $modelId, $data, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', time()]
            );
        } catch (\Exception $e) { /* fail silently — never let logging break the app */ }
    }

    public static function logout(): void {
        if (isset($_SESSION['user_id'])) {
            DB::execute("INSERT INTO audit_log (user_id, action, module, ip, created_at) VALUES (?, 'Logout', 'Auth', ?, ?)", [
                $_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', time()
            ]);
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php?page=login');
        exit;
    }

    public static function id(): ?int    { return $_SESSION['user_id'] ?? null; }
    public static function role(): string { return $_SESSION['role'] ?? ''; }
    public static function empId(): ?int  { return $_SESSION['employee_id'] ?? null; }
    public static function deptId(): ?int { return $_SESSION['dept_id'] ?? null; }
    public static function name(): string { return $_SESSION['username'] ?? ''; }
    public static function email(): string { return $_SESSION['email'] ?? ''; }
    public static function isAdmin(): bool { return ($_SESSION['role'] ?? '') === 'Super Admin'; }
    public static function isManager(): bool { return ($_SESSION['role'] ?? '') === 'Manager'; }
    public static function isEmployee(): bool { return ($_SESSION['role'] ?? '') === 'Employee'; }

    public static function flash(string $type, string $msg): void {
        $_SESSION['flash'][$type] = $msg;
    }
    public static function getFlashes(): array {
        $f = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $f;
    }

    public static function url(string $page, array $params = []): string {
        $q = array_merge(['page' => $page], $params);
        return BASE_URL . '/index.php?' . http_build_query($q);
    }
}

if (!function_exists('e')) {
    function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
if (!function_exists('url')) {
    function url(string $page, array $params = []): string { return Auth::url($page, $params); }
}
