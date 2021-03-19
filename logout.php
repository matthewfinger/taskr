<?php
  session_start();
  include_once "includes/dbconnect.php";

  if (isset($conn)) {
    if (isset($_SESSION['username'])) {
      $user = make_safe($_SESSION['username']);
      if ($conn->query("UPDATE users SET logged_in=0 WHERE username='$user'") && $conn->query("UPDATE users SET session_uid=NULL WHERE username='$user'")) {
        unset($_SESSION['username'], $_SESSION['session_uid'], $_SESSION['current_user']);
        echo "<h1 class='message1'>You've been logged out!</h1>";
        echo "<a href='login.php'>Go to login</a>";
	exit;
      }
    }
  }
  unset($_SESSION['username'], $_SESSION['session_uid']);
  header("Location: /taskr/login.php");
?>
