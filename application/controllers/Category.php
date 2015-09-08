<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Category extends PrivateApiController {

    /**
     * Digunakan untuk mendapatkan list category
     */
    public function index() {
        $categories = $this->model->get_all();

        $this->setResponse($this->setSystem(ResponseStatus::SUCCESS), $categories);
    }

}

?>