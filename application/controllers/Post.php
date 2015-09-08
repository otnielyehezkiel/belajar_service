<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Post extends PrivateApiController {

    /**
     * Digunakan untuk mengambil list post public
     */
    public function index() {
        $posts = $this->model->getPublic();

        $this->setResponse($this->setSystem(ResponseStatus::SUCCESS), $posts);
    }

    public function create() {
        $data = $this->postData;
        $data['user_id'] = $this->user['id'];
        $data['image'] = isset($_FILES['image']) ? $_FILES['image']['name'] : NULL;
        $data['video'] = isset($_FILES['video']) ? $_FILES['video']['name'] : NULL;
        $data['file'] = isset($_FILES['file']) ? $_FILES['file']['name'] : NULL;

        $insert = $this->model->create($data);
        if ($insert === TRUE) {
            $this->setResponse($this->setSystem(ResponseStatus::SUCCESS, NULL, array('Berhasil membuat postingan')));
        } elseif (is_string($insert)) {
            $this->setResponse($this->setSystem(ResponseStatus::ERROR, $insert));
        } else {
            $validation = $this->model->getErrorValidate();
            if (empty($validation)) {
                $this->setResponse($this->setSystem(ResponseStatus::ERROR, 'Gagal membuat postingan'));
            } else {
                $this->setResponse($this->setSystem(ResponseStatus::ERROR_VALIDATION, NULL, array(), $validation));
            }
        }
    }

    /**
     * Digunakan untuk mengambil post dari group dengan ID group
     */
    public function group($id) {
        $posts = $this->model->with(array('Category', 'Group', 'User', 'Comment' => 'User'))->get_many_by('group_id', $id);

        $this->setResponse($this->setSystem(ResponseStatus::SUCCESS), $posts);
    }

    /**
     * Digunakan untuk mengambil post public, dari group yang diikuti, dan dari tag friend
     */
    public function me() {
        $posts = $this->model->getMe($this->user['id']);

        //SET RESPONSE
        $this->setResponse($this->setSystem(ResponseStatus::SUCCESS), $posts);
    }

    /**
     * Digunakan untuk mengambil post dengan ID post beserta commentnya
     */
    public function detail($id) {
        $post = $this->model->with(array('Category', 'Comment' => 'User', 'User', 'Post_user', 'Group'))->get($id);

        //SET READ NOTIFICATION
        if (!empty($post)) {
            $this->load->model('Notification_model');
            $this->load->model('Notification_user_model');
            $this->Notification_user_model->setAsRead($this->user['id'], Notification_model::TYPE_POST_DETAIL, $post['id']);
        }
        
        $this->setResponse($this->setSystem(ResponseStatus::SUCCESS), $post);
    }

    public function delete($id) {
        $delete = $this->model->delete($id);
        if ($delete === TRUE) {
            $this->setResponse($this->setSystem(ResponseStatus::SUCCESS, NULL, array('Berhasil menghapus postingan')));
        } else {
            $this->setResponse($this->setSystem(ResponseStatus::ERROR, "Gagal menghapus postingan"));
        }
    }

}

?>