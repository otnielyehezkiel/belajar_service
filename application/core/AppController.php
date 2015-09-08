<?php

(defined('BASEPATH')) OR exit('No direct script access allowed');

class AppController extends CI_Controller {

    protected $defaultModel;
    protected $class;
    protected $method;
    protected $postData = array();
    protected $model = FALSE;
    protected $data;
    protected $systemResponse;

    public function __construct() {
        parent::__construct();

        //GET CLASS AND METHOD
        $this->class = $this->router->class;
        $this->method = ($this->router->class == $this->router->method) ? 'index' : $this->router->method;

        $this->postData = $this->input->post();

        //LOAD DEFAULT MODEL
        $this->loadDefaultModel();

        $this->systemResponse = new System();

        $this->setLogRequest();
    }

    private function setLogRequest() {
        $data = array(
            'HTTPHEADER' => BenchmarkManager::httpHeader(),
            'URL' => current_url(),
            'GET' => BenchmarkManager::get(),
            'POST' => BenchmarkManager::post(),
            'FILES' => BenchmarkManager::files());
        log_message('debug', "REQUEST : " . json_encode($data));
    }

    private function loadDefaultModel() {
        if (empty($this->defaultModel)) {
            $defaultModel = ucfirst($this->class) . '_model';
        } else {
            $defaultModel = $this->defaultModel;
        }
        if (file_exists(APPPATH . 'models/' . $defaultModel . EXT)) {
            $this->load->model($defaultModel);
            $this->model = new $defaultModel();
        }
    }

    protected function setSystem($errorCode = NULL, $errorMessage = NULL, $infos = NULL, $validation = NULL) {
        if (empty($errorCode)) {
            $errorCode = ResponseStatus::SUCCESS;
        }
        $this->systemResponse->setData($errorCode, $errorMessage, $infos, $validation);
        return $this->systemResponse;
    }

    protected function setResponse($system, $data = array()) {
        if (empty($data)) {
            $data = $this->data;
        }
        $response = new Response();
        $response->setData($system, $data);
        $response->render();
    }

}
