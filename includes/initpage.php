<?php
//this should be included AFTER a session start page. It shouldn't be used unless via include
//if you can't understand the line above, don't write anymore code anywhere, and pick up a book!
include_once 'dbconnect.php';
include_once 'functions.php';
if (isset($_SESSION['username'], $SESSION['session_uid'], $conn)) {
  $user = make_safe($_SESSION['username']);
  $session_uid = make_safe($_SESSION['session_uid']);
  if ($verification_record = $conn->query("SELECT * FROM users WHERE username='$user';")) {
    //convert the first row of the record into an associative array. Note the 'MYSQLI_ASSOC' arg for fetch_array: this makes the result an associative array
    $verification_record->data_seek(0);
    if ($verification_array = $verification_record->fetch_array(MYSQLI_ASSOC)) {
      if (!$verification_array['logged_in'] || $verification_array['session_uid'] !== $session_uid) {
        //if the db says they're not logged in, redirect to the login page
        $location = $_SESSION['target_location'] ? $_SESSION['target_location'] : 'login.php';
        header("Location: http://localhost:8080/taskr/$location");
      }
      $_SESSION['current_user'] = $verification_array;


    }
  }
}


?>
