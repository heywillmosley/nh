<?php

class esigOwner extends Esig_Roles {

    public static function init() {

        add_filter('esig_owner_change_option', array(__CLASS__, 'ownerChangeOption'), 10, 2);
        add_action('esig_document_after_save', array(__CLASS__, 'documentAfterSave'), 1100, 1);
    }

    public static function documentAfterSave($args) {

        $esigOwnerId = esigpost('esigOwnerId');
        if (!$esigOwnerId) {
            return false;
        }

        $docId = $args['document']->document_id;
        $ownerId = WP_E_Sig()->document->get_document_owner_id($docId);
        $currentWpId = WP_E_Sig()->user->getCurrentWPUserID();

        if ($ownerId != $currentWpId) {
            return false;
        }
        if ($ownerId == $esigOwnerId) {
            return false;
        }
        $newOwnerDetails = WP_E_Sig()->user->getUserByWPID($esigOwnerId);
        if (!$newOwnerDetails) {
            return false;
        }

        $oldOwnerDetails = WP_E_Sig()->user->getUserByWPID($ownerId);

        Esign_Query::_update(Esign_Query::$table_documents, array('user_id' => $esigOwnerId), array('document_id' => $docId), array('%d'), array('%d'));

        $eventText = sprintf(__(" Document owner %s has handed over this document to %s %s - %s", 'esig'), $oldOwnerDetails->user_email, $newOwnerDetails->user_email, WP_E_Sig()->document->esig_date($docId), esig_get_ip());

        WP_E_Sig()->document->recordEvent($docId, 'owner_change', $eventText);
        $addSignature = esigpost('add_signature');
        if ($addSignature) {
            WP_E_Sig()->meta->add($docId, "auto_add_signature", $ownerId);
        }
    }

    public static function ownerChangeOption($content, $docId) {

        $data = array();
        $ownerId = WP_E_Sig()->document->get_document_owner_id($docId);
        $currentWpId = WP_E_Sig()->user->getCurrentWPUserID();

        if ($ownerId != $currentWpId) {
            return false;
        }

        $data['ownerId'] = $ownerId;
        $ownerList = self::ownerList($docId, $ownerId);
        if(empty($ownerList)&& !is_array($ownerList)){
            return false;
        }
        $data['ownerList'] = self::ownerList($docId, $ownerId);
        $templates = dirname(__FILE__) . "/view/owner-change-view.php";
        $content = WP_E_View::instance()->html($templates, $data);
        return $content;
    }

    public static function ownerList($docId, $ownerId) {
        
        $result = array();
        $unlimitedRoles = self::get_unlimited_roles_option();
        $unlimitedUsers = self::get_unlimited_uesrs_option();
        if (is_null($unlimitedUsers) && is_null($unlimitedRoles)) {
            return $result;
        }
        $wp_users = get_users();

        
        foreach ($wp_users as $user) {
            if (in_array($user->ID, $unlimitedUsers)) {
                $result[$user->ID] = $user->user_login;
            } else {
                foreach ($user->roles as $role) {
                    if (in_array($role, $unlimitedRoles)) {
                        $result[$user->ID] = $user->user_login;
                    }
                }
            }
        }

        return $result;
    }

}
