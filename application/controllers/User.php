<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User extends PrivateApiController {

    /**
     * Digunakan untuk mengambil list user
     */
    public function index() {
        $user = $this->model->get_all();

        $this->setResponse($this->setSystem(ResponseStatus::SUCCESS), $user);
    }

    /**
     * Digunakan untuk mengambil detail user dengan ID user
     */
    public function detail($id) {
        $user = $this->model->get($id);

        //SET RESPONSE
        $this->setResponse($this->setSystem(ResponseStatus::SUCCESS), $user);
    }

    /**
     * Digunakan untuk mengambil profil user dan role user yang sedang login
     */
    public function me() {
        $user = $this->model->is_mapper(true)->mapper($this->user);
        $user['photos'] = $this->user['photos'];

        //SET RESPONSE
        $this->setResponse($this->setSystem(ResponseStatus::SUCCESS), $user);
    }

    /**
     * Digunakan untuk update profil user
     */
    public function update() {
        $data = $this->postData;
        if (isset($_FILES['photo'])) {
            $data['photo'] = $_FILES['photo']['name'];
        }
        $update = $this->model->updateData($this->user['id'], $data);
        if ($update === TRUE) {
            $user = $this->model->is_role(TRUE)->get($this->user['id']);
            $this->setResponse($this->setSystem(ResponseStatus::SUCCESS, NULL, array('Berhasil mengubah profil')), $user);
        } elseif (is_string($update)) {
            $this->setResponse($this->setSystem(ResponseStatus::ERROR, $update));
        } else {
            $validation = $this->model->getErrorValidate();
            if (empty($validation)) {
                $this->setResponse($this->setSystem(ResponseStatus::ERROR, 'Gagal mengubah profil ' . $this->user['name']));
            } else {
                $this->setResponse($this->setSystem(ResponseStatus::ERROR_VALIDATION, NULL, array(), $validation));
            }
        }
    }

    public function create() {
        $data = $this->postData;
        $data['photo'] = isset($_FILES['photo']) ? $_FILES['photo']['name'] : NULL;
        $insert = $this->model->create($data);
        if ($insert === TRUE) {
            $this->setResponse($this->setSystem(ResponseStatus::SUCCESS, NULL, array('Berhasil membuat user')));
        } elseif (is_string($insert)) {
            $this->setResponse($this->setSystem(ResponseStatus::ERROR, $insert));
        } else {
            $validation = $this->model->getErrorValidate();
            if (empty($validation)) {
                $this->setResponse($this->setSystem(ResponseStatus::ERROR, 'Gagal membuat user'));
            } else {
                $this->setResponse($this->setSystem(ResponseStatus::ERROR_VALIDATION, NULL, array(), $validation));
            }
        }
    }

    /**
     * Digunakan untuk mengganti password user
     */
    public function change_password() {
        $this->form_validation->set_rules('oldPassword', 'Password Lama', 'required');
        $this->form_validation->set_rules('newPassword', 'Password Baru', 'required|min_length[6]');
        $this->form_validation->set_rules('confirmPassword', 'Konfirmasi Password', 'required|min_length[6]|matches[newPassword]');
        if ($this->user['password'] == SecurityManager::hashPassword($this->postData['oldPassword'], $this->user['password_salt'])) {
            if ($this->form_validation->run() == TRUE) {
                $update = $this->model->update($this->user['id'], SecurityManager::encode($this->postData['newPassword']), TRUE);
                if ($update) {
                    $this->setResponse($this->setSystem(ResponseStatus::SUCCESS, 'Berhasil mengganti password'));
                } else {
                    $this->setResponse($this->setSystem(ResponseStatus::ERROR, 'Gagal mengganti password'));
                }
            } else {
                $validation = $this->model->getErrorManualValidation(array('oldPassword', 'newPassword', 'confirmPassword'));
                $this->setResponse($this->setSystem(ResponseStatus::ERROR_VALIDATION, NULL, array(), $validation));
            }
        } else {
            $this->setResponse($this->setSystem(ResponseStatus::ERROR, 'Gagal mengganti password'));
        }
    }

    /**
     * Digunakan untuk mereset password (kirim notifikasi ke email)
     */
    public function forgot_password() {
        
    }

    public function list_me() {
        $users = $this->model->getListFor($this->user['id']);

        $this->setResponse($this->setSystem(ResponseStatus::SUCCESS), $users);
    }

    public function logout() {
        $logout = AuthManager::logout($this->token);
        if ($logout === TRUE) {
            $this->setResponse($this->setSystem(ResponseStatus::SUCCESS, NULL, array('Logout berhasil')));
        } else {
            $this->setResponse($this->setSystem(ResponseStatus::ERROR, $logout));
        }
    }

}

?>