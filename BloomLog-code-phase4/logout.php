
<?php
session_start();

// wipe session data
session_unset();
session_destroy();

// redirect to homepage
header("Location: index.php");
exit();
?>


