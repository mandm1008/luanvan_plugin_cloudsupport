<?php
namespace local_cloudsupport\event;

defined('MOODLE_INTERNAL') || die();

class quiz_time_updated extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r'; // Create (c), Read (r), Update (u), Delete (d)
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'quiz';
    }

    public static function get_name() {
        return get_string('eventquiztimeupdated', 'local_cloudsupport');
    }

    public function get_description() {
        return "The quiz with ID {$this->objectid} in course {$this->courseid} had its timing updated.";
    }

    public function get_url() {
        return new \moodle_url('/mod/quiz/view.php', ['id' => $this->contextinstanceid]);
    }
}
