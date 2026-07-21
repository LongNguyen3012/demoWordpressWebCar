<?php
get_header();
?>
<div class="chat-page">
    <div class="container">
        <h1><?php _te('nav_chat', 'Live Chat'); ?></h1>
        <div id="chat-app">
            <div style="display:flex; height:500px; border:1px solid #ddd; border-radius:4px; overflow:hidden;">
                <div id="room-list" style="width:200px; border-right:1px solid #ddd; padding:10px; overflow-y:auto; background:#f5f5f5;">
                    <h3 style="margin-top:0;">Rooms</h3>
                    <div style="display:flex; gap:5px; margin-bottom:10px;">
                        <button id="new-room-btn" style="padding:4px 12px; flex:1;">+ New Group</button>
                        <button id="new-direct-btn" style="padding:4px 12px; flex:1;" onclick="openDirectModal();">+ Direct</button>
                    </div>
                    <ul id="rooms" style="list-style:none; padding:0; margin:0;">
                    </ul>
                </div>
                <div style="flex:1; display:flex; flex-direction:column;">
                    <div id="room-header" style="padding:10px 15px; background:#fff; border-bottom:2px solid #ddd; display:flex; justify-content:space-between; align-items:center;">
                        <span id="room-title" style="font-weight:bold; font-size:1.1rem;">Select a room</span>
                        <div id="room-header-actions" style="display:flex; gap:8px; align-items:center;">
                            <input type="text" id="search-messages" placeholder="Search messages..." style="padding:4px 8px; border:1px solid #ccc; border-radius:4px; font-size:0.9rem; display:none; width:150px;">
                            <button id="search-toggle" style="background:none; border:none; cursor:pointer; font-size:1.2rem;">🔍</button>
                        </div>
                    </div>
                    <div id="chat-messages" style="flex:1; overflow-y:auto; padding:10px; background:#f9f9f9; min-height:350px;"></div>
                    <div style="display:flex; padding:10px; border-top:1px solid #ddd; background:#fff; align-items:center;">
                        <button id="file-picker" style="padding:8px 12px; margin-right:5px; background:#f0f0f0; border:1px solid #ccc; border-radius:4px; cursor:pointer;">📎</button>
                        <input type="file" id="file-input" style="display:none;">
                        <input type="text" id="message-input" style="flex:1; padding:8px; border:1px solid #ccc; border-radius:4px;" placeholder="<?php _te('chat_type_message', 'Type a message...'); ?>">
                        <button id="send-btn" style="padding:8px 20px; margin-left:10px; background:#2C2C2C; color:#fff; border:none; border-radius:4px; cursor:pointer;"><?php _te('chat_send', 'Send'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer(); ?>