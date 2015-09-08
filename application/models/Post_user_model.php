<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Post_user_model extends AppModel {

    protected $belongs_to = array(
        'User',
        'Post'
    );
    protected $label = array(
        'user_id' => 'User',
        'post_id' => 'Postingan',
    );
    protected $validation = array(
        'user_id' => 'required|integer',
        'post_id' => 'required|integer'
    );

}
