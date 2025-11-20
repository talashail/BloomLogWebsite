<?php
$maintenanceMode = false; // Change to true only when needed

if ($maintenanceMode) {
    header("Location: maintenance.php");
    exit();
}
?>
