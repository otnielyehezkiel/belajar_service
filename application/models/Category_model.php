<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Category_model extends AppModel {

    protected $has_many = array(
        'Post',
    );

}
