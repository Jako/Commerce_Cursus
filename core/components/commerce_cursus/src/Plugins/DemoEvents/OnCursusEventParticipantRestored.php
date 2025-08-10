<?php
/**
 * @package ferienprogramm
 * @subpackage plugin
 */

namespace modmore\Commerce_Cursus\Plugins\DemoEvents;

use comOrder;
use CursusEvents;
use modMail;
use TreehillStudio\Agenda\Agenda;
use TreehillStudio\Cursus\Cursus;
use TreehillStudio\Cursus\Helper\Parse;
use modmore\Commerce_Cursus\Plugins\Plugin;
use xPDO;

class OnCursusEventParticipantRestored extends Plugin
{
    /**
     * A reference to the Cursus instance
     * @var Cursus $cursus
     */
    private $cursus;

    /**
     * {@inheritDoc}
     * @return void
     */
    public function init()
    {
        $corePath = $this->modx->getOption('agenda.core_path', null, $this->modx->getOption('core_path') . 'components/agenda/');
        /** @var Agenda $agenda */
        $agenda = $this->modx->getService('agenda', 'Agenda', $corePath . 'model/agenda/', [
            'core_path' => $corePath
        ]);
        /** @var Cursus $cursus */
        $this->cursus = &$agenda->cursus;
    }

    /**
     * {@inheritDoc}
     * @return void
     */
    public function process()
    {
        /** @var comOrder $order */
        $order = $this->scriptProperties['order'];

        $items = $order->getItems();
        foreach ($items as $item) {
            $event = $this->getEvent([
                'event_id' => $item->get('product'),
            ]);
            if ($event['remaining_booked'] < 0) {
                $eventParticipants = $item->getProperty('cursus_event_participants') ? explode(',', $item->getProperty('cursus_event_participants')) : [];
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Expired event participants ' . implode(', ', $eventParticipants) . ' were set to booked during the payment and the event is currently overbooked.');

                $participantNames = $item->getProperty('participant_names') ? explode(',', $item->getProperty('participant_names')) : [];
                $this->sendMail('Event', 'The reservation of the participants ' . implode(', ', $participantNames) . ' expired before the payment and the event ' . $event['event_id'] . ' is currently overbooked.');
            }
        }
    }

    /**
     * @param $eventsOptions
     * @return array|null
     */
    private function getEvent($eventsOptions)
    {
        $eventClass = new CursusEvents($this->modx);
        $event = $eventClass->getEvent($eventsOptions);
        if ($event) {
            $eventArray = $event->toExtendedArray();
            return Parse::flattenArray($eventArray, '_');
        }
        return null;
    }

    /**
     * @param $email
     * @param $subject
     * @param $message
     * @return void
     */
    private function sendMail($subject, $message)
    {
        $this->modx->getService('mail', 'mail.modPHPMailer');
        $this->modx->mail->set(modMail::MAIL_BODY, $message);
        $this->modx->mail->set(modMail::MAIL_FROM, $this->cursus->getOption('email_from'));
        $this->modx->mail->set(modMail::MAIL_FROM_NAME, $this->cursus->getOption('email_from_name'));
        $this->modx->mail->set(modMail::MAIL_SUBJECT, $subject);
        $this->modx->mail->address('to', $this->cursus->getOption('email_to'));
        $this->modx->mail->setHTML(true);
        if (!$this->modx->mail->send()) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'An error occurred while trying to send the email: ' . $this->modx->mail->mailer->ErrorInfo);
        }
        $this->modx->mail->reset();
    }
}
