<?php


if (!isset($_SESSION['username'])) {
  session_start();
}
include_once "functions.php";
include_once "dbconnect.php";

//function for getting parent task
function get_parent($user, $session_uid, $taskinfo) {
  global $conn;
  $out = array(
    'parent' => false,
    'successful' => false
  );
  try {
  //make values safe for use
  $user = make_safe($user);
  $out['user'] = $user;
  $session_uid = make_safe($session_uid);
  $out['session_uid'] = $session_uid;
  $parent_id = '';
  if (is_string($taskinfo) || is_int($taskinfo)) {
    $taskinfo = array('parent_id' => $taskinfo);
  }
  if (!is_array($taskinfo) || !isset($taskinfo['parent_id']) || $taskinfo['parent_id'] == '') {
    $out['taskinfo'] = $taskinfo;
    return $out;
  } else {
    $parent_id = make_safe(strval($taskinfo['parent_id']));
  }
  //user is logged in and using the correct session
  if (verify_access($user, $session_uid)) {
    //set up the query
    $query = "SELECT * FROM tasks WHERE id=$parent_id";
    //pass query to db
    if ($queryres = $conn->query($query)) {
      if ($queryres->num_rows == 1) { //if we got a row from the query, that row is part of our result
        $out['parent'] = $queryres->fetch_assoc();
	$out['successful'] = true;
      }
    }
  }
  } finally {
  return $out;
  }
}


//function for getting child tasks
function get_child_tasks($user, $session_uid, $task_id) {
  global $conn;
  $out = array(
    'successful' => false,
    'children' => false
  );
  //make vars safe for use
  $user = make_safe($user);
  $session_uid = make_safe($session_uid);
  $task_id = make_safe(strval($task_id));
  //make sure user is logged in and has access
  if (verify_access($user, $session_uid)) {
    //prepare sql query
    $query = "SELECT * FROM tasks WHERE parent_id=$task_id";
    //pass query to db
    if ($queryres = $conn->query($query)) {
      $out['successful'] = true;
      if ($queryres->num_rows > 0) { //if we got any results
	$out['children'] = $queryres->fetch_all(MYSQLI_ASSOC);
      }
    }
  }

  return $out;
}

//function for getting root task | get_root_task (user, session uid, task_id) -> array
function get_root_task($user, $session_uid, $task_id) {
  global $conn;
  $out = array(
    'successful' => false,
    'root' => false
  );
  $user = make_safe($user);
  $session_uid = make_safe($session_uid);
  $task_id = make_safe(strval($task_id));
  //verify user has access
  if (verify_access($user, $session_uid)) {
    //prepare first query
    $query = "SELECT * FROM tasks WHERE id=$task_id";
    if ($queryarray = $conn->query($query)->fetch_assoc()) {
      $out['successful'] = true;
      if (isset($queryarray['parent_id'])) {
	$parent_task = get_parent($user, $session_uid, $queryarray['parent_id']);
	if (isset($parent_task['parent_id']) && $parent_task['parent_id'] != '') {
	  $out['root'] = get_root_task($user, $session_uid, $parent_task['parent_id'])['root'];
	} else {
	  $out['root'] = $parent_task;
	}
      } elseif (isset($queryarray['id'])) {
	$out['root'] = $queryarray;
      }
    }
  }
  return $out;
}


//if we can't verify session, end script
if (!verify_access($_SESSION['username'], $_SESSION['session_uid'])) {
  header('Content-Type: application/json'); //set mime type to json
  echo json_encode(array(
    'something went wrong'
  ));
  exit;
}


//if data posted try to use data for stuff
/*  we want to post json like
{'content': mixed, 'target': <parent | children | root>}*/
if (true) {
  header('Content-Type: application/json');
  $out_array = array(
    'successful' => false
  );
  if ($postdata = json_decode(file_get_contents('php://input'), true)) {
    $out_array['inputData'] = $postdata;
    if (isset($postdata['content'])) {
      switch ($postdata['target']) {
	case "parent":
          $out_array['content'] = get_parent($_SESSION['username'], $_SESSION['session_uid'], $postdata['content']);
	  break;

	case "children":
	  $out_array['content'] = get_child_tasks($_SESSION['username'], $_SESSION['session_uid'], $postdata['content']);
	  break;

	case "root":
	  $out_array['content'] = get_root_task($_SESSION['username'], $_SESSION['session_uid'], $postdata['content']);
	  break;
      }
      $out_array['successful'] = $out_array['content']['successful'] ? true : $out_array['successful'];
    }

  }
  echo json_encode($out_array);
}
