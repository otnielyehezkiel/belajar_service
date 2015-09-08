<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Post_model extends AppModel {

    //TYPE
    const TYPE_PUBLIC = 'C';
    const TYPE_PRIVATE = 'E';
    const TYPE_GROUP = 'G';
    //PRIORITY
    const PRIORITY_MINOR = 0;
    const PRIORITY_BIASA = 1;
    const PRIORITY_PENTING = 2;
    const PRIORITY_URGENT = 3;
    //STATUS
    const STATUS_START = 0;
    const STATUS_PROGRESS = 1;
    const STATUS_FINISH = 2;

    protected $has_many = array(
        'Comment',
    );
    protected $belongs_to = array(
        'User',
        'Group',
        'Category'
    );
    protected $many_to_many = array(
        'Post_user' => array('Post', 'User')
    );
    protected $label = array(
        'category_id' => 'Kategori',
        'image' => 'Gambar',
        'file' => 'Berkas',
        'video' => 'Video'
    );
    protected $validation = array(
        'link' => 'valid_url',
        'category_id' => 'required|integer'
    );

    protected function property($row) {
        $row['images'] = Image::getImage($row['id'], $row['image'], 'posts/photos');
        unset($row['image']);
        $row['file'] = File::getFile($row['id'], $row['file'], 'posts/files');
        $row['video'] = File::getFile($row['id'], $row['video'], 'posts/videos');

        return $row;
    }

    public function getPublic() {
        $this->_database->select('posts.*');
        $this->_database->join('post_users', 'post_users.post_id = posts.id', 'left');
        $this->_database->group_by('posts.id');
        $this->_database->having('count(post_users.*)', 0);
        return $this->order_by("posts.created_at", 'DESC')->with(array('Category', 'User', 'Comment'))->get_many_by(array('posts.group_id IS NULL' => NULL));
    }

    public function getMe($userId) {
        $sql = 'SELECT * FROM posts 
          WHERE (posts.user_id = ' . $userId . ') OR (SELECT COUNT(*) FROM post_users WHERE post_users.post_id = posts.id AND post_users.user_id = ' . $userId . ' LIMIT 1) = 1
          OR (SELECT COUNT(*) FROM group_members WHERE group_members.group_id = posts.group_id AND group_members.user_id = ' . $userId . ' LIMIT 1) = 1 
          ORDER BY posts.created_at DESC';
//        return $this->with(array('Category', 'User', 'Comment', 'Group', 'Post_user'))->get_many_by_sql($sql);
        $posts = $this->with(array('Category', 'User', 'Comment', 'Group'))->get_many_by_sql($sql);

        $this->load->model('User_model');
        $this->load->model('Post_user_model');
        $userMap = Util::toMapObject($this->User_model->get_all(), 'id');
        $postUsers = $this->Post_user_model->get_many_by(array('post_id' => Util::toList($posts, 'id')));
        foreach ($posts as $key => $value) {
            foreach ($postUsers as $pUserKey => $pUserVal) {
                if ($pUserVal['postId'] == $value['id'])
                    $posts[$key]['postUsers'][] = $userMap[$pUserVal['userId']];
            }
        }
        return $posts;
    }

    public function getGroup($id) {
        $this->_database->from('group_members');
        $result = $this->order_by("posts.created_at", 'DESC')->with(array('Group', 'Comment' => 'User'))->get_many_by(array('group_members.user_id' => $id, 'group_members.group_id = posts.group_id'));
        return $result;
    }

    public function getTag($id) {
        $this->_database->from('post_users');
        $result = $this->order_by("posts.created_at", 'DESC')->with(array('Category', 'Post_user', 'Comment' => 'User'))->get_many_by(array('post_users.user_id' => $id, 'post_users.post_id = posts.id'));
        return $result;
    }

    public function create($data, $skip_validation = FALSE, $return = TRUE) {
        if (empty($data['description']) && empty($data['link']) && empty($data['image']) && empty($data['video']) && empty($data['file'])) {
            return 'Data yang dikirim tidak lengkap.';
        }
        $this->_database->trans_begin();
        if (isset($data['postUsers']) && !empty($data['postUsers'])) {
            $data['type'] = self::TYPE_PRIVATE;
        } elseif (isset($data['groupId']) && !empty($data['groupId'])) {
            $data['type'] = self::TYPE_GROUP;
            $this->load->model('Group_model');
            $group = $this->Group_model->with('Group_member')->get($data['groupId']);
            $member = Util::toList($group['groupMembers'], 'userId');
            if (!in_array($data['user_id'], $member)) {
                return 'Selain anggota group ' . $group['name'] . ' tidak dapat menambahkan postingan';
            }
        } else {
            $data['type'] = self::TYPE_PUBLIC;
        }
        $create = parent::create($data, $skip_validation, $return);
        if ($create) {
            if (isset($data['postUsers'])) {
                $toUser = explode(',', $data['postUsers']);
                $this->load->model('Post_user_model');
                foreach ($toUser as $user) {
                    $dataToUser = array(
                        'post_id' => $create,
                        'user_id' => $user
                    );
                    $insert = $this->Post_user_model->insert($dataToUser, TRUE, FALSE);
                    if (!$insert) {
                        $this->_database->trans_rollback();
                        return FALSE;
                    }
                }
            }
            $upload = TRUE;
            if (isset($_FILES['image']) && !empty($_FILES['image'])) {
                $upload = Image::upload('image', $create, $_FILES['image']['name'], 'posts/photos');
            }
            if (isset($_FILES['video']) && !empty($_FILES['video'])) {
                $upload = File::upload('video', $create, 'posts/videos');
            }
            if (isset($_FILES['file']) && !empty($_FILES['file'])) {
                $upload = File::upload('file', $create, 'posts/files');
            }
            if ($upload === TRUE) {
                $this->load->model('Notification_model');
                $this->Notification_model->generate(Notification_model::ACTION_POST_CREATE, $create);
                return $this->_database->trans_commit();
            } else {
                $this->_database->trans_rollback();
                return $upload;
            }
        }
        $this->_database->trans_rollback();
        return FALSE;
    }

}
