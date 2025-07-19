<?php

namespace local_cloudsupport\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class cloud_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('advcheckbox', 'usecloud', get_string('usecloud', 'local_cloudsupport'), ' ');
        $mform->setType('usecloud', PARAM_BOOL);

        $mform->addElement('text', 'cloudregion', get_string('cloudregion', 'local_cloudsupport'));
        $mform->setType('cloudregion', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
