<?php
include ("../../../inc/includes.php");

global $DB, $CFG_GLPI;

use Glpi\Event;

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

if(empty($_GET['lastname']) || empty($_GET['firstname']) || empty($_GET['mail'])){
    echo json_encode("<b>Les champs obligatoire ne peuvent pas être vide", JSON_UNESCAPED_UNICODE);

}else{
    $lastname = $_GET['lastname'];
    $firstname = $_GET['firstname'];
    $mail = $_GET['mail'];
    $phone = $_GET['phone'];
    $entity_id = $_GET['entity_id'];
    $username = $firstname.".".$lastname;
    $username = strtolower($username);
    $mailto = $_GET['mailto'];

    if (filter_var($mail, FILTER_VALIDATE_EMAIL) === FALSE) {
        echo json_encode("Format du mail incorrect", JSON_UNESCAPED_UNICODE);
    }else{
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
                Event::log(
                    $UserId,
                    "users",
                    4,
                    "setup",
                    sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $username)
                );
                $query = "UPDATE glpi_profiles_users SET entities_id = $entity_id, is_recursive = 1 WHERE users_id = $UserId";
            if(!$DB->query($query)){
                echo json_encode("<b>Erreur lors de l'ajout de l'entité : <br><br>" . $DB->error(), JSON_UNESCAPED_UNICODE);
            }else{
                Event::log(
                    $UserId,
                    "users",
                    5,
                    "setup",
                    sprintf(__('%s updates an item'), $_SESSION["glpiname"])
                );
            }
    
                $InputUserMail = [
                    'email'     => $mail,
                    'users_id'   => $UserId,
                ];
            if($usermail->add($InputUserMail)){
                $message = "Demandeur Ajouté avec succès";
                echo json_encode($message, JSON_UNESCAPED_UNICODE);
                if($mailto == "true"){
                    $message = "<br>Informations d'identification envoyées par e-mail : " . $mail;
                    echo json_encode($message, JSON_UNESCAPED_UNICODE);
                }
            }else{
                $message = "<b>Erreur lors de l'ajout du mail du demandeur. Le demandeur a bien été ajouté sans son mail.";
                echo json_encode($message, JSON_UNESCAPED_UNICODE);
            }
        }else{
            $message = "<b>Erreur lors de l'ajout du demandeur : Le demandeur est peut être existant.";
            echo json_encode($message, JSON_UNESCAPED_UNICODE);
        }
    }   
}
?>


