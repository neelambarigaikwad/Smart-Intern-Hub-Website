
<?php
require_once __DIR__ . "/config/db.php";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h1>SmartInternHub Database Connected!</h1>";
    echo "<p>Tables found: " . count($tables) . "</p>";

    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table, ENT_QUOTES, "UTF-8") . "</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo "Unable to verify database tables.";
}
?>
