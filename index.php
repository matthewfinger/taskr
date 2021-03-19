<?php
session_start();
include_once "includes/initpage.php";
include_once "includes/functions.php";
?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <title>taskr</title>
  <link rel="stylesheet" href="css/main.css">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">

</head>
<body>
  <?php include_once "includes/load_js_message.php"; ?>
  <div id="content">
    <?php

    //redirect if user isn't logged in
    $rd = true;
    if (isset($_SESSION['username'], $_SESSION['session_uid'])) {
      if (verify_access($_SESSION['username'], $_SESSION['session_uid'])) {
        $rd = false;
      }
    }
    if ($rd) {
      header("Location: /taskr/login.php");
    }

    //if the page wasn't redirected by initpage.php, then let's welcome the logged in user
    $user = $_SESSION['username'];
    echo "<h1 class='welcome'>Welcome, $user</h1>";
    ?>
     <?php
      include_once 'includes/menu.php';
      if (!isset($menuloaded)) {
        header('Location: brokenpage.php');
      }
     ?>
    <?php include "includes/details.php"; ?>
  </div>
  <script type="text/javascript" src="javascript/main.js"></script>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
  <?php
  echo "<div style='visibility:hidden;height:0px;width:0px;overflow:hidden;' id='userinfo'>";
  echo get_user_tasks_json($_SESSION['username'], $_SESSION['session_uid']);
  echo "</div>";
  ?>
  <?php
  include "includes/confirmation_message.php";
  ?>
</body>
</html>
