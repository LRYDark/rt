<?php
include ("../../../inc/includes.php");

global $DB, $CFG_GLPI;

use Glpi\Event;

$user      = new User();
$usermail  = new UserEmail();
$profile   = new Profile_User();

function cleanString($str) {
    // Supprimer les espaces et convertir en minuscules
    $str = str_replace(' ', '', $str);
    $str = strtolower($str);
    // Supprimer les caractères non alphanumériques
    $str = preg_replace('/[^a-z0-9]/', '', $str);
    return 'JCD' . $str . '123$';
}

// Vérifiez si les champs obligatoires sont vides
if (empty($_GET['lastname']) || empty($_GET['firstname']) || empty($_GET['mail'])) {
    echo json_encode(["error" => "Les champs obligatoires ne peuvent pas être vides"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Récupérez les valeurs des paramètres
$lastname = $_GET['lastname'];
$firstname = $_GET['firstname'];
$mail = $_GET['mail'];
$phone = $_GET['phone'];
$entity_id = $_GET['entity_id'];
$mailto = $_GET['mailto'] ?? 'false';

$username = strtolower($firstname . "." . $lastname);

// Vérifiez le format de l'adresse e-mail
if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); // Code d'erreur HTTP
    echo json_encode(["error" => "Format du mail incorrect"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Récupérez le nom de l'entité
$entities_query = $DB->query("SELECT name FROM glpi_entities WHERE id = $entity_id");
if (!$entities_query || $entities_query->num_rows == 0) {
    http_response_code(400); // Code d'erreur HTTP
    echo json_encode(["error" => "Entité introuvable"], JSON_UNESCAPED_UNICODE);
    exit;
}

$entities_name = $entities_query->fetch_object();
$password = password_hash(cleanString($entities_name->name . $lastname), PASSWORD_DEFAULT);

$InputUser = [
    'name'      => $username,
    'password'  => $password,
    'realname'  => $lastname,
    'firstname' => $firstname,
    'phone'     => $phone
];

// Essayez d'ajouter l'utilisateur
if ($UserId = $user->add($InputUser)) {
    Event::log($UserId, "users", 4, "setup", sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $username));

    // Mettez à jour le profil de l'utilisateur
    $query = "UPDATE glpi_profiles_users SET entities_id = $entity_id, is_recursive = 1 WHERE users_id = $UserId";
    if (!$DB->query($query)) {
        http_response_code(400); // Code d'erreur HTTP
        echo json_encode(["error" => "Erreur lors de l'ajout de l'entité : " . $DB->error], JSON_UNESCAPED_UNICODE);
        exit;
    }

    Event::log($UserId, "users", 5, "setup", sprintf(__('%s updates an item'), $_SESSION["glpiname"]));

    // Ajoutez l'adresse e-mail de l'utilisateur
    $InputUserMail = [
        'email'    => $mail,
        'users_id' => $UserId,
    ];

    if ($usermail->add($InputUserMail)) {
        $cleanedPassword = cleanString($entities_name->name . $lastname); // Mot de passe non haché

        // Message de succès
        if ($mailto == "true") {
            $message = [
                "success" => "Demandeur Ajouté avec succès, informations envoyées par e-mail à : " . $mail,
                "user" => $username,
                "password" => $cleanedPassword
            ];
            echo json_encode($message, JSON_UNESCAPED_UNICODE);
        }else{
            $message = [
                "success" => "Demandeur Ajouté avec succès",
                "user" => $username,
                "password" => $cleanedPassword
            ];
            echo json_encode($message, JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(400); // Code d'erreur HTTP
        echo json_encode(["error" => "Erreur lors de l'ajout du mail du demandeur. Le demandeur a bien été ajouté sans son mail."], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400); // Code d'erreur HTTP
    echo json_encode(["error" => "Erreur lors de l'ajout du demandeur : le demandeur est peut-être existant."], JSON_UNESCAPED_UNICODE);
}
