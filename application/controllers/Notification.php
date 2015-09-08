<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Notification extends PrivateApiController {

    /**
     * Digunakan untuk mengambil notifikasi user yang sedang login
     */
    public function me() {
        $notifications = $this->model->getMe($this->user['id']);

        //SET UNREAD NOTIFICATION
        if (!empty($post)) {
            $notificationsWarning = $this->model->getNotification($this->user['me']);
            $this->load->model('Notification_user_model');
            $this->Notification_user_model->setAsUnRead($this->user['id'], Util::toList($notificationsWarning, 'id'));
        }

        $this->setResponse($this->setSystem(ResponseStatus::SUCCESS), $notifications);
    }

}

?>