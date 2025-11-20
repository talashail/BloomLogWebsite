<?php
session_start();

// DB connection
$conn = new mysqli("127.0.0.1", "root", "root", "bloomlog");
if ($conn->connect_error) die("❌ DB connection failed");

// ========== Get user timezone dynamically ==========
$userID = $_SESSION['userid'] ?? null;

$timezone = "UTC"; // default fallback

if ($userID) {
    $stmt = $conn->prepare("SELECT city FROM users WHERE userid = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $city = strtolower(trim($result['city']));

        // Mapping database city → system timezone
        $timezoneMap = [
            "riyadh" => "Asia/Riyadh",
            "jeddah" => "Asia/Riyadh",
            "dubai" => "Asia/Dubai",
            "london" => "Europe/London",
            "new york" => "America/New_York",
            "tokyo" => "Asia/Tokyo"
        ];

        if (isset($timezoneMap[$city])) {
            $timezone = $timezoneMap[$city];
        }
    }
}

date_default_timezone_set($timezone);

// ========== Create backup folder ==========
$backupFolder = __DIR__ . "/backups";
if (!file_exists($backupFolder)) mkdir($backupFolder, 0777, true);

// backup filename → ONLY DATE no time
$date = date("Y-m-d");
$fileName = "backup_$date.sql";
$filePath = $backupFolder . "/" . $fileName;

// ========== mysqldump path ==========
$mysqldumpPath = '"C:\MAMP\bin\mysql\bin\mysqldump.exe"';

// Run command with no-tablespaces flag
$command = "$mysqldumpPath --user=root --password=root --host=127.0.0.1 --no-tablespaces bloomlog > \"$filePath\"";

exec($command, $output, $result);

if ($result === 0) {
    echo "✅ Backup created: $fileName";
} else {
    echo "❌ Backup failed. Check mysqldump path.";
}
?>
