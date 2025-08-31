<?php
use Glpi\Application\View\TemplateRenderer;

class PluginRtTicketConfig extends CommonDBTM
{
    public static $rightname = 'plugin_rt_ticketconfig';

    const TICKET_TAB  = 1024;
    const TICKET_FORM = 2048;

    public static function getTypeName($nb = 0)
    {
        return _sn('Temps passé (tâches / trajets)', 'Temps passé (tâches / trajets)', $nb, 'rt');
    }

    public static function getIcon()
    {
        return 'ti ti-clock-hour-4';
    }

    /**
     * Show default credit option ticket
     *
     * @param Ticket $ticket
     * @param bool $embed_in_ticket_form
     */
    public static function showForTicket(Ticket $ticket, bool $embed_in_ticket_form = false)
    {
        //load ticket configuration
        $ticket_config = new PluginRtTicketConfig();
        if (!$ticket->isNewItem()) {
            $ticket_config->getFromDBByCrit(["tickets_id" => $ticket->getID()]);
        }

        if ($embed_in_ticket_form) {
            $uncollapsed = (importArrayFromDB(Config::getSafeConfig()['itil_layout'])['items']['plugin-rt-ticket-config'] ?? 'true') == 'true';
        } else {
            $form_url = self::getFormUrl();
        }
            TemplateRenderer::getInstance()->display('@rt/tickets/config.html.twig', [
                'embed_in_ticket_form' => $embed_in_ticket_form,
                'uncollapsed'          => $uncollapsed ?? false,
                'type_name'            => self::getTypeName(),
                'entity_id'            => $ticket->getEntityID(),
                'ticket'               => $ticket,
                'ticket_config'        => $ticket_config,
                'form_url'             => $form_url ?? '',
                // >>> NOUVEAU : HTML du tableau de temps, prêt à être rendu dans Twig
                'times_table_html'     => PluginRtEntity::buildTimesTable($ticket),
            ]);

    }

}
