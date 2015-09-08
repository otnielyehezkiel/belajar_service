<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends AppModel {

    private $_is_role = FALSE;

    /**
     * Digunakan untuk menampilkan data yang akan dikembalikan dari model 
     * Jika semua ditampilkan tidak perlu ditambahkan attribute $soft_data pada model
     * 
     * @var array 
     */
    protected $soft_data = array('id', 'name', 'username', 'photo', 'email', 'gender', 'ym', 'role', 'status', 'created_at', 'updated_at');
    private $public_data = array('id', 'name', 'username', 'photo', 'email', 'gender');

    /**
     * Digunakan untuk relasi antar model
     * 
     * @var array 
     */
    protected $has_many = array(
        'Post',
        'Comment',
        'Group_member',
        'Post_user',
        'Notification',
        'User_token',
        'Schedule',
    );

    /**
     * Digunakan untuk mengcustom nama mapper
     * Jika penamaan standard (user_name => userName) tidak perlu ditambahkan pada attribute $mapper pada model
     * 
     * @var array 
     */
    protected $mapper = array(
        'password_reset_token' => 'resetToken',
    );

    /**
     * Digunakan untuk labeling jika label (user_name => User Name) hanya menyertakan nama fieldnya saja.
     * @var array 
     */
    protected $label = array();

    /**
     * Digunakan untuk validasi create dan update data
     * 
     * @var array 
     */
    protected $validation = array(
        'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
        'email' => 'required|max_length[50]|is_unique[users.email]|valid_email',
        'status' => 'required',
        'role' => 'required'
    );

    public function is_role($role = FALSE) {
        $this->_is_role = TRUE;
        return $this;
    }

    public function getRole($role) {
        $roleUser = array(
            'id' => $role,
            'name' => Role::name($role),
            'permissions' => Role::getPermissions($role)
        );
        return $roleUser;
    }

    protected function property($row) {
        if ($this->_is_role) {
            $row['role'] = $this->getRole($row['role']);
        } else {
            foreach ($row as $key => $val) {
                if (!in_array($key, $this->public_data)) {
                    unset($row[$key]);
                }
            }
        }
        if (isset($row['photo'])) {
            $row['photos'] = Image::getImage($row['id'], $row['photo'], 'users/photos');
        }
        unset($row['photo']);
        return $row;
    }

    public function create($data, $skip_validation = FALSE, $return = TRUE) {
        $data['password_salt'] = $auth_key = SecurityManager::generateAuthKey();
        $data['password'] = SecurityManager::hashPassword($data['password'], $auth_key);
        $data['status'] = Status::ACTIVE;
        $data['role'] = Role::EMPLOYEE;

        $this->_database->trans_begin();
        $create = parent::create($data, $skip_validation, $return);
        if ($create) {
            $upload = TRUE;
            if (isset($_FILES['photo']) && !empty($_FILES['photo'])) {
                $upload = Image::upload('photo', $create, $_FILES['photo']['name'], 'users/photos');
            }
            if ($upload === TRUE) {
                return $this->_database->trans_commit();
            } else {
                $this->_database->trans_rollback();
                return $upload;
            }
        }
        $this->_database->trans_rollback();
        return FALSE;
    }

    public function updateData($primary_value, $data, $skip_validation = TRUE) {

        if (array_key_exists('password', $data) || array_key_exists('password_salt', $data)) {
            unset($data['password'], $data['password_salt']);
        }
        $this->_database->trans_begin();
        $update = parent::update($primary_value, $data, $skip_validation);
        if ($update) {
            $upload = TRUE;
            if (isset($_FILES['photo']) && !empty($_FILES['photo'])) {
                $upload = Image::upload('photo', $primary_value, $_FILES['photo']['name'], 'users/photos');
            }
            if ($upload === TRUE) {
                return $this->_database->trans_commit();
            } else {
                $this->_database->trans_rollback();
                return $upload;
            }
        }
        $this->_database->trans_rollback();
        return FALSE;
    }

    public function getListFor($userId) {
        return $this->order_by('name', 'ASC')->get_many_by(array('id !=' => $userId, 'status' => Status::ACTIVE));
    }

}
