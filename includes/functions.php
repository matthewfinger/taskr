<?php

include_once "dbconnect.php";

$task_attributes = array(
  'id',
  'parent_id',
  'name',
  'description',
  'created_date',
  'due_date',
  'completed',
  'last_modified'
);

/*function add_task(user:string, session_uid:string, taskinfo:array) -> array(bool->successful, result)*/
function add_task($user, $session_uid, $task_info) {
  global $conn;
  $out = array(false, false, array(
    'verified' => false
  ));
  //verify access to the db verify params are in correct format
  if (is_string($user) && is_string($session_uid) && is_array($task_info) && isset($conn, $task_info['name'])) {
    //make params safe
    $user = make_safe($user);
    $session_uid = make_safe($session_uid);

    //pass data to db
    //prepare query

    //make task_info safe and prepare field str and value str
    $field_str = "(";
    $value_str = "(";

    foreach ($task_info as $key => $value) {
      $v = str_replace("'", "''", make_safe((string) $value));
      $task_info[$key] = $v;
      if ($key != 'parent_id') {
	$field_str = $field_str."$key, ";
      }
      if ($key != 'completed' && $key != 'parent_id') {
        $value_str = $value_str."'$v', ";
      } else {
	if (!($key == 'parent_id' && !preg_match("/^\d+$/", $v))) {
	  $value_str = $value_str."$v, ";
	  $field_str = $field_str."$key, ";
	}

      }
    }

    $l = strlen($field_str) - 2;
    $field_str = substr($field_str, 0, $l);
    $field_str = $field_str.")";

    $l = strlen($value_str) - 2;
    $value_str = substr($value_str, 0, $l);
    $value_str = $value_str.")";


    //verify user via verify_access
    if (verify_access($user, $session_uid)) {
      $out[2]['verified'] = true;
      //assemble queries
      $newtaskq = "INSERT INTO tasks $field_str VALUES $value_str;";
      $tname = $task_info['name'];
      $vertaskq = "SELECT * FROM tasks WHERE name='$tname';";
      //insert new task
      $out[2]['newtaskq'] = $newtaskq;
      if ($conn->query($newtaskq)) {
	$out[2]['newtaskquery'] = true;
        //get record of new task
        $vertaskrec = $conn->query($vertaskq)->fetch_assoc();
        //modify user_tasks to give access to new task using id from record of new task
        if (isset($vertaskrec['id'])) {
          give_access($user, $session_uid, $vertaskrec['id'], "administrator");
          $out[0] = true;
          $out[1] = $task_info;
        }

      }
    }
  }
  return $out;
}

function delete_task($user, $session_uid, $task) {
  global $conn;
  $out = false;
  $user = make_safe($user);
  $session_uid = make_safe($session_uid);
  //if no valid task id present, return
  if (!is_array($task) or !isset($task['id'])) {
    return $out;
  }
  $task_id = make_safe(strval($task['id']));
  //verify user
  if (verify_access($user, $session_uid)) {
    //verify user has admin on task
    $user_has_access = false;
    $user_tasks = get_accessible_tasks($user, true);
    foreach($user_tasks as $accessible_id) {
      $user_has_access = $accessible_id==$task_id ? true : $user_has_access;
    }
    if ($user_has_access) {
      //drop the task and the user_tasks access
      //prep the query for the user_tasks table
      $query = "DELETE FROM user_tasks WHERE task_id=$task_id";
      if ($conn->query($query)) { //if the deletion of access to the task was successful, remove task too
	$query = "DELETE FROM tasks WHERE id=$task_id";
	$conn->query($query);
	$out = true;
      }
    }
  }
  //return the results
  return $out;

}



function get_accessible_tasks($user, $administrator = false) {
  global $conn;
  $out_array = array();
  if (isset($conn)) {
    //make everything safe to use
    $user = str_replace("'", "''", make_safe($user));
    $administrator = $administrator===true ? true : false;
    $adminstr = $administrator ? " AND administrator=1" : '';
    if ($accessible_res = $conn->query("SELECT * FROM user_tasks WHERE username='$user'$adminstr ORDER BY administrator DESC")) {
      $accessible_array = $accessible_res->fetch_all(MYSQLI_ASSOC);
      foreach ($accessible_array as $key) {
        array_push($out_array, $key['task_id']);
      }
      $accessible_res->close();
    }
  }
  return $out_array;
}

function get_user_tasks_json ($user, $session_uid, $limit=0, $where_attr='', $administrator=false) {
  return get_user_tasks($user, $session_uid, true ,$limit, $where_attr, $administrator);
}


