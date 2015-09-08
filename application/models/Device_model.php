<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Device_model extends AppModel {

    protected $has_many = array(
        'User_token'
    );
    protected $label = array(
        'secure_id' => 'ID Device',
        'name' => 'Device',
    );
    protected $validation = array(
        'secure_id' => 'required|is_unique[devices.secure_id]',
    );

}
