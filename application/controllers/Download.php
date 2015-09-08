<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Download extends PublicApiController {

    public function index() {
        $status = TRUE;

        $uuid = $this->input->get('file');
        if (empty($uuid)) {
            $status = File::INVALID_TOKEN;
        } else {
            list($secureId, $id, $path) = File::getData($uuid);
            if ($secureId != File::getFileName($id, $path)) {
                $status = File::INVALID_TOKEN;
            } else {
                switch ($path) {
                    default:
                        list($class, $field) = explode('/', $path);
                        $model = ucfirst(singular($class)) . '_model';
                        $this->load->model($model);
                        $data = $this->{$model}->get($id);
                        $filename = $data[singular($field)]['name'];
                        break;
                }
                $status = File::download($uuid, $filename);
            }
        }
        
        switch ($status) {
            case File::INVALID_TOKEN:
                show_error('Invalid URL to access file.', File::INVALID_TOKEN);
                break;
            case File::ERROR:
                show_error('Error to access file.', File::ERROR);
                break;
            case File::UNAUTHORIZED:
                show_error('Unauthorized user to access this file.', File::UNAUTHORIZED);
                break;
            case File::NOT_FOUND:
                show_error('File not found.', File::NOT_FOUND);
                break;
            default :
                show_404();
                break;
        }
    }

}

?>