<?php
include 'dbconnect.php';
header("Content-type: application/json");
if (!isset($_GET['query'], $conn)) {
  echo "{".'"'."content".'"'.":".'"'."something went wrong".'"'."}";
} else {
  $query = make_safe($_GET['query']);
  $queryres = $conn->query($query);
  $queryarray = $queryres->fetch_all(MYSQLI_ASSOC);
  $out = '{"content":[';
  foreach ($queryarray as $record) {
    $out = $out.'{';
    foreach($record as $field => $value) {
      $out = $out."".'"'."$field".'"'.":".'"'."$value".'"'.",";
    }
    $l = strlen($out) - 1;
    $out = substr($out, 0, $l);
    $out = $out.'},';
  }
  $l = strlen($out) - 1;
  $out = substr($out, 0, $l);
  $out = $out.']}';
  echo $out;
  $queryres->close();
}

?>
