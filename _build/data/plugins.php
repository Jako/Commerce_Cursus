<?php

return [
    'CommerceCursusDemo' => [
        'description' => 'Cursus for Commerce that adds and removes Commerce products when an Agenda event is created or deleted. It also sends a mail, when the Cursus event participant is booked or restored from expired.',
        'file' =>  'commercecursusdemo.plugin.php',
        'disabled' => true,
        'events' => [
            'OnAgendaBeforeRemove',
            'OnAgendaSave',
            'OnCursusEventParticipantBooked',
            'OnCursusEventParticipantRestored',
        ],
    ]
];
