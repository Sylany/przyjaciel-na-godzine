<?php
if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$conversations = PNG_Messages::get_user_conversations($user_id);

$selected_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$messages = $selected_user ? PNG_Messages::get_conversation_messages($user_id, $selected_user) : array();
?>

<div class="png-container">
    <h1><?php _e('Wiadomości', 'przyjaciel-na-godzine'); ?></h1>
    
    <div class="png-messages-layout">
        <!-- Lista konwersacji -->
        <div class="png-conversations-list">
            <h3><?php _e('Konwersacje', 'przyjaciel-na-godzine'); ?></h3>
            
            <?php if (empty($conversations)): ?>
                <p><?php _e('Brak konwersacji', 'przyjaciel-na-godzine'); ?></p>
            <?php else: ?>
                <?php foreach ($conversations as $conv): 
                    $other_user = get_userdata($conv->other_user_id);
                    $other_profile = PNG_Users::get_user_profile($conv->other_user_id);
                ?>
                    <div class="png-conversation-item <?php echo $selected_user == $conv->other_user_id ? 'active' : ''; ?>">
                        <a href="<?php echo add_query_arg('user_id', $conv->other_user_id); ?>">
                            <div class="png-conversation-avatar">
                                <?php echo get_avatar($conv->other_user_id, 50); ?>
                            </div>
                            <div class="png-conversation-info">
                                <strong><?php echo esc_html($other_profile->display_name ?: $other_user->user_login); ?></strong>
                                <span class="png-conversation-date">
                                    <?php echo human_time_diff(strtotime($conv->last_message_date)); ?> temu
                                </span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Okno wiadomości -->
        <div class="png-messages-window">
            <?php if ($selected_user): 
                $other_user = get_userdata($selected_user);
                $other_profile = PNG_Users::get_user_profile($selected_user);
            ?>
                <div class="png-messages-header">
                    <h3><?php echo esc_html($other_profile->display_name ?: $other_user->user_login); ?></h3>
                </div>
                
                <div class="png-messages-container" data-conversation-id="<?php echo $selected_user; ?>">
                    <?php if (empty($messages)): ?>
                        <p class="png-no-messages"><?php _e('Brak wiadomości. Rozpocznij konwersację!', 'przyjaciel-na-godzine'); ?></p>
                    <?php else: ?>
                        <?php foreach (array_reverse($messages) as $message): ?>
                            <div class="png-message <?php echo $message->from_user_id == $user_id ? 'outgoing' : 'incoming'; ?>">
                                <div class="png-message-content">
                                    <p><?php echo esc_html($message->message); ?></p>
                                    <span class="png-message-time">
                                        <?php echo date('H:i', strtotime($message->created_at)); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Formularz wysyłania wiadomości -->
                <div class="png-message-form">
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="png_send_message">
                        <input type="hidden" name="to_user_id" value="<?php echo $selected_user; ?>">
                        <?php wp_nonce_field('png_send_message', 'png_nonce'); ?>
                        
                        <div class="png-form-group">
                            <textarea name="message" class="png-form-control" rows="3" 
                                      placeholder="<?php _e('Napisz wiadomość...', 'przyjaciel-na-godzine'); ?>" required></textarea>
                        </div>
                        
                        <div class="png-form-group">
                            <button type="submit" class="png-btn">
                                <?php _e('Wyślij', 'przyjaciel-na-godzine'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="png-no-conversation">
                    <p><?php _e('Wybierz konwersację z listy po lewej stronie', 'przyjaciel-na-godzine'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.png-messages-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 20px;
    height: 600px;
    border: 1px solid #ddd;
    border-radius: 10px;
    overflow: hidden;
}

.png-conversations-list {
    border-right: 1px solid #ddd;
    overflow-y: auto;
}

.png-conversation-item {
    padding: 15px;
    border-bottom: 1px solid #eee;
    transition: background 0.3s ease;
}

.png-conversation-item:hover,
.png-conversation-item.active {
    background: #f5f5f5;
}

.png-conversation-item a {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: inherit;
}

.png-conversation-avatar {
    margin-right: 10px;
}

.png-conversation-info {
    flex: 1;
}

.png-conversation-date {
    font-size: 12px;
    color: #666;
    display: block;
}

.png-messages-window {
    display: flex;
    flex-direction: column;
}

.png-messages-header {
    padding: 15px;
    border-bottom: 1px solid #ddd;
    background: #f9f9f9;
}

.png-messages-container {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.png-message {
    display: flex;
    margin-bottom: 10px;
}

.png-message.outgoing {
    justify-content: flex-end;
}

.png-message.incoming {
    justify-content: flex-start;
}

.png-message-content {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 18px;
    position: relative;
}

.png-message.outgoing .png-message-content {
    background: #007cba;
    color: white;
    border-bottom-right-radius: 5px;
}

.png-message.incoming .png-message-content {
    background: #f1f1f1;
    color: #333;
    border-bottom-left-radius: 5px;
}

.png-message-time {
    font-size: 11px;
    opacity: 0.7;
    display: block;
    margin-top: 5px;
}

.png-message-form {
    padding: 15px;
    border-top: 1px solid #ddd;
    background: #f9f9f9;
}

.png-no-conversation {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #666;
}
</style>