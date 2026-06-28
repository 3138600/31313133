{if $cp_allow_start_conv}
    <div class="ty-float-right">
    {assign var="return_current_url" value=$config.current_url|escape:url}
    <a class="ty-btn ty-btn__primary btn-compose cp-compose-button cm-dialog-opener cm-ajax" href="{"conversations.new&recipient_id=`$company_data.company_id`&vendor_id=`$company_data.company_id`?return_url=`$return_current_url`"|fn_url}" data-ca-dialog-title="{__("new_conversation")}" data-ca-target-id="compose_new_message">
        <i class="cp-icon-edit"></i><span>{__("contact_vendor")}</span>
    </a>
    </div>
{/if}