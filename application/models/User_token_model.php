<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User_token_model extends AppModel {

    protected $belongs_to = array(
        'User',
        'Device',
    );

}
