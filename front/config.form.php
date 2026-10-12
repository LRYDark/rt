<?php

$plugin = new Plugin();
if (!$plugin->isInstalled('rt') || !$plugin->isActivated('rt')) {
   throw new \Glpi\Exception\Http\NotFoundHttpException();
}

Session::checkRight('config', UPDATE);

$config = new PluginRtConfig();

// GLPI 12 : la protection CSRF est assurée par le noyau (en-têtes Sec-Fetch-Site / Origin), sans jeton.
if (isset($_POST["update"])) {
   $config->update($_POST);
   Html::back();
}

// Fichier charge depuis une methode de GLPI : $CFG_GLPI n'y est visible qu'avec « global ».
global $CFG_GLPI;
Html::redirect($CFG_GLPI["root_doc"] . "/front/config.form.php?forcetab=" . urlencode('PluginRtConfig$1'));
