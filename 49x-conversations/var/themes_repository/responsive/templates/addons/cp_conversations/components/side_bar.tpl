<div class="cp-conversations__wrap" id="conversations_top_reload">
    <form action="{""|fn_url}" class="" method="get">
        <input type="hidden" name="result_ids" value="conversations_*">
        <input type="hidden" name="dispatch" value="conversations.list">
        <div class="ty-search-block">
            {strip}
                <input type="text" name="q" value="{$smarty.request.q}" placeholder="{__('search')}" class="ty-search-block__input cm-hint">
                {include file="buttons/magnifier.tpl" but_name="conversations.list" alt=__("search")}
            {/strip}
        </div>
        <ul class="cp-conversations__list">
            <li class="cp-conversations__list-item">
                <a href="{"conversations.list&folder=I"|fn_url}" class="cp-conversation-folder-item {if $search.folder == 'I'} selected{/if}" data-ca-target-id="conversations_*">{__('inbox')}</a>
            </li>
            <li class="cp-conversations__list-item">
                <a href="{"conversations.list&folder=S"|fn_url}" class="cp-conversation-folder-item {if $search.folder == 'S'} selected{/if}" data-ca-target-id="conversations_*">{__('sent')}</a>
            </li>
            <li class="cp-conversations__list-item">
                <a href="{"conversations.list&folder=A"|fn_url}" class="cp-conversation-folder-item {if $search.folder == 'A'} selected{/if}" data-ca-target-id="conversations_*">{__('all')}</a>
            </li>
            <li class="cp-conversations__list-item">
                <a href="{"conversations.list&folder=U"|fn_url}" class="cp-conversation-folder-item {if $search.folder == 'U'} selected{/if}" data-ca-target-id="conversations_*">{__('unread')}</a>
            </li>
            <li class="cp-conversations__list-item">
                <a href="{"conversations.list&folder=P"|fn_url}" class="cp-conversation-folder-item {if $search.folder == 'P'} selected{/if}" data-ca-target-id="conversations_*">{__('spam')}</a>
            </li>
            <li class="cp-conversations__list-item">
                <a href="{"conversations.list&folder=T"|fn_url}" class="cp-conversation-folder-item {if $search.folder == 'T'} selected{/if}" data-ca-target-id="conversations_*">{__('trash')}</a>
            </li>
            {if $customer_folders}
                <li class="cp-side-block-title">{__('folders')}</li>
                {assign var="return_current_url" value=$config.current_url|escape:url}
                {foreach from=$customer_folders item=folder}
                    <li class="cp-conversations__list-item">
                        <a href="{"conversations.list&folder_id=`$folder.folder_id`"|fn_url}" class="cp-conversation-folder-item {if $search.folder_id == $folder.folder_id} selected{/if}" data-ca-target-id="conversations_*">{$folder.folder}
                        </a>
                        <a  href="{"conversations.mass_update.delete_folder&folder_id=`$folder.folder_id`?redirect_url=`$return_current_url`"|fn_url}" class="{if !$detailed}cm-ajax{/if} cm-post" data-ca-target-id="conversations_*"><i class="ty-icon-cancel-circle"></i></a>
                    </li>
                {/foreach}
            {/if}
        </ul>
    </form>
<!--conversations_top_reload--></div>