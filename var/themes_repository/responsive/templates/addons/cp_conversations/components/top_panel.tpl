<div class="ty-sort-container cp-conversations__top-panel {if !$detailed}cp-disabled{/if}">
    <div class="cp-sort-dropdown cp-top-panelfirst-child">
        {if !$detailed}
            <span class="cp-sort-dropdown__wrapper cm-combination cp-check-all">
                <input type="checkbox" class="cm-check-items">
            </span>
        {/if}
        {if $search.folder == "T"}
            <a class="cp-sort-dropdown__wrapper cm-submit cm-ajax cp-trash-button" data-ca-dispatch="dispatch[conversations.mass_update.delete]" data-ca-target-form="conversations_form">
                <i class="cp-icon-trash"></i><span class="button-text">{__("delete_forever")}</span>
            </a>
        {else}
            <a class="cp-sort-dropdown__wrapper cm-submit cm-ajax cp-trash-button" data-ca-dispatch="dispatch[conversations.mass_update.move_to_trash]" data-ca-target-form="conversations_form">
                <i class="cp-icon-trash"></i><span class="button-text">{__("delete")}</span>
            </a>
        {/if}
        {if $search.folder == "A" || $search.folder == "T"}
            <a class="cp-sort-dropdown__wrapper  cm-submit cm-ajax" data-ca-dispatch="dispatch[conversations.mass_update.move_to_inbox]" data-ca-target-form="conversations_form">
                {__("move_to_inbox")}
            </a>
        {elseif $search.folder_id}
            <a class="cp-sort-dropdown__wrapper cm-submit cm-ajax" data-ca-dispatch="dispatch[conversations.mass_update.remove_from_folder.{$search.folder_id}]" data-ca-target-form="conversations_form">
                {__("remove_from_folder")}
            </a>
        {elseif $search.folder != "P"}
            <a class="cp-sort-dropdown__wrapper cm-submit cm-ajax" data-ca-dispatch="dispatch[conversations.mass_update.move_to_archive]" data-ca-target-form="conversations_form">
                {__("archive")}
            </a>
        {/if}
        {if $search.folder != "U"}
            <a class="cp-sort-dropdown__wrapper cm-submit cm-ajax cp-unread-button" data-ca-dispatch="dispatch[conversations.mass_update.mark_as_unread]" data-ca-target-form="conversations_form">
                {__("mark_as_unread")}
            </a>
        {/if}
        {if $search.folder == "P"}
            <a class="cp-sort-dropdown__wrapper cm-submit cm-ajax" data-ca-dispatch="dispatch[conversations.mass_update.move_to_inbox]" data-ca-target-form="conversations_form">
                {__("not_spam")}
            </a>
        {/if}
    </div>
    <div class="cp-sort-dropdown cp-move-buttons">
        <a id="sw_elm_1" class="cp-sort-dropdown__wrapper cm-combination cp-no-border">
            <i class="cp-icon-folder"></i><span class="button-text">{__('move')}</span>
            <i class="ty-sort-dropdown__icon ty-icon-down-micro"></i>
        </a>
        <ul id="elm_1" class="ty-sort-dropdown__content cm-popup-box hidden">
            <li class="ty-sort-dropdown__content-item">
                {foreach from=$customer_folders item=folder}
                    <span class="ty-sort-dropdown__content-item-a">
                        <label><input type="checkbox" value="{$folder.folder_id}" name="folder_ids[]" {if $folder.folder_id|in_array:$selected_folders}checked{/if}>{$folder.folder}</label>
                    </span>
                {/foreach}
                <span class="ty-sort-dropdown__content-item-a cp-no-background">
                    <input type="text" name="new_folder">
                </span>
                <span class="ty-sort-dropdown__content-item-a cp-no-background">
                    <a class="cp-add-right-padd cm-submit cm-ajax" data-ca-dispatch="dispatch[conversations.mass_update.move_to_folder]" data-ca-target-form="conversations_form">{__("move_to")}</a>
                    <a class="ty-float-right cm-submit cm-ajax" data-ca-dispatch="dispatch[conversations.mass_update.add_to_folder]" data-ca-target-form="conversations_form">{__("add_to")}</a>
                </span>
            </li>
        </ul>
    </div>
    <div class="cp-sort-dropdown">
        <a id="sw_elm_2" class="cp-sort-dropdown__wrapper cm-combination cp-no-border">
            {__("more")}
            <i class="ty-sort-dropdown__icon ty-icon-down-micro"></i>
        </a>
        <ul id="elm_2" class="ty-sort-dropdown__content cm-popup-box hidden">
            <li class="ty-sort-dropdown__content-item">
                <a class="ty-sort-dropdown__content-item-a cm-submit cm-ajax" data-ca-dispatch="dispatch[conversations.mass_update.mark_as_read]" data-ca-target-form="conversations_form">
                    {__("mark_as_read")}
                </a>
                <a class="ty-sort-dropdown__content-item-a cm-submit cm-ajax" data-ca-dispatch="dispatch[conversations.mass_update.move_to_spam]" data-ca-target-form="conversations_form">
                    {__("report_spam")}
                </a>
            </li>
        </ul>
    </div>
    {if $cp_allow_start_conv}
        <div class="ty-float-right">
            {if $detailed}
                {if $vendor_info.company.company_id}
                    {$add_param = "&recipient_id=`$vendor_info.company.company_id`"}
                {else}
                    {$add_param = "&admin_message=Y"}
                {/if}
            {/if}
            {if "ULTIMATE"|fn_allowed_for}
                {assign var="cp_conv_comp_id" value=$runtime.company_id}
            {else}
                {assign var="cp_conv_comp_id" value=""}
            {/if}
            <a class="ty-btn ty-btn__primary cp-compose-button cm-dialog-opener cm-ajax cp-enabled" href="{"conversations.new&recipient_id=`$cp_conv_comp_id``$add_param`"|fn_url}" data-ca-dialog-title="{__("new_conversation")}" data-ca-target-id="compose_new_message">
                <i class="cp-icon-edit"></i><span class="button-text">{__("compose")}</span>
            </a>
        </div>
    {/if}
</div>