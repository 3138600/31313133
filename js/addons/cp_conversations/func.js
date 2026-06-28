(function(_, $){
    $.ceEvent('on', 'ce.commoninit', function(context) {
        $('[data-unread-messages]', context).each(function() {
            var menuItem = $(this);
            var messages = parseInt(menuItem.attr('data-unread-messages'));
            if(messages > 0) {
                var parentItem = menuItem.parents('li.dropdown').find('a.dropdown-toggle');
                if(parentItem.find('b.caret')) {
                    var addCaret = true;
                }
                var text = parentItem.text();
                parentItem.text('');
                parentItem.append(text + '(<strong>' + messages + '</strong>)');
                if(addCaret) {
                    parentItem.append('<b class="caret"></b>');
                }
            }
        });
        $('.cp-recipient-selector', context).keyup(function() {
            var q = $(this).val();
            var day = new Date;
            currentTime = day.getTime();
            setTimeout(function() {
                day = new Date;
                var now = day.getTime();
                differ = now - currentTime;
                if (differ >= 500) {
                    $.ceAjax('request', fn_url('conversations.new'), {
                        data: {
                            q: q
                        }, 
                        method: 'get', 
                        result_ids: 'recipient_reload,conversations_reload'
                    });
                }
            }, 500);
        });
        $('.cp-conversation-selector', context).change(function() {
            var conversationId = $(this).val();
            var cur_comp_id = $('#compose_new_message input[name="cur_company_id"]').val();//CP Tier
            var recipientId = $(this).attr('data-recipient-id');
            $.ceAjax('request', fn_url('conversations.new'), {
                data: {
                    conversation_id: conversationId,
                    recipient_id: recipientId,
                    cur_comp_id: cur_comp_id,
                }, 
                method: 'get', 
                result_ids: 'conversations_reload'
            });
        });
        $('#message_container', context).keydown(function(e) {
            var elm = $(this);
            if(e.keyCode == 13 && !e.shiftKey) {
                e.preventDefault();
                if(!elm.val().isEmpty()) {
                    elm.parents('#message_field_reload').find('input[type="submit"]').click();
                }
            }
        });
        if($('#conversation_holder_reload', context).length > 0) {
            $('#conversation_holder_reload', context).slimScroll({
                height: '500px',
                wheelStep : 10,
                start: 'bottom' 
            });
            // $('#conversation_holder_reload', context).scrollbar();
            // $('#conversations_holder_reload', context).scrollTop($('#conversations_holder_reload')[0].scrollHeight);
        }
        $('.cp-conversations__top-panel.cp-disabled input[type="checkbox"], .cp-conversations__table input[type="checkbox"]').change(function() {
            var checked = false;
            $('.cp-conversations__table input[type="checkbox"]').each(function() {
                if($(this).prop('checked')) {
                    checked = true;
                }
            })
            if(checked) {
                $('.cp-conversations__top-panel').removeClass('cp-disabled');
            } else {
                $('.cp-conversations__top-panel').addClass('cp-disabled');
                $('.cp-conversations__top-panel input[type="checkbox"]').prop('checked', false);
            }
        });
        $('a').click(function() {
            if(!$(this).hasClass('cp-enabled') && $(this).parents('.cp-disabled').length > 0) {
                return false;
            }
        });
        $('.cp-folder-selector', context).change(function() {
            if(this.value) {
                window.location.href = this.value;
            }
        });
        $('.cp-external-click', context).click(function(e) {
            if(e && e.target && !$(e.target).is('input') && !$(e.target).is('a') && $(e.target).parents('.hidden-tools').length == 0) {
                var target = $(this).data('caExternalClickId');
                if(target && $('#' + target).length > 0) {
                    var href= $('#' + target).attr('href');
                    if(href) {
                        window.location.href = href;
                    }
                }
            }
        })
    });
})(Tygh, Tygh.$);

function loadMoreMessages(conversationId, start) {
    $.ceAjax('request', fn_url('conversations.update'), {
        data: {
            conversation_id: conversationId,
            start: start
        },
        result_ids: 'conversation_holder_reload',
        pre_processing: function(a, b, c) {
            if(a.html['conversation_holder_reload']) {
                var ans = a.html['conversation_holder_reload'];
                $('#conversation_holder_reload .cp-messages-holder__load_more').remove();
                $('#conversation_holder_reload').prepend(ans);
            }
            a.html = false;
        }
    });
}

function addFileUploader() {
    var html = "\
        <div class=\"cp-conversation-messages__file-input-item hidden\">\
            <input type=\"file\" name=\"message_files[]\" onchange=\"toggleFileInput(this);\" class=\"hidden\" accept=\".gif,.jpg,.jpeg,.png,.pdf\">\
        </div>\
    ";
    $('.cp-conversation-messages__file-inputs-wrap').append(html);
    var input = $('.cp-conversation-messages__file-inputs-wrap input').last();
    if(input) {
        input.click();
    }
}

function toggleFileInput(elm) {
    var allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf', 'gif'];
    var filename = elm.value;
    var filename = filename.replace(/^.*[\\\/]/, '');
    if(allowedExtensions.indexOf(filename.getExtension())  < 0) {
        filename = '';
    }
    if(filename == '') {
        //remove file
        $(elm).parents('.cp-conversation-messages__file-input-item').remove();
    }  else {
        //add new file
        var wrapper = $(elm).parents('.cp-conversation-messages__file-input-item');
        wrapper.append("\
            <div class=\"cp-conversation-messages__file-name\">\
                <input type=\"hidden\" name=\"message_files[]\" value=" + filename + ">\
                <input type=\"hidden\" name=\"message_files[]\" value=\"local\">\
                <div class=\"cp-conversation-messages__file-text\">" + filename +"</div>\
                <a class=\"cp-conversation-messages__file-remove\" onclick=\"removeFileInput(this);\">&#10006;</a>\
            </div>\
        ").removeClass('hidden');
    }
    if($('.cp-conversation-messages__file-inputs-wrap .cp-conversation-messages__file-input-item').length > 0) {
        $('.cp-conversation-messages__file-inputs-wrap').addClass('files');
    } else {
        $('.cp-conversation-messages__file-inputs-wrap').removeClass('files');
    }
}
 
function removeFileInput(elm) {
    var input = $(elm).parents('.cp-conversation-messages__file-input-item').find('input').get(0);
    input.value = '';
    toggleFileInput(input);
}

String.prototype.isEmpty = function() {
    return (this.length === 0 || !this.trim());
};
String.prototype.getExtension = function() {
    var ext = this.split('.').pop();
    return ext.toLowerCase();
};