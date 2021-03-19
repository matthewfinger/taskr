function padNumber (num, size) {
  var s = String(num);
  while (s.length < (size || 2)) {s = '0' + s;}
  return s;
}

function findDetailsContainer(dtl, i=1) {
  //console.log(dtl.parentElement);
  if (dtl) {
    //console.log(dtl.className);
    if (dtl.classList.contains('detailcontainer')) {
      return dtl;
    } else if (dtl.parentElement) {
      //return el.parentElement;
      dtl = dtl.parentElement;
      return findDetailsContainer(dtl, ++i);
    }
  }
  return false;
}

function sortObjects(objectsList, attributesList=[]) {
  if (!Array.isArray(objectsList) || !Array.isArray(attributesList)) {
    throw new Error('Either objectsList or attributesList not an array. sortObjects failed');
    return;
  }
  //make sure all items in objectsList are objects
  for (let i = 0; i < objectsList.length; i++) {
    if (typeof objectsList[i] != 'object') {
      throw new Error('One or more items in the objectsList array wasn\'t an object!');
      return;
    }
  }

  //if no attributes, return the list
  if (attributesList.length == 0) {
    return objectsList;
  }

  //make sure all items in attributesList are strings
  for (let i = 0; i < attributesList.length; i++) {
    attributesList[i][0] = String(attributesList[i][0]);
    attributesList[i][1] = attributesList[i][1] == true ? true : false;
  }

  let currentKeyOrder = attributesList[0];
  attributesList = attributesList.splice(1,0);
  let currentKey = currentKeyOrder[0];
  let currentOrder = currentKeyOrder[1];
  let outputCollection = {};
  let valueUndefinedList = [];
  //first, they'll be in ascending order
  //for each object with a given property,
  //create a list of objects that have the same property into the value of that property of outputCollection
  objectsList.forEach((item, index) => {
    //if item's value of given property is undefined, null, or '', treat it as undefined
    //note the comparision to false, we want to preserve value if type is bool
    let keyValue = (item[currentKey] || item[currentKey] === false) ? item[currentKey] : null;
    keyValue = keyValue == '' ? null : keyValue;
    keyValue = keyValue instanceof Date ? keyValue.getTime() : keyValue;
    if (keyValue != null) {
      if (!outputCollection[keyValue]) {
	outputCollection[keyValue] = [];
      }
      outputCollection[keyValue].push(item);
    } else {
      valueUndefinedList.push(item);
    }

  });
  //create a sorted list of values
  let keyValueSortedList = Object.keys(outputCollection).sort();
  //reverse if not current order
  if (!currentOrder) {
    keyValueSortedList = keyValueSortedList.reverse();
  }
  //put collections into outputList in the oreder of the sorted list
  outputList = [];
  keyValueSortedList.forEach((value, index) => {
    outputList.push(outputCollection[value]);
  });

  //add the valueUndefined list to the end of the outputList
  //(they don't apply to the valuable information sought after in the search criteria)
  outputList.push(valueUndefinedList);

  //sort each sub list with the remaining attributes
  outputList.forEach((sublist, index) => {
    outputList[index] = sortObjects(sublist, attributesList);
  });
  //merge outputList lists into one list, and return
  let output = [];
  outputList.forEach((sublist, index) => {
    output = output.concat(sublist);
  });
  return output;
}

