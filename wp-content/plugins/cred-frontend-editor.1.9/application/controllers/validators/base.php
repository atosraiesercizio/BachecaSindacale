<?php

abstract class CRED_Validator_Base {

    protected $_post_id;
    protected $_formData;
    protected $_formHelper;
    protected $_zebraForm;

    public function __construct($base_form) {
        $this->_post_id = $base_form->get_post_id();
        $this->_formData = $base_form->_formData;
        $this->_formHelper = $base_form->_formHelper;
        $this->_zebraForm = $base_form->_zebraForm;
    }

}