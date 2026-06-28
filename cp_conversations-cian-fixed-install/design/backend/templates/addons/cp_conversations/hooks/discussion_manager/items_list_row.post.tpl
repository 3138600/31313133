{if $post.user_id}
    {include file="addons/cp_conversations/components/start_conv_with_user.tpl" conv_user_id=$post.user_id}
{/if}