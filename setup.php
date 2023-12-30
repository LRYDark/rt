<?php
define('PLUGIN_RT_VERSION', '1.3.5'); // version du plugin

// Minimal GLPI version,
define("PLUGIN_RT_MIN_GLPI", "10.0.3");
// Maximum GLPI version,
define("PLUGIN_RT_MAX_GLPI", "10.2.0");

define("PLUGIN_RT_WEBDIR", Plugin::getWebDir("rt"));

/****************************************************************************************************************************************** */
if (!isset($_SESSION['alert_displayedRT']) && isset($_SESSION['glpiID']) && $_SESSION['glpiactiveprofile']['name'] == 'Super-Admin'){
   $_SESSION['alert_displayedRT'] = true;

   //token GitHub et identification du répertoire
   global $DB;
   $tokenID = $DB->query("SELECT token FROM `glpi_plugin_rt_configs` WHERE id = 1")->fetch_object();
   if (!empty($tokenID->token)){
      $token = $tokenID->token;
      $owner = 'LRYDark';
      $repo = 'rt';
   
      // Créez une fonction pour effectuer des requêtes à l'API GitHub
      function requestGitHubAPIRT($url, $token) {
         $ch = curl_init($url);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $token,
            'User-Agent: PHP-Script',
            'Accept: application/vnd.github+json'
         ]);
         $response = curl_exec($ch);
         curl_close($ch);
         return json_decode($response, true);
      }
   
      // Récupérer la dernière version (release) disponible
      function getLatestReleaseRT($owner, $repo, $token) {
         $url = "https://api.github.com/repos/{$owner}/{$repo}/releases/latest";
         return requestGitHubAPIRT($url, $token);
      }
   
      //$latestRelease = getLatestRelease($owner, $repo, $token);
      $latestRelease = getLatestReleaseRT($owner, $repo, $token);
   
      if(isset($latestRelease['tag_name'])){
         $version = str_replace("rt-", "", $latestRelease['tag_name']);// Utilisation de str_replace pour retirer "rp-"
   
         if ($version > PLUGIN_RT_VERSION){
            // Afficher la pop-up avec JavaScript
            echo "<script>
               window.addEventListener('load', function() {
                  alert('Une nouvelle version du plugin rt est disponible (version : " . $latestRelease['tag_name'] . "). <br>Veuillez mettre à jour dès que possible.');
               });
            </script>";
         }
      }
   }
}
/****************************************************************************************************************************************** */

function plugin_init_rt() { // fonction glpi d'initialisation du plugin
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['rt'] = true;
   $PLUGIN_HOOKS['change_profile']['rt'] = ['PluginRtProfile', 'initProfile'];

   $plugin = new Plugin();

   if (Session::getLoginUserID()) {
      Plugin::registerClass('PluginRtProfile', ['addtabon' => 'Profile']);
   }

   if ($plugin->isActivated('rt')){ // verification si le plugin rt est installé et activé
      Plugin::registerClass('PluginRtTicket', ['addtabon' => 'Ticket']);

      $PLUGIN_HOOKS['config_page']['rt'] = 'front/config.form.php'; // initialisation de la page config
      Plugin::registerClass('PluginRtConfig', ['addtabon' => 'Config']); // ajout de la de la class config dans glpi

      $PLUGIN_HOOKS['post_item_form']['rt'] = ['PluginRtTicket','formroutetime']; // initialisation de la class formroutetime

      $PLUGIN_HOOKS['item_add']['rt'] = [
         'ITILFollowup'   => ['PluginRtTicket', 'addroutetime'],
         'ITILSolution'   => ['PluginRtTicket', 'addroutetime'],
         'TicketTask'     => ['PluginRtTicket', 'addroutetime'],
      ];  // initialisation de la class addroutetime dans les tasks "suivi -> ITILFollowup | solution -> ITILSolution | tache -> TicketTask"

      $PLUGIN_HOOKS['pre_item_update']['rt'] = ['TicketTask' => 'plugin_rt_item_update']; // initialisation de la class
      $PLUGIN_HOOKS['post_show_item']['rt'] = ['PluginRtTicket', 'postShowItemNewTicketRT']; // initialisation de la class
      $PLUGIN_HOOKS['pre_show_item']['rt'] = ['PluginRtTicket', 'postShowItemNewTaskRT']; // initialisation de la class
      $PLUGIN_HOOKS['pre_item_form']['rt'] = ['PluginRtChrono', 'postShowItemChrono']; // initialisation de la class   
   }
}

function plugin_version_rt() { // fonction version du plugin (verification et affichage des infos de la version)
   return [
      'name'           => _n('Route Time', 'Route Time', 2, 'rt'),
      'version'        => PLUGIN_RT_VERSION,
      'author'         => 'REINERT Joris',
      'homepage'       => 'https://www.jcd-groupe.fr',
      'requirements'   => [
         'glpi' => [
            'min' => PLUGIN_RT_MIN_GLPI,
            'max' => PLUGIN_RT_MAX_GLPI,
         ]
      ]
   ];
}

/**
 * @return bool
 */
function plugin_rt_check_prerequisites() {
   return true;
}