//the administrator param determines if the user is only concerned with tasks they own
function get_user_tasks($user, $session_uid, $json=false,$limit=0, $where_attr='', $administrator=false) {
  global $conn, $task_attributes;
  $out = '{"content": []}';
  if (isset($conn)) {
    //make everything safe to use
    $user = make_safe($user);
    $session_uid = make_safe($session_uid);
    $limit = is_int($limit) ? $limit : 0;
    $where_attr = make_safe($where_attr);
    $adinistrator = $administrator===true ? true : false;

    //verify user's access before continuing
    if (verify_access($user, $session_uid)) {
      //get a list of all accessible tasks using get_accessible_tasks function
      $accessible_tasks = get_accessible_tasks($user, $administrator, $conn);

      //make where string
      $where_string = $where_attr==='' ? '' : " AND $where_attr";
      //return $where_attr;

      $task_db_results = array();
      //for all the available tasks, query them
      foreach($accessible_tasks as $task_id) {
        //prepare statement
        $q = "SELECT * FROM tasks WHERE id=$task_id $where_string";
        //query statement
        if ($qres = $conn->query($q)) {
          //add results to $task_db_results
          $qres->data_seek(0);
          array_push($task_db_results, $qres->fetch_assoc());
          //close query
          $qres->close();
        }
      }
      //if the limit is specified, shorten the results to the limit length
      if ($limit > 1) {
        $task_db_results = array_slice($task_db_results, 0, $limit);
      }

      if ($json==false) {
        return $json;
        return $task_db_results;
      }
      //write the output as json in the $out string
      $out = '{"content":[ ';
      foreach ($task_db_results as $row) {
        if (is_array($row)) {
          $out = $out.'{';
          foreach ($row as $key => $value) {
            $out = $out.'"'.$key.'":"'.$value.'",';
          }
          $l = strlen($out) - 1;
          $out = substr($out, 0, $l);
          $out = $out.'},';
        } else {
          $out = $out.'"",';
        }
      }
      $l = strlen($out) - 1;
      $out = substr($out, 0, $l);
      $out = $out."]";
      //write types as types attr
      $out = $out.',"types": [';
      foreach($task_attributes as $ind => $type) {
        $out = $out.'"'.$type.'",';
      }
      $l = strlen($out) - 1;
      $out = substr($out, 0, $l);
      $out = $out.']';
      $out = $out."}";

    } else {
      return 'hi';
    }
    }
  //return output
  return $out;
}

function give_access($user, $session_uid, $task_id, $level) {
  global $conn;
  $user = str_replace("'", "''", make_safe($user));
  $session_uid = make_safe($session_uid);
  if (verify_access($user, $session_uid)){
    $primaryq = "SELECT * FROM user_tasks WHERE task_id=$task_id AND username='$user'";
    $q="";
    $deleteq = "DELETE FROM user_tasks WHERE task_id=$task_id AND username='$user'";
    //prep the statement
    if ($level !== "none") {
      $admin = $level==="administrator" ? '1' : '0';
      $q = "INSERT INTO user_tasks (username, task_id, administrator) VALUES ('$user', $task_id, $admin)";
    }
    //see if any records exist between the user and task
    if ($primaryqres = $conn->query($primaryq)) {
      //delete any existing records
      if ($primaryqres->num_rows>0) {
        $conn->query($deleteq);
      }
      $primaryqres->close();
      //if $q != '', then $level != 'none'. therefore, we want to add a new connection
      if ($q !== '') {
        $conn->query($q);
      }
    }
  }
}

function modify_task($user, $session_uid, $update_fields_array) {
  //get all the necessary vars in the right format
  global $conn, $task_attributes;
  $out = array(false, false, array());
  $user = make_safe($user);
  $session_uid = make_safe($session_uid);
  $task_id = isset($update_fields_array['id']) ? $update_fields_array['id'] : false;
  //verify access && make sure we got and id and at least one other attr
  if (verify_access($user, $session_uid) && $task_id != false && count($update_fields_array) > 1) {
    //create the query
    $query = "UPDATE tasks SET ";
    $where_string = " WHERE id=$task_id";
    //add a field for each one defined in $update_fields_array
    foreach($update_fields_array as $field => $update_value) {
      $safe_update_value = make_safe($update_value);
      switch ($field) {
        case "id":
          break;
        case "parent_id":
        case "completed":
	  if (!($field=='parent_id' && !preg_match("/^\d+$/", $safe_update_value))) {}
          $query = $query."$field=$safe_update_value, ";
          break;
        default:
	  $safe_update_value = str_replace("'", "''", $safe_update_value);
          $query = $query."$field='$safe_update_value', ";
          break;
      }
    }
    //remove the extra comma and space
    $l = strlen($query) - 2;
    $query = substr($query, 0, $l);
    //append where clause to query
    $query = $query.$where_string;
    //send query
    $out[2]['query'] = $query;
    if ($conn->query($query)) {
      //verify results
        if ($db_record = $conn->query("SELECT * FROM tasks WHERE id=$task_id")->fetch_assoc()) {
          //if modified successfully, set $out to true
          $change_out = true;
          foreach($db_record as $field => $value) {
            //if any of the returned reults don't match the inputted values, dont change $Out
            if (isset($update_fields_array[$field])) {
              if ($value !== $update_fields_array[$field]) {
                $change_out = false;
              }
            }
          }
          $out[0] = $change_out ? true : $out[0];
	  $out[1] = $change_out ? $db_record : $out[1];
          //close the select query
        }
    }
  }
  //return array
  return $out;
}

//verifies that the user is logged in and the given session_uid matches the db records
function verify_access($user, $session_uid) {
  global $conn;
  //the last line of this function returns $out
  $out = false;
  //make sure we can access the db
  if (isset($conn)) {
    //make the arg safe to pass into a sql query
    $user = make_safe($user);
    $user = str_replace("'", "''", $user);
    //make the query
    $q = "SELECT * FROM users WHERE username='$user'";
    if ($qres = $conn->query($q) ) {
      if ($qres->num_rows>0) {
        //if the database shows the user has access, set out to true
        $qres->data_seek(0);
        $res = $qres->fetch_assoc();
        if ($res['session_uid'] == $session_uid && $res['logged_in']) {
          $out = true;
        }
      }
      
    }
  }
  return $out;
}


function verify_user_has_access($user, $task_id, $admin) {
  global $conn;
  $admin = intval(boolval($admin));
  $user = str_replace("'", "''", make_safe($user));
  $task_id = make_safe($task_id);
  $out = false;
  try {
    //assemble the query
    $query = "SELECT * FROM user_tasks WHERE username='$user' AND id=$task_id AND administrator=$admin";
    //pass the query

    //use the results
  } finally {
    return /*$out*/ true;
  }
}


?>
