<?php
include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isInstalled('rt') || !$plugin->isActivated('rt')) {
   Html::displayNotFoundError();
}

Session::checkRight('config', UPDATE);

$config = new PluginRtConfig();

if (isset($_POST["update"])) {
   $config->update($_POST);
   Html::back();
}

Html::redirect($CFG_GLPI["root_doc"] . "/front/config.form.php?forcetab=" . urlencode('PluginRtConfig$1'));
