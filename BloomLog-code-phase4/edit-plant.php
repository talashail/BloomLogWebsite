<?php
require_once "config.php";

session_start();

// 1️⃣ Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

// 2️⃣ Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "bloomlog";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 3️⃣ Get user_plant_id from URL
if (!isset($_GET['id'])) {
    header("Location: homepage.php");
    exit;
}
$userPlantID = $_GET['id'];
$userID = $_SESSION['userid'];

// 4️⃣ Fetch plant details using bind_result (safe for all servers)
$stmt = $conn->prepare("
    SELECT u.user_plant_id, u.user_id, u.nickname, u.last_watered_date, u.next_watered_date, u.notes,
           c.plantid, c.plantName, c.wateringfrequency, c.image_path, c.plant_Info
    FROM userplants u
    JOIN plantcatalog c ON u.plant_catalog_id = c.plantid
    WHERE u.user_plant_id = ? AND u.user_id = ?
");
$stmt->bind_param("ii", $userPlantID, $userID);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $_SESSION['message'] = "⚠ Plant not found.";
    $_SESSION['messageType'] = "error";
    header("Location: homepage.php");
    exit;
}

$stmt->bind_result($user_plant_id, $user_id, $nickname, $last_watered_date, $next_watered_date, $notes,
                   $plantid, $plantName, $wateringfrequency, $image_path, $plant_Info);
$stmt->fetch();
$plant = [
    'user_plant_id' => $user_plant_id,
    'user_id' => $user_id,
    'nickname' => $nickname,
    'last_watered_date' => $last_watered_date,
    'next_watered_date' => $next_watered_date,
    'notes' => $notes,
    'plantid' => $plantid,
    'plantName' => $plantName,
    'wateringfrequency' => $wateringfrequency,
    'image_path' => $image_path,
    'plant_Info' => $plant_Info
];

// 5️⃣ Handle form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nickname_new = trim($_POST['nickname']);
    $last_watered_new = $_POST['last_watered'];
    $notes_new = trim($_POST['notes']);

    // Validation
    if (empty($nickname_new)) {
        $error = "⚠ Nickname is required.";
    } elseif (!preg_match("/^[a-zA-Z0-9\s]+$/", $nickname_new)) {
        $error = "⚠ Nickname can only contain letters and numbers.";
    } elseif (!empty($notes_new) && !preg_match("/^[a-zA-Z0-9\s]+$/", $notes_new)) {
        $error = "⚠ Notes can only contain letters and numbers.";
    } elseif (empty($last_watered_new)) {
        $error = "⚠ Please select the last watered date.";
    }

    // If validation failed → stop here
    if (empty($error)) {

        // Calculate next_watered_date
        $next_watered_new = date(
            'Y-m-d',
            strtotime("+{$plant['wateringfrequency']} days", strtotime($last_watered_new))
        );

        // Update plant
        $update = $conn->prepare("
            UPDATE userplants
            SET nickname = ?, last_watered_date = ?, next_watered_date = ?, notes = ?
            WHERE user_plant_id = ? AND user_id = ?
        ");
        $update->bind_param(
            "ssssii",
            $nickname_new,
            $last_watered_new,
            $next_watered_new,
            $notes_new,
            $userPlantID,
            $userID
        );

        if ($update->execute()) {
            header("Location: view-plant.php?id=$userPlantID");
            exit;
        } else {
            $error = "⚠ Failed to update plant. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BloomLog - Edit Plant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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

<div class="form-header">
    <h2>Edit Plant</h2>
    <a href="view-plant.php?id=<?= $userPlantID ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Plant</a>
</div>

<?php if ($error): ?>


<div class="message-box error"><?= $error ?></div>


<?php endif; ?>

<div class="plant-detail-container">


<!-- Plant Image -->
<div class="plant-detail-image card">
    <img src="<?= htmlspecialchars($plant['image_path']); ?>" alt="<?= htmlspecialchars($plant['plantName']); ?>">
</div>

<!-- Plant Info + Edit Form -->
<div class="plant-detail-info card">
    <form method="POST">

        <h1>
            <input type="text" name="nickname" value="<?= htmlspecialchars($plant['nickname']); ?>" required 
                   style="font-size:1.5em; width:100%; border:none; border-bottom:1px solid #ccc; padding:4px;">
        </h1>
        <p class="plant-species"><?= htmlspecialchars($plant['plantName']); ?></p>

        <div class="detail-item"><strong>Added:</strong> <?= date("M d, Y", strtotime($plant['last_watered_date'])); ?></div>
        <div class="detail-item"><strong>Watering:</strong> Every <?= $plant['wateringfrequency']; ?> days</div>

        <div class="form-group">
            <label for="last_watered"><strong>Last Watered:</strong></label>
            <input type="date" id="last_watered" name="last_watered" value="<?= $plant['last_watered_date']; ?>" required>
        </div>

        <div class="form-group">
            <label for="notes"><strong>Care Notes (Optional):</strong></label>
            <textarea id="notes" name="notes" rows="4"><?= htmlspecialchars($plant['notes']); ?></textarea>
        </div>

        <div class="detail-item"><strong>Next Watering:</strong> <?= date("M d, Y", strtotime($plant['next_watered_date'])); ?></div>

        <h3>Care Instructions</h3>
        <p><?= nl2br($plant['plant_Info']); ?></p>

        <button type="submit" class="btn">Update Plant</button>

    </form>
</div>


</div>

</section>

<footer>
    <div class="container">
        <p style="text-align:center;margin-top:30px;">© BloomLog 2025. All rights reserved.</p>
    </div>
</footer>

</body>
</html>
