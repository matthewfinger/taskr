<?php
include_once "includes/functions.php";
include_once "includes/dbconnect.php";
?>

<div class="contentsection" id="menusection">
  <button type="button" data-toggle="collapse" data-target="#menu" class="highlightonhover hovershadow">Menu</button>
  <div class="collapse scrolly" id="menu">
    <div class="menusection" id="tasks">
      <h3 class="sectionheader">Tasks</h3>
      <?php
      echo "<div class='subsection'>";
      $fillvar = "<p class='notice'>You don't have any tasks.  <span id='createFirstTask' data-toggle='collapse' data-target='#menu'>Try creating one!</span></p>";
      if (isset($_SESSION['username'], $_SESSION['session_uid'])) {
        $user = make_safe($_SESSION['username']);
        $session_uid = make_safe($_SESSION['session_uid']);
        //just making absolutely sure that the user has access
        if (verify_access($user, $session_uid)) {
          //first, let's check the user_tasks table to see what tasks this user has access to
          $accessible_tasks = get_accessible_tasks($user);
          if (count($accessible_tasks)>0) {
            $fillvar = '';
            foreach($accessible_tasks as $task) {
              $fillvar = $fillvar.
		"<div class='menubuttondiv'>".
		  "<button class='taskmenubutton menubutton hovershadow highlightonhover' data-taskid='$task'></button>".
		  "<div>".
		    "<span><input type='checkbox' class='menucheckedbutton' data-taskid='$task'> Completed</span>".
		    "<button class='menudeletebutton' data-taskid='$task'><img data-taskid='$task' alt='delete'></button>".
		  "</div>".
		"</div>";
            }
          }
        }
      }
      echo $fillvar;
      echo "</div>"
      ?>
    </div>
    <div class="menusection" id="account">
      <h3 class='sectionheader'>Account</h3>
      <!--a class="highlightonhover menubutton hovershadow" href="accountsettings.php">Account Settings</a-->
      <a href="logout.php" class="highlightonhover menubutton hovershadow" >Logout</a>
    </div>
  </div>
</div>


<?php
$menuloaded = 1;
?>
