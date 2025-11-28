<?php


session_start();

// Prevent anyone from entering the page if NOT logged in


// ====== DATABASE CONNECTION ======
$host = "localhost";
$user = "root"; 
$pass = "root";
$db   = "bloomlog";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Database Connection Error: " . mysqli_connect_error());
}
function getWeatherData($city) {
    $apiKey = "7cb722d142511044ba2ed72897a0b183";
    $url = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric";

    $response = file_get_contents($url);
    if (!$response) {
        return ["temp" => 25.0, "humidity" => 50.0]; // fallback
    }

    $data = json_decode($response, true);

    return [
        "temp" => $data["main"]["temp"],
        "humidity" => $data["main"]["humidity"]
    ];
}

// ===================================================
//                      LOGIN SECTION
// ===================================================
$login_error = "";

// في قسم LOGIN - غير جزء الـ header فقط
if (isset($_POST["login"])) {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // 1. Prepare SELECT
    $sql = $conn->prepare("SELECT * FROM users WHERE email = ? OR name = ?");
    $sql->bind_param("ss", $username, $username);
    $sql->execute();
    $result = $sql->get_result();

    // 2. Check user
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row["password"])) {
            // 3. Store session
            $_SESSION["userid"] = $row["userid"];
            $_SESSION["username"] = $row["name"];
            $_SESSION["city"] = $row["city"];
            $_SESSION["user_role"] = $row["role"];

            // 4. Redirect based on email
            if ($row["email"] == "admin@ploomlog.com") {
                header("Location: admin.php");
            } else {
                header("Location: homepage.php");
            }
            exit;

        } else {
            $login_error = "Password is incorrect.";
        }
    } else {
        $login_error = "User not found.";
    }
}
// ===================================================
//                    SIGNUP SECTION
// ===================================================
$signup_error = "";
$signup_success = "";

