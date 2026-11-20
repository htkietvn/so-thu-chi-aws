<?php
// Test database connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Check config file
$configFile = dirname(__DIR__).'/config/database.json';
echo "<p><strong>Config file:</strong> $configFile</p>";

if (!file_exists($configFile)) {
    die("<p style='color:red'>ERROR: Config file does not exist!</p>");
}

echo "<p style='color:green'>✓ Config file exists</p>";

// Read config
$config = json_decode(file_get_contents($configFile), true);
echo "<p><strong>Config:</strong></p>";
echo "<pre>";
print_r([
    'host' => $config['host'],
    'database' => $config['database'],
    'username' => $config['username'],
    'password' => str_repeat('*', strlen($config['password']))
]);
echo "</pre>";

// Test connection
try {
    $dsn = "mysql:host={$config['host']};port=3306;dbname={$config['database']};charset=utf8mb4";
    echo "<p><strong>DSN:</strong> $dsn</p>";
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    echo "<p style='color:green; font-size:20px'><strong>✓ CONNECTION SUCCESS!</strong></p>";
    
    // Test query
    $result = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
    echo "<p>Number of users in database: <strong>{$result['count']}</strong></p>";
    
    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Tables in database:</strong></p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Check pdo_mysql extension
    echo "<p style='color:green'>✓ pdo_mysql extension: LOADED</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'><strong>✗ CONNECTION FAILED!</strong></p>";
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    
    // Check extension
    if (!extension_loaded('pdo_mysql')) {
        echo "<p style='color:red'>✗ pdo_mysql extension NOT loaded!</p>";
    }
}

echo "<hr>";
echo "<p><a href='/'>← Back to app</a></p>";