//attributes list shall be a list of attribute filter arrays
// ex. ['description', 'compareval', 'eq|gt|lt']
function filterObjects(objectsList, attributesList) {
  let out = [];
  let comparisonTypes = ['eq', 'lt', 'gt'];
  //make sure params are in correct format
  //both params are arrays
  if (!Array.isArray(objectsList) || !Array.isArray(attributesList)) {
    throw new Error('Either the objectsList or the attributesList passed weren\'nt arrays!');
    return;
  }

  //all items in objectsList are objects
  for (let i = 0; i < objectsList.length; i++) {
    if (typeof objectsList[i] != 'object') {
      throw new Error('One or more of the items in the objectsList wasn\'t an object!');
      return;
    }
  }

  //if no attributes to compare, simply return all objects
  if (attributesList.length == 0) {
    return objectsList;
  }

  //all items in attributesList are arrays
  for (let i = 0; i < attributesList.length; i++) {
    if (!Array.isArray(attributesList[i])) {
      throw new Error('One or more items within the attributesList aren\'t arrays!');
      return;
    }
  }

  //pop a value from the attributesList as the current filterAttribute
  let filterAttribute = attributesList.pop();
  //making sure the filterAttribute's item are all in the correct format
  filterAttribute[0] = String(filterAttribute[0]);
  filterAttribute[1] = String(filterAttribute[1]);
  filterAttribute[2] = comparisonTypes.includes(filterAttribute[2]) ? filterAttribute[2] : comparisonTypes[0];
  //push to out all items where the value of the attribute matches the specified comparision
  switch (filterAttribute[2]) {
    case 'gt':
      objectsList.forEach((obj, index) => {
	if (obj[filterAttribute[0]] > filterAttribute[1]) {
	  out.push(obj);
	}
      });
      break;
    case 'lt':
      objectsList.forEach((obj, index) => {
	if (obj[filterAttribute[0]] < filterAttribute[1]) {
	  out.push(obj);
	}
      });
      break;
    case 'eq':
    default:
      objectsList.forEach((obj, index) => {
	if (obj[filterAttribute[0]] == filterAttribute[1]) {
	  out.push(obj);
	}
      });
      break;
  }

  //call filterObjects on out with the remaining attributes
  return filterObjects(out, attributesList);
}


//global var used to filter tasks
let filterVar = [];

//global var used to order tasks
let sortVar = [['last_modified', false]];