if (isset($_POST["signup"])) {

    $fullname  = trim($_POST["fullname"]);
    $email     = trim($_POST["email"]);
    $username  = trim($_POST["new_username"]);
    $password  = password_hash($_POST["new_password"], PASSWORD_DEFAULT);
    $city      = trim($_POST["city"]);

    // Default values (your DB requires them)
   $weather = getWeatherData($city);
$humidity = $weather["humidity"];
$temp = $weather["temp"];
    $createdAt = date("Y-m-d");

    // 1. Check if email exists
    $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $exists = $check->get_result();

    if (mysqli_num_rows($exists) > 0) {
        $signup_error = "Email already exists.";
    } else {

        // 2. Insert new user
        $insert = $conn->prepare("
            INSERT INTO users (name, email, password, humidity, city, createdAt, temperature)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $insert->bind_param(
            "ssssssd",
            $fullname,
            $email,
            $password,
            $humidity,
            $city,
            $createdAt,
            $temp
        );

        if ($insert->execute()) {

            // 3. Get inserted user ID
            $userID = $insert->insert_id;

            // 4. Store session
            $_SESSION["userid"] = $userID;
            $_SESSION["username"] = $fullname;
            $_SESSION["city"] = $city;

            // 5. Redirect
            header("Location: homepage.php");
            exit;

        } else {
            $signup_error = "Signup failed. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BloomLog - Login</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<!-- HEADER -->
<header>
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <span class="logo-icon"><img src="image/logo.PNG" width="130"></span>
                <h1>BloomLog</h1>
            </div>
        </div>
    </div>
</header>

<!-- AUTH BOX -->
<section class="container">
    <div class="auth-container card">

        <div class="auth-tabs">
            <div class="auth-tab active" id="login-tab">Login</div>
            <div class="auth-tab" id="signup-tab">Sign Up</div>
        </div>

        <!-- ============ LOGIN FORM ============ -->
        <form id="login-form" method="POST">
            <?php if ($login_error): ?>
                <p style="color:red;"><?= $login_error ?></p>
            <?php endif; ?>

            <div class="form-group">
                <label>Username / Email</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn" name="login">Login</button>
        </form>

        <!-- ============ SIGNUP FORM ============ -->
        <form id="signup-form" method="POST" style="display:none">

            <?php if ($signup_error): ?>
                <p style="color:red;"><?= $signup_error ?></p>
            <?php endif; ?>

            <?php if ($signup_success): ?>
                <p style="color:green;"><?= $signup_success ?></p>
            <?php endif; ?>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="new_username" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="new_password" required>
            </div>

            <div class="form-group">
                <label>City</label>
             <select name="city" required>
    <option value="">Choose your city</option>

    <!-- ---------------- Saudi Arabia ---------------- -->
    <optgroup label="Saudi Arabia">
        <option value="Riyadh">Riyadh</option>
        <option value="Jeddah">Jeddah</option>
        <option value="Mecca">Mecca</option>
        <option value="Medina">Medina</option>
        <option value="Dammam">Dammam</option>
        <option value="Khobar">Khobar</option>
        <option value="Dhahran">Dhahran</option>
        <option value="Abha">Abha</option>
        <option value="Khamis Mushait">Khamis Mushait</option>
        <option value="Jazan">Jazan</option>
        <option value="Najran">Najran</option>
        <option value="Tabuk">Tabuk</option>
        <option value="Hail">Hail</option>
        <option value="Taif">Taif</option>
        <option value="Al Baha">Al Baha</option>
        <option value="Al Qassim">Al Qassim</option>
        <option value="Buraidah">Buraidah</option>
        <option value="Sakaka">Sakaka</option>
        <option value="Arar">Arar</option>
        <option value="Yanbu">Yanbu</option>
        <option value="Rabigh">Rabigh</option>
        <option value="Al Jubail">Al Jubail</option>
        <option value="Al Ahsa">Al Ahsa</option>
        <option value="Al Qatif">Al Qatif</option>
    </optgroup>

    <!-- ---------------- International ---------------- -->
    <optgroup label="International">
        <option value="Dubai">Dubai</option>
        <option value="Abu Dhabi">Abu Dhabi</option>
        <option value="Manama">Manama</option>
        <option value="Doha">Doha</option>
        <option value="Kuwait City">Kuwait City</option>
        <option value="Muscat">Muscat</option>

        <option value="Cairo">Cairo</option>
        <option value="Alexandria">Alexandria</option>

        <option value="London">London</option>
        <option value="Paris">Paris</option>
        <option value="Berlin">Berlin</option>
        <option value="Rome">Rome</option>
        <option value="Madrid">Madrid</option>
        <option value="Athens">Athens</option>

        <option value="New York">New York</option>
        <option value="Los Angeles">Los Angeles</option>
        <option value="Chicago">Chicago</option>
        <option value="Toronto">Toronto</option>
        <option value="Vancouver">Vancouver</option>

        <option value="Tokyo">Tokyo</option>
        <option value="Osaka">Osaka</option>
        <option value="Seoul">Seoul</option>
        <option value="Singapore">Singapore</option>
        <option value="Bangkok">Bangkok</option>
        <option value="Hong Kong">Hong Kong</option>
    </optgroup>
</select>

            </div>

            <button type="submit" class="btn" name="signup">Sign Up</button>
        </form>
    </div>
</section>

<!-- TAB SWITCH SCRIPT -->
<script>
document.getElementById('login-tab').onclick = () => switchTab('login');
document.getElementById('signup-tab').onclick = () => switchTab('signup');

function switchTab(tab){
    let loginForm = document.getElementById('login-form');
    let signupForm = document.getElementById('signup-form');
    let loginTab = document.getElementById('login-tab');
    let signupTab = document.getElementById('signup-tab');

    if (tab === "login") {
        loginForm.style.display = "block";
        signupForm.style.display = "none";
        loginTab.classList.add("active");
        signupTab.classList.remove("active");
    } else {
        signupForm.style.display = "block";
        loginForm.style.display = "none";
        signupTab.classList.add("active");
        loginTab.classList.remove("active");
    }
}
</script>

</body>
</html>
