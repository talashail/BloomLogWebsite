<?php
session_start();

 //1️⃣ Ensure user is logged in
  if (!isset($_SESSION['userid'])) {
     header("Location: index.php");
      exit();
}

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "bloomlog";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get logged-in user ID
$userID = $_SESSION['userid'];

// 2️⃣ Fetch user info (temperature and humidity)
$userQuery = "SELECT temperature, humidity FROM users WHERE userid = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userID);
$stmt->execute();
$userResult = $stmt->get_result();
$userData = $userResult->fetch_assoc();
$userTemp = $userData['temperature'];
$userHumidity = $userData['humidity'];

// 3️⃣ Fetch plants matching user’s environment
$plantQuery = "
    SELECT * FROM plantcatalog 
    WHERE ? BETWEEN mintemperature AND maxtemperature
      AND ? BETWEEN minhumidity AND maxhumidity
";
$stmt = $conn->prepare($plantQuery);
$stmt->bind_param("dd", $userTemp, $userHumidity);
$stmt->execute();
$plants = $stmt->get_result();

// ✅ Check if there are any matching plants
if ($plants->num_rows === 0) {
    echo "<script>
        alert('Sorry, there are no plants that suit your environment.');
        window.location.href = 'homepage.php';
    </script>";
    exit();
}


// 4️⃣ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname']);
    $plantID = $_POST['plant_type'] ?? null;
    $notes = trim($_POST['notes']);

    // Check required fields
    if (empty($nickname) || empty($plantID)) {
        echo "<script>alert('Please fill in all required fields.');</script>";
    } elseif (!preg_match("/^[a-zA-Z0-9\s]+$/", $nickname)) {
        echo "<script>alert('Nickname can only contain letters and numbers.');</script>";
    } elseif (!empty($notes) && !preg_match("/^[a-zA-Z0-9\s]+$/", $notes)) {
        echo "<script>alert('Notes can only contain letters and numbers.');</script>";
    } else {
        // Get watering frequency for selected plant
        $freqQuery = "SELECT wateringfrequency FROM plantcatalog WHERE plantid = ?";
        $stmt = $conn->prepare($freqQuery);
        $stmt->bind_param("i", $plantID);
        $stmt->execute();
        $freqResult = $stmt->get_result()->fetch_assoc();
        $wateringFreq = (int)$freqResult['wateringfrequency'];

        // Calculate dates
        $today = date('Y-m-d');
        $nextWaterDate = date('Y-m-d', strtotime("+$wateringFreq days"));

        // Insert into userplants
        $insertQuery = "INSERT INTO userplants (user_id, plant_catalog_id, nickname, notes, last_watered_date, next_watered_date, date_added)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iisssss", $userID, $plantID, $nickname, $notes, $today, $nextWaterDate, $today);
        $stmt->execute();

        // Redirect to homepage
        //header("Location: homepage.php");
        echo "<script>
            alert('Plant added successfully!');
            setTimeout(function() {
            window.location.href = 'homepage.php';
             }, 1500); // 1.5 seconds delay
           </script>";

        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BloomLog - Add Plant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .container { width: 90%; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .plant-selection-container { max-width: 800px; margin: 30px auto; }
        .plant-option { display: flex; align-items: center; padding: 20px; border: 2px solid var(--light-gray);
            border-radius: 16px; margin-bottom: 20px; cursor: pointer; transition: all 0.3s ease; }
        .plant-option:hover { border-color: var(--primary-light); background-color: rgba(76, 175, 80, 0.05); }
        .plant-option.selected { border-color: var(--primary); background-color: rgba(76, 175, 80, 0.1); }
        .plant-option input[type="radio"] { margin-right: 20px; transform: scale(1.5); }
        .plant-icon { margin-right: 20px; width: 80px; }
        .plant-details-text { flex: 1; }
        .plant-name { font-size: 20px; font-weight: bold; margin-bottom: 5px; color: var(--primary-dark); }
        .plant-description { color: var(--gray); margin-bottom: 10px; }
        .plant-care-info { display: flex; gap: 15px; font-size: 14px; color: var(--gray); }
        .care-item { display: flex; align-items: center; }
        .care-item i { margin-right: 5px; color: var(--primary); }
    </style>
</head>
<body>
<header>
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <span class="logo-icon"><img src="image/logo.PNG" alt="bloomlog logo" width="130" height="100"></span>
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
    <div class="plant-selection-container">
        <div class="form-header">
            <h2>Add a New Plant</h2>
            <a href="homepage.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to HomePage</a>
        </div>
        <div class="card">
            <p class="plant-selection-description">Choose a plant that suits your home environment.</p>

            <form id="add-plant-form" method="POST">
                <div class="form-group">
                    <label for="plant-nickname">Plant Nickname *</label>
                    <input type="text" id="plant-nickname" name="nickname" required>
                </div>

                <h3>Select a Plant Type</h3>
                <?php while ($row = $plants->fetch_assoc()): ?>
                    <div class="plant-option">
                        <input type="radio" name="plant_type" value="<?php echo $row['plantid']; ?>" required>
                        <img src="<?php echo $row['image_path']; ?>" alt="<?php echo $row['plantName']; ?>" class="plant-icon">
                        <div class="plant-details-text">
                            <div class="plant-name"><?php echo htmlspecialchars($row['plantName']); ?></div>
                            <div class="plant-description"><?php echo htmlspecialchars($row['plant_summary']); ?></div>
                            <div class="plant-care-info">
                                <div class="care-item">
                                    <i class="fas fa-tint"></i> Every <?php echo $row['wateringfrequency']; ?> days
                                </div>
                                <div class="care-item">
                                    <i class="fas fa-thermometer-half"></i>
                                    <?php echo $row['mintemperature'] . '°C - ' . $row['maxtemperature'] . '°C'; ?>
                                </div>
                                <div class="care-item">
                                    <i class="fas fa-water"></i>
                                    <?php echo $row['minhumidity'] . '% - ' . $row['maxhumidity'] . '%'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

                <div class="form-group">
                    <label for="plant-notes">Care Notes (Optional)</label>
                    <textarea id="plant-notes" name="notes" rows="3" placeholder="Add any special care instructions or notes about your plant..."></textarea>
                </div>
                <input type="submit" value="Add Plant" class="btn">
            </form>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>BloomLog</h3>
                <p>Your personal plant management assistant.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul><li><a href="homepage.php"><i class="fas fa-home"></i> Home</a></li></ul>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <ul>
                    <li><a href="#"><i class="fas fa-envelope"></i> info@BloomLog.com</a></li>
                    <li><a href="#"><i class="fas fa-phone"></i> (123) 456-7890</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 BloomLog. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
document.querySelectorAll('.plant-option').forEach(option => {
    option.addEventListener('click', function() {
        const radio = this.querySelector('input[type="radio"]');
        radio.checked = true;
        document.querySelectorAll('.plant-option').forEach(opt => opt.classList.remove('selected'));
        this.classList.add('selected');
    });
});
</script>
</body>
</html>

