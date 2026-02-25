<?php
include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isInstalled('rt') || !$plugin->isActivated('rt')) {
   Html::displayNotFoundError();
}

Session::checkRight('config', UPDATE);

$config = new PluginRtConfig();

function pluginRtCheckCSRF(array $data): void {
   if (!empty($data['plugin_rt_csrf_token'])) {
      Session::checkCSRF([
         '_glpi_csrf_token' => (string)$data['plugin_rt_csrf_token']
      ], true);
      return;
   }

   Session::checkCSRF($data, true);
}

if (isset($_POST["update"])) {
   pluginRtCheckCSRF($_POST);
   $config->update($_POST);
   Html::back();
}

Html::redirect($CFG_GLPI["root_doc"] . "/front/config.form.php?forcetab=" . urlencode('PluginRtConfig$1'));
