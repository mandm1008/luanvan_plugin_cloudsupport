<?php
defined('MOODLE_INTERNAL') || die();

function local_cloudsupport_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = [])
{
    $fs = get_file_storage();

    $itemid   = array_shift($args);
    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args) . '/';

    $file = $fs->get_file($context->id, 'local_cloudsupport', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    // Cho phép tải file, kể cả khách không đăng nhập
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function local_cloudsupport_extend_settings_navigation(settings_navigation $nav, context $context) {
    global $PAGE;

    if ($PAGE->cm && $PAGE->cm->modname === 'quiz') {
        $quiznode = $nav->find('modulesettings', navigation_node::TYPE_SETTING);
        if ($quiznode) {
            $url = new moodle_url('/local/cloudsupport/quizcloud.php', ['cmid' => $PAGE->cm->id]);
            $quiznode->add('Cloud Setting', $url, navigation_node::TYPE_SETTING, null, 'cloudsupporttab');
        }
    }
}
