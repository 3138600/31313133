{if $order_info.user_id}
    {assign var="return_current_url" value=$config.current_url|escape:url}
    {if "ULTIMATE"|fn_allowed_for}
        {assign var="cp_conv_link_text" value=__("cp_comminicate_with_admin")}
    {else}
        {assign var="cp_conv_link_text" value=__("communicate_vendor")}
    {/if}
    {if $order_info.exst_conversations}
        <a class="ty-btn orders-print__pdf ty-btn__text text-button " href="{"conversations.view&conversation_id=`$order_info.exst_conversations`"|fn_url}"><i class="cp-icon-edit"></i>{__("cp_view_convers")}</a>
    {else}
        <a class="ty-btn orders-print__pdf ty-btn__text text-button cm-dialog-opener cm-ajax cp-enabled" href="{"conversations.new&recipient_id=`$order_info.company_id`&order_id=`$order_info.order_id`&subject_order=`$order_info.order_id`?return_url=`$return_current_url`"|fn_url}" data-ca-dialog-title="{__('new_conversation')}" data-ca-target-id="compose_new_message">
            <i class="cp-icon-edit"></i><span>{$cp_conv_link_text}</span>
        </a>
    {/if}
{/if}