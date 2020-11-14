<?php

/**
 * Load modules
 */
include_once sprintf("%sDlSettings.php", plugin_dir_path(__FILE__));

/**
 * Class to sync users
 */
class DlUser {

    private static $meta_from = [
        'last_name' => 'lname',
        'billing_last_name' => 'lname',
        'first_name' => 'fname',
        'billing_first_name' => 'fname',
        'billing_phone' => 'telland',
        'billing_email' => 'email',
        'billing_address_1' => 'addressname1',
        'billing_postcode' => 'postcode'
    ];

    private static $meta_to = [
        'lname' => 'last_name',
        'fname' => 'first_name',
        'telland' => 'billing_phone',
        'email' => 'billing_email',
        'addressname1' => 'billing_address_1',
        'postcode' => 'billing_postcode'
    ];

    public static function blExists($id) {
        $ret = false;
        if ($id) {
            $user = new WP_User($id);
            $ret = $user->exists();
        }
        return $ret;
    }

    /**
     * @function getDataForCRM
     */
    protected static function getDataForCRM($id = null) {
        $ret = [];
        foreach (self::$meta_to as $key => $value) {
            $ret[$key] = get_user_meta($id, $value, true);
        }
        return $ret;
    }


    /**
     * @function updateMeta
     */
    protected static function updateMeta($data = [], $id = 0) {
        if (!empty($data)) {
            foreach (self::$meta_from as $key => $value) {
                if (isset($data[$value])) {
                    update_user_meta($id, $key, $data[$value]);
                }
            }
            if (isset($data['email'])) {
                wp_update_user([
                    'ID' => $id,
                    'user_email' => $data['email']
                ]);
            }
            update_user_meta($id, 'dl_remarks', 'updated/created from crm');
        }
    }

    /**
     * @function Create
     */
    public static function Create($req) {
        $params = $req->get_params();
        $ret = ['success' => false, 'msg' => 'GEN_ERR_MSG', 'data' => []];
        $password = "12345";
        try {
            if (in_array($params['token'], DlSettings::getTokens(), true)) {
                if (isset($params['username']) && isset($params['email'])) {
                    $blCreated = false;
                    $username = trim($params['username']);
                    $email = trim($params['email']);
                    $id = email_exists($email);
                    if (!$id) {
                        if (!username_exists($username)) {
                            $id = wp_create_user($username, $password, $email);
                            $NewUser = new WP_User($id);
                            $NewUser->set_role('customer');
                            $blCreated = true;
                        } else {
                            $id = null;
                            $ret['msg'] = 'USERNAME_ALREADY_EXISTS';
                        }
                    }
                    if (is_int($id) && $id) {
                        self::updateMeta($params, $id);
                        $ret['success'] = true;
                        $ret['msg'] = 'UPDATED';
                        if ($blCreated) {
                            $ret['msg'] = 'CREATED';
                            $ret['data'] = [
                                'id' => $id,
                                'dtupdate' => get_user_meta($id, 'last_update', true)
                            ];
                        }
                    }
                } else {
                    $ret['msg'] = 'NECESSARY_FIELDS_ARE_MISSING';
                }
            } else {
                $ret['msg'] = 'INVALID_TOKEN';
            }
        } catch (\Exception $e) {
            $ret = ['success' => false, 'msg' => 'FATAL ERR', 'data' => [
                'fatal_msg' => $e->getMessage()
            ]];
        }
        $response = new WP_REST_Response($ret);
        $response->set_status(200);
        return $response;
    }

    /**
     * @function Update
     */
    public static function Update($req) {
        $params = $req->get_params();
        $ret = ['success' => false, 'msg' => 'GEN_ERR_MSG', 'data' => []];
        try {
            if (in_array($params['token'], DlSettings::getTokens(), true)) {
                if (isset($params['id']) && $params['last_update']) {
                    if (self::blExists($params['id'])) {
                        $wp_last_update = get_user_meta($params['id'], 'last_update', true);
                        if ($wp_last_update) {
                            $date = new DateTime();
                            $date->setTimezone(new DateTimeZone('Europe/Athens'));
                            $date->setTimestamp($wp_last_update);
                            if (strtotime($date->format('Y-m-d H:i:s')) > strtotime($params['last_update'])) {
                                $ret = ['success' => true, 'msg' => '', 'data' => [
                                    'action' => 'update_crm',
                                    'id' => $params['id'],
                                    'data' => self::getDataForCRM($params['id'])
                                ]];
                            } else {
                                self::updateMeta($params, $params['id']);
                                $ret = ['success' => true, 'msg' => 'UPDATED', 'data' => []];
                            }
                            $ret['data']['dt_post'] = $params['last_update'];
                            $ret['data']['dt_db'] = $date->format('Y-m-d H:i:s');
                        } else {
                            $ret['msg'] = 'INVALID_LAST_UPDATE_IN_DB';
                        }
                    } else {
                        $ret['msg'] = 'USER_NOT_EXISTS';
                    }
                } else {
                    $ret['msg'] = 'NECESSARY_FIELDS_ARE_MISSING';
                }
            } else {
                $ret['msg'] = 'INVALID_TOKEN';
            }
        } catch (\Exception $e) {
            $ret = ['success' => false, 'msg' => 'FATAL ERR', 'data' => [
                'fatal_msg' => $e->getMessage()
            ]];
        }
        $response = new WP_REST_Response($ret);
        $response->set_status(200);
        return $response;
    }

}