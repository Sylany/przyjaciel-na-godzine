<?php
/**
 * Lokalizacja: /templates/messages.php
 */

if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$conversations = PNG_Messages::get_conversations($user_id);
$current_conversation = isset($_GET['conversation']) ? sanitize_text_field($_GET['conversation']) : '';
$messages = array();

if ($current_conversation) {
    $messages = PNG_Messages::get_conversation($current_conversation, $user_id);
    PNG_Messages::mark_conversation_read($current_conversation, $user_id);
}
?>

<div class="png-messages-wrapper">
    <div class="png-messages-container">
        <!-- Conversations List -->
        <div class="png-conversations-sidebar">
            <div class="png-sidebar-header">
                <h3><?php _e('Wiadomości', 'png'); ?></h3>
                <span class="png-unread-count"><?php echo PNG_Messages::get_unread_count($user_id); ?></span>
            </div>
            
            <div class="png-search-messages">
                <input type="text" 
                       id="search-conversations" 
                       placeholder="<?php _e('Szukaj...', 'png'); ?>"
                       class="png-form-input">
            </div>
            
            <div class="png-conversations-list">
                <?php if (empty($conversations)): ?>
                <div class="png-no-conversations">
                    <i class="fas fa-comments fa-3x"></i>
                    <p><?php _e('Brak wiadomości', 'png'); ?></p>
                </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                    <a href="?conversation=<?php echo esc_attr($conv->conversation_id); ?>" 
                       class="png-conversation-item <?php echo ($current_conversation === $conv->conversation_id) ? 'active' : ''; ?> <?php echo $conv->unread_count > 0 ? 'unread' : ''; ?>">
                        <img src="<?php echo esc_url($conv->other_user_avatar); ?>" 
                             alt="<?php echo esc_attr($conv->other_user_name); ?>"
                             class="png-conversation-avatar">
                        
                        <div class="png-conversation-info">
                            <h4><?php echo esc_html($conv->other_user_name); ?></h4>
                            <p class="png-last-message"><?php echo esc_html(wp_trim_words($conv->last_message, 10)); ?></p>
                            <span class="png-message-time">
                                <?php echo human_time_diff(strtotime($conv->last_message_time), current_time('timestamp')); ?> <?php _e('temu', 'png'); ?>
                            </span>
                        </div>
                        
                        <?php if ($conv->unread_count > 0): ?>
                        <span class="png-unread-badge"><?php echo $conv->unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="png-messages-area">
            <?php if ($current_conversation && !empty($messages)): ?>
                <?php 
                $other_user_id = $messages[0]->sender_id == $user_id ? $messages[0]->receiver_id : $messages[0]->sender_id;
                $other_user = get_userdata($other_user_id);
                ?>
                
                <!-- Chat Header -->
                <div class="png-chat-header">
                    <div class="png-chat-user">
                        <img src="<?php echo get_avatar_url($other_user_id); ?>" alt="">
                        <div>
                            <h4><?php echo esc_html($other_user->display_name); ?></h4>
                            <span class="png-user-status <?php echo PNG_Users::is_online($other_user_id) ? 'online' : 'offline'; ?>">
                                <?php echo PNG_Users::is_online($other_user_id) ? __('Online', 'png') : __('Offline', 'png'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="png-chat-actions">
                        <button type="button" class="png-icon-button" title="<?php _e('Usuń konwersację', 'png'); ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <!-- Messages List -->
                <div class="png-messages-list" id="png-messages-list">
                    <?php foreach ($messages as $message): ?>
                    <div class="png-message-item <?php echo $message->sender_id == $user_id ? 'sent' : 'received'; ?>" 
                         data-message-id="<?php echo $message->id; ?>">
                        
                        <?php if ($message->sender_id != $user_id): ?>
                        <img src="<?php echo get_avatar_url($message->sender_id); ?>" 
                             alt="" 
                             class="png-message-avatar">
                        <?php endif; ?>
                        
                        <div class="png-message-content">
                            <div class="png-message-bubble">
                                <?php echo nl2br(esc_html($message->message)); ?>
                            </div>
                            <span class="png-message-time">
                                <?php echo date_i18n('d.m.Y H:i', strtotime($message->created_at)); ?>
                            </span>
                        </div>
                        
                        <?php if ($message->sender_id == $user_id): ?>
                        <img src="<?php echo get_avatar_url($message->sender_id); ?>" 
                             alt="" 
                             class="png-message-avatar">
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Message Input -->
                <form class="png-message-form" id="png-send-message-form">
                    <input type="hidden" name="conversation_id" value="<?php echo esc_attr($current_conversation); ?>">
                    <input type="hidden" name="receiver_id" value="<?php echo $other_user_id; ?>">
                    
                    <div class="png-message-input-wrapper">
                        <button type="button" class="png-icon-button">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        
                        <textarea name="message" 
                                  id="message-input" 
                                  placeholder="<?php _e('Napisz wiadomość...', 'png'); ?>"
                                  rows="1"></textarea>
                        
                        <button type="submit" class="png-send-button">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>

            <?php elseif ($current_conversation): ?>
                <div class="png-empty-state">
                    <i class="fas fa-comments fa-3x"></i>
                    <h3><?php _e('Nie można załadować wiadomości', 'png'); ?></h3>
                </div>
            <?php else: ?>
                <div class="png-empty-state">
                    <i class="fas fa-comments fa-3x"></i>
                    <h3><?php _e('Wybierz konwersację', 'png'); ?></h3>
                    <p><?php _e('Wybierz konwersację z listy po lewej stronie, aby zobaczyć wiadomości', 'png'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.png-messages-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.png-messages-container {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 0;
    height: calc(100vh - 200px);
    min-height: 600px;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 16px rgba(0,0,0,0.1);
}

.png-conversations-sidebar {
    border-right: 1px solid #e0e0e0;
    display: flex;
    flex-direction: column;
}

.png-sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.png-sidebar-header h3 {
    margin: 0;
}

.png-unread-count {
    background: #007cba;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.png-search-messages {
    padding: 16px;
    border-bottom: 1px solid #e0e0e0;
}

.png-conversations-list {
    flex: 1;
    overflow-y: auto;
}

.png-conversation-item {
    display: flex;
    gap: 12px;
    padding: 16px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
    color: inherit;
    position: relative;
}

.png-conversation-item:hover {
    background: #f8f8f8;
}

.png-conversation-item.active {
    background: #e3f2fd;
    border-left: 3px solid #007cba;
}

.png-conversation-item.unread {
    background: #f0f8ff;
}

.png-conversation-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}

.png-conversation-info {
    flex: 1;
    min-width: 0;
}

.png-conversation-info h4 {
    margin: 0 0 4px 0;
    font-size: 15px;
}

.png-last-message {
    margin: 0;
    font-size: 13px;
    color: #666;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.png-message-time {
    font-size: 11px;
    color: #999;
}

.png-unread-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    background: #007cba;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

.png-messages-area {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.png-chat-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.png-chat-user {
    display: flex;
    gap: 12px;
    align-items: center;
}

.png-chat-user img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
}

.png-chat-user h4 {
    margin: 0 0 4px 0;
}

.png-user-status {
    font-size: 12px;
    color: #999;
}

.png-user-status.online {
    color: #4caf50;
}

.png-user-status.online::before {
    content: "●";
    margin-right: 4px;
}

.png-messages-list {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f5f5f5;
}

.png-message-item {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    align-items: flex-end;
}

.png-message-item.sent {
    justify-content: flex-end;
}

.png-message-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
}

