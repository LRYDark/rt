<?php
include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isInstalled('rt') || !$plugin->isActivated('rt')) {
   Html::displayNotFoundError();
}

Session::checkRight('config', UPDATE);

$config = new PluginRtConfig();

if (isset($_POST["update"])) {
   $config->check($_POST['id'], UPDATE);
   $config->update($_POST);
   Html::back();
}/*else(isset($_POST["defaut"])){
  $config->check($_POST['id'], UPDATE);
  $default = ['id'                     => 1,
               'showColorTimer'        => '#000000',
               'showBackgroundTimer'   => '#fec95c',
               'showcolorbutton'       => 0,
             ];
  if($config->update($default)){
      Session::addMessageAfterRedirect(
         __('Configuration par defaut appliquée', 'rp'),
         true,
         INFO
      );
  }
}*/

Html::redirect($CFG_GLPI["root_doc"] . "/front/config.form.php?forcetab=" . urlencode('PluginRtConfig$1'));
