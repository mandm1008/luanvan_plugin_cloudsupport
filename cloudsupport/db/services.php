<?php
$functions = [
    'local_cloudsupport_export_course' => [
        'classname'   => 'local_cloudsupport\\external\\export_course',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Export a course to .mbz file',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'moodle/backup:backupcourse',
    ],

    'local_cloudsupport_restore_course' => [
        'classname'   => 'local_cloudsupport\\external\\restore_course',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Restore a course from .mbz file',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'moodle/restore:restorecourse',
    ],

    'local_cloudsupport_export_users' => [
        'classname'   => 'local_cloudsupport\\external\\export_users',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Export users of a course into CSV and return download path.',
        'type'        => 'read',
        'capabilities'=> 'moodle/user:viewdetails',
        'ajax'        => true,
    ],

    'local_cloudsupport_import_users' => [
        'classname'   => 'local_cloudsupport\\external\\import_users',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Import users from CSV file located in moodledata/temp/backup.',
        'type'        => 'write',
        'capabilities'=> 'moodle/user:create',
        'ajax'        => true,
    ],

    'local_cloudsupport_upload_backup_file' => [
        'classname'   => 'local_cloudsupport\\external\\upload_backup_file',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Upload a backup .mbz file to temp backup folder.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'moodle/restore:uploadfile',
    ],
];
$services = [
    'Cloud Support Service' => [
        'functions' => [
            'local_cloudsupport_restore_course', 
            'local_cloudsupport_export_course', 
            'local_cloudsupport_export_users',
            'local_cloudsupport_import_users',
            'local_cloudsupport_upload_backup_file'
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
