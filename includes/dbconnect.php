<?php

$dbServerName = "localhost:3306";
$dbUserName = 'admin';
$dbPassword = '083100Matt#';
$dbName = 'taskr';

$conn = mysqli_connect(
  $dbServerName,
  $dbUserName,
  $dbPassword,
  $dbName
);


//function to remove all semicolons to provide primitive protection against sql injections
function make_safe($str) {
  return str_replace(';','',$str);
}

//if (isset($conn)) {
 // echo "hi";
//  $d = $conn->query("INSERT INTO users (username, password) values ('matf', 'password')");
  /*if ($d) {
    echo "a success";
  } else {
    echo "it didnt work!";
  }*/
//}