this.onload = () => {
  //basic constants that the whole page needs
  const hiddendiv = document.getElementById('userinfo');
  //console.log(hiddendiv.innerHTML);
  var taskContent = JSON.parse(hiddendiv.innerHTML)['content'];
  const types = JSON.parse(hiddendiv.innerHTML)['types'];
  const hiddenJson = JSON.parse(hiddendiv.innerHTML);
  const addAttributeArea = document.getElementById("addattibutearea");
  var addAttributesInputs = [];
  //for the task stuff
  let selectedTask = [{}, null];

  //splits types into two arrays, on the basis of whether or not they're defined for the currently selected task
  function separateTypesByDefined() {
    //return an array, the first element will be the types used, the second element will be the types not used
    let out = [ [], [] ];
    try {
      types.forEach((type, index) => {
	//if item visible, its defined -> push to out[0]
	if (detailsElements[type][0] && detailsElements[type][0].style.visibility != 'hidden' && detailsElements[type][0].style.visibility != 'HIDDEN') {
	  out[0].push(type);
	} else {
	  //otherwise, push to out[1]
	  out[1].push(type);
	}
      });
    } catch(error) {
      console.warn(error);
    } finally {
      return out;
    }
  }

  //converts displayed attributes into an obj
  function getDisplayedTask() {
    let out = {};
    types.forEach((item, i) => {
      let currentDetail = detailsElements[item][0];
      if (currentDetail && currentDetail.style.visibility != 'hidden' && currentDetail.style.visibility != 'HIDDEN') {
        if (['INPUT','SELECT','TEXTAREA'].includes(currentDetail.tagName)) {
          if (currentDetail.type=='checkbox') {
            out[currentDetail.dataset['field']] = currentDetail.checked ? '1' : '0';
          } else {
            out[currentDetail.dataset['field']] = currentDetail.value;
          }
        } else {
          out[currentDetail.dataset['field']] = currentDetail.innerHTML;
        }
      }
    });



    return out;
  }

  //function that filters and sorts a list of tasks based on the global sortVar and filterVar
  function filterSortTasks(tasks = taskContent) {
    return sortObjects(filterObjects(tasks, filterVar), sortVar);
  }

  //function to verify if an attribute of the selectedTask was changed (by the user)
  function attributeChanged(attr) {
    console.log(attr);
    let out = getDisplayedTask()[attr] !== selectedTask[0][attr];
    if (detailsElements[attr][0].style.visibility=='hidden' || detailsElements[attr][0].style.visibility == 'HIDDEN') {
      out = false;
    }
    return out;
  }

  //function that verifies whether or not the task details in the form(s) match the details in the selectedTask
  function taskChanged() {
    let out = false;
    try {
      //first, loop through all the task elements
      types.forEach((item, i) => {
        let taskElement = detailsElements[item][0];
        let taskAttribute = selectedTask[0][item];
        //html element exists, and is visible (meaning that the user edited it)
        if (!(['created_date','last_modified'].includes(item)) && taskElement && taskElement.style.visibility !== 'hidden' && taskElement.style.visibility !== 'HIDDEN') {
          let taskElementValue = (taskElement.tagName=='INPUT' || taskElement.tagName=='SELECT') ? taskElement.value : taskElement.innerHTML;
          if (taskElement.type == 'checkbox') {
            taskElementValue = taskElement.checked ? '1' : '0';
          } else if (taskElement.tagName == 'TEXTAREA') {
            taskElementValue = taskElement.value;
          } else if (item=='due_date' && taskAttribute instanceof Date) {
	    taskAttribute = `${taskAttribute.getFullYear()}-${padNumber(taskAttribute.getMonth()+1, 2)}-${padNumber(taskAttribute.getDate(), 2)}`;
//	    console.log(taskElementValue, taskAttribute);
	  }
          out = taskElementValue==taskAttribute ? out : true;
//	  if(item=='due_date') console.log(taskAttribute);
        }
      });
      //also check all the possibly hitherto unused types
      findAddAttributeInputs().forEach(input => {
	if (input.value.length > 0) {
	  out = true;
	}
      });

    } catch (err) {
      console.warn(err);
    }
    return out;
  }

  //code for sending a request to the right page and stuff
  async function sendChanges(element=null) {
    //iterate and add all the changed values that were modified by user to postvars obj
    let postvars = {};
    postvars['id'] = selectedTask[0]['id'];
    //if one of the menu inputs (checkboxes for complete task) then use that for the post vars
    if (element.tagName == "INPUT" && element.className.includes('menucheckedbutton')) {
      try {
	postvars['id'] = element.dataset['taskid'];
	postvars['completed'] = element.checked ? '1' : '0';
      } catch (error) {
	console.warn(error);
	return;
      }

    } else { //otherwise, use info from the task detials section of the page
      let currentDetail = getDisplayedTask();

      Object.keys(currentDetail).forEach((item, i) => {
        if (!(['created_date', 'last_modified'].includes(item)) && currentDetail[item] !== selectedTask[0][item]) {
          postvars[item] = currentDetail[item];
        }
      });

      //then, add to postvars any added attributes
      let addAttributeInputs = findAddAttributeInputs();
      addAttributeInputs.forEach((input, index) => {
        //if input was filled out add it to post vars
        if (input.value.length > 0) {
          console.log(input);
	  if (!(input.dataset['field'] == 'parent_id' && parseInt(input.value))) {
	    postvars[input.dataset['field']] = input.value;
	    console.log(postvars);
	  }
        }
      });
    }

    //send changes to /taskr/includes/modify_task
    try {
      fetch('http://173.230.134.109/taskr/includes/modify_task.php', {
        method: 'POST',
	headers: {
	  'Content-Type': 'application/json'
	},
        body: JSON.stringify(postvars)
      })
        .then(res => res.json())
	.then(data => {
	  if (data['success']) {
	    selectedTask[0] = data['content'];
	    taskContent[selectedTask[1]] = data['content'];
	  }
	})
	.then(updateTasks);

      //change vars to vars retrieved from db call
    } catch (err) {
      console.warn(err);
    }

  }


  //button to save changes made to a task
  const saveButton = document.getElementById('saveButton');
  saveButton.onclick = sendChanges;

  //returns promise (bool resolve val) and makes a modal appear and ask a question
  function getUserConfirmation(message='Are you sure you want to delete?') {
    return new Promise((resolve, reject) => {
      let enteredval = false;
      try {
	let popupdiv = document.getElementById('userconfirmationbg');
	popupdiv.style.visibility = 'visible';
	document.getElementById('userconfirmationmessage').innerHTML = message;
	document.getElementById('userconfirmyes').onclick = ()=>{
	  popupdiv.style.visibility = 'hidden';
	  resolve(true);
	};

	document.getElementById('userconfirmno').onclick = ()=> {
	  popupdiv.style.visibility = 'hidden';
	  resolve(false);
	};
      } catch (error) {
	reject(error);
      }
    });

  }

  //code for the delete task function to work
  async function sendDeleteButton(element=null) {
    //if there aren't any tasks, return
    if (document.getElementById('createFirstTask')) {
      return;
    }
    let _selectedTask = [selectedTask[0]];
    //if user passed an element, use that element to get the task to delete
    if (element.dataset != undefined) {
      try {
	_selectedTask[0] = { id : element.dataset['taskid'] };
        console.log(element);
      } catch (error) {
	console.warn(error);
      }
    }

    //prompt and make sure the user wants to delete
    let userConfirmation = await getUserConfirmation();
    if (!userConfirmation) {
      return;
    }

    let postvar = { id : _selectedTask[0]['id'] };
    //query the postvar
    try {
      fetch("http://173.230.134.109/taskr/includes/delete_task.php", {
	method: 'POST',
	headers: {
	  'Content-Type': 'application/json'
	},
	body: JSON.stringify(postvar)
      })
      .then(res=>res.json())
      .then(response => {
	if (response['successful']) {
	  //refresh the page
	  location.reload();
	}
      });
    //log the res to the console
    } catch (error) {
      console.warn(error);
    }
  }

  const deleteTaskButton = document.getElementById('deleteTaskButton');
  deleteTaskButton.onclick = sendDeleteButton;


  async function getParentTask(task=selectedTask[0]) {
    let out = false;
    if (task['id']) {
      try {
	postvars = {
	  target : 'parent',
	  content : task
	};
	console.log(JSON.stringify(postvars));
	fetch('http://173.230.134.109/taskr/includes/task_tree.php', {
	  method: 'POST',
	  headers : {
	    'Content-Type' : 'application/json'
	  },
	  body : JSON.stringify(postvars)
	})
	.then(response => response.json())
	.then(data => {
	  console.log(data);
	  if (data['successful']) {
	    out = data['content'] ? data['content'] : {'parent' : false};
	  }
	});
      } catch (error) {
        console.warn(error);
      } finally {
        return out;
      }
    }
  }

  async function getChildTasks(task=selectedTask[0]) {
    let out = {'content': []};
    try {
      if (task['id']) {
        //create data in correct format
        postvars = {
	  target: 'children',
	  content: task['id']
        };
        //send data to server
	fetch('http://173.230.134.109/taskr/includes/task_tree.php', {
	  method: 'POST',
	  headers: {
	    'Content-Type' : 'application/json'
	  },
	  body: JSON.stringify(postvars)
	})
	.then(response => response.json())
	.then(data => {
          //act on results
	  console.log(data);
	  if (data['successful']) {
	    out = data['content'] ? data['content'] : out;
	  }
	});
      }
    } catch (error) {
      console.warn(error);
    } finally {
      return out;
    }
  }

  async function getRootTask(task=selectedTask[0]) {
    let out = task;
    try {
      if (task['id']) {
	let postvars = {
	  target : 'root',
	  content : task['id']
	};

	fetch ('http://173.230.134.109/taskr/includes/task_tree.php', {
	  method: 'POST',
	  headers: {
	    'Content-Type' : 'application/json'
	  },
	  body: JSON.stringify(postvars)
	})
	.then(response => response.json())
	.then(data => {
	  console.log(data);
	  if (data['successful']) {
	    out = data['content'] ? data['content'] : out;
	  }
	});
      } else {
	throw new Error('Task not a vaild task! (getRootTask was passed task=' + task + ')');
      }
    } catch (error) {
      console.warn(error);
    } finally {
      return out;
    }
  }


  let detailsElementsTemp = {};
  if (types.length > 0) {
    for (i in types) {
      let type = types[i];
      let idstr = 'task' + type.replace(/[^a-z]/g, '');
      let idlabelstr = idstr + 'label';
      if (idstr || idlabelstr) {
        detailsElementsTemp[type] = [
          document.getElementById(idstr),
          document.getElementById(idlabelstr),
          null //will (maybe) be defined later on
        ];

        //hide the label and fill inner html if it exists
        if(detailsElementsTemp[type][1]) {
          let underscoreIndex = type.indexOf("_");
          let label = underscoreIndex>0 ? type.substring(0, underscoreIndex) : type;
	  if (type=='last_modified') {
	    label = 'Last Modified';
	  }
          //console.log(underscoreIndex, label);
          if (label[0]) {
            label = label.replace(label[0], label[0].toUpperCase());
          }
          label += ": ";
          detailsElementsTemp[type][1].innerHTML = label;
          detailsElementsTemp[type][1].style.visibility = 'hidden';
        }
        //hide the content div if it exists and add the container as well
        if (detailsElementsTemp[type][0]) {
          if (findDetailsContainer(detailsElementsTemp[type][0])) {
            detailsElementsTemp[type][2] = findDetailsContainer(detailsElementsTemp[type][0]);
            detailsElementsTemp[type][2].style.visibility = 'hidden';
          }
          detailsElementsTemp[type][0].style.visibility = 'hidden';
          detailsElementsTemp[type][0].dataset['field'] = type;
        }

      }
    }
  }


  const detailsElements = detailsElementsTemp;
  //buttons that select tasks to be opened in the menu
  const taskMenuButtons = document.getElementsByClassName('taskmenubutton');

  //buttons that mark tasks 'complete' in the menu
  const taskMenuCompletedButtons = document.getElementsByClassName('menucheckedbutton');

  //buttons that delete tasks from the menu
  const taskMenuDeleteButtons = document.getElementsByClassName('menudeletebutton');

  function readFromDatabase(table="tasks", whereval=null, whereattr=null, reqlistenter=(e) =>{
  //  console.log(JSON.parse(e['target']['responseText']));
  }) {
    let whereinsert = (whereval===null||whereattr==null) ? '' : `%20WHERE%20${whereattr}=${whereval}`;
    let getQuery = `?query=SELECT%20*%20FROM%20${table}${whereinsert}`;
    let newurl = 'http://localhost:8080/taskr/includes/dbquery.php' + getQuery;
    let oReq = new XMLHttpRequest();
    oReq.addEventListener('load', reqlistenter);
    oReq.open('GET',newurl);
    oReq.send();
  }

  function fillDetail(detail) {
    let el;
    let ellbl;
    let elcontainer;
    let sl;

    if (detailsElements[detail]) {
      el = detailsElements[detail][0];
      ellbl = detailsElements[detail][1];
      elcontainer = detailsElements[detail][2];
    }

    if ((selectedTask[0][detail] && selectedTask[0][detail] !== '') || (detail=='last_modified')) {
      sl = selectedTask[0][detail];
      if (ellbl) {
        ellbl.style.visibility = 'visible';
        ellbl.style.width = 'auto';
        ellbl.style.height = 'auto';
      }

      if (elcontainer) {
        elcontainer.style.visibility = 'visible';
        elcontainer.style.width = 'auto';
        elcontainer.style.height = 'auto';
        elcontainer.style.margin = '20px';
      }

      //fill the element and make visible
      if (el) {
        el.style.visibility = 'visible';
        el.style.width = detail!='description' ? 'auto' : '100%';
        el.style.height = 'auto';
        if (el.tagName == 'INPUT') {
          if (el.type=='checkbox') {
            el.checked = sl==='1' ? true : false;
	    if (detail == 'completed' && getMenuCheckedButtonById(selectedTask[0]['id'])) {
	      getMenuCheckedButtonById(selectedTask[0]['id']).checked = sl=='1' ? true : false;
	    }
          } else {
            el.value = sl;
	    if (sl instanceof Date) {
	      let timestr = `${sl.getFullYear()}-${padNumber(sl.getMonth()+1, 2)}-${padNumber(sl.getDate(), 2)}`;
	      console.log(timestr);
	      el.value = timestr;
	      if (detail == 'last_modified') {
		el.value += sl.toTimeString();
	      }
	    }
          }
        } else if (el.tagName == 'SELECT') {
	  if (detail=='parent_id') {
	    let parentOptions = getParentTaskInputOptions(noParent=true);
	    for (let i=0;i<parentOptions.length; i++) {
	      if (sl == parentOptions[i].value || (sl=='' && parentOptions[i].value == '')) {
		el.insertBefore(parentOptions[i], el.firstChild);
		el.selectedIndex = 0;
	      } else {
		el.appendChild(parentOptions[i]);
	      }
	    }
	  }
	} else {
          el.innerHTML = sl;
	  if (sl instanceof Date) {
	    el.innerHTML = sl.toDateString();
	    if (detail != 'created_date') {
	      el.innerHTML += `, ${(sl.getHours() % 12) == 0 ? 12 : sl.getHours() % 12}:${sl.getMinutes()}  ${sl.getHours() >= 12 ? 'PM' : 'AM'}`;
	    }
	  }
        }
      }
    } else { //if detail undefined, we want to make it invisible
      if (el) {
        el.style.visibility = 'hidden';
        el.style.width = '0';
        el.style.height = '0';
        if (el.tagName == 'input') {
          el.value = '';
        } else {
          el.innerHTML = '';
        }
      }
      if (ellbl) {
        ellbl.style.visibility = 'hidden';
        ellbl.innerHTML = '';
        ellbl.style.width = '0';
        ellbl.style.height = '0';
      }

      if (elcontainer) {
        elcontainer.style.visibility = 'hidden';
        elcontainer.style.width = '0';
        elcontainer.style.height = '0';
        elcontainer.style.margin = 'none';
      }
    }

  }

  function fillDetails() {
    types.forEach((item, ind) => {
      fillDetail(item);
    });
  }

  //function to fill all the attributes (other than id) that are null/unfilled for the current task
  function fillAddAttributes() {
    let addAttributeArea = document.getElementById('addattributearea');
    try {
      //first, we access and remove all the items currently in the div
      let addAttributeAreaObjects = addAttributeArea.children;
      let addAttributeAreaItems = [];
      for (let i=0;i<addAttributeAreaObjects.length; i++) {
	addAttributeAreaItems.push(addAttributeAreaObjects[i]);
      }
      for (let i = 0; i < addAttributeAreaItems.length; i++ ) {
	if (addAttributeAreaItems[i].tagName != "H1") {
	  addAttributeAreaItems[i].remove();
	}
      }

      //second, add a label and input for each 'addable' attribute
      let addableAttributes = separateTypesByDefined()[1];
      addableAttributes.forEach((attribute, index) => {
	if (attribute != 'id') {
	let newLabel = document.createElement('label');
	let newInput;
	if (attribute == 'parent_id') {
	  newInput = document.createElement('select');
	} else if (attribute == 'description') {
	  newInput = document.createElement('textarea');
	} else {
	  newInput = document.createElement('input');
	  newInput.type = 'text';
	}
	newLabel.htmlFor = attribute;
	newLabel.innerHTML = attribute.replace(/_/g, ' ');
	newLabel.dataset['toggle'] = 'collapse';
	newLabel.dataset['target'] = `#${attribute}`;
	newLabel.className = "menubutton highlightonhover hovershadow";
	newInput.name = attribute;
	newInput.dataset['field'] = attribute;
	newInput.id = attribute;
	newInput.className = 'collapse';
        //conditions for a different type
        switch (attribute) {
	  case "due_date":
	    newInput.type = 'date';
	    break;
	  case "parent_id":
	    let parentOptions = getParentTaskInputOptions();
	    if (parentOptions.length == 0) {
	      newInput.style.visibility = 'hidden';
	      break;
	    }
	    parentOptions.forEach((option, index) => {
	      newInput.appendChild(option);
	    });
	    break;
	}

	addAttributeArea.appendChild(newLabel);
	addAttributeArea.appendChild(newInput);
	}
      });
    } catch (error) {
      console.warn(error);
    }
  }

  function getParentSelectOptions(all=false) {
    let out = [];
    for (let i = 0; i < taskContent.length; i++) {
      if (i !== selectedTask[1] || all) {
	out.push(taskContent[i]);
      }
    }
    return out;
  }

  function getParentTaskInputOptions(all=false, noParent=true) {
    let out = [];
    let options = getParentSelectOptions(all);
    if (noParent) {
      options = [{id:'', name:'No Parent'}].concat(options);
    }
    options.forEach((option, index) => {
      let newOption = document.createElement('OPTION');
      newOption.value = option['id'];
      newOption.innerHTML = option['name'];
      out.push(newOption);
    });

    return out;
  }

  function fillNewTaskParentOptions() {
    let optionElements = getParentTaskInputOptions(true);
    let selectElement = document.getElementById('parentTaskSelect');
    let selectContainer = document.getElementById('parentTaskContainer');
    if (selectElement) {
      for (let i = 0; i < optionElements.length; i++) {
	selectElement.appendChild(optionElements[i]);
	if (i==0) { //run only once
	  selectContainer.style.visibility = 'visible';
	  selectContainer.style.height = 'auto';
	  selectContainer.style.width = 'auto';
	}
      }
    }
  }


  //return all the input fields currently in the attribute div
  function findAddAttributeInputs() {
    let addAttributeArea = document.getElementById('addattributearea');
    let out = [];
    let items = addAttributeArea.children;
    for (let i=0; i < items.length; i++) {
      if (['INPUT', 'SELECT', 'TEXTAREA'].includes(items[i].tagName)) {
	out.push(items[i]);
      }
    }
    return out;
  }


  function fillTaskMenuButtons() {
    for (var i = 0; i < taskMenuButtons.length; i++) {
      let taskId = taskMenuButtons[i].dataset['taskid'];
      let thisTask = findTaskFromId(taskId);
      let checkbox = taskMenuCompletedButtons[i];
      let deletebutton = taskMenuDeleteButtons[i];
      taskMenuButtons[i].innerHTML = thisTask[0]['name'];
      taskMenuButtons[i].onclick = e => {
        selectedTask = thisTask;
        for(let j = 0; j < taskMenuButtons.length; j++) {
          taskMenuButtons[j].style.background='#00f3';
        }
        e.target.style.background = '#00f7';
        updateTasks();
      }
      checkbox.addEventListener('input', e => {
        sendChanges(e.target);
      }, true);

      deletebutton.addEventListener('click', e => {
        sendDeleteButton(e.target);
      }, false)
    }
  }

  function findTaskFromId(id) {
    let tsk = {};
    let tskIndex;
    taskContent.forEach((task, index) => {
      if (task['id'] == id) {
        tsk = task;
	tskIndex = index;
      }
    });
    return [tsk, tskIndex];
  }

  function getMenuCheckedButtonById(id) {
    for (let i=0; i < taskMenuCompletedButtons.length; i++) {
      if (taskMenuCompletedButtons[i].dataset['taskid'] == id) {
	return taskMenuCompletedButtons[i];
      }
    }
    return false;
  }

  function updateTasks() {
    //make sure all 'last_modified', 'created_date', and 'due_date' are dates
    let dateFields = ['created_date', 'last_modified', 'due_date'];
    taskContent.forEach(task => {
      dateFields.forEach(_date => {
	if (typeof task[_date] == 'string' && task[_date] != '') {
	  task[_date] = new Date(task[_date].replace(" ", "T"));
	}
      });
    });
    taskContent = filterSortTasks();
    //if the selected task isn't defined, let's define it
    if (taskContent.length > 0 && selectedTask[1] === null) {
      selectedTask = [taskContent[0], 0];
    }

    fillTaskMenuButtons();
    fillDetails();
    fillAddAttributes();
  }

  hiddendiv.innerHTML='';
  for (let i = 0; i < taskMenuButtons.length; i++) {
    if (taskMenuButtons[i].dataset['taskid'] == selectedTask['id']) {
      taskMenuButtons[i].style.background = '#00f7';
    }
  }

  updateTasks();

  //to make the menu close properly
  var menubuttons = document.getElementsByClassName('menubutton');
  for (var i = 0; i < menubuttons.length; i++) {
    if (menubuttons[i].href) {
      continue;
    }
    if (!menubuttons[i].dataset['toggle']) {
      menubuttons[i].dataset['toggle'] = 'collapse';
    }

    if (!menubuttons[i].dataset['target']) {
      menubuttons[i].dataset['target'] = '#menu';
    }
  }


  //make the 'try creating one' text that appears b4 the first task is created work
  let createFirstTaskButton = document.getElementById('createFirstTask');
  if (createFirstTaskButton) {
  createFirstTaskButton.onclick = () => {
    let addtaskform = document.getElementById('addtaskform');
    addtaskform.className = 'collapse show';
    addtaskform.scrollIntoView();
    addtaskform.style.boxShadow = '0 0 5px blue';
    setTimeout(() => {
      addtaskform.style.boxShadow = 'none';
    }, 3000);
  };
  }


  //once all other stuff is done, make the app visible/usable and remove the enable js message
  document.getElementById('content').style.visibility = "visible";
  //inside of a try block bc the removal may not work if the php in the back end changes
  try {
    document.getElementById('loadMessage').remove();
  } catch (err) {
    console.warn(err);
  }

  //console.log(taskChanged());
  //an interval for page stuff

  let newchange = false;
  var x = setInterval(()=> {

    if (taskChanged()) {
      saveButton.style.background = '#acf';
      if (newchange) {
        saveButton.style.boxShadow = "0 0 5px #00f";
        newchange = false;
        setTimeout(()=>{
          saveButton.style.boxShadow = '';
        }, 800);
      }
    } else {
      saveButton.style.background = '#aaa';
      newchange = true;
    }

  }, 500);

  fillNewTaskParentOptions();
}
