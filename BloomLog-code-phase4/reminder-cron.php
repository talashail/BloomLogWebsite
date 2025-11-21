<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

$conn = new mysqli("localhost", "root", "root", "bloomlog");

if ($conn->connect_error) die("❌ DB connection error");

// ===== City → Timezone mapping =====
$timezoneMap = [
    "riyadh" => "Asia/Riyadh",
    "jeddah" => "Asia/Riyadh",
    "dubai" => "Asia/Dubai",
    "london" => "Europe/London",
    "new york" => "America/New_York",
    "tokyo" => "Asia/Tokyo"
];

// ===== Reminder Log File =====
$logFile = "reminder_log.json";
$log = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];

// ===== Fetch Users =====
$userQuery = $conn->query("SELECT userid, name, email, city FROM users");

while ($user = $userQuery->fetch_assoc()) {

    $userID = $user["userid"];
    $email = $user["email"];
    $username = $user["name"];
    $city = strtolower(trim($user["city"]));

    // 1) Apply timezone based on user's stored city
    $timezone = $timezoneMap[$city] ?? "UTC";
    date_default_timezone_set($timezone);

    // 2) Get today based on user's timezone
    $today = date("Y-m-d");

    // ensure log array exists for today
    if (!isset($log[$today])) $log[$today] = [];

    // 3) Get plants due today
    $plantQuery = $conn->prepare("
        SELECT user_plant_id, nickname 
        FROM userplants 
        WHERE user_id = ? AND next_watered_date = ?
    ");
    $plantQuery->bind_param("is", $userID, $today);
    $plantQuery->execute();
    $plants = $plantQuery->get_result();

    $plantList = "";
    $sendEmail = false;

    while ($plant = $plants->fetch_assoc()) {
        $id = $plant['user_plant_id'];

        // Skip if already sent today
        if (in_array($id, $log[$today])) continue;

        $sendEmail = true;
        $plantList .= "🌿 <strong>{$plant['nickname']}</strong><br>";

        // mark reminder as sent
        $log[$today][] = $id;
    }

    if (!$sendEmail) continue;

    // ===== Send Email using Gmail SMTP =====
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com"; 
        $mail->SMTPAuth = true;
        $mail->Username = "alanoudkh16@gmail.com";
        $mail->Password = "snpv zbcd honh jghy"; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = "UTF-8";

        $mail->setFrom($mail->Username, "BloomLog Reminder");
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "💧 Watering Reminder — $today";

        $mail->Body = "
            <div style='font-family:Arial;padding:20px;background:#f6fff6;border-radius:10px;'>
                <h2>Hello $username 👋</h2>
                <p>The following plants need watering today based on your region (<strong>$city</strong>):</p>
                <p style='font-size:18px;'>$plantList</p>
                <p style='margin-top:20px'>💚 Keep your plants hydrated!</p>
            </div>
        ";

        $mail->send();

    } catch (Exception $e) {
        echo "❌ Failed: {$mail->ErrorInfo}<br>";
    }
}

// save updated log
file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT));

echo "📩 Reminder check complete.";

?>
