<?php

$observers = [
    [
        'eventname'   => '\core\event\course_module_created',
        'callback'    => 'local_cloudsupport_observer::quiz_created',
        'includefile' => '/local/cloudsupport/observer.php',
        'priority'    => 9999,
    ],
    [
        'eventname'   => '\core\event\course_module_updated',
        'callback'    => 'local_cloudsupport_observer::quiz_updated',
        'includefile' => '/local/cloudsupport/observer.php',
        'priority'    => 9999,
    ],
];
