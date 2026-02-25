<?php
use Glpi\Event;

include ('../../../inc/includes.php');

Session::haveRight("ticket", UPDATE);

$PluginRtTicket = new PluginRtTicket();
$ticket = new Ticket();

$input = [
    'tickets_id'                => $ticket->getID(),
    'entities_id'               => $ticket->getEntityID(),
    'routetime'                 => (int)($_REQUEST['routetime_quantity'] ?? 0),
    'users_id'                  => Session::getLoginUserID(),
];
if ($PluginRtTicket->add($input)) {
    Session::addMessageAfterRedirect(
        __('Temps de trajet ajouté : ' . ((int)$input['routetime'] / 60) . ' Minute(s)', 'rt'),
        true,
        INFO
    );
    Html::back();
}

Html::displayErrorAndDie("lost");
