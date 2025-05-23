<?php
header('Content-Type: application/json; charset=utf-8');
include("../../../inc/includes.php");
use Glpi\Event;

$errors = [];
$success = [];

global $DB, $CFG_GLPI;

$user      = new User();
$usermail  = new UserEmail();
$profile   = new Profile_User();

function cleanString($str) {
    $str = str_replace(' ', '', $str);
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9]/', '', $str);
    return 'JCD' . $str . '123$';
}

// Champs requis
if (empty($_GET['lastname']) || empty($_GET['firstname']) || empty($_GET['mail'])) {
    $errors[] = "Les champs obligatoires ne peuvent pas être vides";
}

$lastname = $_GET['lastname'] ?? '';
$firstname = $_GET['firstname'] ?? '';
$mail = $_GET['mail'] ?? '';
$phone = $_GET['phone'] ?? '';
$entity_id = $_GET['entity_id'] ?? 0;
$mailto = $_GET['mailto'] ?? 'false';

$username = strtolower($firstname . "." . $lastname);

if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Format du mail incorrect";
}

$entities_query = $DB->doQuery("SELECT name FROM glpi_entities WHERE id = $entity_id");
if (!$entities_query || $entities_query->num_rows == 0) {
    $errors[] = "Entité introuvable";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => $success,
        'errors'  => $errors
    ], JSON_UNESCAPED_UNICODE);
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

if ($UserId = $user->add($InputUser)) {
    Event::log($UserId, "users", 4, "setup", sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $username));

    $query = "UPDATE glpi_profiles_users SET entities_id = $entity_id, is_recursive = 1 WHERE users_id = $UserId";
    if (!$DB->doQuery($query)) {
        $errors[] = "Erreur lors de l'ajout de l'entité : " . $DB->error;
    }

    Event::log($UserId, "users", 5, "setup", sprintf(__('%s updates an item'), $_SESSION["glpiname"]));

    $InputUserMail = [
        'email'    => $mail,
        'users_id' => $UserId,
    ];

    if ($usermail->add($InputUserMail)) {
        $cleanedPassword = cleanString($entities_name->name . $lastname);

        if ($mailto == "true") {
            $Balises = [
                ['Balise' => '##id.user##',        'Value' => $username],
                ['Balise' => '##user.password##',  'Value' => $cleanedPassword],
                ['Balise' => '##user.lastname##',  'Value' => $lastname],
                ['Balise' => '##user.firstname##', 'Value' => $firstname]
            ];

            function balise($corps){
                global $Balises;
                foreach($Balises as $balise) {
                    $corps = str_replace($balise['Balise'], $balise['Value'], $corps);
                }
                return $corps;
            }

            $mmail  = new GLPIMailer();
            $config = PluginRtConfig::getInstance();

            $notificationtemplates_id = $config->fields['gabarit'];
            $NotifMailTemplate = $DB->doQuery("SELECT * FROM glpi_notificationtemplatetranslations WHERE notificationtemplates_id=$notificationtemplates_id")->fetch_object();

            if ($NotifMailTemplate) {
                $BodyHtml = html_entity_decode($NotifMailTemplate->content_html, ENT_QUOTES, 'UTF-8');
                $BodyText = html_entity_decode($NotifMailTemplate->content_text, ENT_QUOTES, 'UTF-8');
            } else {
                $errors[] = "Erreur !! Aucun gabarit rattaché, impossible d'envoyer un e-mail.";
            }

            $footer = $DB->doQuery("SELECT value FROM glpi_configs WHERE name = 'mailing_signature'")->fetch_object();
            $footer = !empty($footer->value) ? html_entity_decode($footer->value, ENT_QUOTES, 'UTF-8') : '';

            $mmail->AddCustomHeader("X-Auto-Response-Suppress: OOF, DR, NDR, RN, NRN");
            $from_email = !empty($CFG_GLPI["from_email"]) ? $CFG_GLPI["from_email"] : $CFG_GLPI["admin_email"];
            $from_name  = $CFG_GLPI["from_email_name"] ?? $CFG_GLPI["admin_email_name"] ?? 'GLPI';

            if (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Adresse e-mail expéditeur invalide. Vérifiez la configuration GLPI.";
                $success[] = "Demandeur ajouté avec succès (mais sans envoi d'e-mail)";
            } else {
                $mmail->SetFrom($from_email, $from_name, false);
                $mmail->AddAddress($mail);
                $mmail->isHTML(true);
                $mmail->Subject = balise($NotifMailTemplate->subject);
                $mmail->Body = GLPIMailer::normalizeBreaks(balise($BodyHtml)) . $footer;
                $mmail->AltBody = GLPIMailer::normalizeBreaks(balise($BodyText)) . $footer;

                if ($mmail->send()) {
                    $success[] = "Demandeur ajouté avec succès, informations envoyées par e-mail à : $mail";
                } else {
                    $success[] = "Demandeur ajouté avec succès";
                    $errors[] = "Erreur lors de l'envoi du mail à : $mail";
                }

                $mmail->ClearAddresses();
            }
        } else {
            $success[] = "Demandeur ajouté avec succès";
        }

        $user_info = [
            'user'     => $username,
            'password' => $cleanedPassword
        ];

    } else {
        $success[] = "Demandeur ajouté sans adresse mail";
        $errors[]  = "Erreur lors de l'ajout du mail du demandeur.";
        $user_info = [
            'user'     => $username,
            'password' => cleanString($entities_name->name . $lastname)
        ];
    }
} else {
    $errors[] = "Erreur lors de l'ajout du demandeur : le demandeur est peut-être existant.";
}

http_response_code(!empty($errors) ? 400 : 200);
echo json_encode([
    'success' => $success,
    'errors'  => $errors,
    'data'    => $user_info ?? null
], JSON_UNESCAPED_UNICODE);
exit;
