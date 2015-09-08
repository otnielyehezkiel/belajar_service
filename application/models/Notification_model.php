<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_model extends AppModel {

    //STATUS
    const STATUS_NOT_SEND = 'N';
    const STATUS_SEND = 'S';
    //TYPE
    const TYPE_POST_DETAIL = 'PD';
    const TYPE_GROUP_DETAIL = 'GD';
    //ACTION
    const ACTION_POST_CREATE = 'PC';
    const ACTION_COMMENT_CREATE = 'CC';
    const ACTION_GROUP_CREATE = 'GC';

    protected $belongs_to = array(
        'User'
    );
    protected $has_many = array(
        'Notification_user'
    );

    public function save($action = NULL, $message = NULL, $senderId, $receiverIds = array(), $type = NULL, $referenceId = NULL) {
        $data = array(
            'reference_id' => $referenceId,
            'message' => $message,
            'action' => $action,
            'reference_type' => $type,
            'status' => self::STATUS_NOT_SEND,
            'send_at' => NULL,
            'user_id' => $senderId,
        );

        $this->_database->trans_begin();
        $save = $this->create($data);
        if ($save) {
            $receiver = array();
            $this->load->model('Notification_user_model');
            foreach ($receiverIds as $receiverId) {
                $receiver[] = array(
                    'user_id' => $receiverId,
                    'notification_id' => $save,
                    'status' => Notification_user_model::STATUS_NOTIFICATION,
                    'created_at' => Util::timeNow(),
                    'updated_at' => Util::timeNow(),
                );
            }
            $this->_database->insert_batch('notification_users', $receiver);
            return $this->_database->trans_complete();
        }
        $this->_database->trans_rollback();
        return FALSE;
    }

    public function generate($action, $referenceId, $senderId = NULL) {
        $this->load->model('User_model');
        switch ($action) {
            case self::ACTION_POST_CREATE:
                $this->load->model('Post_model');
                $post = $this->Post_model->with(array('Group' => 'Group_member', 'Post_user'))->get($referenceId);
                $senderId = $post['userId'];
                $type = self::TYPE_POST_DETAIL;
                switch ($post['type']) {
                    case Post_model::TYPE_PUBLIC:
                        $users = $this->User_model->getListFor($senderId);
                        $receiverIds = Util::toList($users, 'id');
                        break;
                    case Post_model::TYPE_GROUP:
                        $receiverIds = Util::toList($post['group']['groupMembers'], 'userId', array($senderId));
                        break;
                    case Post_model::TYPE_PRIVATE:
                        $receiverIds = Util::toList($post['postUsers'], 'id', array($senderId));
                        break;
                }
                if (!empty($post['images'])) {
                    $message = 'memposting gambar "' . $post['images'] . '"';
                } elseif (!empty($post['file'])) {
                    $message = 'memposting berkas "' . $post['file'] . '"';
                } elseif (!empty($post['video'])) {
                    $message = 'memposting video "' . $post['video'] . '"';
                } elseif (!empty($post['link'])) {
                    $message = 'memposting link "' . $post['link'] . '"';
                } elseif (!empty($post['description'])) {
                    if (strlen($post['description']) > 30) {
                        $str = substr($post['description'], 0, 30) . '...';
                    } else {
                        $str = $post['description'];
                    }
                    $message = 'memposting "' . $str . '"';
                }
                break;
            case self::ACTION_COMMENT_CREATE:
                $this->load->model('Comment_model');
                $comment = $this->Comment_model->with(array('Post' => array('Group' => 'Group_member', 'Post_user')))->get($referenceId);
                $senderId = $comment['userId'];
                $type = self::TYPE_POST_DETAIL;
                switch ($comment['post']['type']) {
                    case Post_model::TYPE_PUBLIC:
                        $users = $this->User_model->getListFor($senderId);
                        $receiverIds = Util::toList($users, 'id');
                        break;
                    case Post_model::TYPE_GROUP:
                        $receiverIds = Util::toList($post['user']['groupMembers'], 'userId', array($senderId));
                        break;
                    case Post_model::TYPE_PRIVATE:
                        $receiverIds = Util::toList($post['postUsers'], 'id', array($senderId));
                        if (!in_array($comment['post']['userId'], $receiverIds)) {
                            $receiverIds[] = $comment['post']['userId'];
                        }
                        break;
                }
                if (strlen($comment['text']) > 30) {
                    $str = substr($comment['text'], 0, 30) . '...';
                } else {
                    $str = $comment['text'];
                }
                $message = 'mengomentari "' . $str . '"';
                break;
            case self::ACTION_GROUP_CREATE:
                $this->load->model('Group_model');
                $this->load->model('Group_member_model');
                $group = $this->Group_model->with('Group_member')->get($referenceId);
                $type = self::TYPE_POST_DETAIL;
                foreach ($group['groupMembers'] as $member) {
                    if ($member['groupMemberRole']['id'] == Group_member_model::ROLE_OUWNER) {
                        $senderId = $member['userId'];
                    } else {
                        $receiverIds[] = $member['userId'];
                    }
                }
                $message = 'menambahkan anda';
                break;
        }

        $this->save($action, $message, $senderId, $receiverIds, $type, $referenceId);
    }

    public function getMe($userId) {
        $this->_database->select("notifications.*, notification_users.status");
        $this->_database->join("notification_users", "notification_users.notification_id = notifications.id");
        $notifications = $this->with('User')->order_by("notifications.created_at", "DESC")->get_many_by(array('notification_users.user_id' => $userId));
        $types = array();
        foreach ($notifications as $notification) {
            $types[$notification['referenceType']][] = $notification['referenceId'];
        }
        $data = array();
        foreach ($types as $key => $value) {
            switch ($key) {
                case self::TYPE_POST_DETAIL:
                    $this->load->model('Post_model');
                    $data[$key] = Util::toMapObject($this->Post_model->with(array('Group', 'Post_user', 'User'))->get_many_by(array('id' => $value)), 'id');
                    break;
                case self::TYPE_GROUP_DETAIL:
                    $this->load->model('Group_model');
                    $data[$key] = Util::toMapObject($this->Group_model->get_many_by(array('id' => $value)), 'id');
                    break;
            }
        }
        $ouput = array();
        foreach ($notifications as $notification) {
            $message = NULL;
            if (!empty($notification['message']) && empty($notification['action'])) {
                $message = $notification['message'];
            } elseif (!empty($notification['action']) && !empty($notification['message'])) {
                switch ($notification['action']) {
                    case self::ACTION_POST_CREATE:
                        $post = $data[self::TYPE_POST_DETAIL][$notification['referenceId']];
                        if (!empty($post['group'])) {
                            $message = $notification['message'] . ' di grup ' . $post['group']['name'];
                        } elseif (!empty($post['postUsers'])) {
                            $str = (count($post['postUsers']) > 1 ) ? ' dan ' . count($post['postUsers']) . ' orang lainnya' : NULL;
                            $message = $notification['message'] . ' ke anda' . $str;
                        } else {
                            $message = $notification['message'];
                        }
                        break;
                    case self::ACTION_COMMENT_CREATE:
                        $post = $data[self::TYPE_POST_DETAIL][$notification['referenceId']];
                        $message = $notification['message'] . ' pada postingan ' . $post['user']['name'];
                        if (!empty($post['group'])) {
                            $message .=" di grup " . $post['group']['name'];
                        }
                        break;
                    case self::ACTION_GROUP_CREATE:
                        $group = $data[self::TYPE_GROUP_DETAIL][$notification['referenceId']];
                        $message = $notification['message'] . ' di grup ' . $group['name'];
                        break;
                }
            }
            $ouput[] = self::toObject($notification['id'], $message, $notification['user'], $notification['referenceId'], $notification[
                            'referenceType'], $notification['status'], $notification['createdAt'], $notification['updatedAt']);
        }
        return $ouput;
    }

    public static function toObject($id, $message, $sender, $referenceId, $referenceType, $status, $createdAt, $updatedAt) {
        return array(
            'id' => (int) $id,
            'message' => $message,
            'sender' => $sender,
            'referenceId' => (int) $referenceId,
            'referenceType' => $referenceType,
            'status' => $status,
            'createdAt' => $createdAt,
            'updatedAt' => $updatedAt
        );
    }

    public function getNotification($userId) {
        $this->load->model('Notification_user_model');
        $this->_database->select("notifications.*, notification_users.status");
        $this->_database->join("notification_users", "notification_users.notification_id = notifications.id");
        return $this->order_by("notifications.created_at", "DESC")->get_many_by(array('notification_users.user_id' => $userId, 'notification_users.status' => Notification_user_model::STATUS_NOTIFICATION));
    }

}
