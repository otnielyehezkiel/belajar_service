<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_user_model extends AppModel {

    //STATUS
    const STATUS_NOTIFICATION = 'N';
    const STATUS_UNREAD = 'U';
    const STATUS_READ = 'R';

    protected $belongs_to = array(
        'User',
        'Notification'
    );

    public function setAsUnRead($userId, $notificationId = array()) {
        $condition = array('user_id' => $userId, 'notification_id' => $notificationId);
        return $this->update_by($condition, array('status' => self::STATUS_UNREAD));
    }

    public function setAsRead($userId, $referenceType, $referenceId) {
        $condition = array('user_id' => $userId);
        $this->load->model('Notification_model');
        $notifications = $this->Notification_model->get_many_by(array('reference_id' => $referenceId, 'refereance_type' => $referenceType));
        $condition['notification_id'] = Util::toList($notifications, 'id');
        return $this->update_by($condition, array('status' => self::STATUS_READ));
    }

    public function getCountMe($userId) {
        return $this->count_by(array('user_id' => $userId, 'status' => self::STATUS_NOTIFICATION));
    }

}
