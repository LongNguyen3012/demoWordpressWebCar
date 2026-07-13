window.ChatConfig = window.ChatConfig || {};

if (typeof chatSettings !== 'undefined') {
    Object.assign(window.ChatConfig, chatSettings);
    window.ChatConfig.isAdmin = window.ChatConfig.isAdmin === '1';
    window.ChatConfig.userId = parseInt(window.ChatConfig.userId);
}