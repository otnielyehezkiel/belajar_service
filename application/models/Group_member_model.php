<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Group_member_model extends AppModel {

    const ROLE_OUWNER = 'O';
    const ROLE_ADMINISTRATOR = 'A';
    const ROLE_MEMBER = 'M';

    protected $after_get = array('mapper', 'property');
    protected $belongs_to = array(
        'Group',
        'User'
    );
    protected $label = array(
        'group_id' => 'Group',
        'user_id' => 'User',
        'role' => 'Role',
    );
    protected $validation = array(
        'user_id' => 'required|integer',
        'group_id' => 'required|integer',
        'role' => 'required|in_list[O,A,M]'
    );

    public static function getRole($role = NULL) {
        $roles = array(
            self::ROLE_OUWNER => 'Owner',
            self::ROLE_ADMINISTRATOR => 'Admnistrator',
            self::ROLE_MEMBER => 'Member'
        );
        return empty($role) ? $roles : $roles[$role];
    }

    protected function property($row) {
        if (!empty($row['role'])) {
            $row['groupMemberRole'] = array(
                'id' => $row['role'],
                'name' => self::getRole($row['role'])
            );
        }
        unset($row['role']);
        return $row;
    }

}
