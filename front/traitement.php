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

        if ($mailto === "true") {
            global $DB, $CFG_GLPI;

            // --- Balises ---
            $Balises = [
                ['Balise' => '##id.user##',        'Value' => (string)$username],
                ['Balise' => '##user.password##',  'Value' => (string)$cleanedPassword],
                ['Balise' => '##user.lastname##',  'Value' => (string)$lastname],
                ['Balise' => '##user.firstname##', 'Value' => (string)$firstname],
            ];

            if (!function_exists('balise')) {
                function balise($corps, $Balises = []) {
                    if ($corps === null) return '';
                    if (!isset($Balises) || !is_iterable($Balises)) return (string)$corps;
                    foreach ($Balises as $b) {
                        $tag = isset($b['Balise']) ? (string)$b['Balise'] : '';
                        if ($tag === '') continue;
                        $val = array_key_exists('Value', $b) ? (string)$b['Value'] : '';
                        $corps = str_replace($tag, $val, (string)$corps);
                    }
                    return $corps;
                }
            }

            if (!function_exists('normalize_eols')) {
                function normalize_eols(string $s): string {
                    $s = str_replace("\0", '', $s);
                    return preg_replace("/\r\n|\r|\n/u", "\r\n", $s);
                }
            }

            // --- Validation destinataire ---
            $mail = trim((string)($mail ?? ''));
            if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                $errors[]  = "Adresse e-mail destinataire invalide : $mail";
                $success[] = "Demandeur ajouté avec succès (mais sans envoi d'e-mail)";
            } else {

                // --- Récupération du gabarit (avec fallback de langue) ---
                $config = PluginRtConfig::getInstance();
                $notificationtemplates_id = (int)($config->fields['gabarit'] ?? 0);

                $Subject = $BodyHtml = $BodyText = '';

                if ($notificationtemplates_id > 0) {
                    $curLang = $_SESSION['glpilanguage'] ?? ($CFG_GLPI['language'] ?? 'fr_FR');
                    $langs   = array_values(array_unique([$curLang, substr($curLang, 0, 2), '']));

                    // ORDER BY FIELD(language, curLang, short, '')
                    $order = new \QueryExpression(
                        "FIELD(language,'" . implode("','", array_map('addslashes', $langs)) . "')"
                    );

                    $row = $DB->request([
                        'SELECT' => ['subject','content_text','content_html','language'],
                        'FROM'   => 'glpi_notificationtemplatetranslations',
                        'WHERE'  => [
                            'notificationtemplates_id' => $notificationtemplates_id,
                            'language'                 => $langs, // IN (...)
                        ],
                        'ORDER'  => [$order],
                        'LIMIT'  => 1
                    ])->current();

                    if (!is_array($row)) {
                        // Ultime recours : sans filtre de langue
                        $row = $DB->request([
                            'SELECT' => ['subject','content_text','content_html','language'],
                            'FROM'   => 'glpi_notificationtemplatetranslations',
                            'WHERE'  => ['notificationtemplates_id' => $notificationtemplates_id],
                            'LIMIT'  => 1
                        ])->current();
                    }

                    if (is_array($row)) {
                        $Subject  = (string)($row['subject'] ?? '');
                        $BodyText = isset($row['content_text'])
                                    ? html_entity_decode((string)$row['content_text'], ENT_QUOTES, 'UTF-8') : '';
                        $BodyHtml = isset($row['content_html'])
                                    ? html_entity_decode((string)$row['content_html'], ENT_QUOTES, 'UTF-8') : '';
                    } else {
                        $errors[]  = "Erreur : aucun gabarit valide trouvé, e-mail non envoyé.";
                        $success[] = "Demandeur ajouté avec succès (mais sans envoi d'e-mail)";
                    }
                } else {
                    $errors[]  = "Erreur : aucun gabarit rattaché dans la configuration.";
                    $success[] = "Demandeur ajouté avec succès (mais sans envoi d'e-mail)";
                }

                if (empty($errors) || (count($errors) && ($Subject.$BodyHtml.$BodyText) !== '')) {
                    // --- Footer (signature) ---
                    $footerVal = '';
                    $cfgRow = $DB->request([
                        'SELECT' => ['value'],
                        'FROM'   => 'glpi_configs',
                        'WHERE'  => ['name' => 'mailing_signature'],
                        'LIMIT'  => 1
                    ])->current();
                    if (is_array($cfgRow) && !empty($cfgRow['value'])) {
                        $footerVal = html_entity_decode((string)$cfgRow['value'], ENT_QUOTES, 'UTF-8');
                    }

                    // --- Construction e-mail (GLPIMailer / Symfony Mailer) ---
                    $mmail = new GLPIMailer();
                    $mmail->addCustomHeader("X-Auto-Response-Suppress: OOF, DR, NDR, RN, NRN");

                    // From sécurisé (fallback)
                    $fromEmail = !empty($CFG_GLPI['from_email'])
                        ? (string)$CFG_GLPI['from_email']
                        : (!empty($CFG_GLPI['admin_email']) ? (string)$CFG_GLPI['admin_email'] : 'no-reply@localhost');

                    $fromName = $CFG_GLPI['from_email_name'] ?? $CFG_GLPI['admin_email_name'] ?? null;
                    $fromName = (is_string($fromName) && $fromName !== '') ? $fromName : 'GLPI';

                    $emailObj = $mmail->getEmail();
                    $emailObj->from(new \Symfony\Component\Mime\Address($fromEmail, $fromName));
                    $emailObj->to($mail); // pas de "name" pour éviter null

                    // Sujet / Corps avec balises + normalisation EOL
                    $Subject   = is_string($Subject)  ? $Subject  : '';
                    $BodyHtml  = is_string($BodyHtml) ? $BodyHtml : '';
                    $BodyText  = is_string($BodyText) ? $BodyText : '';
                    $footerStr = is_string($footerVal) ? $footerVal : '';

                    if ($Subject !== '') {
                        $mmail->Subject = balise($Subject, $Balises);
                    }

                    $html = normalize_eols(balise($BodyHtml, $Balises));
                    $txt  = normalize_eols(balise($BodyText, $Balises));

                    if ($footerStr !== '') {
                        $html .= "<br>" . $footerStr;
                        $txt  .= "\r\n" . strip_tags($footerStr);
                    }

                    $mmail->Body    = $html;
                    $mmail->AltBody = $txt;

                    // Envoi
                    if ($mmail->send()) {
                        $success[] = "Demandeur ajouté avec succès, informations envoyées par e-mail à : $mail";
                    } else {
                        $success[] = "Demandeur ajouté avec succès";
                        $errors[]  = "Erreur lors de l'envoi du mail à : $mail";
                    }

                    // Nettoyage
                    $mmail->ClearAddresses();
                }
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
