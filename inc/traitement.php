<?php
include ("../../../inc/includes.php");

global $DB, $CFG_GLPI;

//if(isset($_POST['lastname']) && isset($_POST['firstname']) && isset($_POST['mail']) && isset($_POST['phone']) && isset($_POST['entity_id'])){
    $lastname = $_GET['lastname'];
    $firstname = $_GET['firstname'];
    $mail = $_GET['mail'];
    $phone = $_GET['phone'];
    $entity_id = $_GET['entity_id'];

    $username = $firstname.".".$lastname;
    
    $query = "INSERT INTO glpi_users (name, realname, firstname, phone) VALUES ('$username', '$lastname', '$firstname', $phone)";
    $DB->query($query)


    $query = "INSERT INTO glpi_users (name, realname, firstname, phone) VALUES ('$username', '$lastname', '$firstname', $phone)";
    if(!$DB->query($query)){
        echo json_encode("<b>Erreur lors de l'ajout du demandeur : <br><br>" . $DB->error());
    }

    

//}

?>


