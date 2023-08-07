<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to tdis file");
}

class PluginRtCri extends CommonDBTM {

   static $rightname = 'plugin_rt_cri_create';

   static function getTypeName($nb = 0) {
      return _n('Ajout utilisateur', 'Ajout utilisateur', $nb, 'rt');
   }

   function showForm($ID, $options = []) {
      global $DB, $CFG_GLPI;

      echo 'test';

      $config = PluginRtConfig::getInstance();
      $job    = new Ticket();
      $plugin = new Plugin();
      $job->getfromDB($ID);
      $img_sum_task = 0;
      $img_sum_suivi = 0;
      $sumtask = 0;

      $params = ['job'         => $ID,
                 'form'       => 'formReport',
                 'root_doc'   => PLUGIN_RT_WEBDIR];

    
                 echo 'test2';
   }
}
