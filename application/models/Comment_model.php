<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Comment_model extends AppModel {

    protected $belongs_to = array(
        'User',
        'Post'
    );
    protected $label = array(
        'user_id' => 'User',
        'post_id' => 'Postingan',
        'text' => 'Komentar',
    );
    protected $validation = array(
        'user_id' => 'required|integer',
        'post_id' => 'required|integer',
        'text' => 'required'
    );

    public function create($data, $skip_validation = TRUE, $return = TRUE) {
        $this->load->model('Post_model');
        $post = $this->Post_model->with(array('Group' => 'Group_member', 'User', 'Post_user'))->get($data['postId']);
        switch ($post['type']) {
            case Post_model::TYPE_GROUP:
                $member = Util::toList($post['group']['groupMembers'], 'userId');
                if (!in_array($data['user_id'], $member)) {
                    return 'Selain anggota group ' . $post['group']['name'] . ' tidak dapat menambahkan komentar';
                }
            case Post_model::TYPE_PRIVATE:
                $member = Util::toList($post['postUsers'], 'id');
                if ($data['user_id'] != $post['userId'] && !in_array($data['user_id'], $member)) {
                    return 'Selain anggota postingan tidak dapat menambahkan komentar';
                }
        }
        return parent::create($data, $skip_validation, $return);
    }

}
