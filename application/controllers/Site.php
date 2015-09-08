<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Site extends PublicApiController {

    public function login() {
        $data = AuthManager::login($this->input->post('username'), $this->input->post('password'), $this->input->post('device'));
        if (is_string($data)) {
            $this->setResponse($this->setSystem(ResponseStatus::ERROR, $data));
        } else {
            list($message, $token, $user) = $data;
            $this->systemResponse->token = $token;
            $this->setResponse($this->setSystem(ResponseStatus::SUCCESS, NULL, array($message)), $user);
        }
    }

}

?>