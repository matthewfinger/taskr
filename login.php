<?php
session_start();
include 'includes/dbconnect.php';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <title>Login | Taskr</title>
  <link rel="stylesheet" href="css/main.css">
</head>
<body>


  <h1 class="banner"><span>Login</span></h1>
  <div class="loginregistercontainer">
    <?php
    //first, check if all the necessary variables are set needed to grant access, otherwise, render the form
    if (isset($_POST['username'], $_POST['password'], $conn)) {
      //if all of the right variables are set, try logging in
      $user = str_replace(';','',$_POST['username']); //prevent an sql injection
      //find all users with that username
      $recres = mysqli_query($conn, "SELECT * FROM users WHERE username='$user';");
      if ($recres->num_rows == 0) {
        echo "<h2 class='warning'> No user exists with the name, ".'"'.$user.'"'."!</h2>";
      } elseif ($recres->num_rows == 1) {
        $recres->data_seek(0);
        $result = $recres->fetch_array(MYSQLI_ASSOC);
        //we'll stop right there if the password can't be verified
        if (!password_verify($_POST['password'], $result['password'])) {
          echo "<h2 class='warning'> Password invalid! </h2>";
          $recres->close();
        } else {
          //now, let's create a session id for this login
          $recres->close();
          $session_uid = uniqid();
          $_SESSION['session_uid'] = $session_uid;
          $_SESSION['username'] = $user;
          if ($queryres = $conn->query("UPDATE users SET logged_in=1 WHERE username='$user'")) {
            $queryres = $conn->query("UPDATE users SET session_uid='$session_uid' WHERE username='$user'");
            header('Location: index.php');
            echo "hello world";
          }
        }
      }
    }
    ?>

    <form action="login.php" method="post" class="loginregister">
      <label for="username">Username</label>
      <input type="text" name="username" required>
      <label for="password">Password</label>
      <input type="password" name="password" required>
      <input type="submit" value="Login!">
      <div class="simple_container">
        <p>Don't have an account?</p>
        <a href="register.php">Register!</a>
      </div>
    </form>
  </div>

</body>
</html>
