{if !$smarty.request.extra}
<script type="text/javascript">
(function(_, $) {
    _.tr('text_items_added', '{__("text_items_added")|escape:"javascript"}');
    var display_type = '{$smarty.request.display|escape:javascript nofilter}';

    $.ceEvent('on', 'ce.formpost_features_form', function(frm, elm) {
        var features = {};

        if ($('input.cm-item:checked', frm).length > 0) {
            $('input.cm-item:checked', frm).each( function() {
                var feature_id = $(this).val();
                if (display_type != 'radio') {
                    features[feature_id] = {
                        feature: $('#feature_' + feature_id).text(),
                        path_items: ''
                    };
                    var parent = $(this).closest('.table-tree').parent().prev('.table-tree');
                    while (parent.length > 0) {
                        var path_id = $('.cm-item', parent).first().val();
                        if (path_id) {
                            var path_name = $('#feature_' + path_id).text();
                            features[feature_id]['path_items'] =
                                '<a class="ty-breadcrumbs__a" target="_blank">'+path_name+'</a> / ' +
                                    features[feature_id]['path_items'];
                        }
                        parent = parent.parent().prev('.table-tree');
                    }
                }
                else {
                    features[feature_id] = $('#feature_' + feature_id).text()
                }
            });

            if (display_type != 'radio') {
                {literal}
                $.cePicker('add_js_item', frm.data('caResultId'), features, 'c', {
                    '{feature_id}': '%id',
                    '{feature}': '%item.feature',
                    '{path_items}': '%item.path_items'
                });
                {/literal}
            } else {
                {literal}
                $.cePicker('add_js_item', frm.data('caResultId'), features, 'c', {
                    '{feature_id}': '%id',
                    '{feature}': '%item'
                });
                {/literal}
            }


            if (display_type != 'radio') {
                $.ceNotification('show', {
                    type: 'N', 
                    title: _.tr('notice'), 
                    message: _.tr('text_items_added'), 
                    message_state: 'I'
                });
            }
        }

        return false;
    });
}(Tygh, Tygh.$));
</script>
{/if}
<form action="{$smarty.request.extra|fn_url}" data-ca-result-id="{$smarty.request.data_id}" method="post" name="features_form">
<div class="items-container multi-level">
    {if $features}
       
        {include file="addons/cp_similar_products/views/components/features_simple.tpl" header=true checkbox_name=$smarty.request.checkbox_name feature_id=$feature_id display=$smarty.request.display}
    {else}
        
        <p class="no-items center">
            {__("no_data")}
  
    {/if}
</div>
<div class="buttons-container">
        {assign var="but_close_text" value=__("cp_add_features_and_close")}
        {assign var="but_text" value=__("cp_add_features")}
    {include file="buttons/add_close.tpl" is_js=$smarty.request.extra|fn_is_empty}
</div>
</form>