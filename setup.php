<?php

define('PLUGIN_RT_VERSION', '1.1.2');

// Minimal GLPI version,
define("PLUGIN_RT_MIN_GLPI", "10.0.3");
// Maximum GLPI version,
define("PLUGIN_RT_MAX_GLPI", "10.0.6");

function plugin_init_rt() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['rt'] = true;
   $plugin = new Plugin();

   if ($plugin->isActivated('rt')){
      Plugin::registerClass('PluginRtTicket', ['addtabon' => 'Ticket']);

      $PLUGIN_HOOKS['post_item_form']['rt'] = ['PluginRtTicket','formroutetime'];

      $PLUGIN_HOOKS['item_add']['rt'] = [
         'ITILFollowup'   => ['PluginRtTicket', 'addroutetime'],
         'ITILSolution'   => ['PluginRtTicket', 'addroutetime'],
         'TicketTask'     => ['PluginRtTicket', 'addroutetime'],
      ];

      $PLUGIN_HOOKS['pre_item_update']['rt'] = ['TicketTask' => 'plugin_rt_item_update'];
      $PLUGIN_HOOKS['post_show_item']['rt'] = ['PluginRtTicket', 'postShowItem'];
   }
}

function plugin_version_rt() {
   return [
      'name'           => _n('Route Time', 'Route Time', 2, 'rt'),
      'version'        => PLUGIN_RT_VERSION,
      'author'         => 'REINERT Joris',
      'license'        => 'GPLv3',
      'homepage'       => 'https://www.jcd-groupe.fr',
      'requirements'   => [
         'glpi' => [
            'min' => PLUGIN_RT_MIN_GLPI,
            'max' => PLUGIN_RT_MAX_GLPI,
         ]
      ]
   ];
}
