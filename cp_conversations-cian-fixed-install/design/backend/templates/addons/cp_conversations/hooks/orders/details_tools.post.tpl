{if $order_info.user_id}
    <li class="divider"></li>
    {if $order_info.exst_conversations}
        <li>{btn type="list" text=__("cp_view_convers") href="conversations.update&conversation_id=`$order_info.exst_conversations`" icon="cp-icon-edit" class="cm-new-window"}</li>
    {else}
        <li>
            <a class="cm-dialog-opener cm-ajax" href="{"conversations.new&recipient_id=`$order_info.user_id`&subject_order=`$order_info.order_id`&order_id=`$order_info.order_id`&return_url="|fn_url}" data-ca-target-id="compose_new_message" data-ca-dialog-title="{__("new_conversation")}">
                {__("new_conversation")}<i class="cp-icon-edit"></i>
            </a>
        </li>
    {/if}
{/if}