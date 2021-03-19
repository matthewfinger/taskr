<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include_once('functions.php');
include_once('dbconnect.php');

//array that will be encoded w/ json and ultimately returned
$out_array = array(
  'successful' => false
);

$out_array['sessionvars'] = array(
  $_SESSION['username'],
  $_SESSION['session_uid']
);
//verify the sender is logged in
if (isset($_SESSION['username'], $_SESSION['session_uid'])) {
  $user = make_safe($_SESSION['username']);
  $session_uid = make_safe($_SESSION['session_uid']);
  if (verify_access($user, $session_uid)) {
    //find (if any) post data
    if ($postfilecontents = file_get_contents('php://input')) {
      //call delete_task function on post data
      $postdata = json_decode($postfilecontents, true);
      $out_array['request'] = $postdata;
      $delete_task_success = delete_task($user, $session_uid, $postdata);
      //if delete_task was successful, set successful to true
      $out_array['successful'] = $delete_task_success;
    }
  }
}




//encode and respond w/ the result
echo json_encode($out_array);
