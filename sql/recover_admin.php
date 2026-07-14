<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {

    // Check if admin already exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute(['admin']);

    if ($check->fetch()) {
        die("Admin account already exists.");
    }

    // Hash the password
    $password = password_hash('smoke25450', PASSWORD_DEFAULT);

    // Insert admin user
    $stmt = $conn->prepare("
        INSERT INTO users
        (fullname, username, password, role, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        'SmokeTech Administrator',
        'admin',
        $password,
        'Admin'
    ]);

    echo "<h2 style='color:green;'>✔ Administrator account created successfully.</h2>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> smoke25450</p>";
    echo "<hr>";
    echo "<p style='color:red;'><strong>Delete recover_admin.php immediately after logging in.</strong></p>";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>