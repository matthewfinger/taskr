<?php
include_once 'dbconnect.php';
include_once 'functions.php';
if (!isset($_SESSION)) {
  session_start();
}
//make sure we're returning json, not html
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$out_array = array(
  'success' => false,
  'content' => array(),
  'user' => 'none'
);

try {
$out_array['user'] = make_safe($_SESSION['username']);
if ($postinput = file_get_contents('php://input')) {
  $postdata = json_decode($postinput, true);
  $out_array['taskid'] = $postdata['id'];



if (isset($_SESSION['username'], $_SESSION['session_uid'], $postdata['id'])) {
//    echo 'hi';
    //create user and session_uid safe vars
    $user = make_safe($_SESSION['username']);
    $session_uid = make_safe($_SESSION['session_uid']);
    //verify access b4 continuing
    if (verify_access($user, $session_uid)) {
      //make sure we have the correct post data

      if (isset ($postdata['id'])) {
        //scrape post data (that is a task attribute type) nd put it into $update_fields_array
        $update_fields_array = array();

        foreach($task_attributes as $attribute) {
	  if ($attribute == 'last_modified') {
	    $update_fields_array[$attribute] = date('Y-m-d H:i:s');
	  } elseif (isset($postdata[$attribute])) {
	    $postvalue = $postdata[$attribute];
	    switch ($attribute) {
	      case "completed":
		$update_fields_array[$attribute] = $postvalue;
	      default:
		$update_fields_array[$attribute] = make_safe($postvalue);
		break;
	    }
	  }
	}
      }
        //for all the other attribute s, if they're in the post data, add them to the $update_fields_array
        //pass user, session uid, and update_fields_array to modify_task function
        $successful = modify_task($user, $session_uid, $update_fields_array);
	$out_array['success'] = $successful[0];
	//if the modification was successful, change the content in the response to the updated values
	$out_array['content'] = $successful[1];
	$out_array['active_task'] = $successful[2];
    }
}
}
} catch(Exception $error) {
echo "hi";


} finally {
  $_SESSION['active_task'] = $out_array['active_task'];
  echo json_encode($out_array);
}
