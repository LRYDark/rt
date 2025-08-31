<?php
define('PLUGIN_RT_VERSION', '1.6.0_beta3'); // version du plugin

// Minimal GLPI version,
define("PLUGIN_RT_MIN_GLPI", "10.0.3");
// Maximum GLPI version,
define("PLUGIN_RT_MAX_GLPI", "11.0.1");

define("PLUGIN_RT_WEBDIR", Plugin::getWebDir("rt"));
define("PLUGIN_RT_DIR", Plugin::getPhpDir("rt"));

function plugin_init_rt() { // fonction glpi d'initialisation du plugin
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['rt'] = true;
   $PLUGIN_HOOKS['change_profile']['rt'] = ['PluginRtProfile', 'initProfile'];

   $plugin = new Plugin();

   if (Session::getLoginUserID()) {
      Plugin::registerClass('PluginRtProfile', ['addtabon' => 'Profile']);
   }

   if ($plugin->isInstalled('rt') && $plugin->isActivated('rt')){ // verification si le plugin rt est installé et activé
      Plugin::registerClass('PluginRtTicket', ['addtabon' => 'Ticket']);

      $PLUGIN_HOOKS['config_page']['rt'] = '../../front/config.form.php?forcetab=' . urlencode('PluginRtConfig$1'); // initialisation de la page config
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

      if (Session::haveRight("plugin_rt_chrono", READ)) {
         $PLUGIN_HOOKS['pre_item_form']['rt'] = ['PluginRtChrono', 'postShowItemChrono']; // initialisation de la class   
      }
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
