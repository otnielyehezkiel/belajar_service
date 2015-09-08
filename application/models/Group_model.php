<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Group_model extends AppModel {

    const PRIVACY_PUBLIC = 'C';
    const PRIVACY_PRIVATE = 'E';

    protected $has_many = array(
        'Post',
        'Group_member'
    );
    protected $label = array(
        'name' => 'Nama',
        'privacy' => 'Privasi',
        'image' => 'Gambar',
    );
    protected $validation = array(
        'name' => 'required',
        'privacy' => 'required|in_list[C,E]'
    );

    protected function property($row) {
        $row['images'] = Image::getImage($row['id'], $row['image'], 'groups/images');
        unset($row['image']);

        return $row;
    }

    public function getMe($userId) {
        $this->_database->select('groups.*');
        $this->_database->join('group_members', 'group_members.group_id = groups.id', 'LEFT');
        $groups = $this->with(array('Group_member' => 'User'))->order_by('groups.name', 'ASC')->get_many_by(
                "(group_members.user_id = $userId AND groups.privacy = '" . self::PRIVACY_PRIVATE . "') OR (group_members.user_id IS NULL AND groups.privacy = '" . self::PRIVACY_PUBLIC . "')");
        foreach ($groups as $key => $group) {
            $groups[$key] = self::setOwner($group);
        }
        return $groups;
    }

    public function getAll() {
        $groups = $this->with(array('Group_member' => 'User'))->order_by('name', 'ASC')->get_all();
        foreach ($groups as $key => $group) {
            $groups[$key] = self::setOwner($group);
        }
        return $groups;
    }

    public static function setOwner($group) {
        if (!empty($group['groupMembers'])) {
            foreach ($group['groupMembers'] as $member) {
                if ($member['groupMemberRole']['id'] == Group_member_model::ROLE_OUWNER) {
                    $group['owner'] = $member['user'];
                    break;
                }
            }
        }
        return $group;
    }

    public function create($data, $skip_validation = FALSE, $return = TRUE) {
        if( empty($data['members']) || !isset($data['members']) ){
            return 'Data member tidak ada.';
        }
        $this->_database->trans_begin();
        $create = parent::create($data, $skip_validation, $return);

        if ($create) {
            $toMember = explode(',', $data['members']);
            array_push($toMember, $data['user_id']);
            $this->load->model('Group_member_model');
            foreach ($toMember as $member) {
                $dataToMember = array(
                        'group_id' => $create,
                        'user_id' => $member,
                        'role' => (($member==$data['user_id']) ? Group_member_model::ROLE_OUWNER : Group_member_model::ROLE_MEMBER)
                    );
                $insert = $this->Group_member_model->insert($dataToMember, TRUE, FALSE);
                if (!$insert) {
                    $this->_database->trans_rollback();
                    return FALSE;
                }
            }

            $upload = TRUE;
            if (isset($_FILES['image']) && !empty($_FILES['image'])) {
                $upload = Image::upload('image', $create, $_FILES['image']['name'], 'groups/images');
            }
            if ($upload === TRUE) {
                $this->load->model('Notification_model');
                $this->Notification_model->generate(Notification_model::ACTION_GROUP_CREATE,$create);
                return $this->_database->trans_commit();
            } else {
                $this->_database->trans_rollback();
                return $upload;
            }
        }
        $this->_database->trans_rollback();
        return FALSE;
    }
    
    public function getForMe($userId) {
        $this->_database->select('groups.*');
        $this->_database->join('group_members', 'group_members.group_id = groups.id');
        $groups = $this->with(array('Group_member' => 'User'))->order_by('groups.name', 'ASC')->get_many_by(array('group_members.user_id' => $userId));
        foreach ($groups as $key => $group) {
            $groups[$key] = self::setOwner($group);
        }
        return $groups;
    }

}
