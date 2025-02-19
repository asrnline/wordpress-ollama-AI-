<?php
/*
Plugin Name: AI Chat Interface
Description: A ChatGPT-style chat interface using Ollama
Version: 1.0
Author: Your Name
*/

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. 菜单页面相关
 */
// 添加菜单页面
function ai_chat_add_menu_page() {
    add_menu_page(
        'AI Chat',
        'AI Chat',
        'read',  // 允许所有登录用户访问
        'inoo-panel',
        'ai_chat_render_page',
        'dashicons-format-chat'
    );
}
add_action('admin_menu', 'ai_chat_add_menu_page');

// 处理直接URL访问
function ai_chat_handle_direct_access() {
    global $wp;
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/inoo-panel') !== false) {
        if (!is_user_logged_in()) {
            auth_redirect();
            exit;
        }
        ai_chat_render_page();
        exit;
    }
}
add_action('init', 'ai_chat_handle_direct_access');

// 注册必要的脚本
function ai_chat_enqueue_scripts($hook) {
    if ('toplevel_page_inoo-panel' === $hook || isset($_GET['page']) && $_GET['page'] === 'inoo-panel') {
        wp_enqueue_script('jquery');
    }
}
add_action('admin_enqueue_scripts', 'ai_chat_enqueue_scripts');

/**
 * 2. 页面渲染函数
 */
