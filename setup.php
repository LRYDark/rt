<?php
define('PLUGIN_RT_VERSION', '1.1.7'); // version du plugin

// Minimal GLPI version,
define("PLUGIN_RT_MIN_GLPI", "10.0.3");
// Maximum GLPI version,
define("PLUGIN_RT_MAX_GLPI", "10.0.9");

function plugin_init_rt() { // fonction glpi d'initialisation du plugin
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['rt'] = true;
   $plugin = new Plugin();

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
      $PLUGIN_HOOKS['post_show_item']['rt'] = ['PluginRtTicket', 'postShowItemRT']; // initialisation de la class
      $PLUGIN_HOOKS['pre_show_item']['rt'] = ['PluginRtChrono', 'postShowItemChrono']; // initialisation de la class   
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
