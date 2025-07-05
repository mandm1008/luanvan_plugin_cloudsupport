<?php
$capabilities = [
    'local/cloudsupport:viewexport' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['manager' => CAP_ALLOW]
    ]
];
