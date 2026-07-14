<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function db()
{
    return getDB();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header("Location: /smoketech_inventory/login.php");
        exit;
    }
}

function requireRole(array $roles): void
{
    requireLogin();

    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit("Access Denied");
    }
}

function attemptLogin(string $username, string $password): bool
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT id,
               fullname,
               username,
               password,
               role
        FROM users
        WHERE username = ?
        LIMIT 1
    ");

    $stmt->execute([$username]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return false;
    }

    if (!password_verify($password, $user['password'])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['role'] = $user['role'];

    // Record login in audit log if the table exists
    try {

        $audit = $pdo->prepare("
            INSERT INTO system_audit_log
            (
                user_id,
                action,
                target_table,
                target_id,
                description,
                ip_address
            )
            VALUES
            (
                ?,
                'LOGIN',
                'users',
                ?,
                'User logged into the system',
                ?
            )
        ");

        $audit->execute([
            $user['id'],
            $user['id'],
            $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);

    } catch (Exception $e) {
        // Ignore if audit table is unavailable
    }

    return true;
}

function logout(): void
{
    if (isLoggedIn()) {

        try {

            $pdo = db();

            $audit = $pdo->prepare("
                INSERT INTO system_audit_log
                (
                    user_id,
                    action,
                    target_table,
                    target_id,
                    description,
                    ip_address
                )
                VALUES
                (
                    ?,
                    'LOGOUT',
                    'users',
                    ?,
                    'User logged out',
                    ?
                )
            ");

            $audit->execute([
                $_SESSION['user_id'],
                $_SESSION['user_id'],
                $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
            ]);

        } catch (Exception $e) {
            // Ignore
        }

    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );

    }

    session_destroy();
}

function currentUser(): array
{
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'fullname' => $_SESSION['fullname'] ?? null,
        'role' => $_SESSION['role'] ?? null,
    ];
}

function isAdmin(): bool
{
    return ($_SESSION['role'] ?? '') === 'Admin';
}

function isManager(): bool
{
    return ($_SESSION['role'] ?? '') === 'Manager';
}

function isCashier(): bool
{
    return ($_SESSION['role'] ?? '') === 'Cashier';
}

function isTechnician(): bool
{
    return ($_SESSION['role'] ?? '') === 'Technician';
}