<?php
if (!isset($_SESSION)) {
  session_start();
}

include_once "functions.php";
//verify a user is logged in this session
if (isset($_SESSION['username'], $_SESSION['session_uid'])) {
  if (verify_access($_SESSION['username'], $_SESSION['session_uid'])) {

    //verify that post data exists
    $new_task_info = array();
    //verify at least a taskname was provided
    if(isset($_POST['name'])) {
      $new_task_info['name'] = $_POST['name'];
      //verify whether the rest of the task info was provided
      foreach($task_attributes as $attribute) {
        if (isset($_POST[$attribute]) && $attribute != 'name' && $attribute != 'last_modified') {
          if (!($attribute == 'due_date' && $_POST[$attribute] == '')) {
            $new_task_info[$attribute] = $_POST[$attribute];
          }
        } elseif ($attribute == 'last_modified') {
	  $new_task_info[$attribute] = date('Y-m-d G:i:s');
	}
      }
      //pass post data into add_task function
      $add_task_result = add_task($_SESSION['username'], $_SESSION['session_uid'], $new_task_info);
      //set session var for onload message (depending on success of add_task)
      $_SESSION['active_task'] = $add_task_result;
      if ($add_task_result[0]) {
//	$_SESSION['active_task'] = $add_task_result;
      }
      header("Location: http://173.230.134.109/taskr/index.php");
    }

  }
}
//redirect to index


?>


<div id="taskOptions">
  <button class="hovershadow highlightonhover m-2" type="button" data-toggle="collapse" data-target="#addtaskform">New Task</button>
  <button class="hovershadow highlightonhover m-2" type="button" id="saveButton">Save</button>
  <button class="hovershadow highlightonhover m-2" type="button" id="addAttributeButton" data-toggle="collapse" data-target="#addattributearea">Add Attribute</button>
  <button class="hovershadow highlightonhover m-2" type="button" id="deleteTaskButton">Delete Task</button>
  <div class="accordion" id="taskOptionsContainer">

    <form method="POST" action="/taskr/includes/addtask.php" id="addtaskform" class="collapse" data-parent="#taskOptionsContainer">
      <div>
        <label for="name">Task Name</label>
        <input type="text" name="name" required>
      </div>
      <div id="parentTaskContainer">
        <label for="parent_id">Parent Task</label>
        <select id="parentTaskSelect" name="parent_id">
        </select>
      </div>
      <div>
        <label for="description">Description</label>
        <textarea type="text" name="description"></textarea>
      </div>
      <div>
        <label for="due_date">Due Date</label>
        <input type="date" name="due_date">
      </div>
      <div>
        <input type="submit" value="Add Task!">
      </div>
    </form>
    <div class="collapse" id="addattributearea" data-parent="#taskOptionsContainer">
      <h1>Add Attribute</h1>
    </div>
  </div>
</div>

<?php
//var_dump($_SESSION['active_task']);
//echo "HI";
?>
