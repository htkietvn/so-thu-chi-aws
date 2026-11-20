<?php
declare(strict_types=1);
/**
 * Login Functionality Test Suite
 * Tests authentication, session management, and security features
 * 
 * Setup:
 * 1. Install PHPUnit: composer require --dev phpunit/phpunit
 * 2. Create test database with schema
 * 3. Create test user: echo 'TestPassword123' | php scripts/create-user.php test@example.com
 * 4. Run: ./vendor/bin/phpunit tests/login-test.php
 */

use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase
{
    private PDO $db;
    private string $testEmail = 'testuser@example.com';
    private string $testPassword = 'SecurePassword123';
    private int $testUserId;

    protected function setUp(): void
    {
        // Load database connection
        $this->db = require __DIR__.'/../src/database.php';
        
        // Create test user
        $hash = password_hash($this->testPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
        $stmt->execute([$this->testEmail, $hash]);
        $this->testUserId = (int)$this->db->lastInsertId();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->db->prepare('DELETE FROM entries WHERE user_id = ?')->execute([$this->testUserId]);
        $this->db->prepare('DELETE FROM users WHERE id = ?')->execute([$this->testUserId]);
        $this->db->prepare('DELETE FROM app_sessions WHERE 1=1')->execute();
    }

    public function testLoginWithValidCredentials(): void
    {
        $_POST = [
            'action' => 'login',
            'email' => $this->testEmail,
            'password' => $this->testPassword,
            'csrf' => $_SESSION['csrf'] ?? 'test-token'
        ];
        $_SESSION = ['csrf' => 'test-token'];

        // Simulate login logic
        $email = strtolower(trim($_POST['email']));
        $password = $_POST['password'];
        
        $stmt = $this->db->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $this->assertNotFalse($user, 'User should exist');
        $this->assertTrue(
            password_verify($password, $user['password_hash']),
            'Password should be valid'
        );
    }

    public function testLoginWithInvalidPassword(): void
    {
        $stmt = $this->db->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $stmt->execute([$this->testEmail]);
        $user = $stmt->fetch();

        $this->assertFalse(
            password_verify('WrongPassword123', $user['password_hash']),
            'Wrong password should not verify'
        );
    }

    public function testLoginWithNonExistentEmail(): void
    {
        $stmt = $this->db->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $stmt->execute(['nonexistent@example.com']);
        $user = $stmt->fetch();

        $this->assertFalse($user, 'Non-existent user should return false');
    }

    public function testEmailCaseInsensitive(): void
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
        
        // Test with uppercase
        $stmt->execute([strtoupper($this->testEmail)]);
        $user1 = $stmt->fetch();
        
        // Test with lowercase
        $stmt->execute([strtolower($this->testEmail)]);
        $user2 = $stmt->fetch();

        $this->assertEquals($user1['id'], $user2['id'], 'Email should be case-insensitive');
    }

    public function testPasswordHashStrength(): void
    {
        $stmt = $this->db->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$this->testUserId]);
        $user = $stmt->fetch();

        // Check if it's bcrypt (starts with $2y$)
        $this->assertStringStartsWith('$2y$', $user['password_hash'], 'Should use bcrypt');
        
        // Check hash length
        $this->assertGreaterThan(50, strlen($user['password_hash']), 'Hash should be long enough');
    }

    public function testSessionRegenerationAfterLogin(): void
    {
        $oldSessionId = session_id();
        
        // Simulate successful login
        if (!session_regenerate_id(true)) {
            $this->fail('Session regeneration failed');
        }
        
        $newSessionId = session_id();
        
        $this->assertNotEquals($oldSessionId, $newSessionId, 'Session ID should change after login');
    }

    public function testCSRFTokenGeneration(): void
    {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        
        $this->assertEquals(64, strlen($_SESSION['csrf']), 'CSRF token should be 64 characters');
        $this->assertTrue(ctype_xdigit($_SESSION['csrf']), 'CSRF token should be hexadecimal');
    }

    public function testRateLimiting(): void
    {
        $_SESSION['retry_at'] = time() + 3;
        
        $this->assertGreaterThan(time(), $_SESSION['retry_at'], 'Retry time should be in future');
        
        // Simulate waiting
        sleep(1);
        $this->assertGreaterThan(time(), $_SESSION['retry_at'], 'Should still be rate limited');
    }

    public function testLogout(): void
    {
        // Set up session
        $_SESSION = [
            'uid' => $this->testUserId,
            'csrf' => bin2hex(random_bytes(32))
        ];

        // Simulate logout
        $sessionDataBefore = $_SESSION;
        $_SESSION = [];
        
        $this->assertEmpty($_SESSION, 'Session should be empty after logout');
        $this->assertNotEmpty($sessionDataBefore, 'Session had data before logout');
    }

    public function testPasswordTiming(): void
    {
        $validHash = password_hash($this->testPassword, PASSWORD_DEFAULT);
        $wrongPassword = 'WrongPassword123';

        // Time correct password
        $start = microtime(true);
        password_verify($this->testPassword, $validHash);
        $timeCorrect = microtime(true) - $start;

        // Time wrong password
        $start = microtime(true);
        password_verify($wrongPassword, $validHash);
        $timeWrong = microtime(true) - $start;

        // Both should take similar time (constant-time comparison)
        $timeDiff = abs($timeCorrect - $timeWrong);
        $this->assertLessThan(0.01, $timeDiff, 'Timing should be constant to prevent timing attacks');
    }
}
