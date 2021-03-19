<?php
//include (if not already) the functions necessary
include_once "functions.php";
?>

<div id="taskdetails" class="contentsection">
  <div >
    <div id="detailssection">
      <h1 id="taskname"> </h1>
      <div id="details_div">
        <!-- This section will be filled using client side javascript -->
        <div>
            <span class="detaillabel" id="taskdescriptionlabel"></span>
            <textarea class='description detailsInput' id="taskdescription"></textarea>
        </div>
        <div>
          <span class="detailcontainer">
            <span class="detaillabel" id="taskcreateddatelabel"></span>
            <span id="taskcreateddate"></span>
          </span>
	  <span class="detailcontainer">
	    <span class="detaillabel" id="tasklastmodifiedlabel"></span>
	    <span id="tasklastmodified"></span>
	  </span>
          <span class="detailcontainer">
            <span class="detaillabel" id="taskduedatelabel"></span>
            <input type="date" id="taskduedate">
          </span>
          <span class="detailcontainer">
            <!-- to be filled w/ js -->
            <span class="detaillabel" id='taskparentidlabel'>Parent</span>
            <select id='taskparentid'></select>
          </span>
          <span class="detailcontainer">
            <span class="detaillabel" id="taskcompletedlabel"></span>
            <input type="checkbox" id="taskcompleted">
          </span>
        </div>
      </div>
    </div>
    <?php include_once "addtask.php"; ?>
  </div>
</div>
