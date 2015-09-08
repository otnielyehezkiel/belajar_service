<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Comment extends PrivateApiController {

    public function create() {
        $data = $this->postData;
        $data['user_id'] = $this->user['id'];
        $insert = $this->model->create($data);
        if (is_numeric($insert)) {
            $this->load->model('Notification_model');
            $this->Notification_model->generate(Notification_model::ACTION_COMMENT_CREATE, $insert);

            $data = $this->model->get($insert);
            $this->load->model('Post_model');
            $post = $this->Post_model->with(array('Category', 'Comment' => 'User', 'User', 'Post_user', 'Group'))->get($data['postId']);
            $this->setResponse($this->setSystem(ResponseStatus::SUCCESS, NULL, array('Berhasil membuat komentar')), $post);
        } elseif (is_string($insert)) {
            $this->setResponse($this->setSystem(ResponseStatus::ERROR, $insert));
        } else {
            $validation = $this->model->getErrorValidate();
            if (empty($validation)) {
                $this->setResponse($this->setSystem(ResponseStatus::ERROR, 'Gagal membuat komentar'));
            } else {
                $this->setResponse($this->setSystem(ResponseStatus::ERROR_VALIDATION, NULL, array(), $validation));
            }
        }
    }

    public function delete($id) {
        $delete = $this->model->delete($id);
        if ($delete === TRUE) {
            $this->setResponse($this->setSystem(ResponseStatus::SUCCESS, NULL, array('Berhasil menghapus komentar')));
        } else {
            $this->setResponse($this->setSystem(ResponseStatus::ERROR, "Gagal menghapus komentar"));
        }
    }

}

?>