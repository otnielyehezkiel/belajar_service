<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Group extends PrivateApiController {

    /**
     * Digunakan untuk mengambil list group
     */
    public function list_me() {
        if ($this->user['role']['id'] == Role::ADMINISTRATOR) {
            $groups = $this->model->getAll();
        } else {
            $groups = $this->model->getMe($this->user['id']);
        }

        $this->setResponse($this->setSystem(ResponseStatus::SUCCESS), $groups);
    }
    
    public function list_for_me() {
        if ($this->user['role']['id'] == Role::ADMINISTRATOR) {
            $groups = $this->model->getAll();
        } else {
            $groups = $this->model->getForMe($this->user['id']);
        }

        $this->setResponse($this->setSystem(ResponseStatus::SUCCESS), $groups);
    }

    /**
     * Digunakan untuk mengambil detail group beserta member dari group dengan ID group
     */
    public function detail($id) {
        $group = $this->model->with(array('Group_member' => 'User'))->get($id);

        //SET READ NOTIFICATION
        if (!empty($post)) {
            $this->load->model('Notification_model');
            $this->load->model('Notification_user_model');
            $this->Notification_user_model->setAsRead($this->user['id'], Notification_model::TYPE_GROUP_DETAIL, $group['id']);
        }

        $this->setResponse($this->setSystem(ResponseStatus::SUCCESS), $group);
    }

    public function create() {
        $data = $this->postData;
        $data['image'] = isset($_FILES['image']) ? $_FILES['image']['name'] : NULL;
        $data['user_id'] = $this->user['id'];
        $insert = $this->model->create($data);
        if ($insert === TRUE) {
            $this->setResponse($this->setSystem(ResponseStatus::SUCCESS, NULL, array('Berhasil membuat group')));
        } elseif (is_string($insert)) {
            $this->setResponse($this->setSystem(ResponseStatus::ERROR, $insert));
        } else {
            $validation = $this->model->getErrorValidate();
            if (empty($validation)) {
                $this->setResponse($this->setSystem(ResponseStatus::ERROR, 'Gagal membuat group'));
            } else {
                $this->setResponse($this->setSystem(ResponseStatus::ERROR_VALIDATION, NULL, array(), $validation));
            }
        }
    }

}

?>