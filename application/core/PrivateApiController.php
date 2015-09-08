<?php

(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Digunakan untuk autentifikasi user yang telah login
 */
class PrivateApiController extends AppController {

    protected $token;
    protected $user;

    public function __construct() {
        parent::__construct();

        $this->validateToken();

        //SET MAPPER RESPONSE
        if (!empty($this->model)) {
            $this->model->is_mapper(TRUE);
        }
    }

    /**
     * Digunakan untuk validasi token dan penyimpanan history request
     */
    private function validateToken() {
        if (!isset($this->postData['token'])) {
            $this->setResponse($this->setSystem(ResponseStatus::UNAUTHORIZED, 'Token tidak dikirim.'));
        }

        $auth = AuthManager::validateToken($this->postData['token']);
        if (is_string($auth)) {
            $this->setResponse($this->setSystem(ResponseStatus::UNAUTHORIZED, $auth));
        } elseif (is_array($auth)) {
            list($token, $user) = $auth;
            $this->token = $this->systemResponse->token = $token;
            $this->user = $user;
        }
        unset($this->postData['token']);
    }

    protected function setSystem($errorCode = NULL, $errorMessage = NULL, $infos = NULL, $validation = NULL) {
        if (empty($errorCode)) {
            $errorCode = ResponseStatus::SUCCESS;
        }
        $this->load->model('Notification_user_model');
        $notification = $this->Notification_user_model->getCountMe($this->user['id']);
        $this->systemResponse->setData($errorCode, $errorMessage, $infos, $validation, $notification);
        return $this->systemResponse;
    }

}
