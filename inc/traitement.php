<?php
include ("../../../inc/includes.php");

global $DB, $CFG_GLPI;
$user      = new User();
$usermail  = new UserEmail();
$profile   = new Profile_User();

    function cleanString($str) {
        // Supprimer les espaces
        $str = str_replace(' ', '', $str);
        // Convertir les majuscules en minuscules
        $str = strtolower($str);
        // Supprimer les caractères non alphanumériques (vous pouvez ajuster l'expression régulière selon vos besoins)
        $str = preg_replace('/[^a-z0-9]/', '', $str);
        $str =  'JCD'.$str.'123$';
        return $str;
    }
    
//if(isset($_POST['lastname']) && isset($_POST['firstname']) && isset($_POST['mail']) && isset($_POST['phone']) && isset($_POST['entity_id'])){
    $lastname = $_GET['lastname'];
    $firstname = $_GET['firstname'];
    $mail = $_GET['mail'];
    $phone = $_GET['phone'];
    $entity_id = $_GET['entity_id'];
    $username = $firstname.".".$lastname;
    $username = strtolower($username);

    $entities_name  = $DB->query("SELECT name from glpi_entities WHERE id = $entity_id")->fetch_object();
    $password = password_hash(cleanString($entities_name->name), PASSWORD_DEFAULT);

        $InputUser = [
            'name'          => $username,
            'password'      => $password,
            'realname'      => $lastname,
            'firstname'     => $firstname,
            'phone'         => $phone
        ];
    if($UserId = $user->add($InputUser)){
            $query = "UPDATE glpi_profiles_users SET entities_id = $entity_id, is_recursive = 1 WHERE users_id = $UserId";
        if(!$DB->query($query)){
            echo json_encode("<b>Erreur lors de l'ajout de l'entité : <br><br>" . $DB->error());
        }

            $InputUserMail = [
                'email'     => $mail,
                'users_id'   => $UserId,
            ];
        if($usermail->add($InputUserMail)){
            echo json_encode("Demandeur Ajouté");
        }else{
            echo json_encode("<b>Erreur lors de l'ajout du mail : <br><br>" . $DB->error());
        }

    }else{
        echo json_encode("<b>Erreur lors de l'ajout du demandeur : <br><br>" . $DB->error());
    }
    

    /*$query = "INSERT INTO glpi_users (name, password, realname, firstname, phone) VALUES ('$username', '$password','$lastname', '$firstname', $phone)";
    if(){
        echo json_encode("<b>Erreur lors de l'ajout du demandeur : <br><br>" . $DB->error());
    }*/

    

//}

?>


