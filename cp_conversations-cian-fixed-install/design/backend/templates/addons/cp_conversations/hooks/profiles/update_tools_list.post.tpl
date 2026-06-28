{if $user_data.user_id && $user_data.user_type == "C"}
    <li class="divider"></li>
    {*
    <li>{btn type="list" text=__("new_conversation") icon="cp-icon-edit" href="conversations.manage?start_with_user_id=`$id`"}</li>
    *}
    <li>
        <a class="cm-dialog-opener cm-ajax" href="{"conversations.new&recipient_id=`$id`&return_url="|fn_url}" data-ca-target-id="compose_new_message" data-ca-dialog-title="{__("new_conversation")}">
            {__("new_conversation")}<i class="cp-icon-edit"></i>
        </a>
    </li>
    <li class="divider"></li>
{/if}