function ai_chat_render_page() {
    $is_admin = current_user_can('manage_options');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>AI Chat Panel</title>
        
        <!-- 添加网站图标 -->
        <link rel="icon" href="<?php echo get_site_icon_url(); ?>" sizes="32x32" />
        <link rel="icon" href="<?php echo get_site_icon_url(64); ?>" sizes="64x64" />
        <link rel="apple-touch-icon" href="<?php echo get_site_icon_url(180); ?>" />
        <meta name="msapplication-TileImage" content="<?php echo get_site_icon_url(270); ?>" />
        
        <!-- 添加 MathJax 支持 -->
        <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
        <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
        <script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/input/mhchem.js"></script>
        
        <!-- 修改 Prism.js 加载顺序 -->
        <link href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
        <!-- 首先加载 Prism 核心 -->
        <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-core.min.js"></script>
        <!-- 然后加载自动加载器 -->
        <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
        <!-- 加载基础语言和依赖 -->
        <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-markup.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-css.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-clike.min.js"></script>
        <!-- 加载 markup-templating，这是 PHP 的依赖 -->
        <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-markup-templating.min.js"></script>
        <!-- 加载其他语言 -->
        <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-javascript.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-java.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-python.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-php.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-sql.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-bash.min.js"></script>
        <script>
            // 配置 Prism autoloader
            Prism.plugins.autoloader.languages_path = 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/';
            
            // 确保所有语言都已加载完成
            document.addEventListener('DOMContentLoaded', function() {
                Prism.highlightAll();
            });
        </script>
        <style>
            /* 在最开始添加这些样式 */
            #backtotop,
            .backtotop,
            .back-to-top,
            #back-to-top,
            [class*="backtotop"],
            [id*="backtotop"],
            [class*="back-to-top"],
            [id*="back-to-top"] {
                display: none !important;
            }
            
            /* 确保我们的面板容器样式正确 */
            .panel-container {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 99999;
                background: #ffffff;
                width: 100vw;
                height: 100vh;
                overflow: hidden;
            }
            body {
                margin: 0;
                padding: 0;
                font-family: "Söhne", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Ubuntu, Cantarell, "Noto Sans", sans-serif;
                background: #ffffff;
                overflow: hidden;
            }
            .panel-container {
                display: flex;
                height: 100vh;
                width: 100vw;
            }
            .sidebar {
                width: 260px;
                min-width: 260px;
                background: #ffffff;
                display: flex;
                flex-direction: column;
                transition: transform 0.3s ease;
                position: relative;
                z-index: 999;
                border-right: 1px solid rgba(0,0,0,0.1);
            }
            .new-chat-btn {
                margin: 8px;
                padding: 12px 16px;
                border: 1px solid rgba(0,0,0,0.1);
                border-radius: 6px;
                color: #333333;
                background: transparent;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 14px;
                transition: all 0.2s;
            }
            .new-chat-btn:hover {
                background: rgba(0,0,0,0.05);
                border-color: rgba(0,0,0,0.2);
            }
            .chat-history {
                flex: 1;
                padding: 8px;
                overflow-y: auto;
            }
            .history-item {
                padding: 12px 14px;
                margin: 2px 6px;
                border-radius: 6px;
                cursor: pointer;
                color: #333333;
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 14px;
                transition: background 0.2s;
                position: relative;
            }
            .history-item:hover {
                background: rgba(0,0,0,0.05);
            }
            .history-item.active {
                background: rgba(0,0,0,0.05);
                border-left: 3px solid #1a8cff;
                color: #1a8cff;
            }
            .history-item svg {
                color: #666666;
            }
            .history-item.active svg {
                color: #1a8cff;
            }
            .history-item .delete-btn {
                position: absolute;
                right: 8px;
                display: none;  /* 默认隐藏删除按钮 */
                color: #666;
                padding: 4px;
                border-radius: 4px;
                transition: all 0.2s;
                background: none;
                border: none;
                cursor: pointer;
            }
            .history-item.active .delete-btn {
                display: block;  /* 选中时显示删除按钮 */
            }
            .history-item .delete-btn:hover {
                background: rgba(0,0,0,0.1);
                color: #ff4d4f;
            }
            .history-item .history-text {
                flex: 1;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                margin-right: 30px; /* 为删除按钮预留空间 */
            }
            .main-content {
                flex: 1;
                display: flex;
                flex-direction: column;
                position: relative;
                background: #ffffff;
                margin-left: 0;
                transition: margin-left 0.3s ease;
                width: calc(100vw - 260px);
                overflow: hidden;
            }
            .main-content.collapsed {
                margin-left: -260px;
            }
            .chat-messages {
                flex: 1;
                overflow-y: auto;
                padding-bottom: 120px;
                width: 100%;
                height: calc(100vh - 120px);
            }
            .message-wrapper {
                border-bottom: none;
                margin: 0;
                padding: 0;
            }
            .user-message {
                background: #ffffff;
                padding: 8px 0;
            }
            .ai-message {
                background: #ffffff;
                padding: 8px 0;
            }
            .message {
                max-width: 800px;
                margin: 0 auto;
                padding: 16px 24px;
                display: flex;
                gap: 12px; /* 减小间距 */
                position: relative;
                width: 100%;
                align-items: flex-start;
            }
            .user-message .message {
                flex-direction: row-reverse;
            }
            .message-avatar {
                width: 30px;
                height: 30px;
                border-radius: 2px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                flex-shrink: 0;
                margin: 0; /* 移除所有外边距 */
            }
            .user-message .message-avatar {
                background: #1a8cff;
                color: white;
                /* 移除 margin-left: 10px; */
            }
            .ai-message .message-avatar {
                background: #19c37d;
                color: white;
                /* 移除 margin-right: 10px; */
            }
            .message-content {
                flex: 1;
                line-height: 1.6;
                font-size: 15px;
                color: #343541;
                max-width: 100%;
                word-wrap: break-word;
                padding: 0;
                min-height: 30px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .message-content p {
                margin: 0 0 8px 0;
            }
            .message-content p:last-child {
                margin-bottom: 0;
            }
            .ai-message .message-content {
                font-size: 15px;
                line-height: 1.6;
                color: #343541;
            }
            .user-message .message-content {
                text-align: right;
                font-size: 15px;
                line-height: 1.6;
            }
            .ai-reasoning .message-content {
                font-size: 14px;
                color: #666666;
                font-style: italic;
                line-height: 1.6;
                padding: 4px 0;
            }
            .user-message .message-delete-btn {
                left: 10px;
                right: auto;
            }
            .ai-message .message-delete-btn {
                right: 10px;
                left: auto;
            }
            .input-area {
                position: fixed;
                bottom: 0;
                left: 260px;
                right: 0;
                background: linear-gradient(180deg, rgba(255,255,255,0) 0%, #ffffff 50%);
                padding: 24px 0 24px;
                transition: left 0.3s ease;
                z-index: 100;
            }
            .input-container {
                max-width: 800px;
                margin: 0 auto;
                position: relative;
                padding: 0 32px;
            }
            #aiChatInput {
                width: 100%;
                min-height: 24px;
                max-height: 200px;
                padding: 14px 45px 14px 14px;
                border: 1px solid rgba(0,0,0,0.1);
                border-radius: 6px;
                resize: none;
                font-family: inherit;
                font-size: 16px;
                line-height: 1.5;
                box-shadow: 0 0 10px rgba(0,0,0,0.05);
                background: #ffffff;
                box-sizing: border-box;
            }
            #aiChatInput:focus {
                outline: none;
                border-color: #1a8cff;
                box-shadow: 0 0 0 2px rgba(26,140,255,0.2);
            }
            .send-button {
                position: absolute;
                right: 40px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                cursor: pointer;
                padding: 8px;
                color: #1a8cff;
                opacity: 0.8;
                transition: opacity 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 2;
            }
            .send-button:hover {
                opacity: 1;
            }
            .send-button:disabled {
                opacity: 0.4;
                cursor: not-allowed;
            }
            .send-button svg {
                width: 16px;
                height: 16px;
            }
            .sidebar-footer {
                padding: 12px 16px;
                border-top: 1px solid rgba(0,0,0,0.1);
                background: #ffffff;
            }
            .user-info {
                display: flex;
                align-items: center;
                gap: 12px;
                color: #333333;
                font-size: 14px;
                padding: 8px 12px;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s;
                position: relative;
            }
            .user-info:hover {
                background: rgba(0,0,0,0.05);
            }
            .user-info svg {
                color: #666666;
                width: 20px;
                height: 20px;
            }
            /* 添加用户菜单样式 */
            .user-menu {
                position: absolute;
                bottom: 100%;
                left: 0;
                right: 0;
                background: #ffffff;
                border: 1px solid rgba(0,0,0,0.1);
                border-radius: 6px;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                display: none;
                z-index: 1000;
            }
            .user-menu.show {
                display: block;
            }
            .user-menu-item {
                padding: 8px 12px;
                display: flex;
                align-items: center;
                gap: 8px;
                color: #333333;
                transition: all 0.2s;
                cursor: pointer;
                font-size: 14px;
            }
            .user-menu-item:hover {
                background: rgba(0,0,0,0.05);
            }
            .user-menu-item svg {
                width: 16px;
                height: 16px;
                color: #666666;
            }
            .user-menu-item.danger {
                color: #ff4d4f;
            }
            .user-menu-item.danger svg {
                color: #ff4d4f;
            }
            /* 自定义滚动条样式 */
            .chat-history::-webkit-scrollbar {
                width: 6px;
            }
            .chat-history::-webkit-scrollbar-track {
                background: transparent;
            }
            .chat-history::-webkit-scrollbar-thumb {
                background: rgba(0,0,0,0.1);
                border-radius: 3px;
            }
            .chat-history::-webkit-scrollbar-thumb:hover {
                background: rgba(0,0,0,0.2);
            }
            .toggle-sidebar {
                position: fixed;
                left: 260px;
                top: 16px;
                width: 36px;
                height: 36px;
                background: #ffffff;
                border: 1px solid rgba(0,0,0,0.1);
                border-radius: 6px;
                color: #666666;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                z-index: 1000;
                box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            }
            .toggle-sidebar:hover {
                background: rgba(0,0,0,0.05);
                border-color: rgba(0,0,0,0.2);
            }
            .toggle-sidebar.collapsed {
                left: 16px;
                transform: rotate(180deg);
            }
            .sidebar.collapsed {
                transform: translateX(-260px);
            }
            .input-area.collapsed {
                left: 0;
            }
            .main-content {
                flex: 1;
                display: flex;
                flex-direction: column;
                position: relative;
                background: #ffffff;
                margin-left: 0;
                transition: margin-left 0.3s ease;
                width: calc(100vw - 260px);
                overflow: hidden;
            }
            .main-content.collapsed {
                margin-left: -260px;
            }
            @media (max-width: 768px) {
                .sidebar {
                    position: fixed;
                    height: 100vh;
                    z-index: 1001;
                    background: #ffffff;
                    box-shadow: 2px 0 8px rgba(0,0,0,0.1);
                    transform: translateX(-100%);
                    transition: transform 0.3s ease;
                }

                .sidebar:not(.collapsed) {
                    transform: translateX(0);
                }
                
                .toggle-sidebar {
                    background: #ffffff;
                    left: 16px;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                    z-index: 1002;
                }
                
                .sidebar:not(.collapsed) + .toggle-sidebar {
                    left: 276px;
                }
                
                .main-content {
                    width: 100vw;
                    margin-left: 0 !important;
                }
                
                .input-area {
                    left: 0;
                    width: 100%;
                    padding: 12px 0;
                }

                .input-container {
                    padding: 0 16px;
                }
                
                .chat-messages {
                    width: 100vw;
                    padding-bottom: 100px;
                }

                .message {
                    padding: 16px;
                }

                .message-content {
                    font-size: 14px;
                }

                .settings-content {
                    width: 95%;
                    max-height: 90vh;
                    margin: 20px auto;
                }

                .model-item {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 8px;
                }

                .model-actions {
                    width: 100%;
                    display: flex;
                    gap: 8px;
                }

                .model-button {
                    flex: 1;
                }

                .user-menu {
                    position: fixed;
                    bottom: auto;
                    top: 100%;
                    left: 0;
                    right: 0;
                    border-radius: 0 0 6px 6px;
                }
            }
            /* 添加消息删除按钮的样式 */
            .message {
                position: relative;
            }
            .message-delete-btn {
                position: absolute;
                right: 10px;
                top: 10px;
                display: none;
                background: none;
                border: none;
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
                color: #666;
                transition: all 0.2s;
                z-index: 10;
            }
            .message-wrapper:hover .message-delete-btn {
                display: block;
            }
            .message-delete-btn:hover {
                background: rgba(0,0,0,0.1);
                color: #ff4d4f;
            }
            /* 设置对话框样式 */
            .settings-dialog {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 100000;
            }

            .settings-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: #ffffff;
                padding: 24px;
                border-radius: 8px;
                width: 90%;
                max-width: 600px;
                max-height: 80vh;
                overflow-y: auto;
            }

            .settings-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .settings-title {
                font-size: 18px;
                font-weight: bold;
                color: #333;
            }

            .close-settings {
                background: none;
                border: none;
                cursor: pointer;
                padding: 8px;
                color: #666;
            }

            .settings-section {
                margin-bottom: 24px;
            }

            .settings-section-title {
                font-size: 16px;
                font-weight: 600;
                margin-bottom: 12px;
                color: #333;
            }

            .model-list {
                border: 1px solid #e0e0e0;
                border-radius: 6px;
                overflow: hidden;
            }

            .model-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px;
                border-bottom: 1px solid #e0e0e0;
            }

            .model-item:last-child {
                border-bottom: none;
            }

            .model-info {
                flex: 1;
            }

            .model-name {
                font-weight: 500;
                margin-bottom: 4px;
            }

            .model-status {
                font-size: 12px;
                color: #666;
            }

            .model-actions {
                display: flex;
                gap: 8px;
            }

            .model-button {
                padding: 6px 12px;
                border-radius: 4px;
                border: 1px solid #e0e0e0;
                background: #fff;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.2s;
            }

            .model-button:hover {
                background: #f5f5f5;
            }

            .model-button.primary {
                background: #1a8cff;
                color: #fff;
                border-color: #1a8cff;
            }

            .model-button.primary:hover {
                background: #0066cc;
            }

            .model-button.danger {
                color: #ff4d4f;
                border-color: #ff4d4f;
            }

            .model-button.danger:hover {
                background: #fff1f0;
            }

            .loading-indicator {
                display: none;
                text-align: center;
                padding: 20px;
                color: #666;
            }

            .loading-indicator.show {
                display: block;
            }

            /* 添加特殊块的样式 */
            .special-block {
                background: #f8f9fa;
                border-left: 4px solid;
                padding: 12px 16px;
                margin: 12px 0;
                border-radius: 4px;
                position: relative;
            }

            .special-block::before {
                position: absolute;
                top: 8px;
                right: 8px;
                font-size: 12px;
                color: #666;
                padding: 2px 6px;
                border-radius: 3px;
                background: rgba(255,255,255,0.8);
            }

            /* 数学块 */
            .math-block {
                background: #f8f9fa;
                border-left: 4px solid #1a8cff;
                padding: 20px;
                margin: 15px 0;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }

            .math-title {
                font-size: 16px;
                font-weight: 600;
                color: #1a8cff;
                margin-bottom: 15px;
            }

            .formula-content {
                font-size: 18px;
                line-height: 2;
                overflow-x: auto;
                padding: 10px 0;
            }

            .math-result {
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px dashed rgba(26,140,255,0.2);
            }

            .result-label {
                font-size: 14px;
                color: #666;
                margin-bottom: 10px;
            }

            /* 优化 MathJax 渲染效果 */
            .MathJax {
                font-size: 120% !important;
            }

            .MathJax_Display {
                margin: 0.5em 0 !important;
            }

            /* 化学块 */
            .chemistry-block {
                border-left-color: #52c41a;
                background: #f6ffed;
                padding: 16px;
                margin: 12px 0;
                overflow-x: auto;
            }
            .chemistry-block .chemistry-title {
                font-weight: 600;
                margin-bottom: 8px;
                color: #52c41a;
            }
            .chemistry-block .chemistry-result {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed rgba(82,196,26,0.2);
            }

            /* 物理块 */
            .physics-block {
                border-left-color: #722ed1;
                background: #f9f0ff;
                padding: 16px;
                margin: 12px 0;
                overflow-x: auto;
            }
            .physics-block .physics-title {
                font-weight: 600;
                margin-bottom: 8px;
                color: #722ed1;
            }
            .physics-block .physics-result {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed rgba(114,46,209,0.2);
            }

            /* 天文块 */
            .astronomy-block {
                border-left-color: #eb2f96;
                background: #fff0f6;
                padding: 16px;
                margin: 12px 0;
                overflow-x: auto;
            }
            .astronomy-block .astronomy-title {
                font-weight: 600;
                margin-bottom: 8px;
                color: #eb2f96;
            }
            .astronomy-block .astronomy-result {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed rgba(235,47,150,0.2);
            }

            /* 表格块 */
            .table-block {
                border-left-color: #fa8c16;
                background: #fff7e6;
                overflow-x: auto;
            }
            .table-block::before {
                content: "表格";
                color: #fa8c16;
                background: rgba(250,140,22,0.1);
            }

            /* 表格样式 */
            .message-content table {
                border-collapse: collapse;
                width: 100%;
                margin: 8px 0;
                font-size: 14px;
                text-align: left;
            }

            .message-content table th {
                background: #fafafa;
                font-weight: 600;
                color: #333;
            }

            .message-content table td,
            .message-content table th {
                padding: 8px 12px;
                border: 1px solid #e8e8e8;
            }

            .message-content table tr:nth-child(even) {
                background: #fafafa;
            }

            .message-content table tr:hover {
                background: #f5f5f5;
            }

            /* 代码块语言标识 */
            .code-language {
                position: absolute;
                top: 4px;
                right: 4px;
                padding: 2px 6px;
                font-size: 12px;
                color: #999;
                background: rgba(40, 44, 52, 0.8);
                border-radius: 4px;
                user-select: none;
                z-index: 2;
            }
            /* 语法高亮样式 - 基于 One Dark 主题 */
            .token.comment,
            .token.prolog,
            .token.doctype,
            .token.cdata {
                color: #5c6370;
                font-style: italic;
            }

            .token.function {
                color: #61afef;
            }

            .token.keyword {
                color: #c678dd;
            }

            .token.string {
                color: #98c379;
            }

            .token.number {
                color: #d19a66;
            }

            .token.boolean,
            .token.constant {
                color: #56b6c2;
            }

            .token.operator {
                color: #56b6c2;
            }

            .token.punctuation {
                color: #abb2bf;
            }

            .token.class-name {
                color: #e5c07b;
            }

            .token.property {
                color: #e06c75;
            }

            .token.variable {
                color: #e06c75;
            }

            .token.regex {
                color: #c678dd;
            }

            .token.important {
                color: #e06c75;
                font-weight: bold;
            }

            .token.tag {
                color: #e06c75;
            }

            .token.attr-name {
                color: #d19a66;
            }

            .token.attr-value {
                color: #98c379;
            }

            .message-content {
                flex: 1;
                line-height: 1.6;
                font-size: 15px;
                color: #343541;
                max-width: 100%;
                word-wrap: break-word;
                padding: 0;
                min-height: 30px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            .message-content p {
                margin: 0 0 8px 0;
            }

            .message-content p:last-child {
                margin-bottom: 0;
            }

            .message-content pre {
                margin: 8px 0;
                padding: 0;
                background: none;
                position: relative;
                border-radius: 8px;
                overflow: hidden;
            }

            .message-content pre code {
                display: block;
                padding: 16px;
                background: #282c34;
                color: #abb2bf;
                font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, Courier, monospace;
                font-size: 14px;
                line-height: 1.6;
                overflow-x: auto;
                white-space: pre !important;
                tab-size: 4;
                -moz-tab-size: 4;
                -o-tab-size: 4;
                border-radius: 8px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            }

            /* 确保代码内容保持原始格式 */
            .message-content pre code * {
                white-space: pre !important;
                font-family: inherit !important;
            }

            /* 修复代码换行和空格显示 */
            .message-content pre code {
                white-space: pre !important;
                word-wrap: normal !important;
                word-break: keep-all !important;
                overflow-wrap: normal !important;
                -webkit-font-smoothing: auto;
                -moz-osx-font-smoothing: auto;
            }

            /* 优化代码块滚动条 */
            .message-content pre code::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }

            .message-content pre code::-webkit-scrollbar-track {
                background: rgba(0, 0, 0, 0.1);
                border-radius: 3px;
            }

            .message-content pre code::-webkit-scrollbar-thumb {
                background: rgba(255, 255, 255, 0.2);
                border-radius: 3px;
            }

            .message-content pre code::-webkit-scrollbar-thumb:hover {
                background: rgba(255, 255, 255, 0.3);
            }

            .copy-button {
                position: absolute;
                top: 8px;
                right: 70px;
                padding: 2px 8px;
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 4px;
                font-size: 12px;
                color: #abb2bf;
                cursor: pointer;
                display: none;
                transition: all 0.2s;
                z-index: 2;
                font-family: system-ui, -apple-system, sans-serif;
            }

            .copy-button:hover {
                background: rgba(255, 255, 255, 0.2);
                border-color: rgba(255, 255, 255, 0.3);
                color: #fff;
            }

            .message-content pre:hover .copy-button {
                display: block;
            }

            .copy-button.copied {
                background: #98c379;
                color: #282c34;
                border-color: #98c379;
            }

            .ai-message .message-content {
                font-size: 15px;
                line-height: 1.6;
                color: #343541;
            }

            .user-message .message-content {
                text-align: right;
                font-size: 15px;
                line-height: 1.6;
            }

            .ai-reasoning .message-content {
                font-size: 14px;
                color: #666666;
                font-style: italic;
                line-height: 1.6;
                padding: 4px 0;
            }

            /* 添加推理过程的样式 */
            .ai-reasoning {
                background: #f8f9fa;
                border-left: 4px solid #1a8cff;
                margin: 8px 0;
            }

            .ai-reasoning .message-content {
                font-size: 14px;
                color: #666666;
                font-style: italic;
                line-height: 1.6;
                padding: 4px 12px;
            }

            /* 添加新的样式 */
            .reasoning-section {
                margin: 0;  /* 修改这行 */
                padding: 12px 0;  /* 修改这行 */
                font-size: 14px;
                color: #666666;
                font-style: italic;
                line-height: 1.6;
                position: relative;
                transition: all 0.3s ease;
                overflow: hidden;
                border: none;
                background: none;
                width: 100%;  /* 添加这行 */
            }

            .reasoning-section.collapsed {
                max-height: 28px;
            }

            .reasoning-section:not(.collapsed) {
                max-height: none; /* 修改这里，移除固定高度限制 */
            }

            .reasoning-section .content-wrapper {
                padding: 0;
                margin: 0;  /* 修改这行 */
                width: 100%;  /* 添加这行 */
            }

            .reasoning-section .toggle-reasoning {
                position: absolute;
                right: 0;
                bottom: 0; /* 修改按钮位置到底部 */
                background: none;
                border: none;
                padding: 4px 8px;
                color: #666666;
                font-size: 12px;
                cursor: pointer;
                user-select: none;
            }

            .reasoning-section .toggle-reasoning::after {
                content: '收起推理过程';
            }

            .reasoning-section.collapsed .toggle-reasoning::after {
                content: '展开推理过程';
            }

            .reasoning-section .toggle-reasoning:hover {
                color: #333333;
            }

            /* 移除SVG图标 */
            .reasoning-section .toggle-reasoning svg {
                display: none;
            }

            .answer-section {
                padding: 0;  /* 修改这行 */
                font-size: 15px;
                line-height: 1.6;
                color: #343541;
                margin-top: 8px;
                width: 100%;  /* 添加这行 */
            }

            /* 添加清理记录按钮样式 */
            .clear-history-btn {
                width: 100%;
                padding: 8px 12px;
                background: #fff1f0;
                border: 1px solid #ffa39e;
                color: #ff4d4f;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                margin-top: 16px;
                transition: all 0.2s;
            }

            .clear-history-btn:hover {
                background: #fff0f0;
                border-color: #ff4d4f;
            }

            .clear-history-btn svg {
                width: 16px;
                height: 16px;
            }

            /* 添加科学计算块 */
            .calc-block {
                border-left-color: #1890ff;
                background: #e6f7ff;
                padding: 16px;
                margin: 12px 0;
            }
            .calc-block::before {
                content: "计算";
                color: #1890ff;
                background: rgba(24,144,255,0.1);
            }

            /* 增强数学公式显示 */
            .math-block {
                border-left-color: #1a8cff;
                background: #f0f7ff;
                padding: 16px;
                margin: 12px 0;
                overflow-x: auto;
            }
            .math-block .math-title {
                font-weight: 600;
                margin-bottom: 8px;
                color: #1a8cff;
            }
            .math-block .math-result {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed rgba(26,140,255,0.2);
            }

            /* 优化物理公式显示 */
            .physics-block {
                border-left-color: #722ed1;
                background: #f9f0ff;
                padding: 16px;
                margin: 12px 0;
                overflow-x: auto;
            }
            .physics-block .physics-title {
                font-weight: 600;
                margin-bottom: 8px;
                color: #722ed1;
            }
            .physics-block .physics-result {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed rgba(114,46,209,0.2);
            }

            /* 优化化学公式显示 */
            .chemistry-block {
                border-left-color: #52c41a;
                background: #f6ffed;
                padding: 16px;
                margin: 12px 0;
                overflow-x: auto;
            }
            .chemistry-block .chemistry-title {
                font-weight: 600;
                margin-bottom: 8px;
                color: #52c41a;
            }
            .chemistry-block .chemistry-result {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed rgba(82,196,26,0.2);
            }

            /* 优化天文数据显示 */
            .astronomy-block {
                border-left-color: #eb2f96;
                background: #fff0f6;
                padding: 16px;
                margin: 12px 0;
                overflow-x: auto;
            }
            .astronomy-block .astronomy-title {
                font-weight: 600;
                margin-bottom: 8px;
                color: #eb2f96;
            }
            .astronomy-block .astronomy-result {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed rgba(235,47,150,0.2);
            }

            /* 自定义滚动条样式 */
            .message-content pre code::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }

            .message-content pre code::-webkit-scrollbar-track {
                background: rgba(0, 0, 0, 0.1);
                border-radius: 4px;
            }

            .message-content pre code::-webkit-scrollbar-thumb {
                background: rgba(255, 255, 255, 0.2);
                border-radius: 4px;
            }

            .message-content pre code::-webkit-scrollbar-thumb:hover {
                background: rgba(255, 255, 255, 0.3);
            }

            /* 代码语言标识样式优化 */
            .code-language {
                position: absolute;
                top: 8px;
                right: 8px;
                padding: 2px 8px;
                font-size: 12px;
                color: #abb2bf;
                background: rgba(40, 44, 52, 0.8);
                border-radius: 4px;
                user-select: none;
                z-index: 2;
                font-family: "SFMono-Regular", Consolas, monospace;
            }

            /* 添加公式块的样式 */
            .special-block {
                margin: 12px 0;
                padding: 16px;
                border-radius: 8px;
                overflow-x: auto;
            }

            .special-block .block-title {
                font-weight: 600;
                margin-bottom: 12px;
                font-size: 14px;
            }

            /* 数学公式块 */
            .math-block {
                background: #f0f7ff;
                border-left: 4px solid #1a8cff;
            }
            .math-block .block-title { color: #1a8cff; }

            /* 化学公式块 */
            .chemistry-block {
                background: #f6ffed;
                border-left: 4px solid #52c41a;
            }
            .chemistry-block .block-title { color: #52c41a; }

            /* 物理公式块 */
            .physics-block {
                background: #f9f0ff;
                border-left: 4px solid #722ed1;
            }
            .physics-block .block-title { color: #722ed1; }

            /* 生物公式块 */
            .biology-block {
                background: #fff7e6;
                border-left: 4px solid #fa8c16;
            }
            .biology-block .block-title { color: #fa8c16; }

            /* 天文公式块 */
            .astronomy-block {
                background: #fff0f6;
                border-left: 4px solid #eb2f96;
            }
            .astronomy-block .block-title { color: #eb2f96; }

            /* 公式结果样式 */
            .special-block .math-result,
            .special-block .chemistry-result,
            .special-block .physics-result,
            .special-block .biology-result,
            .special-block .astronomy-result {
                margin-top: 12px;
                padding-top: 12px;
                border-top: 1px dashed rgba(0,0,0,0.1);
            }

            /* 优化公式显示 */
            .special-block .MathJax {
                overflow-x: auto;
                overflow-y: hidden;
                max-width: 100%;
            }

            /* 表格容器样式 */
            .table-container {
                margin: 12px 0;
                padding: 16px;
                background: #fff7e6;
                border-left: 4px solid #fa8c16;
                border-radius: 8px;
                overflow-x: auto;
            }

            .table-title {
                font-weight: 600;
                margin-bottom: 12px;
                color: #fa8c16;
                font-size: 14px;
            }

            /* 表格样式 */
            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin: 8px 0;
                font-size: 14px;
            }

            .data-table th {
                background: #fff1e6;
                font-weight: 600;
                text-align: left;
                padding: 12px;
                border: 1px solid #ffd591;
            }

            .data-table td {
                padding: 12px;
                border: 1px solid #ffd591;
            }

            .data-table tr:nth-child(even) {
                background: #fff7f0;
            }

            .data-table tr:hover {
                background: #fff4e6;
            }

            /* 图表容器样式 */
            .chart-container {
                margin: 12px 0;
                padding: 16px;
                background: #e6f7ff;
                border-left: 4px solid #1890ff;
                border-radius: 8px;
            }

            .chart-title {
                font-weight: 600;
                margin-bottom: 12px;
                color: #1890ff;
                font-size: 14px;
            }

            .chart-wrapper {
                position: relative;
                height: 300px;
                margin: 12px 0;
            }
            
            /* ... 现有样式 ... */
            
            .article-search-indicator {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 12px;
                color: #666;
                margin-bottom: 8px;
                padding: 4px 8px;
                background: #f5f5f5;
                border-radius: 4px;
                width: fit-content;
            }
            
            .article-search-indicator svg {
                color: #1a8cff;
            }
            
            .setting-item {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 8px;
            }
            
            .description {
                font-size: 12px;
                color: #666;
                margin-top: 4px;
            }

            /* 添加相应的 CSS 样式 */
            .biology-block {
                border-left-color: #fa8c16;
                background: #fff7e6;
                padding: 16px;
                margin: 12px 0;
            }
            .biology-block .biology-title {
                font-weight: 600;
                margin-bottom: 8px;
                color: #fa8c16;
            }
            .biology-block .biology-result {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed rgba(250,140,22,0.2);
            }

            .calc-block {
                border-left-color: #1890ff;
                background: #e6f7ff;
                padding: 16px;
                margin: 12px 0;
            }
            .calc-block .calc-title {
                font-weight: 600;
                margin-bottom: 8px;
                color: #1890ff;
            }
            .calc-block .calc-result {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed rgba(24,144,255,0.2);
            }

            .stats-block {
                border-left-color: #722ed1;
                background: #f9f0ff;
                padding: 16px;
                margin: 12px 0;
            }
            .stats-block .stats-title {
                font-weight: 600;
                margin-bottom: 8px;
                color: #722ed1;
            }
            .stats-block .stats-result {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px dashed rgba(114,46,209,0.2);
            }

            .mindmap-block {
                border-left-color: #13c2c2;
                background: #e6fffb;
                padding: 16px;
                margin: 12px 0;
            }
            .mindmap-block .mindmap-title {
                font-weight: 600;
                margin-bottom: 8px;
                color: #13c2c2;
            }
            .mindmap-container {
                min-height: 300px;
                border: 1px solid rgba(19,194,194,0.2);
                border-radius: 4px;
                overflow: hidden;
            }

            .map-block {
                border-left-color: #52c41a;
                background: #f6ffed;
                padding: 16px;
                margin: 12px 0;
            }
            .map-block .map-title {
                font-weight: 600;
                margin-bottom: 8px;
                color: #52c41a;
            }
            .map-container {
                min-height: 300px;
                border: 1px solid rgba(82,196,26,0.2);
                border-radius: 4px;
                overflow: hidden;
            }

            .reasoning-title {
                font-weight: 600;
                color: #666;
                margin-bottom: 8px;
                margin-left: 0;  /* 移除左边距 */
            }

            /* 设置按钮基础样式 */
            .settings-btn {
                cursor: pointer;
                padding: 8px 12px;
                display: flex;
                align-items: center;
                gap: 8px;
                width: 100%;
                transition: all 0.2s ease;
                border: none;
                background: none;
                font-size: 14px;
                color: #333333;
            }

            /* 设置对话框响应式样式 */
            .settings-dialog {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 100000;
                display: none;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch; /* 为 iOS 添加弹性滚动 */
            }

            .settings-content {
                position: relative;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: #ffffff;
                padding: 24px;
                border-radius: 8px;
                width: 90%;
                max-width: 600px;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            /* 移动设备适配 */
            @media (max-width: 768px) {
                .settings-content {
                    width: 95%;
                    margin: 20px auto;
                    top: 0;
                    transform: translate(-50%, 0);
                    max-height: 85vh;
                    padding: 16px;
                }
                
                .settings-dialog {
                    align-items: flex-start;
                    padding: 10px 0;
                }
                
                /* 改善移动设备上的触摸体验 */
                .settings-btn,
                .close-settings,
                .model-button {
                    min-height: 44px; /* 确保触摸目标足够大 */
                    padding: 12px;
                }
                
                /* 优化移动设备上的滚动 */
                .settings-content {
                    -webkit-overflow-scrolling: touch;
                }
            }

            /* 触摸设备优化 */
            @media (hover: none) {
                .settings-btn:active,
                .model-button:active {
                    background-color: rgba(0, 0, 0, 0.1);
                }
            }

            /* 确保在各种设备上的可访问性 */
            .settings-btn:focus,
            .close-settings:focus,
            .model-button:focus {
                outline: 2px solid #1a8cff;
                outline-offset: 2px;
            }

            /* 添加触摸反馈 */
            .settings-btn,
            .close-settings,
            .model-button {
                touch-action: manipulation; /* 优化触摸操作 */
                -webkit-tap-highlight-color: transparent; /* 移除移动设备上的点击高亮 */
            }

            /* 暗色模式支持 */
            @media (prefers-color-scheme: dark) {
                .settings-content {
                    background: #1f1f1f;
                    color: #ffffff;
                }
                
                .settings-btn,
                .close-settings {
                    color: #ffffff;
                }
            }

            /* 添加无障碍支持 */
            .settings-btn,
            .close-settings,
            .model-button {
                role: button;
                aria-pressed: false;
            }

            /* 优化动画性能 */
            .settings-dialog,
            .settings-content {
                will-change: transform, opacity;
                backface-visibility: hidden;
            }

            /* 添加安全区域支持 */
            @supports(padding: max(0px)) {
                .settings-content {
                    padding-left: max(24px, env(safe-area-inset-left));
                    padding-right: max(24px, env(safe-area-inset-right));
                    padding-bottom: max(24px, env(safe-area-inset-bottom));
                }
            }

            /* 主内容区域响应式布局 */
            .main-content {
                flex: 1;
                display: flex;
                flex-direction: column;
                position: relative;
                background: #ffffff;
                margin-left: 0;
                transition: margin-left 0.3s ease;
                width: calc(100vw - 260px);
                overflow: hidden;
            }

            /* 消息容器响应式布局 */
            .message {
                max-width: 800px;
                margin: 0 auto;
                padding: 16px 24px;
                display: flex;
                gap: 12px;
                position: relative;
                width: 100%;
                align-items: flex-start;
                box-sizing: border-box; /* 添加这行 */
            }

            /* 移动设备适配 */
            @media (max-width: 768px) {
                .main-content {
                    width: 100vw;
                    margin-left: 0;
                }
                
                .message {
                    padding: 12px 16px; /* 减小移动设备上的内边距 */
                    max-width: 100%;
                }
                
                .message-content {
                    max-width: calc(100% - 42px); /* 42px = 头像宽度(30px) + 间距(12px) */
                }
                
                .user-message .message-content {
                    margin-right: 12px; /* 为用户消息添加右边距 */
                }
                
                .ai-message .message-content {
                    margin-left: 12px; /* 为AI消息添加左边距 */
                }
                
                .input-area {
                    left: 0;
                    padding: 12px;
                }
                
                .input-container {
                    padding: 0;
                    max-width: 100%;
                }
            }

            /* 平板设备适配 */
            @media (min-width: 769px) and (max-width: 1024px) {
                .main-content {
                    width: calc(100vw - 260px);
                }
                
                .message {
                    max-width: 90%;
                }
            }

            /* 确保内容不会溢出容器 */
            .message-content {
                overflow-wrap: break-word;
                word-wrap: break-word;
                word-break: break-word;
                hyphens: auto;
                max-width: 100%;
            }

            /* 优化图片和媒体内容的响应式显示 */
            .message-content img,
            .message-content video,
            .message-content iframe {
                max-width: 100%;
                height: auto;
            }

            /* 优化代码块在移动设备上的显示 */
            .message-content pre {
                max-width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            /* 优化表格在移动设备上的显示 */
            .message-content table {
                max-width: 100%;
                overflow-x: auto;
                display: block;
                -webkit-overflow-scrolling: touch;
            }

            /* 确保头像始终保持固定大小和位置 */
            .message-avatar {
                flex: 0 0 30px; /* 防止头像被压缩 */
                position: sticky;
                top: 0;
            }
        </style>
        <?php wp_enqueue_script('jquery'); ?>
        <?php wp_print_scripts('jquery'); ?>
        <script>
        MathJax = {
            tex: {
                inlineMath: [['\\(', '\\)']],
                displayMath: [['\\[', '\\]']],
                packages: {'[+]': ['mhchem', 'cancel', 'color', 'bbox']},
                macros: {
                    // 添加常用的宏命令
                    RR: "{\\mathbb{R}}",
                    NN: "{\\mathbb{N}}",
                    ZZ: "{\\mathbb{Z}}",
                    QQ: "{\\mathbb{Q}}",
                    CC: "{\\mathbb{C}}",
                    // 物理常量
                    hbar: "{\\hslash}",
                    deg: "{^\\circ}",
                    // 化学相关
                    ph: "{\\mathrm{pH}}",
                    // 天文单位
                    AU: "{\\mathrm{AU}}",
                    ly: "{\\mathrm{ly}}",
                    pc: "{\\mathrm{pc}}",
                    // 常用数学符号
                    dd: "{\\mathrm{d}}",
                    // 向量
                    vec: ["{\\boldsymbol{#1}}", 1],
                    // 偏导
                    pd: ["{\\frac{\\partial #1}{\\partial #2}}", 2],
                    
                    // 统计相关
                    mean: ["{\\overline{#1}}", 1],
                    var: ["{\\text{Var}(#1)}", 1],
                    std: ["{\\text{SD}(#1)}", 1],
                    corr: ["{\\text{Corr}(#1,#2)}", 2],
                    
                    // 矩阵相关
                    mat: ["{\\begin{matrix}#1\\end{matrix}}", 1],
                    pmat: ["{\\begin{pmatrix}#1\\end{pmatrix}}", 1],
                    bmat: ["{\\begin{bmatrix}#1\\end{bmatrix}}", 1],
                    
                    // 集合相关
                    set: ["{\\{#1\\}}", 1],
                    union: "{\\cup}",
                    intersect: "{\\cap}",
                    
                    // 微积分相关
                    diff: ["{\\frac{d#1}{d#2}}", 2],
                    pdiff: ["{\\frac{\\partial #1}{\\partial #2}}", 2],
                    
                    // 概率相关
                    prob: ["{\\text{P}(#1)}", 1],
                    expect: ["{\\text{E}(#1)}", 1]
                }
            },
            svg: {
                fontCache: 'global',
                scale: 1.2, // 增大公式尺寸
            },
            options: {
                enableMenu: false,
                renderActions: {
                    addMenu: [],
                    checkLoading: []
                }
            },
            startup: {
                ready: () => {
                    MathJax.startup.defaultReady();
                    MathJax.startup.promise.then(() => {
                        // 公式渲染完成后的回调
                        console.log('数学公式渲染完成');
                    });
                }
            }
        };
        </script>
        <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
        <script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/input/mhchem.js"></script>
        <!-- 在 <head> 标签中添加 Chart.js 库 -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>
    <body>
        <div class="panel-container">
            <div class="sidebar">
                <div class="new-chat-btn">
                    <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" height="16" width="16" xmlns="http://www.w3.org/2000/svg">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>新建对话</span>
                </div>
                <div class="chat-history" id="chatHistory">
                    <!-- 历史记录将在这里动态显示 -->
                </div>
                <div class="sidebar-footer">
                    <div class="user-info">
                        <svg class="user-icon" stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span><?php echo wp_get_current_user()->display_name; ?></span>
                        <svg class="arrow-icon" stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" height="16" width="16" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 9l-7 7-7-7"></path>
                        </svg>
                        <div class="user-menu">
                            <div class="user-menu-item settings-btn">
                                <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 15a3 3 0 100-6 3 3 0 000 6z" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path>
                                    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"></path>
                                </svg>
                                <span>设置</span>
                            </div>
                            <a href="<?php echo admin_url(); ?>" class="user-menu-item">
                                <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                <span>返回后台</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <button class="toggle-sidebar">
                <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M15 18l-6-6 6-6"></path></svg>
            </button>
            <div class="main-content">
                <div class="chat-messages" id="aiChatMessages">
                    <!-- 消息将在这里动态显示 -->
                </div>
                <div class="input-area">
                    <div class="input-container">
                        <textarea id="aiChatInput" placeholder="发送消息..." rows="1"></textarea>
                        <button class="send-button" type="button">
                            <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 设置对话框 -->
        <div class="settings-dialog" id="settingsDialog">
            <div class="settings-content">
                <div class="settings-header">
                    <div class="settings-title">设置</div>
                    <button class="close-settings">
                        <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" height="24" width="24">
                            <path d="M18 6L6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <?php if (current_user_can('manage_options')): ?>
                <div class="settings-section">
                    <div class="settings-section-title">API配置</div>
                    <div class="api-config" style="margin-bottom: 20px;">
                        <div style="margin-bottom: 12px;">
                            <label for="apiEndpoint" style="display: block; margin-bottom: 8px; font-size: 14px; color: #666;">Ollama API地址</label>
                            <input type="text" id="apiEndpoint" placeholder="例如：http://localhost:11434" 
                                style="width: 100%; padding: 8px; border: 1px solid #e0e0e0; border-radius: 4px; font-size: 14px;">
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button class="model-button primary" id="testApiConfig">测试连接</button>
                            <button class="model-button primary" id="saveApiConfig" disabled>保存配置</button>
                        </div>
                        <div id="testResult" style="margin-top: 10px; padding: 10px; border-radius: 4px; display: none;">
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="settings-section">
                    <div class="settings-section-title">模型管理</div>
                    <div class="model-list" id="modelList">
                        <!-- 模型列表将通过JavaScript动态加载 -->
                    </div>
                    <div class="loading-indicator" id="loadingIndicator">
                        <svg class="animate-spin" fill="none" height="24" viewBox="0 0 24 24" width="24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <div>加载中...</div>
                    </div>
                </div>
                <div class="settings-section">
                    <div class="settings-section-title">聊天记录管理</div>
                    <div style="margin-top: 12px;">
                        <button class="clear-history-btn" id="clearHistory">
                            <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            清除所有聊天记录
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // 在脚本开始处定义变量
        const wpNonce = '<?php echo wp_create_nonce("wp_rest"); ?>';
        const isAdmin = <?php echo current_user_can('manage_options') ? 'true' : 'false'; ?>;
        
        // 修改 fetchWithCORS 函数
        async function fetchWithCORS(url, options = {}) {
            const defaultOptions = {
                mode: 'cors',
                credentials: 'omit',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            };

            const finalOptions = {
                ...defaultOptions,
                ...options,
                headers: {
                    ...defaultOptions.headers,
                    ...(options.headers || {})
                }
            };

            try {
                const response = await fetch(url, finalOptions);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response;
            } catch (error) {
                throw new Error('无法连接到API服务器，请检查服务器是否正在运行');
            }
        }

        jQuery(document).ready(function($) {
            // 修改 API 地址检查函数
            function checkApiEndpoint(url) {
                if (!url) {
                    return {
                        isValid: false,
                        message: 'API地址不能为空',
                        url: null
                    };
                }

                // 移除末尾的斜杠
                url = url.replace(/\/$/, '');
                
                return {
                    isValid: true,
                    message: '有效的API地址',
                    url: url
                };
            }

            const chatMessages = $('#aiChatMessages');
            const chatInput = $('#aiChatInput');
            const chatHistory = $('#chatHistory');
            let currentChatId = null;
            
            // 修改加载历史记录函数
            function loadChatHistory() {
                try {
                    const history = JSON.parse(localStorage.getItem('chatHistory') || '[]');
                    console.log('加载历史记录:', history); // 添加日志
                    
                    chatHistory.empty();
                    history.sort((a, b) => b.timestamp - a.timestamp);
                    
                    history.forEach(chat => {
                        const historyItem = $(`
                            <div class="history-item" data-chat-id="${chat.id}">
                                <svg class="history-icon" stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" height="16" width="16" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                <span class="history-text">${chat.title}</span>
                                <button class="delete-btn" title="删除对话">
                                    <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" height="16" width="16">
                                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        `);
                        
                        chatHistory.append(historyItem);
                    });
                    
                    // 如果有当前对话，高亮显示
                    if (currentChatId) {
                        $(`.history-item[data-chat-id="${currentChatId}"]`).addClass('active');
                    }
                } catch (error) {
                    console.error('加载历史记录时发生错误:', error); // 添加错误日志
                }
            }

            // 修改历史记录点击事件
            $(document).on('click', '.history-item', function(e) {
                const deleteBtn = $(this).find('.delete-btn');
                
                // 如果点击的是删除按钮，不执行后续操作
                if ($(e.target).closest('.delete-btn').length) {
                    console.log('点击了删除按钮'); // 添加日志
                    return;
                }
                
                console.log('点击了历史记录项'); // 添加日志
                
                // 移除其他项的active类
                $('.history-item').removeClass('active');
                // 添加当前项的active类
                $(this).addClass('active');
                
                const chatId = $(this).data('chat-id');
                console.log('加载对话ID:', chatId); // 添加日志
                
                loadChat(chatId);
                
                // 移动端自动收起侧边栏
                if ($(window).width() <= 768) {
                    $('.sidebar').addClass('collapsed');
                    $('.toggle-sidebar').addClass('collapsed');
                    $('.input-area').addClass('collapsed');
                }
            });

            // 修改删除对话功能
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const historyItem = $(this).closest('.history-item');
                const chatId = historyItem.data('chat-id');
                
                if (!chatId) {
                    console.error('无法获取对话ID');
                    alert('删除失败：无法获取对话ID');
                    return;
                }
                
                if (confirm('确定要删除这个对话吗？')) {
                    try {
                        let history = JSON.parse(localStorage.getItem('chatHistory') || '[]');
                        
                        // 过滤掉要删除的对话
                        const newHistory = history.filter(chat => chat.id !== chatId);
                        
                        // 保存新的历史记录
                        localStorage.setItem('chatHistory', JSON.stringify(newHistory));
                        
                        // 如果删除的是当前对话
                        if (chatId === currentChatId) {
                            currentChatId = null;
                            chatMessages.empty();
                            
                            if (newHistory.length > 0) {
                                // 加载最新的对话
                                currentChatId = newHistory[0].id;
                                loadChat(currentChatId);
                            } else {
                                // 如果没有对话了，创建新对话
                                currentChatId = Date.now().toString();
                                saveNewChat('新对话');
                            }
                        }
                        
                        // 从DOM中移除对话项
                        historyItem.remove();
                        
                        // 重新加载历史记录
                        loadChatHistory();
                        
                    } catch (error) {
                        console.error('删除对话时发生错误:', error);
                        alert('删除对话时发生错误：' + error.message);
                    }
                }
            });

            // 修改侧边栏切换逻辑
            $('.toggle-sidebar').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const sidebar = $('.sidebar');
                const toggleButton = $(this);
                const inputArea = $('.input-area');
                const mainContent = $('.main-content');
                
                sidebar.toggleClass('collapsed');
                toggleButton.toggleClass('collapsed');
                inputArea.toggleClass('collapsed');
                mainContent.toggleClass('collapsed');
                
                // 保存侧边栏状态
                localStorage.setItem('sidebarCollapsed', sidebar.hasClass('collapsed'));
                
                // 触发窗口resize事件以更新布局
                $(window).trigger('resize');
            });

            // 添加窗口大小变化处理
            $(window).on('resize', function() {
                const sidebar = $('.sidebar');
                const toggleButton = $('.toggle-sidebar');
                const inputArea = $('.input-area');
                const mainContent = $('.main-content');
                
                if ($(window).width() <= 768) {
                    // 移动端布局
                    if (!sidebar.hasClass('collapsed')) {
                        toggleButton.css('left', '276px');
                        inputArea.css('left', '0');
                    } else {
                    toggleButton.css('left', '16px');
                    }
                } else {
                    // 桌面端布局
                    if (!sidebar.hasClass('collapsed')) {
                        toggleButton.css('left', '260px');
                        inputArea.css('left', '260px');
                    } else {
                        toggleButton.css('left', '16px');
                        inputArea.css('left', '0');
                    }
                }
            });

            // 初始化时检查保存的侧边栏状态
            $(document).ready(function() {
                const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                if (sidebarCollapsed) {
                    $('.sidebar').addClass('collapsed');
                    $('.toggle-sidebar').addClass('collapsed');
                    $('.input-area').addClass('collapsed');
                    $('.main-content').addClass('collapsed');
                }
                
                // 触发一次resize事件以正确设置初始布局
                $(window).trigger('resize');
            });

            // 修改初始化函数，确保新用户有默认模型设置
            function initializeChat() {
                try {
                    let history = JSON.parse(localStorage.getItem('chatHistory') || '[]');
                    const currentUserId = '<?php echo get_current_user_id(); ?>';
                    
                    // 确保新用户有默认模型设置
                    if (!localStorage.getItem(`selectedModel_${currentUserId}`)) {
                        // 设置默认模型
                        fetch('/wp-json/ollama/v1/tags', {
                            headers: {
                                'Accept': 'application/json',
                                'X-WP-Nonce': wpNonce
                            },
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data && Array.isArray(data.models) && data.models.length > 0) {
                                localStorage.setItem(`selectedModel_${currentUserId}`, data.models[0].name);
                            } else {
                                localStorage.setItem(`selectedModel_${currentUserId}`, 'llama2');
                            }
                        })
                        .catch(() => {
                            localStorage.setItem(`selectedModel_${currentUserId}`, 'llama2');
                        });
                    }
                    
                    // 确保history是数组
                    if (!Array.isArray(history)) {
                        history = [];
                        localStorage.removeItem('chatHistory');
                        localStorage.setItem('chatHistory', '[]');
                    }
                    
                    // 清理空对话和无效对话
                    history = history.filter(chat => 
                        chat && 
                        chat.id && 
                        chat.messages && 
                        Array.isArray(chat.messages)
                    );
                    localStorage.setItem('chatHistory', JSON.stringify(history));
                    
                    if (history.length > 0) {
                        // 加载最新的对话
                        currentChatId = history[0].id;
                        loadChat(currentChatId);
                    } else {
                        // 创建新对话
                        currentChatId = Date.now().toString();
                        saveNewChat('新对话');
                        loadChatHistory();
                        chatMessages.empty();
                    }
                } catch (error) {
                    console.error('初始化聊天时发生错误:', error);
                    // 重置历史记录
                    localStorage.removeItem('chatHistory');
                    localStorage.setItem('chatHistory', '[]');
                    currentChatId = Date.now().toString();
                    saveNewChat('新对话');
                    loadChatHistory();
                    chatMessages.empty();
                }
            }

            // 修改新建对话按钮点击事件
            $('.new-chat-btn').on('click', function(e) {
                e.preventDefault();
                currentChatId = Date.now().toString();
                chatMessages.empty();
                chatInput.val('');
                
                // 取消所有历史记录的选中状态
                $('.history-item').removeClass('active');
                
                // 创建新对话
                saveNewChat('新对话');
                loadChatHistory();
                chatInput.focus();
            });

            // 初始化
            loadChatHistory();
            initializeChat();

            // 修改加载对话函数
            function loadChat(chatId) {
                try {
                const history = JSON.parse(localStorage.getItem('chatHistory') || '[]');
                const chat = history.find(c => c.id === chatId);
                
                if (chat) {
                    currentChatId = chatId;
                    chatMessages.empty();
                    
                    // 更新选中状态
                    $('.history-item').removeClass('active');
                    $(`.history-item[data-chat-id="${chatId}"]`).addClass('active');
                    
                        if (chat.messages && Array.isArray(chat.messages)) {
                    chat.messages.forEach(msg => {
                                if (msg && msg.content && msg.type) {
                        appendMessage(msg.content, msg.type);
                                }
                    });
                        }
                    
                    chatMessages.scrollTop(chatMessages[0].scrollHeight);
                    chatInput.focus();
                    }
                } catch (error) {
                    console.error('加载对话时发生错误:', error);
                }
            }

            // 修改发送消息函数
            function sendMessage() {
                const message = chatInput.val().trim();
                if (!message) return;

                // 获取当前用户ID和对应的模型
                const currentUserId = '<?php echo get_current_user_id(); ?>';
                const currentModel = localStorage.getItem(`selectedModel_${currentUserId}`) || 'llama2';

                // 禁用输入和发送按钮
                chatInput.prop('disabled', true);
                $('.send-button').prop('disabled', true);

                if (!currentChatId) {
                    currentChatId = Date.now().toString();
                    saveNewChat('新对话');
                }

                // 添加用户消息并保存
                appendMessage(message, 'user');
                saveMessageToHistory(message, 'user');
                chatInput.val('').height('auto');

                // 添加AI的加载消息
                const loadingMessage = $('<div>').addClass('message-wrapper ai-message loading-message');
                loadingMessage.html(`
                    <div class="message">
                        <div class="message-avatar" style="background: #10a37f">AI</div>
                        <div class="message-content">正在思考...</div>
                    </div>
                `);
                chatMessages.append(loadingMessage);
                chatMessages.scrollTop(chatMessages[0].scrollHeight);

                // 设置请求超时
                const timeoutPromise = new Promise((_, reject) => {
                    setTimeout(() => reject(new Error('请求超时')), 120000); // 120秒超时
                });

                // 发起API请求
                Promise.race([
                    fetch('/wp-json/ollama/v1/generate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-WP-Nonce': wpNonce
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            model: currentModel,
                            prompt: `你是一个专业的AI助手。请按照以下格式回答问题：

推理过程：
1. 首先分析用户的问题/需求
2. 思考解决方案和关键点
3. 确定最佳答案方式

最终答案：
[你的详细回答]

用户问题：${message}`,
                            stream: false,
                            options: {
                                temperature: 0.7,
                                num_predict: -1,
                                top_k: 40,
                                top_p: 0.9,
                                seed: 0,
                                raw: true
                            }
                        })
                    }),
                    timeoutPromise
                ])
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 401) {
                            throw new Error('认证失败，请刷新页面重试');
                        }
                        if (response.status === 504) {
                            throw new Error('请求超时，请检查服务器状态或稍后重试');
                        }
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // 移除加载消息
                    $('.loading-message').remove();
                    
                    if (data && data.response) {
                        // 解析推理过程和最终回答
                        let response = data.response;
                        
                        // 检查模型是否支持推理过程
                        const hasReasoningCapability = response.includes('推理过程：') || 
                                                     response.includes('思考过程：') || 
                                                     response.includes('分析过程：') ||
                                                     response.includes('解题思路：');
                        
                        // 如果模型支持推理过程
                        if (hasReasoningCapability) {
                            // 尝试提取推理过程和最终答案
                            let reasoning = '';
                            let finalAnswer = '';
                            
                            // 处理不同格式的推理标记
                            if (response.includes('推理过程：')) {
                                [reasoning, finalAnswer] = response.split('最终答案：');
                                reasoning = reasoning.replace('推理过程：', '').trim();
                            } else if (response.includes('思考过程：')) {
                                [reasoning, finalAnswer] = response.split('回答：');
                                reasoning = reasoning.replace('思考过程：', '').trim();
                            } else if (response.includes('分析过程：')) {
                                [reasoning, finalAnswer] = response.split('结论：');
                                reasoning = reasoning.replace('分析过程：', '').trim();
                            } else if (response.includes('解题思路：')) {
                                [reasoning, finalAnswer] = response.split('答案：');
                                reasoning = reasoning.replace('解题思路：', '').trim();
                            }
                            
                            if (reasoning && finalAnswer) {
                                // 将推理过程和最终答案组合在一起，添加切换按钮
                                const combinedMessage = `
                                    <div class="reasoning-section">
                                        <div class="content-wrapper">
                                            <div class="reasoning-title">推理过程：</div>
                                            ${reasoning}
                                        </div>
                                        <button class="toggle-reasoning"></button>
                                    </div>
                                    <div class="answer-section">${finalAnswer.trim()}</div>`;
                                
                                // 添加组合后的消息
                                appendMessage(combinedMessage, 'ai');
                                
                                // 保存到历史记录
                                saveMessageToHistory(combinedMessage, 'ai');
                            } else {
                                // 如果无法正确分割，就把整个响应作为答案
                                appendMessage(response, 'ai');
                                saveMessageToHistory(response, 'ai');
                            }
                        } else {
                            // 如果模型不支持推理过程，直接显示响应
                            appendMessage(response, 'ai');
                            saveMessageToHistory(response, 'ai');
                        }
                    } else {
                        appendMessage('抱歉，服务器返回了无效的响应格式', 'ai');
                    }
                    chatMessages.scrollTop(chatMessages[0].scrollHeight);
                })
                .catch(error => {
                    // 移除加载消息
                    $('.loading-message').remove();
                    
                    let errorMessage = '发生错误：\n';
                    if (error.message.includes('认证失败')) {
                        errorMessage += error.message;
                    } else if (error.message.includes('请求超时')) {
                        errorMessage += '请求处理时间过长，请检查：\n';
                        errorMessage += '1. 模型是否正在加载\n';
                        errorMessage += '2. 服务器资源是否充足\n';
                        errorMessage += '3. 网络连接是否稳定\n';
                        errorMessage += '\n建议：\n';
                        errorMessage += '- 稍后重试\n';
                        errorMessage += '- 选择较小的模型\n';
                        errorMessage += '- 检查服务器状态';
                    } else {
                        errorMessage += '无法连接到API服务器，请检查：\n';
                        errorMessage += '1. API服务器是否正在运行\n';
                        errorMessage += '2. API地址是否正确\n';
                        errorMessage += '3. 网络连接是否正常\n';
                    }
                    appendMessage(errorMessage, 'ai');
                    console.error('API Error:', error);
                })
                .finally(() => {
                    chatInput.prop('disabled', false);
                    $('.send-button').prop('disabled', false);
                    chatInput.focus();
                });
            }

            // 修改保存新对话函数
            function saveNewChat(title) {
                try {
                    let history = JSON.parse(localStorage.getItem('chatHistory') || '[]');
                    
                    // 确保history是数组
                    if (!Array.isArray(history)) {
                        history = [];
                    }
                    
                    // 清理空对话和无效对话
                    history = history.filter(chat => 
                        chat && 
                        chat.id && 
                        chat.messages && 
                        Array.isArray(chat.messages)
                    );
                    
                    const newChat = {
                        id: currentChatId || Date.now().toString(),
                        title: title || '新对话',
                        messages: [],
                        timestamp: Date.now()
                    };
                    
                    // 检查是否已存在相同ID的对话
                    const existingIndex = history.findIndex(chat => chat.id === newChat.id);
                    if (existingIndex === -1) {
                        history.unshift(newChat);
                        localStorage.setItem('chatHistory', JSON.stringify(history));
                        console.log('新对话已保存:', newChat);
                    }
                } catch (error) {
                    console.error('保存新对话时发生错误:', error);
                }
            }

            // 修改保存消息到历史记录函数
            function saveMessageToHistory(message, type) {
                if (!currentChatId) return;
                
                const history = JSON.parse(localStorage.getItem('chatHistory') || '[]');
                const chatIndex = history.findIndex(chat => chat.id === currentChatId);
                
                if (chatIndex !== -1) {
                    history[chatIndex].messages.push({
                        type: type,
                        content: message,
                        timestamp: Date.now()
                    });
                    
                    // 仅在第一条用户消息时更新标题
                    if (type === 'user' && history[chatIndex].messages.filter(m => m.type === 'user').length === 1) {
                        history[chatIndex].title = message.substring(0, 30) + (message.length > 30 ? '...' : '');
                    }
                    
                    history[chatIndex].timestamp = Date.now(); // 更新对话时间戳
                    localStorage.setItem('chatHistory', JSON.stringify(history));
                    loadChatHistory();
                }
            }

            // 修改消息显示函数
            function appendMessage(content, type) {
                const avatar = type === 'user' ? 'U' : 'AI';
                const avatarBg = type === 'user' ? '#1a8cff' : '#10a37f';
                const wrapper = $('<div>').addClass('message-wrapper').addClass(type === 'user' ? 'user-message' : (type === 'ai-reasoning' ? 'ai-reasoning' : 'ai-message'));
                
                let messageStyle = '';
                if (type === 'ai-reasoning') {
                    messageStyle = 'font-size: 14px; color: #666666; font-style: italic;';
                }
                
                // 处理特殊块
                content = content
                    // 处理表格块
                    .replace(/```table([\s\S]*?)```/g, function(match, table) {
                        return `<div class="special-block table-block">${table.trim()}</div>`;
                    })
                    // 处理数学块
                    .replace(/\$\$\$([\s\S]*?)\$\$\$/g, function(match, math) {
                        return `<div class="special-block math-block">\\[${math.trim()}\\]</div>`;
                    })
                    // 处理化学块
                    .replace(/```chemistry([\s\S]*?)```/g, function(match, chem) {
                        return `<div class="special-block chemistry-block">${chem.trim()}</div>`;
                    })
                    // 处理物理块
                    .replace(/```physics([\s\S]*?)```/g, function(match, phys) {
                        return `<div class="special-block physics-block">${phys.trim()}</div>`;
                    })
                    // 处理天文块
                    .replace(/```astronomy([\s\S]*?)```/g, function(match, astro) {
                        return `<div class="special-block astronomy-block">${astro.trim()}</div>`;
                    })
                    // 处理普通代码块
                    .replace(/```(\w+)?\n([\s\S]*?)```/g, function(match, lang, code) {
                        const language = lang || 'plaintext';
                        // 保持原始格式，包括换行和缩进
                        const formattedCode = code
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;')
                            // 保留换行符，但转换为 HTML 换行
                            .replace(/\n/g, '&#10;')
                            // 保留空格
                            .replace(/ /g, '&nbsp;');
                        
                        return `
                            <pre><code class="language-${language}">${formattedCode}</code>
                            <span class="code-language">${language}</span>
                            <button class="copy-button">复制代码</button>
                            </pre>
                        `;
                    })
                    // 处理行内代码
                    .replace(/`([^`]+)`/g, '<code>$1</code>')
                    // 处理行内数学公式
                    .replace(/\$(.*?)\$/g, '\\($1\\)');
                
                // 处理换行
                content = content.replace(/\n/g, '<br>');
                
                // 处理科学计算块
                content = content
                    // 处理科学计算块
                    .replace(/```calc([\s\S]*?)```/g, function(match, calc) {
                        return `<div class="special-block calc-block">${calc.trim()}</div>`;
                    })
                    // 处理数学块，增加标题和结果区域
                    .replace(/```math\s*\[(.*?)\]([\s\S]*?)(?:\[结果\]([\s\S]*?))?```/g, function(match, title, content, result) {
                        return `
                            <div class="special-block math-block">
                                <div class="math-title">${title || '数学公式'}</div>
                                <div class="formula-content">
                                    ${content.trim().split('\n').map(line => `\\[${line.trim()}\\]`).join('\n')}
                                </div>
                                ${result ? `
                                    <div class="math-result">
                                        <div class="result-label">计算结果：</div>
                                        ${result.trim().split('\n').map(line => `\\[${line.trim()}\\]`).join('\n')}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    })
                    // 处理物理块，增加标题和结果区域
                    .replace(/```physics\s*\[(.*?)\]([\s\S]*?)(?:\[结果\]([\s\S]*?))?```/g, function(match, title, content, result) {
                        return `
                            <div class="special-block physics-block">
                                <div class="physics-title">${title || '物理公式'}</div>
                                <div class="formula-content">
                                    ${content.trim().split('\n').map(line => `\\[${line.trim()}\\]`).join('\n')}
                                </div>
                                ${result ? `
                                    <div class="physics-result">
                                        <div class="result-label">计算结果：</div>
                                        ${result.trim().split('\n').map(line => `\\[${line.trim()}\\]`).join('\n')}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    })
                    // 处理化学块，增加标题和结果区域
                    .replace(/```chemistry\s*\[(.*?)\]([\s\S]*?)(?:\[结果\]([\s\S]*?))?```/g, function(match, title, content, result) {
                        return `
                            <div class="special-block chemistry-block">
                                <div class="chemistry-title">${title || '化学方程式'}</div>
                                <div class="formula-content">
                                    ${content.trim().split('\n').map(line => `\\ce{${line.trim()}}`).join('\n')}
                                </div>
                                ${result ? `
                                    <div class="chemistry-result">
                                        <div class="result-label">反应结果：</div>
                                        ${result.trim().split('\n').map(line => `\\ce{${line.trim()}}`).join('\n')}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    })
                    // 处理天文块，增加标题和结果区域
                    .replace(/```astronomy\s*\[(.*?)\]([\s\S]*?)(?:\[结果\]([\\s\S]*?))?```/g, function(match, title, content, result) {
                        return `
                            <div class="special-block astronomy-block">
                                <div class="astronomy-title">${title || '天文数据'}</div>
                                <div class="formula-content">
                                    ${content.trim().split('\n').map(line => `\\[${line.trim()}\\]`).join('\n')}
                                </div>
                                ${result ? `
                                    <div class="astronomy-result">
                                        <div class="result-label">计算结果：</div>
                                        ${result.trim().split('\n').map(line => `\\[${line.trim()}\\]`).join('\n')}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    })
                    // 处理生物块，增加标题和结果区域
                    .replace(/```biology\s*\[(.*?)\]([\s\S]*?)(?:\[结果\]([\s\S]*?))?```/g, function(match, title, content, result) {
                        return `
                            <div class="special-block biology-block">
                                <div class="biology-title">${title || '生物公式'}</div>
                                <div class="formula-content">
                                    ${content.trim().split('\n').map(line => `\\[${line.trim()}\\]`).join('\n')}
                                </div>
                                ${result ? `
                                    <div class="biology-result">
                                        <div class="result-label">计算结果：</div>
                                        ${result.trim().split('\n').map(line => `\\[${line.trim()}\\]`).join('\n')}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    })
                    // 处理计算块，增加标题和结果区域
                    .replace(/```calc\s*\[(.*?)\]([\s\S]*?)(?:\[结果\]([\s\S]*?))?```/g, function(match, title, content, result) {
                        return `
                            <div class="special-block calc-block">
                                <div class="calc-title">${title || '计算过程'}</div>
                                <div class="formula-content">
                                    ${content.trim().split('\n').map(line => `\\[${line.trim()}\\]`).join('\n')}
                                </div>
                                ${result ? `
                                    <div class="calc-result">
                                        <div class="result-label">计算结果：</div>
                                        ${result.trim().split('\n').map(line => `\\[${line.trim()}\\]`).join('\n')}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    })
                    // 处理特殊公式块
                    .replace(/```math\s*\[(.*?)\]([\s\S]*?)(?:\[结果\]([\s\S]*?))?```/g, function(match, title, content, result) {
                        return `
                            <div class="special-block math-block">
                                <div class="block-title">${title || '数学公式'}</div>
                                <div class="formula-content">\\[${content.trim()}\\]</div>
                                ${result ? `<div class="formula-result">结果：${result.trim()}</div>` : ''}
                            </div>
                        `;
                    })
                    // 处理化学公式块
                    .replace(/```chemistry\s*\[(.*?)\]([\s\S]*?)(?:\[结果\]([\s\S]*?))?```/g, function(match, title, content, result) {
                        return `
                            <div class="special-block chemistry-block">
                                <div class="block-title">${title || '化学方程式'}</div>
                                <div class="formula-content">\\ce{${content.trim()}}\\]</div>
                                ${result ? `<div class="formula-result">结果：${result.trim()}</div>` : ''}
                            </div>
                        `;
                    })
                    // 处理物理公式块
                    .replace(/```physics\s*\[(.*?)\]([\s\S]*?)(?:\[结果\]([\s\S]*?))?```/g, function(match, title, content, result) {
                        return `
                            <div class="special-block physics-block">
                                <div class="block-title">${title || '物理公式'}</div>
                                <div class="formula-content">\\[${content.trim()}\\]</div>
                                ${result ? `<div class="formula-result">结果：${result.trim()}</div>` : ''}
                            </div>
                        `;
                    })
                    // 处理生物公式块
                    .replace(/```biology\s*\[(.*?)\]([\s\S]*?)(?:\[结果\]([\s\S]*?))?```/g, function(match, title, content, result) {
                        return `
                            <div class="special-block biology-block">
                                <div class="block-title">${title || '生物公式'}</div>
                                <div class="formula-content">\\[${content.trim()}\\]</div>
                                ${result ? `<div class="formula-result">结果：${result.trim()}</div>` : ''}
                            </div>
                        `;
                    })
                    // 处理天文公式块
                    .replace(/```astronomy\s*\[(.*?)\]([\s\S]*?)(?:\[结果\]([\s\S]*?))?```/g, function(match, title, content, result) {
                        return `
                            <div class="special-block astronomy-block">
                                <div class="block-title">${title || '天文数据'}</div>
                                <div class="formula-content">\\[${content.trim()}\\]</div>
                                ${result ? `<div class="formula-result">结果：${result.trim()}</div>` : ''}
                            </div>
                        `;
                    })
                    // 处理行内公式
                    .replace(/\$(.*?)\$/g, '\\($1\\)');
                
                // 处理随机数据表格
                content = content.replace(/```randomtable\s*\[(.*?)\]([\s\S]*?)```/g, function(match, title, config) {
                    try {
                        // 生成随机数据
                        const data = generateRandomData();
                        
                        return `
                            <div class="table-container">
                                <div class="table-title">${title || '随机数据'}</div>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>序号</th>
                                            <th>随机整数</th>
                                            <th>随机浮点数</th>
                                            <th>随机字母</th>
                                            <th>随机日期</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.map((row, index) => `
                                            <tr>
                                                <td>${index + 1}</td>
                                                <td>${row.integer}</td>
                                                <td>${row.float}</td>
                                                <td>${row.letter}</td>
                                                <td>${row.date}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } catch (error) {
                        console.error('生成随机数据表格失败:', error);
                        return `<div class="error">表格生成失败: ${error.message}</div>`;
                    }
                });

                const messageHtml = `
                    <div class="message">
                        <div class="message-avatar" style="background: ${avatarBg}">${avatar}</div>
                        <div class="message-content" style="${messageStyle}">${content}</div>
                        <button class="message-delete-btn" title="删除消息" data-message-type="${type}">
                            <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" height="16" width="16">
                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                `;
                wrapper.html(messageHtml);
                chatMessages.append(wrapper);
                
                // 重新渲染数学公式
                if (window.MathJax) {
                    MathJax.typesetPromise([wrapper[0]]).catch((err) => console.error('数学公式渲染错误:', err));
                }
                
                chatMessages.scrollTop(chatMessages[0].scrollHeight);
                
                // 添加复制代码功能
                wrapper.find('.copy-button').on('click', function() {
                    const codeElement = $(this).prev('code');
                    const textArea = document.createElement('textarea');
                    textArea.value = codeElement.text();
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    
                    const button = $(this);
                    button.text('已复制').addClass('copied');
                    setTimeout(() => {
                        button.text('复制代码').removeClass('copied');
                    }, 2000);
                });

                // 对新添加的代码块应用语法高亮
                if (window.Prism) {
                    try {
                        wrapper.find('code[class*="language-"]').each(function() {
                            const element = this;
                            const language = element.className.match(/language-(\w+)/)?.[1] || 'plaintext';
                            
                            // 确保语言组件已加载
                            if (Prism.languages[language]) {
                                Prism.highlightElement(element);
                            } else {
                                // 如果语言未加载，先设置为纯文本
                                element.className = 'language-plaintext';
                                Prism.highlightElement(element);
                                
                                // 等待语言加载完成后重新高亮
                                Prism.plugins.autoloader.loadLanguages([language], function() {
                                    element.className = `language-${language}`;
                                    Prism.highlightElement(element);
                                });
                            }
                        });
                    } catch (error) {
                        console.warn('语法高亮应用失败:', error);
                    }
                }

                // 处理表格
                content = content.replace(/```table\s*\[(.*?)\]([\s\S]*?)```/g, function(match, title, tableData) {
                    try {
                        // 解析表格数据
                        const rows = tableData.trim().split('\n').map(row => 
                            row.trim().split('|').map(cell => cell.trim())
                        );
                        
                        const headers = rows[0];
                        const data = rows.slice(1);
                        
                        let tableHtml = `
                            <div class="table-container">
                                <div class="table-title">${title || '数据表格'}</div>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            ${headers.map(h => `<th>${h}</th>`).join('')}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.map(row => `
                                            <tr>
                                                ${row.map(cell => `<td>${cell}</td>`).join('')}
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                        
                        return tableHtml;
                    } catch (error) {
                        console.error('表格渲染错误:', error);
                        return `<div class="error">表格渲染失败: ${error.message}</div>`;
                    }
                });
                
                // 处理图表
                content = content.replace(/```chart\s*\[(.*?)\]\s*\{(.*?)\}([\s\S]*?)```/g, function(match, title, type, chartData) {
                    try {
                        const chartId = 'chart-' + Math.random().toString(36).substr(2, 9);
                        const chartConfig = JSON.parse(chartData);
                        
                        // 创建图表容器
                        const chartHtml = `
                            <div class="chart-container">
                                <div class="chart-title">${title || '数据图表'}</div>
                                <div class="chart-wrapper">
                                    <canvas id="${chartId}"></canvas>
                                </div>
                            </div>
                        `;
                        
                        // 在下一个事件循环中初始化图表
                        setTimeout(() => {
                            const ctx = document.getElementById(chartId).getContext('2d');
                            new Chart(ctx, {
                                type: type.trim(),
                                data: chartConfig.data,
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    ...chartConfig.options
                                }
                            });
                        }, 0);
                        
                        return chartHtml;
                    } catch (error) {
                        console.error('图表渲染错误:', error);
                        return `<div class="error">图表渲染失败: ${error.message}</div>`;
                    }
                });

                // 处理统计数据块
                content = content.replace(/```stats\s*\[(.*?)\]([\s\S]*?)(?:\[结果\]([\s\S]*?))?```/g, function(match, title, content, result) {
                    return `
                        <div class="special-block stats-block">
                            <div class="stats-title">${title || '统计分析'}</div>
                            <div class="formula-content">
                                ${content.trim().split('\n').map(line => `\\[${line.trim()}\\]`).join('\n')}
                            </div>
                            ${result ? `
                                <div class="stats-result">
                                    <div class="result-label">分析结果：</div>
                                    ${result.trim().split('\n').map(line => `\\[${line.trim()}\\]`).join('\n')}
                                </div>
                            ` : ''}
                        </div>
                    `;
                })
                
                // 处理表格块（支持更复杂的表格格式）
                .replace(/```table\s*\[(.*?)\]\s*\{(.*?)\}([\s\S]*?)```/g, function(match, title, config, content) {
                    try {
                        const tableConfig = JSON.parse(`{${config}}`);
                        const rows = content.trim().split('\n').map(row => 
                            row.trim().split('|').map(cell => cell.trim())
                        );
                        
                        return `
                            <div class="special-block table-block">
                                <div class="table-title">${title || '数据表格'}</div>
                                <div class="table-container">
                                    <table class="data-table ${tableConfig.class || ''}">
                                        ${rows.map((row, i) => `
                                            <tr>
                                                ${row.map((cell, j) => {
                                                    const isHeader = i === 0 || tableConfig.rowHeaders && j === 0;
                                                    const tag = isHeader ? 'th' : 'td';
                                                    return `<${tag}>${cell}</${tag}>`;
                                                }).join('')}
                                            </tr>
                                        `).join('')}
                                    </table>
                                </div>
                            </div>
                        `;
                    } catch (error) {
                        console.error('表格渲染错误:', error);
                        return `<div class="error">表格渲染失败: ${error.message}</div>`;
                    }
                })
                
                // 处理思维导图块
                .replace(/```mindmap\s*\[(.*?)\]([\s\S]*?)```/g, function(match, title, content) {
                    const id = 'mindmap-' + Math.random().toString(36).substr(2, 9);
                    setTimeout(() => {
                        // 这里可以集成思维导图库，如 MindElixir 或 Markmap
                        // 需要先引入相应的库
                    }, 0);
                    return `
                        <div class="special-block mindmap-block">
                            <div class="mindmap-title">${title || '思维导图'}</div>
                            <div class="mindmap-container">
                                <div id="${id}" class="mindmap-canvas"></div>
                            </div>
                        </div>
                    `;
                })
                
                // 处理地图块
                .replace(/```map\s*\[(.*?)\]\s*\{(.*?)\}([\s\S]*?)```/g, function(match, title, config, content) {
                    const id = 'map-' + Math.random().toString(36).substr(2, 9);
                    try {
                        const mapConfig = JSON.parse(`{${config}}`);
                        setTimeout(() => {
                            // 这里可以集成地图库，如 Leaflet 或 OpenLayers
                            // 需要先引入相应的库
                        }, 0);
                        return `
                            <div class="special-block map-block">
                                <div class="map-title">${title || '地理位置'}</div>
                                <div class="map-container">
                                    <div id="${id}" class="map-canvas"></div>
                                </div>
                            </div>
                        `;
                    } catch (error) {
                        console.error('地图渲染错误:', error);
                        return `<div class="error">地图渲染失败: ${error.message}</div>`;
                    }
                });
            }

            // 修改消息删除事件处理
            $(document).on('click', '.message-delete-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const messageWrapper = $(this).closest('.message-wrapper');
                const messageContent = messageWrapper.find('.message-content').html().replace(/<br>/g, '\n').trim();
                const messageType = $(this).data('message-type');
                
                if (confirm('确定要删除这条消息吗？')) {
                    try {
                        // 获取当前对话的历史记录
                        let history = JSON.parse(localStorage.getItem('chatHistory') || '[]');
                        const chatIndex = history.findIndex(chat => chat.id === currentChatId);
                        
                        if (chatIndex !== -1) {
                            // 找到并删除对应的消息
                            const messages = history[chatIndex].messages;
                            const messageIndex = messages.findIndex(msg => 
                                msg.type === messageType && 
                                msg.content.trim() === messageContent
                            );
                            
                            if (messageIndex !== -1) {
                                messages.splice(messageIndex, 1);
                                
                                // 如果删除后没有消息了
                                if (messages.length === 0) {
                                    // 删除整个对话
                                    history.splice(chatIndex, 1);
                                    localStorage.setItem('chatHistory', JSON.stringify(history));
                                    
                                    // 如果没有其他对话了
                                    if (history.length === 0) {
                                        currentChatId = Date.now().toString();
                                        saveNewChat('新对话');
                                        loadChatHistory();
                                        chatMessages.empty();
                                    } else {
                                        // 切换到最新的对话
                                        currentChatId = history[0].id;
                                        loadChat(currentChatId);
                                    }
                                } else {
                                    // 更新对话标题（如果删除的是第一条用户消息）
                                    if (messageType === 'user') {
                                        const firstUserMessage = messages.find(msg => msg.type === 'user');
                                        if (firstUserMessage) {
                                            history[chatIndex].title = firstUserMessage.content.substring(0, 30) + 
                                                (firstUserMessage.content.length > 30 ? '...' : '');
                                        }
                                    }
                                    
                                    // 保存更新后的历史记录
                                    history[chatIndex].messages = messages;
                                    localStorage.setItem('chatHistory', JSON.stringify(history));
                                    
                                    // 从DOM中移除消息
                                    messageWrapper.remove();
                                    
                                    // 重新加载历史记录以更新标题
                                    loadChatHistory();
                                }
                            }
                        }
                    } catch (error) {
                        console.error('删除消息时发生错误:', error);
                        alert('删除消息时发生错误：' + error.message);
                    }
                }
            });

            // 自动调整输入框高度
            chatInput.on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // 添加发送按钮点击事件
            $('.send-button').on('click', function(e) {
                e.preventDefault();
                sendMessage();
            });

            // 添加回车发送功能
            chatInput.on('keydown', function(e) {
                if (e.keyCode === 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // 添加错误处理
            window.onerror = function(msg, url, lineNo, columnNo, error) {
                // 忽略 TBUI 相关的错误
                if (msg.includes('TBUI')) {
                    return true;
                }
                console.error('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo + '\nColumn: ' + columnNo + '\nError object: ' + JSON.stringify(error));
                return false;
            };

            // 初始化时检查 localStorage 是否可用
            try {
                localStorage.setItem('test', 'test');
                localStorage.removeItem('test');
            } catch (e) {
                console.error('localStorage is not available:', e);
                alert('您的浏览器不支持本地存储，部分功能可能无法使用。');
            }

            // 添加用户菜单交互
            $('.user-info').on('click', function(e) {
                e.stopPropagation();
                $('.user-menu').toggleClass('show');
            });

            // 点击其他地方关闭菜单
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.user-info').length) {
                    $('.user-menu').removeClass('show');
                }
            });

            // 设置对话框相关功能
            const settingsDialog = $('#settingsDialog');
            const modelList = $('#modelList');
            const loadingIndicator = $('#loadingIndicator');

            // 打开设置对话框
            $('.settings-btn').on('click', function(e) {
                e.preventDefault();
                settingsDialog.show();
                loadModels();
            });

            // 关闭设置对话框
            $('.close-settings').on('click', function() {
                settingsDialog.hide();
            });

            // 点击对话框外部关闭
            settingsDialog.on('click', function(e) {
                if (e.target === this) {
                    settingsDialog.hide();
                }
            });

            // 修改API配置相关功能
            const apiEndpointInput = $('#apiEndpoint');
            const testApiButton = $('#testApiConfig');
            const saveApiButton = $('#saveApiConfig');
            const testResult = $('#testResult');
            
            // 初始化时从后端获取 API 地址
            fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_ollama_endpoint'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    apiEndpointInput.val(data.data);
                }
            })
            .catch(error => {
                console.error('获取API地址失败:', error);
            });
            
            // 修改测试按钮点击事件
            testApiButton.on('click', async function() {
                const apiUrl = apiEndpointInput.val().trim();
                if (!apiUrl) {
                    alert('请输入API地址');
                    return;
                }
                
                const button = $(this);
                button.prop('disabled', true).text('测试中...');
                testResult.hide();
                
                try {
                    // 更新后端API地址
                    const updateResponse = await fetch('/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=update_ollama_endpoint&endpoint=' + encodeURIComponent(apiUrl)
                    });

                    if (!updateResponse.ok) {
                        throw new Error('更新API地址失败');
                    }

                    // 测试连接
                    const response = await fetch('/wp-json/ollama/v1/tags', {
                        headers: {
                            'Accept': 'application/json',
                            'X-WP-Nonce': wpNonce
                        },
                        credentials: 'same-origin'
                    });

                    const data = await response.json();
                    
                    if (!response.ok) {
                        throw new Error(data.message || `服务器返回错误: ${response.status}`);
                    }
                    
                    if (data && Array.isArray(data.models)) {
                        testResult.html(`
                            <div style="color: #52c41a;">
                                <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" height="16" width="16" style="display: inline-block; vertical-align: middle;">
                                    <path d="M20 6L9 17l-5-5"></path>
                                </svg>
                                连接成功！发现 ${data.models.length} 个模型
                            </div>
                        `).css('background', '#f6ffed').show();
                        
                        saveApiButton.prop('disabled', false);
                        loadModels();
                    } else {
                        throw new Error('API返回的数据格式不正确');
                    }
                } catch (error) {
                    console.error('测试API连接失败:', error);
                    testResult.html(`
                        <div style="color: #ff4d4f;">
                            <strong>连接失败</strong><br>
                            ${error.message.split('\n').join('<br>')}
                        </div>
                    `).css('background', '#fff2f0').show();
                    saveApiButton.prop('disabled', true);
                } finally {
                    button.prop('disabled', false).text('测试连接');
                }
            });
            
            // 修改保存API配置函数
            saveApiButton.on('click', async function() {
                const newEndpoint = apiEndpointInput.val().trim();
                if (!newEndpoint) {
                    alert('请输入API地址');
                    return;
                }
                
                const button = $(this);
                button.prop('disabled', true).text('保存中...');
                
                try {
                    // 更新后端API地址
                    const updateResponse = await fetch('/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=update_ollama_endpoint&endpoint=' + encodeURIComponent(newEndpoint)
                    });

                    if (!updateResponse.ok) {
                        throw new Error('更新API地址失败');
                    }

                    const result = await updateResponse.json();
                    if (!result.success) {
                        throw new Error(result.data || '保存失败');
                    }

                    testResult.html(`
                        <div style="color: #52c41a;">
                            <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" height="16" width="16" style="display: inline-block; vertical-align: middle;">
                                <path d="M20 6L9 17l-5-5"></path>
                            </svg>
                            配置已保存
                        </div>
                    `).css('background', '#f6ffed').show();
                    
                    // 重新加载模型列表
                    await loadModels();
                } catch (error) {
                    console.error('保存API配置失败:', error);
                    testResult.html(`
                        <div style="color: #ff4d4f;">
                            <strong>保存失败</strong><br>
                            ${error.message}
                        </div>
                    `).css('background', '#fff2f0').show();
                } finally {
                    button.prop('disabled', false).text('保存配置');
                }
            });

            // 修改拉取新模型函数
            $(document).on('click', '.pull-model', async function() {
                const modelName = $('#newModelName').val().trim();
                if (!modelName) {
                    alert('请输入模型名称');
                    return;
                }

                const button = $(this);
                button.prop('disabled', true).text('拉取中...');
                
                try {
                    // 获取nonce
                    const wpNonce = '<?php echo wp_create_nonce("wp_rest"); ?>';
                    
                    const response = await fetch('/wp-json/ollama/v1/pull', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-WP-Nonce': wpNonce
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ name: modelName })
                    });

                    if (!response.ok) {
                        if (response.status === 401) {
                            throw new Error('认证失败，请刷新页面重试');
                        }
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();
                    if (result.error) {
                        throw new Error(result.error);
                    }

                    alert('模型拉取成功！');
                    await loadModels();
                } catch (error) {
                    console.error('拉取模型失败:', error);
                    let errorMessage = '拉取模型失败：';
                    if (error.message.includes('认证失败')) {
                        errorMessage = error.message;
                    } else {
                        errorMessage += error.message;
                    }
                    alert(errorMessage);
                } finally {
                    button.prop('disabled', false).text('拉取新模型');
                }
            });

            // 修改删除模型函数
            $(document).on('click', '.delete-model', async function() {
                const modelItem = $(this).closest('.model-item');
                const modelName = modelItem.data('model');

                if (confirm(`确定要删除模型 ${modelName} 吗？`)) {
                    const button = $(this);
                    button.prop('disabled', true);
                    
                    try {
                        // 获取nonce
                        const wpNonce = '<?php echo wp_create_nonce("wp_rest"); ?>';
                        
                        const response = await fetch('/wp-json/ollama/v1/delete', {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-WP-Nonce': wpNonce
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ name: modelName })
                        });

                        if (!response.ok) {
                            if (response.status === 401) {
                                throw new Error('认证失败，请刷新页面重试');
                            }
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const result = await response.json();
                        if (result.error) {
                            throw new Error(result.error);
                        }

                        alert('模型删除成功！');
                        await loadModels();
                    } catch (error) {
                        console.error('删除模型失败:', error);
                        let errorMessage = '删除模型失败：';
                        if (error.message.includes('认证失败')) {
                            errorMessage = error.message;
                        } else {
                            errorMessage += error.message;
                        }
                        alert(errorMessage);
                        button.prop('disabled', false);
                    }
                }
            });

            // 修改加载模型列表函数
            async function loadModels() {
                try {
                    modelList.empty();
                    loadingIndicator.addClass('show');
                    
                    // 获取当前用户ID
                    const currentUserId = '<?php echo get_current_user_id(); ?>';
                    
                    console.log('开始加载模型列表...');
                    
                    // 首先获取后端API地址
                    const backendEndpoint = await fetch('/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=get_ollama_endpoint'
                    }).then(res => res.json());
                    
                    if (!backendEndpoint.success) {
                        throw new Error('获取API地址失败：' + backendEndpoint.data);
                    }
                    
                    const response = await fetch('/wp-json/ollama/v1/tags', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': wpNonce
                        },
                        credentials: 'same-origin'
                    });

                    const data = await response.json();
                    
                    if (!response.ok) {
                        throw new Error(data.message || `服务器返回错误: ${response.status}`);
                    }
                    
                    if (data && Array.isArray(data.models)) {
                        // 使用用户特定的存储键
                        const currentModel = localStorage.getItem(`selectedModel_${currentUserId}`) || data.models[0]?.name;
                        
                        if (data.models.length === 0) {
                            modelList.append(`
                                <div style="padding: 12px; text-align: center; color: #666;">
                                    <div>没有找到任何模型</div>
                                    ${isAdmin ? `
                                        <div style="margin-top: 10px;">
                                            <button class="model-button primary pull-default-model">拉取默认模型</button>
                                        </div>
                                    ` : ''}
                                </div>
                            `);
                        } else {
                            data.models.forEach(model => {
                                const modelItem = $(`
                                    <div class="model-item" data-model="${model.name}">
                                        <div class="model-info">
                                            <div class="model-name">${model.name}</div>
                                            ${isAdmin ? `
                                                <div class="model-status">
                                                    ${formatSize(model.size)} | 
                                                    参数量: ${model.details?.parameter_size || '未知'} | 
                                                    量化: ${model.details?.quantization_level || '未知'}
                                                </div>
                                            ` : ''}
                                        </div>
                                        <div class="model-actions">
                                            <button class="model-button primary select-model ${model.name === currentModel ? 'active' : ''}" 
                                                ${model.name === currentModel ? 'disabled' : ''}>
                                                ${model.name === currentModel ? '当前使用' : '使用此模型'}
                                            </button>
                                            ${isAdmin ? `
                                                <button class="model-button danger delete-model">删除</button>
                                            ` : ''}
                                        </div>
                                    </div>
                                `);
                                modelList.append(modelItem);
                            });
                        }

                        // 只有管理员可以看到添加新模型的部分
                        if (isAdmin) {
                            modelList.append(`
                                <div class="model-item" style="border-top: 1px dashed #e0e0e0;">
                                    <div class="model-info" style="width: 100%;">
                                        <div style="margin-bottom: 8px;">
                                            <input type="text" id="newModelName" placeholder="输入模型名称" 
                                                style="width: 100%; padding: 8px; border: 1px solid #e0e0e0; border-radius: 4px;">
                                        </div>
                                        <div style="display: flex; gap: 8px;">
                                            <button class="model-button primary pull-model">拉取新模型</button>
                                        </div>
                                    </div>
                                </div>
                            `);
                        }
                    } else {
                        throw new Error('API返回的数据格式不正确');
                    }
                } catch (error) {
                    console.error('加载模型列表失败:', error);
                    modelList.html(`
                        <div style="padding: 12px; color: #ff4d4f;">
                            <strong>加载失败</strong><br>
                            ${error.message.split('\n').join('<br>')}
                            ${!error.message.includes('Ollama服务器') ? '<br><br>如果问题持续存在，请联系管理员' : ''}
                        </div>
                    `);
                } finally {
                    loadingIndicator.removeClass('show');
                }
            }

            // 添加格式化文件大小的函数
            function formatSize(bytes) {
                if (typeof bytes !== 'number') return '未知大小';
                const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                if (bytes === 0) return '0 Bytes';
                const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
            }

            // 修改模型选择事件处理
            $(document).on('click', '.select-model:not([disabled])', async function() {
                const modelItem = $(this).closest('.model-item');
                const modelName = modelItem.data('model');
                const currentUserId = '<?php echo get_current_user_id(); ?>';
                
                try {
                    // 使用用户特定的存储键保存选中的模型
                    localStorage.setItem(`selectedModel_${currentUserId}`, modelName);
                    
                    // 更新所有模型按钮状态
                    $('.select-model').prop('disabled', false).text('使用此模型').removeClass('active');
                    $(this).prop('disabled', true).text('当前使用').addClass('active');
                    
                    // 显示成功提示
                    const successMessage = $(`
                        <div style="position: fixed; top: 20px; right: 20px; background: #f6ffed; 
                                border: 1px solid #b7eb8f; padding: 10px 20px; border-radius: 4px; 
                                box-shadow: 0 2px 8px rgba(0,0,0,0.1); z-index: 1000;">
                            <div style="color: #52c41a; display: flex; align-items: center; gap: 8px;">
                                <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" height="16" width="16">
                                    <path d="M20 6L9 17l-5-5"></path>
                                </svg>
                                <span>已切换到模型: ${modelName}</span>
                            </div>
                        </div>
                    `);
                    
                    $('body').append(successMessage);
                    setTimeout(() => successMessage.fadeOut('slow', function() {
                        $(this).remove();
                    }), 3000);
                    
                } catch (error) {
                    console.error('切换模型失败:', error);
                    alert('切换模型失败: ' + error.message);
                }
            });

            // 修改用户信息显示
            const userInfo = $('.user-info');
            const userName = '<?php echo esc_js(wp_get_current_user()->display_name); ?>';
            if (userName) {
                userInfo.find('span').text(userName);
            }

            // 添加清理记录功能的JavaScript代码
            $('#clearHistory').on('click', function() {
                if (confirm('确定要清除所有聊天记录吗？此操作不可恢复。')) {
                    try {
                        const currentUserId = '<?php echo get_current_user_id(); ?>';
                        const selectedModel = localStorage.getItem(`selectedModel_${currentUserId}`);
                        
                        // 清除本地存储中的聊天记录
                        localStorage.removeItem('chatHistory');
                        
                        // 保留用户的模型选择
                        if (selectedModel) {
                            localStorage.setItem(`selectedModel_${currentUserId}`, selectedModel);
                        }
                        
                        // 重置当前聊天ID
                        currentChatId = Date.now().toString();
                        
                        // 清空聊天消息区域
                        chatMessages.empty();
                        
                        // 创建新的空对话
                        saveNewChat('新对话');
                        
                        // 重新加载历史记录列表
                        loadChatHistory();
                        
                        // 显示成功提示
                        const successMessage = $(`
                            <div style="position: fixed; top: 20px; right: 20px; background: #f6ffed; 
                                    border: 1px solid #b7eb8f; padding: 10px 20px; border-radius: 4px; 
                                    box-shadow: 0 2px 8px rgba(0,0,0,0.1); z-index: 1000;">
                                <div style="color: #52c41a; display: flex; align-items: center; gap: 8px;">
                                    <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" height="16" width="16">
                                        <path d="M20 6L9 17l-5-5"></path>
                                    </svg>
                                    <span>所有聊天记录已清除</span>
                                </div>
                            </div>
                        `);
                        
                        $('body').append(successMessage);
                        setTimeout(() => successMessage.fadeOut('slow', function() {
                            $(this).remove();
                        }), 3000);
                        
                        // 关闭设置对话框
                        $('#settingsDialog').hide();
                        
                    } catch (error) {
                        console.error('清除聊天记录时发生错误:', error);
                        alert('清除聊天记录失败: ' + error.message);
                    }
                }
            });

            // 添加推理过程切换功能
            $(document).on('click', '.toggle-reasoning', function(e) {
                e.preventDefault();
                const reasoningSection = $(this).closest('.reasoning-section');
                reasoningSection.toggleClass('collapsed');
            });

            // 初始化时默认展开所有推理过程
            $(document).ready(function() {
                $('.reasoning-section').removeClass('collapsed');
            });

            // 添加用户模型初始化检查函数
            async function checkUserModelSettings() {
                const currentUserId = '<?php echo get_current_user_id(); ?>';
                if (!localStorage.getItem(`selectedModel_${currentUserId}`)) {
                    try {
                        const response = await fetch('/wp-json/ollama/v1/tags', {
                            headers: {
                                'Accept': 'application/json',
                                'X-WP-Nonce': wpNonce
                            },
                            credentials: 'same-origin'
                        });
                        
                        const data = await response.json();
                        if (data && Array.isArray(data.models) && data.models.length > 0) {
                            localStorage.setItem(`selectedModel_${currentUserId}`, data.models[0].name);
                        } else {
                            localStorage.setItem(`selectedModel_${currentUserId}`, 'llama2');
                        }
                    } catch (error) {
                        console.error('初始化用户模型设置失败:', error);
                        localStorage.setItem(`selectedModel_${currentUserId}`, 'llama2');
                    }
                }
            }

            // 在页面加载时调用
            $(document).ready(function() {
                checkUserModelSettings();
                // ... 其他初始化代码 ...
            });

            // 添加错误处理函数
            function handleError(error, context) {
                console.error(`${context}:`, error);
                const errorMessage = error.message || '发生未知错误';
                const userMessage = current_user_can('manage_options') ? 
                    `${context}: ${errorMessage}` : 
                    '操作失败，请稍后重试或联系管理员';
                
                alert(userMessage);
            }

            // 添加生成随机数据的辅助函数
            function generateRandomData() {
                const data = [];
                const rows = 10;
                
                for (let i = 0; i < rows; i++) {
                    data.push({
                        integer: Math.floor(Math.random() * 100),
                        float: (Math.random() * 10).toFixed(2),
                        letter: String.fromCharCode(65 + Math.floor(Math.random() * 26)),
                        date: new Date(2025, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28) + 1)
                            .toISOString().split('T')[0]
                    });
                }
                
                return data;
            }
        });
        </script>
        <?php wp_footer(); ?>
    </body>
    </html>
    <?php
}

// 添加自定义路由处理
function custom_ai_chat_endpoint() {
    if ($_SERVER['REQUEST_URI'] == '/inoo-panel') {
        if (!is_user_logged_in()) {
            auth_redirect();
            exit;
        }
        ai_chat_render_page();
        exit;
    }
}
add_action('parse_request', 'custom_ai_chat_endpoint');

// 添加 API 路由处理
add_action('rest_api_init', function () {
    register_rest_route('ollama/v1', '/tags', array(
        'methods' => 'GET',
        'callback' => 'proxy_ollama_tags',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));

    register_rest_route('ollama/v1', '/generate', array(
        'methods' => 'POST',
        'callback' => 'proxy_ollama_generate',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));

    register_rest_route('ollama/v1', '/pull', array(
        'methods' => 'POST',
        'callback' => 'proxy_ollama_pull',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ));

    register_rest_route('ollama/v1', '/delete', array(
        'methods' => 'DELETE',
        'callback' => 'proxy_ollama_delete',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ));

    register_rest_route('ollama/v1', '/search-articles', array(
        'methods' => 'POST',
        'callback' => 'search_site_articles',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));
});

// 添加AJAX处理函数
add_action('wp_ajax_get_ollama_endpoint', function() {
    $api_url = get_option('ollama_api_endpoint', 'http://localhost:11434');
    
    if (empty($api_url)) {
        wp_send_json_error('API地址未配置');
        return;
    }

    // 检查API地址格式
    if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
        wp_send_json_error('API地址格式无效');
        return;
    }

    // 检查API是否可访问
    $response = wp_remote_get($api_url . '/api/tags');
    if (is_wp_error($response)) {
        if (current_user_can('manage_options')) {
            // 管理员可以看到详细错误信息
            wp_send_json_error('API连接失败：' . $response->get_error_message());
        } else {
            // 普通用户只看到简单提示
            wp_send_json_error('API服务暂时不可用，请联系管理员');
        }
        return;
    }

    wp_send_json_success($api_url);
});

// 修改代理函数
function proxy_ollama_request($endpoint, $method = 'GET', $body = null) {
    $api_url = get_option('ollama_api_endpoint', 'http://localhost:11434');
    $api_url = rtrim($api_url, '/');
    
    // 检查API地址是否有效
    if (empty($api_url)) {
        return new WP_REST_Response(array(
            'error' => true,
            'message' => current_user_can('manage_options') ? 
                'API地址未配置，请在设置中配置正确的API地址' : 
                'API服务暂时不可用，请联系管理员'
        ), 500);
    }

    // 检查API地址格式
    if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
        return new WP_REST_Response(array(
            'error' => true,
            'message' => current_user_can('manage_options') ? 
                "API地址格式无效: $api_url" : 
                'API服务配置有误，请联系管理员'
        ), 400);
    }

    $args = array(
        'method' => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ),
        'timeout' => 120,
        'sslverify' => false,
        'blocking' => true,
        'redirection' => 5,
        'httpversion' => '1.1',
        'cookies' => array()
    );

    if ($body) {
        $args['body'] = json_encode($body);
    }

    // 记录请求信息（仅管理员可见）
    if (current_user_can('manage_options')) {
        error_log("Ollama API Request - URL: $api_url$endpoint, Method: $method");
    }

    $response = wp_remote_request("$api_url$endpoint", $args);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        $error_code = $response->get_error_code();
        
        if (current_user_can('manage_options')) {
            error_log("Ollama API Error - Code: $error_code, Message: $error_message");
        }
        
        // 根据用户权限返回不同的错误信息
        if (current_user_can('manage_options')) {
            $message = "无法连接到Ollama服务器 ($api_url)，请检查：\n" .
                      "1. Ollama服务是否已启动\n" .
                      "2. API地址是否正确\n" .
                      "3. 服务器防火墙设置\n" .
                      "4. 网络连接是否正常";
        } else {
            $message = "API服务暂时不可用，请稍后重试或联系管理员";
        }
        
        return new WP_REST_Response(array(
            'error' => true,
            'message' => $message
        ), 503);
    }

    $status = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($status !== 200) {
        $error_body = json_decode($body, true);
        $error_message = isset($error_body['error']) ? $error_body['error'] : '未知错误';
        
        if (current_user_can('manage_options')) {
            error_log("Ollama API Error Response - Status: $status, Body: " . print_r($error_body, true));
        }
        
        return new WP_REST_Response(array(
            'error' => true,
            'message' => current_user_can('manage_options') ? 
                "Ollama服务器返回错误:\n状态码: $status\n错误信息: $error_message" :
                "服务暂时不可用，请稍后重试"
        ), $status);
    }

    return json_decode($body, true);
}

// 修改代理各个 API 端点的函数
function proxy_ollama_tags() {
    return proxy_ollama_request('/api/tags');
}

function proxy_ollama_generate($request) {
    $params = $request->get_json_params();
    return proxy_ollama_request('/api/generate', 'POST', $params);
}

function proxy_ollama_pull($request) {
    $params = $request->get_json_params();
    return proxy_ollama_request('/api/pull', 'POST', $params);
}

function proxy_ollama_delete($request) {
    $params = $request->get_json_params();
    return proxy_ollama_request('/api/delete', 'DELETE', $params);
}

// 添加设置页面
function ollama_settings_init() {
    register_setting('ollama_settings', 'ollama_api_endpoint');
    
    add_settings_section(
        'ollama_settings_section',
        'Ollama API 设置',
        null,
        'ollama_settings'
    );
    
    add_settings_field(
        'ollama_api_endpoint',
        'API 地址',
        'ollama_api_endpoint_callback',
        'ollama_settings',
        'ollama_settings_section'
    );

    // 添加文章收集开关设置
    register_setting('ollama_settings', 'ollama_collect_posts', array(
        'type' => 'boolean',
        'default' => false
    ));
    
    add_settings_field(
        'ollama_collect_posts',
        '收集网站文章',
        'ollama_collect_posts_callback',
        'ollama_settings',
        'ollama_settings_section'
    );
}
add_action('admin_init', 'ollama_settings_init');

function ollama_api_endpoint_callback() {
    $value = get_option('ollama_api_endpoint', 'http://localhost:11434');
    echo '<input type="text" name="ollama_api_endpoint" value="' . esc_attr($value) . '" style="width: 300px;">';
}

// 添加开关回调函数
function ollama_collect_posts_callback() {
    $value = get_option('ollama_collect_posts', false);
    echo '<label><input type="checkbox" name="ollama_collect_posts" value="1" ' . checked(1, $value, false) . '> 启用文章收集和搜索功能</label>';
    echo '<p class="description">启用后，AI 将能够搜索和引用网站内的文章内容</p>';
}

// 添加AJAX处理函数
add_action('wp_ajax_update_ollama_endpoint', 'handle_update_ollama_endpoint');
function handle_update_ollama_endpoint() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('权限不足');
        return;
    }

    $endpoint = isset($_POST['endpoint']) ? esc_url_raw($_POST['endpoint']) : '';
    if (empty($endpoint)) {
        wp_send_json_error('API地址不能为空');
        return;
    }

    // 删除可能存在的旧值
    delete_option('ollama_api_endpoint');
    
    // 添加新值
    if (add_option('ollama_api_endpoint', $endpoint, '', 'no') || update_option('ollama_api_endpoint', $endpoint)) {
        wp_send_json_success('API地址已更新');
    } else {
        wp_send_json_error('API地址更新失败');
    }
}

// 添加获取API地址的AJAX处理函数
add_action('wp_ajax_get_ollama_endpoint', 'handle_get_ollama_endpoint');
add_action('wp_ajax_nopriv_get_ollama_endpoint', 'handle_get_ollama_endpoint');
function handle_get_ollama_endpoint() {
    $endpoint = get_option('ollama_api_endpoint', 'http://localhost:11434');
    wp_send_json_success($endpoint);
}

// 文章搜索处理函数
function search_site_articles($request) {
    $params = $request->get_json_params();
    $query = isset($params['query']) ? sanitize_text_field($params['query']) : '';
    
    if (empty($query)) {
        return new WP_REST_Response(array(
            'error' => true,
            'message' => '搜索关键词不能为空'
        ), 400);
    }
    
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        's' => $query,
    );
    
    $search_query = new WP_Query($args);
    $results = array();
    
    if ($search_query->have_posts()) {
        while ($search_query->have_posts()) {
            $search_query->the_post();
            $results[] = array(
                'title' => get_the_title(),
                'excerpt' => wp_trim_words(get_the_excerpt(), 50),
                'url' => get_permalink(),
                'date' => get_the_date()
            );
        }
    }
    
    wp_reset_postdata();
    
    return new WP_REST_Response(array(
        'success' => true,
        'results' => $results
    ));
}

// 修改现有的 AJAX 处理函数
add_action('wp_ajax_update_ollama_settings', 'handle_update_ollama_settings');
function handle_update_ollama_settings() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('权限不足');
        return;
    }

    $endpoint = isset($_POST['endpoint']) ? esc_url_raw($_POST['endpoint']) : '';

    if (empty($endpoint)) {
        wp_send_json_error('API地址不能为空');
        return;
    }

    // 删除可能存在的旧值
    delete_option('ollama_api_endpoint');
    
    // 添加新值
    if (add_option('ollama_api_endpoint', $endpoint, '', 'no') || update_option('ollama_api_endpoint', $endpoint)) {
        wp_send_json_success('设置已更新');
    } else {
        wp_send_json_error('设置更新失败');
    }
}
