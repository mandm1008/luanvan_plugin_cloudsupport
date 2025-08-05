<?php
namespace local_cloudsupport\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

class clean_legacy_data extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('task:cleanlegacydata', 'local_cloudsupport');
    }

    public function execute() {
        global $DB;

        $fs = get_file_storage();
        $context = \context_system::instance();

        $files = $fs->get_area_files($context->id, 'local_cloudsupport', 'backupfiles', 0, 'timemodified', false);
        foreach ($files as $file) {
            if ((time() - $file->get_timemodified()) > 1 * 86400) { // 1 day
                $file->delete();
            }
        }

        // Xóa record trong local_cloudsupport_quizcfg nếu quiz không còn
        $cfgrecords = $DB->get_records('local_cloudsupport_quizcfg');
        foreach ($cfgrecords as $record) {
            if (!$DB->record_exists('quiz', ['id' => $record->quizid])) {
                $DB->delete_records('local_cloudsupport_quizcfg', ['id' => $record->id]);
            }
        }
    }

}
