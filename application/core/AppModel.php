<?php

(defined('BASEPATH')) OR exit('No direct script access allowed');

class AppModel extends BehaviorModel {

    /**
     * Initialize Behaviour Model 
     */
    protected $return_type = 'array';
    protected $protected_attributes = array('hash');
    protected $before_create = array('created_at', 'updated_at');
    protected $before_update = array('updated_at');
    protected $soft_data = NULL;
    protected $mapper = array();
    protected $label = array();
    protected $validation = array();
    protected $after_get = array('mapper', 'property');

    public function __construct() {
        parent::__construct();

        $this->setLabel();

        $this->setValidation();
    }

    protected function setLabel() {
        $label = array();
        foreach ($this->label as $key => $val) {
            if (is_numeric($key)) {
                $label[$val] = humanize($val);
            } else {
                $label[$key] = $val;
            }
        }
        $this->label = $label;
    }

    public function getLabel($field) {
        return $this->label[$field];
    }

    protected function setValidation() {
        foreach ($this->validation as $key => $val) {
            $this->validate[] = array(
                'field' => $key,
                'label' => (isset($this->label[$key]) ? $this->label[$key] : humanize($key)),
                'rules' => $val
            );
        }
    }

    public function getErrorValidate() {
        $error = array();
        foreach ($this->validation as $key => $val) {
            $err = form_error($key);
            if (!empty($err)) {
                $error[camelize($key)] = $err;
            }
        }
        return $error;
    }

    public function getErrorManualValidation($fields = array()) {
        $error = array();
        foreach ($fields as $field) {
            $err = form_error($field);
            if (!empty($err)) {
                $error[camelize($field)] = $err;
            }
        }
        return $error;
    }

    public function mapper($row) {
        if (!$this->_is_mapper) {
            return $row;
        }
        $newRow = array();
        foreach ($row as $key => $val) {
            //MAPPER ATRIBUTE
            if (is_null($this->soft_data) || in_array($key, $this->soft_data)) {
                $newKey = key_exists($key, $this->mapper) ? $this->mapper[$key] : camelize($key);
                $newRow[$newKey] = $val;
            }
        }
        return $newRow;
    }

    public function getClassName() {
        return str_replace(' ', '', humanize(singular(preg_replace('/(_m|_model)?$/', '', get_class($this)))));
    }

    public function create($data, $skip_validation = TRUE, $return = TRUE) {
        $fields = $this->_database->list_fields($this->_table);
        $mapperData = array();
        foreach ($data as $field => $value) {
            if ($this->_is_mapper)
                $field = Util::camelizeToUnderscore($field);

            if (in_array($field, $fields)) {
                $mapperData[$field] = $value;
            }
        }
        return $this->insert($mapperData, $skip_validation, $return);
    }

    protected function property($row) {
        return $row;
    }

}
