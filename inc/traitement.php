<?php
include ("../../../inc/includes.php");

global $DB, $CFG_GLPI;

$query = $_POST['query'];

if (isset($query) && !empty($query)) {
    $result = $DB->query($query);
}
?>
