{assign var=unread_messages value=false|fn_cp_conversations_get_unread_messages}
<li class="ty-account-info__item ty-dropdown-box__item">
    <a class="ty-account-info__a underlined" href="{"conversations.list"|fn_url}" rel="nofollow">
        {__("conversations")}{if $unread_messages} <strong>({$unread_messages})</strong>{/if}
    </a>
</li>
{if $unread_messages}
    {capture name="title"}
        <a class="ty-account-info__title" href="{"profiles.update"|fn_url}">
            <i class="ty-icon-moon-user"></i>
            <span class="hidden-phone" {live_edit name="block:name:{$block.block_id}"}>{$title} <strong>({$unread_messages})</strong></span>
            <i class="ty-icon-down-micro ty-account-info__user-arrow"></i>
        </a>
    {/capture}
{/if}