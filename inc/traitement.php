<?php
include ("../../../inc/includes.php");

global $DB, $CFG_GLPI;

$query = "INSERT INTO glpi_users (NAME, realname, firstname, phone) VALUES ('RJ', 'Reinert', 'Joris', 0603905636)";
$DB->query($query);

/*if(isset($_POST['nom']) && isset($_POST['prenom']) && isset($_POST['mail']) && isset($_POST['phone'])){
    $lastname = $_POST['lastname'];
    $firstname = $_POST['firstname'];
    $mail = $_POST['mail'];
    $phone = $_POST['phone'];

    $query = "INSERT INTO glpi_users (name, realname, firstname, phone) VALUES ('RJ', '$lastname', '$firstname', $phone)";
    $DB->query($query);
}*/
?>
