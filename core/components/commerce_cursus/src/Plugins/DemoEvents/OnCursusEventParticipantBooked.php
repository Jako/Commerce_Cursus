<?php
/**
 * @package commerce_cursus
 * @subpackage plugin
 */

namespace modmore\Commerce_Cursus\Plugins\DemoEvents;

use CursusEventParticipants;
use CursusEvents;
use CursusParticipants;
use TreehillStudio\Agenda\Agenda;
use TreehillStudio\Cursus\Cursus;
use TreehillStudio\Cursus\Helper\Mail;
use modmore\Commerce_Cursus\Plugins\Plugin;
use xPDO;

class OnCursusEventParticipantBooked extends Plugin
{
    /** @var Agenda $agenda */
    public $agenda;
    /** @var Cursus $cursus */
    public $cursus;

    public function process()
    {
        $corePath = $this->modx->getOption('agenda.core_path', null, $this->modx->getOption('core_path') . 'components/agenda/');
        $this->agenda = $this->modx->getService('agenda', 'Agenda', $corePath . 'model/agenda/', [
            'core_path' => $corePath
        ]);
        $this->cursus = &$this->agenda->cursus;

        /** @var CursusEventParticipants $eventParticipant */
        $eventParticipant = $this->scriptProperties['event_participant'];

        // Send a mail to the eventparticipant
        if ($eventParticipant) {
            /** @var CursusParticipants $participant */
            $participant = $eventParticipant->getOne('Participant');
            if (!$participant) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Participant not found!', '', 'OnCursusEventParticipantBooked');
                return;
            }

            if (!$this->modx->loadClass('cursus.CursusEvents', $this->cursus->getOption('modelPath'))) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not load CursusEvents class!', '', 'OnCursusEventParticipantBooked');
                return;
            }
            $eventClass = new CursusEvents($this->modx);
            $c = $this->modx->newQuery('CursusEvents');
            $c = $eventClass->eventsQuery($c, [
                'event_id' => $eventParticipant->get('event_id'),
                'all' => true,
            ]);
            /** @var CursusEvents $event */
            $event = $this->modx->getObject('CursusEvents', $c);

            if ($event) {
                $mail = new Mail($this->modx);
                $mail->sendParticipantMail($event, $participant, [], [
                    'userSubject' => 'tplCommerceCursusBookedMailUserSubject',
                    'userText' => 'tplCommerceCursusBookedMailText',
                    'sendParticipantMail' => true,
                ]);
            } else {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Event not found!', '', 'OnCursusEventParticipantBooked');
            }
        } else {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'EventParticipant not found!', '', 'OnCursusEventParticipantBooked');
        }
    }
}
