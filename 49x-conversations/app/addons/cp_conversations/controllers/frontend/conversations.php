<?php

if(!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;
use Tygh\Session;
use Tygh\Helpdesk;

if($mode == 'answer') {
    if(!empty($_REQUEST['ekey']) && !empty($_REQUEST['user_id']) && !empty($_REQUEST['conversation_id'])) {
        $exists = db_get_field("SELECT conversation_id FROM ?:cp_conversation_users WHERE recipient_id = ?i AND ekey = ?s AND conversation_id = ?i", $_REQUEST['user_id'], $_REQUEST['ekey'], $_REQUEST['conversation_id']);
        if(!empty($exists)) {
            Session::regenerateId();
            if (!empty($_SESSION['auth']['order_ids'])) {
                foreach ($_SESSION['auth']['order_ids'] as $k => $v) {
                    db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i", array('user_id' => $_REQUEST['user_id']), $v);
                }
            }
            fn_login_user($_REQUEST['user_id']);
            Helpdesk::auth();
        }
        return array(CONTROLLER_STATUS_REDIRECT, "conversations.view.&conversation_id=" . $_REQUEST['conversation_id']);        
    }
}

if (empty($_SESSION['auth']['user_id'])) {
    $url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : Registry::get('config.current_url');
    if(defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('force_redirection', fn_url("auth.login_form?return_url=" . urlencode($url)));
    }
    return array(CONTROLLER_STATUS_REDIRECT, fn_url("auth.login_form?return_url=" . urlencode($url)));
}

if($mode == 'list') {
    if(empty($_REQUEST['folder_id']) && empty($_REQUEST['folder'])) {
        //by default this is all section folder="A"
        $_REQUEST['folder'] = 'A';
    }
    list($conversations, $search) = fn_cp_conversations_get_conversations($_REQUEST);
    Registry::get('view')->assign('conversations', $conversations);
    Registry::get('view')->assign('search', $search);
    $customer_folders = db_get_array("SELECT * FROM ?:cp_conversation_folders WHERE user_id = ?i", $_SESSION['auth']['user_id']);
    Registry::get('view')->assign('customer_folders', $customer_folders);
    fn_add_breadcrumb(__('conversations'));

} elseif($mode == 'view') {
    if(!empty($_REQUEST['conversation_id'])) {
        fn_add_breadcrumb(__('conversations'), fn_url('conversations.list'));
        //mark conversation as read
        $udata = array('read' => 'Y');
        db_query("UPDATE ?:cp_conversation_users SET ?u WHERE conversation_id = ?i AND recipient_id = ?i", $udata, $_REQUEST['conversation_id'], $_SESSION['auth']['user_id']);

        $_REQUEST['group_for_customer'] = empty($_REQUEST['view_all']);

        $conversation = fn_cp_conversations_get_conversation_data($_REQUEST['conversation_id'], $_REQUEST);
        if(!empty($conversation)) {        
            $recipient_ids = $conversation['recipients'];
            //get first recipient but not me
            foreach($recipient_ids as $id) {
                if($id != $_SESSION['auth']['user_id']) {
                    $recipient = $id;
                    break;
                }
            }
            if(!empty($recipient)) {
                Registry::get('view')->assign('target_recipient', $recipient);
                // if this is vendor admin => get vendor info
                $company_id = db_get_field("SELECT company_id FROM ?:users WHERE user_id = ?i AND user_type = ?s", $recipient, 'V');
                if(!empty($company_id)) {
                    $vendor_info['company'] = fn_get_company_data($company_id);
                    $vendor_info['company']['logos'] = fn_get_logos($company_id);
                    list($vendor_info['orders']) = fn_get_orders(array(
                        'user_id' => $_SESSION['auth']['user_id'],
                        'company_id' => $company_id
                    ), 3);
                    $vendor_ids = db_get_fields("SELECT user_id FROM ?:users WHERE company_id = ?i AND user_type = ?s", $company_id, 'V');
                    list($vendor_info['conversations']) = fn_cp_conversations_get_conversations(array(
                        'recipient_ids' => $vendor_ids,
                        'limit' => 3
                    ));
                    Registry::get('view')->assign('vendor_info', $vendor_info);
                }
            }
            fn_add_breadcrumb(__('conversation_beetween') . ' ' . $conversation['formatted_usernames']);
        }

        //get current user data
        $current_user_data = array();
        $current_user_data['name'] = fn_get_user_name($_SESSION['auth']['user_id']);
        $current_user_data['image'] = fn_get_image_pairs($_SESSION['auth']['user_id'], 'user_image', 'M', true, true, CART_LANGUAGE);
        Registry::get('view')->assign('current_user_data', $current_user_data);

        Registry::get('view')->assign('conversation', $conversation);
        $customer_folders = db_get_array("SELECT * FROM ?:cp_conversation_folders WHERE user_id = ?i", $_SESSION['auth']['user_id']);
        Registry::get('view')->assign('customer_folders', $customer_folders);
        
        if(defined('AJAX_REQUEST')) {
            Registry::get('view')->display('addons/cp_conversations/views/conversations/view.tpl');
            exit;
        }
    }
}
