<?php

if(!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry; 

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	 // ====================================================================
    // НАЧАЛО НОВОГО КОДА: Обработка отправки первого сообщения в CIAN
    // ====================================================================
    if ($mode == 'start_cian_chat') {
        $chat_id = isset($_REQUEST['cian_chat_id']) ? trim($_REQUEST['cian_chat_id']) : '';
        $message = isset($_REQUEST['cian_message']) ? trim($_REQUEST['cian_message']) : '';

        if (!empty($chat_id) && !empty($message)) {
            // Вызываем добавленную функцию из func.cian.php
            list($status, $msg) = fn_cp_conversations_cian_send_message($chat_id, $message);
            
            if ($status) {
                fn_set_notification('N', __('notice'), $msg);
            } else {
                fn_set_notification('E', __('error'), $msg);
            }
        } else {
            fn_set_notification('E', __('error'), 'Пожалуйста, укажите Chat ID и текст сообщения.');
        }
        
        // Возвращаемся обратно к списку бесед
        return array(CONTROLLER_STATUS_REDIRECT, 'conversations.manage');
    }
    // ====================================================================
    // КОНЕЦ НОВОГО КОДА
    // =====================
    $author_id = $_SESSION['auth']['user_id'];
    if($mode == 'update') {
        if(!empty($_REQUEST['conversation_id'])) {
            $udata = array(
                'subject' => $_REQUEST['conversation_data']['subject']
            );
            db_query("UPDATE ?:cp_conversations SET ?u WHERE conversation_id = ?i", $udata, $_REQUEST['conversation_id']);

        } else {
            $udata = array(
                'timestamp' => time(),
                'author_id' => $_SESSION['auth']['user_id'],
                'subject' => $_REQUEST['conversation_data']['subject']
            );
            $_REQUEST['conversation_id'] = db_query("INSERT INTO ?:cp_conversations ?e", $udata);
        }
        //upate conversation users
        if(!is_array($_REQUEST['conversation_data']['recipients'])) {
            $_REQUEST['conversation_data']['recipients'] = explode(',', $_REQUEST['conversation_data']['recipients']);
        }
        $added_users = $new_users = $_REQUEST['conversation_data']['recipients'];
        $existing_users = db_get_fields("SELECT recipient_id FROM ?:cp_conversation_users WHERE conversation_id = ?i", $_REQUEST['conversation_id']);
        if(!empty($_REQUEST['conversation_data']['recipients'])) {
            $added_users = array_diff($new_users, $existing_users);
            $deleted_users = array_diff($existing_users, $new_users);
        }
        if(!empty($deleted_users)) {
            db_query("DELETE FROM ?:cp_conversation_users WHERE conversation_id = ?i AND recipient_id IN (?n)", $_REQUEST['conversation_id'], $deleted_users);
        }
        if(!empty($added_users)) {
            foreach($added_users as $user_id) {
                $udata = array(
                    'conversation_id' => $_REQUEST['conversation_id'],
                    'recipient_id' => $user_id,
                    'read' => 'N'
                );
                db_query("REPLACE INTO ?:cp_conversation_users ?e",$udata);
            }
        }
        return array(CONTROLLER_STATUS_OK, fn_url('conversations.update&conversation_id=' . $_REQUEST['conversation_id']));
    } elseif($mode == 'm_delete') {
        if(!empty($_REQUEST['conversation_ids'])) {
            foreach($_REQUEST['conversation_ids'] as $id) {
                fn_cp_conversations_delete_conversation($id);
            }
            fn_set_notification('N', __('notice'), __('selected_conversations_deleted'));
        }
        return array(CONTROLLER_STATUS_OK, fn_url('conversations.manage'));
    }
}

if($mode == 'manage') {
    list($conversations, $search) = fn_cp_conversations_get_conversations($_REQUEST);
    Registry::get('view')->assign('conversations', $conversations);
    Registry::get('view')->assign('search', $search);
//CP Tier
    if (!empty($_REQUEST['start_with_user_id'])) {
        $admin_ind = Registry::get('config.admin_index');
        $new_url = defined('HTTPS') ? Registry::get('config.https_path') . '/' : Registry::get('config.http_path') . '/';
        $new_url .= $admin_ind . '?dispatch=conversations.manage';
        Registry::get('view')->assign('start_with_new_url', $new_url);
        Registry::get('view')->assign('start_with_user_id', $_REQUEST['start_with_user_id']);
    }
//

} elseif($mode == 'update') {
    if(!empty($_REQUEST['conversation_id'])) {
        $conversation = fn_cp_conversations_get_conversation_data($_REQUEST['conversation_id'], $_REQUEST);
        $company_id = Registry::get('runtime.company_id');
        if(!empty($company_id)) {
            $vendor_admins = db_get_hash_array("SELECT CONCAT_WS(' ', firstname, lastname) as name, user_id FROM ?:users WHERE company_id = ?i AND user_type = ?s", 'user_id', $company_id, 'V');
            Registry::get('view')->assign('vendor_admins', $vendor_admins);
            $is_root_admin = db_get_field("SELECT user_id FROM ?:users WHERE user_id = ?i AND is_root = ?s", $_SESSION['auth']['user_id'], 'Y');
            Registry::get('view')->assign('is_root_admin', $is_root_admin);
        }
        if(!empty($conversation)) {
            //mark conversation as read
            $udata = array('read' => 'Y');
            db_query("UPDATE ?:cp_conversation_users SET ?u WHERE conversation_id = ?i AND recipient_id = ?i", $udata, $conversation['conversation_id'], $_SESSION['auth']['user_id']);
            Registry::get('view')->assign('conversation', $conversation);
            if(defined('AJAX_REQUEST')) {
                Registry::get('view')->display('addons/cp_conversations/views/conversations/update.tpl');
                exit;
            }
            Registry::set('navigation.tabs', array(
                'messages' => array(
                    'js' => true,
                    'title' => __('messages')
                ),
                'general' => array(
                    'js' => true,
                    'title' => __('general')
                )
            ));
        }
    } else {
        return array(CONTROLLER_STATUS_OK, fn_url('conversations.manage'));    
    }
} elseif($mode == 'delete') {
    if(!empty($_REQUEST['conversation_id'])) {
        fn_cp_conversations_delete_conversation($_REQUEST['conversation_id']);
        fn_set_notification('N', __('notice'), __('selected_conversations_deleted'));
    }
    return array(CONTROLLER_STATUS_OK, fn_url('conversations.manage'));
}