.png-message-content {
    max-width: 60%;
}

.png-message-bubble {
    padding: 12px 16px;
    border-radius: 18px;
    background: white;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.png-message-item.sent .png-message-bubble {
    background: #007cba;
    color: white;
}

.png-message-time {
    display: block;
    font-size: 11px;
    color: #999;
    margin-top: 4px;
}

.png-message-item.sent .png-message-time {
    text-align: right;
}

.png-message-form {
    padding: 16px 20px;
    border-top: 1px solid #e0e0e0;
    background: white;
}

.png-message-input-wrapper {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}

.png-message-input-wrapper textarea {
    flex: 1;
    border: 2px solid #e0e0e0;
    border-radius: 24px;
    padding: 12px 16px;
    resize: none;
    font-family: inherit;
    max-height: 120px;
}

.png-send-button {
    background: #007cba;
    color: white;
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.2s;
}

.png-send-button:hover {
    background: #005a87;
}

.png-icon-button {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 10px;
}

.png-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #999;
}

.png-empty-state i {
    margin-bottom: 20px;
    opacity: 0.3;
}

.png-no-conversations {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.png-no-conversations i {
    margin-bottom: 16px;
    opacity: 0.3;
}

@media (max-width: 768px) {
    .png-messages-container {
        grid-template-columns: 1fr;
        height: auto;
    }
    
    .png-conversations-sidebar {
        display: none;
    }
    
    .png-messages-area {
        height: calc(100vh - 150px);
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var $messagesList = $('#png-messages-list');
    var $messageForm = $('#png-send-message-form');
    var $messageInput = $('#message-input');
    
    // Scroll to bottom
    function scrollToBottom() {
        if ($messagesList.length) {
            $messagesList.scrollTop($messagesList[0].scrollHeight);
        }
    }
    
    scrollToBottom();
    
    // Auto-resize textarea
    $messageInput.on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Send message
    $messageForm.on('submit', function(e) {
        e.preventDefault();
        
        var message = $messageInput.val().trim();
        if (!message) return;
        
        var data = {
            action: 'png_send_message',
            nonce: pngData.nonce,
            receiver_id: $('input[name="receiver_id"]').val(),
            message: message,
            conversation_id: $('input[name="conversation_id"]').val()
        };
        
        $.ajax({
            url: pngData.ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $messageInput.val('').css('height', 'auto');
                    location.reload(); // Refresh to show new message
                }
            }
        });
    });
    
    // Search conversations
    $('#search-conversations').on('input', function() {
        var search = $(this).val().toLowerCase();
        $('.png-conversation-item').each(function() {
            var name = $(this).find('h4').text().toLowerCase();
            $(this).toggle(name.indexOf(search) > -1);
        });
    });
    
    // Auto-refresh messages every 30 seconds
    if ($messagesList.length) {
        setInterval(function() {
            location.reload();
        }, 30000);
    }
});
</script>