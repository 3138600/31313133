{* Форма для инициации диалога CIAN *}
<form action="{""|fn_url}" method="post" name="start_cian_chat_form" class="form-horizontal form-edit">
    
    <div class="control-group">
        <label class="control-label cm-required" for="cian_chat_id">ID чата CIAN (Chat ID):</label>
        <div class="controls">
            <input type="text" name="cian_chat_id" id="cian_chat_id" value="" class="input-large" size="50" placeholder="Например: 12345678 или offer_123_456" />
            <p class="muted description">Введите ID чата, который можно получить из панели CIAN.</p>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required" for="cian_message">Сообщение:</label>
        <div class="controls">
            <textarea name="cian_message" id="cian_message" class="input-large" rows="5" cols="50" placeholder="Введите ваше сообщение..."></textarea>
        </div>
    </div>

    <div class="buttons-container">
        {include file="buttons/save_cancel.tpl" but_text="Отправить сообщение" but_name="dispatch[conversations.start_cian_chat]" cancel_action="close"}
    </div>
    
</form>