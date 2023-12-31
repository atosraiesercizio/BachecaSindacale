<?php

require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.video.php';

class WPToolset_Field_Credvideo extends WPToolset_Field_Credfile
{
    protected $_settings = array('min_wp_version' => '3.6');

    public function metaform()
    {
        //TODO: check if this getValidationData does not break PHP Validation _cakePHP required file.
        $validation = $this->getValidationData();
        $validation = WPToolset_Field_Video::addTypeValidation($validation);
        $this->setValidationData($validation);
        return parent::metaform();        
    }
}
