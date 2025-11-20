<?php
require_once "config.php";

session_start();

// Prevent access if user not logged in
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

// Message system (from session)
$message = $_SESSION['message'] ?? "";
$messageType = $_SESSION['messageType'] ?? "";
unset($_SESSION['message'], $_SESSION['messageType']); // clear after use

$host = "localhost";
$user = "root";
$pass = "root";
$db   = "bloomlog";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    $message = "⚠ Database connection failed.";
    $messageType = "error";
}

// Get plant ID from URL
if (!isset($_GET['id'])) {
    header("Location: homepage.php");
    exit();
}

$userPlantID = $_GET['id'];
$userID = $_SESSION['userid'];

// Fetch plant details
$sql = $conn->prepare("
    SELECT u.*, c.plantName, c.image_path, c.wateringfrequency, c.mintemperature, c.maxtemperature, 
           c.minhumidity, c.maxhumidity, c.plant_Info
    FROM userplants u
    JOIN plantcatalog c ON u.plant_catalog_id = c.plantid
    WHERE u.user_plant_id = ? AND u.user_id = ?
");
$sql->bind_param("ii", $userPlantID, $userID);
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "⚠ Plant not found.";
    $_SESSION['messageType'] = "error";
    header("Location: homepage.php");
    exit();
}

$plant = $result->fetch_assoc();

// Status calculation
$today = strtotime(date('Y-m-d'));
$nextWater = strtotime($plant['next_watered_date']);
$daysLeft = ceil(($nextWater - $today) / 86400);

$status = $daysLeft <= 0 ? "Needs Watering" : "Well Watered";
$statusClass = $daysLeft <= 0 ? "needs-water" : "watered";

// --- Water Action ---
if (isset($_POST['water'])) {

    // Prevent watering if already watered
    if ($status === "Well Watered") {
        $_SESSION['message'] = "✨ Already watered — no action needed.";
        $_SESSION['messageType'] = "error";
        header("Location: view-plant.php?id=$userPlantID");
        exit();
    }

    $today = date('Y-m-d');
    $next = date('Y-m-d', strtotime("+{$plant['wateringfrequency']} days"));

    $update = $conn->prepare("UPDATE userplants SET last_watered_date=?, next_watered_date=? WHERE user_plant_id=?");
    $update->bind_param("ssi", $today, $next, $userPlantID);

    if ($update->execute()) {
        $_SESSION['message'] = "💧 Watering updated successfully!";
        $_SESSION['messageType'] = "success";
    } else {
        $_SESSION['message'] = "⚠ Error watering plant.";
        $_SESSION['messageType'] = "error";
    }

    header("Location: view-plant.php?id=$userPlantID");
    exit();
}

// --- Delete Action ---
if (isset($_POST['delete'])) {
    $delete = $conn->prepare("DELETE FROM userplants WHERE user_plant_id=? AND user_id=?");
    $delete->bind_param("ii", $userPlantID, $userID);

    if ($delete->execute()) {
        header("Location: homepage.php"); // message shows only in homepage
        exit();
    } else {
        $_SESSION['message'] = "⚠ Cannot delete plant.";
        $_SESSION['messageType'] = "error";
        header("Location: view-plant.php?id=$userPlantID");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BloomLog - Plant Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

<style>

.message-box {
    padding: 14px;
    border-radius: 10px;
    margin: 15px auto;
    width: 90%;
    max-width: 450px;
    font-weight: 600;
    text-align: center;
    font-size: 15px;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.12);
    animation: fadeIn 0.6s ease-out;
}

.success {
    background: #dfffe1;
    color: #206a2a;
    border-left: 6px solid #2ecc71;
}

.error {
    background: #ffe1e1;
    color: #b30000;
    border-left: 6px solid #e74c3c;
}

.disabled-btn {
    background: #cccccc !important;
    cursor: not-allowed;
    opacity: 0.6;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

</style>
</head>


<body>

<header>
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <span class="logo-icon"><img src="image/logo.PNG" width="130"></span>
                <h1>BloomLog</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>


<section class="container">

    <?php if (!empty($message)): ?>
        <div id="system-message" class="message-box <?= $messageType ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>


    <div class="form-header">
        <h2>Plant Details</h2>
        <a href="homepage.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Garden</a>
    </div>

    <div class="plant-detail-container">

        <div class="plant-detail-image card">
             <img src="<?= $plant['image_path']; ?>" alt="<?= $plant['plantName']; ?>">
        </div>

        <div class="plant-detail-info card">

            <h1><?= htmlspecialchars($plant['nickname']); ?></h1>
            <p class="plant-species"><?= htmlspecialchars($plant['plantName']); ?></p>

            <div class="detail-item"><strong>Added:</strong> <?= date("M d, Y", strtotime($plant['date_added'])); ?></div>
            <div class="detail-item"><strong>Watering:</strong> Every <?= $plant['wateringfrequency']; ?> days</div>
            <div class="detail-item"><strong>Last watered:</strong> <?= date("M d, Y", strtotime($plant['last_watered_date'])); ?></div>
            <div class="detail-item"><strong>Next watering:</strong> <?= date("M d, Y", strtotime($plant['next_watered_date'])); ?></div>

            <div class="detail-item">
                <strong>Status:</strong>
                <span class="watering-status <?= $statusClass; ?>"><?= $status ?></span>
            </div>

            <h3>Notes</h3>
            <p><?= $plant['notes'] ?: "No notes added." ?></p>

            <h3>Care Instructions</h3>
            <p><?= nl2br($plant['plant_Info']); ?></p>

            <div class="plant-detail-actions">

                <a href="edit-plant.php?id=<?= $userPlantID ?>" class="btn btn-secondary">Edit</a>

                <form method="POST">
                    <button class="btn <?= $status === 'Well Watered' ? 'disabled-btn' : '' ?>" 
                            name="water" 
                            <?= $status === 'Well Watered' ? 'disabled' : '' ?>>
                        Water Now
                    </button>
                </form>

                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this plant?');">
                    <button class="btn btn-accent" name="delete">Delete</button>
                </form>

            </div>

        </div>
    </div>
</section>


<footer>
    <div class="container">
        <p style="text-align:center;margin-top:30px;">© BloomLog 2025. All rights reserved.</p>
    </div>
</footer>


<script>
// Scroll directly to the message area if it exists
let box = document.getElementById("system-message");
if(box){
    box.scrollIntoView({ behavior: "smooth", block: "center" });

    // auto fade-out
    setTimeout(()=> box.style.opacity = "0", 2500);
}
</script>

</body>
</html>
