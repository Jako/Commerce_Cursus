<?php
/**
 * @package commerce_cursus
 * @subpackage plugin
 */

namespace modmore\Commerce_Cursus\Plugins\DemoEvents;

use AgendaEvents;
use Commerce;
use TreehillStudio\Agenda\Agenda;
use TreehillStudio\Cursus\Cursus;
use modmore\Commerce_Cursus\Plugins\Plugin;
use xPDO;

class OnAgendaSave extends Plugin
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

        $eventId = $this->scriptProperties['id'];
        $className = $this->scriptProperties['className'];

        if ($eventId && $className == 'AgendaEvents') {
            if (!$this->modx->loadClass('agenda.AgendaEvents', $this->agenda->getOption('modelPath'))) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not load AgendaEvents class!', '', 'OnAgendaSave');
            } else {
                $eventClass = new AgendaEvents($this->modx);
                $eventsOptions = [
                    'id' => $eventId,
                    'active' => true,
                ];
                $eventsOptions = array_merge($eventsOptions, $this->cursus->getOption('event_where'));
                /** @var AgendaEvents $event */
                $event = $eventClass->getEvent($eventsOptions);
                if ($event) {
                    $ta = $event->toExtendedArray();
                    // Instantiate the Commerce class
                    $path = $this->modx->getOption('commerce.core_path', null, MODX_CORE_PATH . 'components/commerce/') . 'model/commerce/';
                    $params = ['mode' => $this->modx->getOption('commerce.mode')];
                    /** @var Commerce|null $commerce */
                    $commerce = $this->modx->getService('commerce', 'Commerce', $path, $params);
                    if (!($commerce instanceof Commerce)) {
                        $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not load Commerce class!', '', 'OnAgendaSave');
                    }
                    /** @var \comProduct $commerceProduct */
                    $commerceProduct = $this->modx->getObject('comProduct', ['id' => $ta['id']]);
                    if (!$commerceProduct) {
                        $commerceProduct = $this->modx->newObject('comProduct');
                        $commerceProduct->set('id', $ta['id']);
                    }
                    $commerceProduct->fromArray([
                        'sku' => 'EVENT-' . $ta['id'],
                        'name' => $ta['title'],
                        'description' => $ta['description'],
                    ]);
                    $commerceProduct->save();
                }
            }

            $this->modx->cacheManager->refresh([
                'resource' => []
            ]);
        }
    }
}
