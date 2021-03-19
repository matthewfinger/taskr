<?php
session_start();
/*if (isset($_SESSION['redirect'])) {
if ($_SESSION['redirect'] !== 0){
echo 'i got here';
$_SESSION['redirect'] = 0;
header('Location: http://localhost:8080/taskr/index.php');
exit;
}
}*/
?>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <title>Register | Taskr</title>
  <link rel="stylesheet" href="css/main.css">
</head>
<body>




  <h1 class="banner"><span>Register</span></h1>

  <?php
  include_once 'includes/dbconnect.php';
  if (isset($_POST['username'], $_POST['password'], $_POST['firstname'])) {
    if (isset($conn)) {
      $user = make_safe($_POST['username']);
      if ($existing_users = $conn->query("SELECT * FROM users WHERE username='$user'")) {
        if ($existing_users->num_rows > 0) {
          echo "<h2 class='warning'> A user already exists with that username! </h2>";
          exit;
        }
        $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $existing_users->close();
        $firstname = $_POST['firstname'] ? make_safe($_POST['firstname']) : '';
        $lastname = $_POST['lastname'] ? make_safe($_POST['lastname']) : '';
        $email = $_POST['email'] ? make_safe($_POST['email']) : '';
        $session_uid = uniqid();
        if ($create_user = $conn->query("INSERT INTO users (first_name, last_name, username, password, logged_in, session_uid, email) VALUES (
          '$firstname',
          '$lastname',
          '$user',
          '$pass',
          1,
          '$session_uid',
          '$email'

        )") ) {
          $_SESSION['username'] = $user;
          $_SESSION['session_uid'] = $session_uid;
          echo "<p class='simplemessage'> Created User $user Successfully! </p>";
          echo "<a href='index.php' class=''>Go to main page</a>";
        } else {
	  echo "hi"; }
      }
    }
  }

  ?>
 <div class="loginregistercontainer">
    <form action="register.php" class="loginregister" method="post">
      <p>*Required</p>
      <label for="username">Username*</label>
      <input type="text" name="username" required>
      <label for="password">Password*</label>
      <input type="password" name="password" required>
      <label for="email">Email</label>
      <input type="text" name="email">
      <label for="firstname">First Name*</label>
      <input type="text" name="firstname" required>
      <label for="lastname">Last Name</label>
      <input type="text" name="lastname">
      <div>
        <input type="checkbox" name="waiver" required>
        <span>I accept that this is unliscensed software, and I am responsible for any lawful operations done with it.</span>
      </div>
      <label for="waiver">I accept to have the information given in this form recorded and stored by the tasker software, and agree to hold myself accountable for any operation done by or whilst using taskr.</label>
      <input type="submit" name="submit" value="Register!">
      <div class="simple_container">
        <p>Already have an account?</p>
        <a href="login.php">Login!</a>
      </div>
    </form>
  </div>
</body>
</html>
