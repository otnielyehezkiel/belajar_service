<?php

class Role {

    const ADMINISTRATOR = 'A';
    const MANAGEMENT = 'M';
    const EMPLOYEE = 'E';
    const LEADER = 'L';

    public static function name($role = NULL) {
        $names = array(
            self::ADMINISTRATOR => 'Administrator',
            self::MANAGEMENT => 'Management',
            self::EMPLOYEE => 'Employee',
            self::LEADER => 'Leader'
        );
        return empty($role) ? $names : $names[$role];
    }

    public static function permission() {
        $permissions = array(
            //POST
            'post/public' => '*',
            'post/group' => array(self::MANAGEMENT, self::EMPLOYEE),
            'post/private' => array(self::MANAGEMENT, self::EMPLOYEE),
            'post/me' => '*',
            'post/create_public' => array(self::ADMINISTRATOR, self::MANAGEMENT),
            'post/create_group' => array(self::MANAGEMENT, self::EMPLOYEE),
            'post/create_private' => array(self::MANAGEMENT, self::EMPLOYEE),
            //COMMENT
            'comment/create' => '*',
            //GROUP
            'group/create' => self::LEADER,
            //USER
            'user/update' => '*',
            'user/change_password' => '*',
            //AGENDA
            'agenda/me' => '*',
            'agenda/create' => '*',
            //DEVICE
            'device/register' => '*',
        );
        return $permissions;
    }

    public static function getPermissions($role = NULL) {
        $permissions = array();
        foreach (self::permission() as $key => $val) {
            if ((is_string($val) && ($role == $val || $val == '*')) || (is_array($val) && in_array($role, $val))) {
                $permissions[] = $key;
            }
        }
        return $permissions;
    }

}
