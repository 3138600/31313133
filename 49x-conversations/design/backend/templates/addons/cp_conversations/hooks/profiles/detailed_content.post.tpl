<div class="control-group">
    <label class="control-label">{__("image")}:</label>
    <div class="controls">
        {include file="common/attach_images.tpl" image_name="user_image" image_object_type="user_image" image_pair=$user_data.user_image no_thumbnail=true}
    </div>
</div>
{if "ULTIMATE"|fn_allowed_for && $user_data.user_type == "A"}
    <div class="control-group">
        <label class="control-label">{__("cp_convers_user")}</label>
        <div class="controls">
            <input type="hidden" name="user_data[cp_for_conversation]" value="N" />
            <input type="checkbox" name="user_data[cp_for_conversation]" value="Y" {if $user_data.cp_for_conversation == "Y"}checked="checked"{/if} />
        </div>
    </div>
{/if}