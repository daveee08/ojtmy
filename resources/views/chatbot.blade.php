<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="chatbot-container">
    <button class="chatbot-toggle" title="Chat with CK Virtual Tutor">
        <i class="fas fa-robot"></i>
    </button>
    <div class="chatbot-sidebar">
        <div class="chatbot-header">
            <span>Ask CK Virtual Tutors</span>
            <button class="chatbot-close" title="Close Chat">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="chatbot-body">
            <p>👋 Hi there! How can I assist you today?</p>
        </div>
        <div class="chatbot-footer">
            <input type="text" placeholder="Type your question...">
            <button><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<style>
    .chatbot-container {
        position: fixed;
        top: 0;
        right: 0;
        height: 100vh;
        z-index: 1000;
    }

    .chatbot-toggle {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #E91E63;
        color: white;
        border: none;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        font-size: 26px;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        transition: background 0.3s, transform 0.2s;
        z-index: 1001;
    }

    .chatbot-toggle:hover {
        background: #d81b60;
        transform: scale(1.05);
    }

    .chatbot-sidebar {
        position: fixed;
        top: 75px;
        right: -420px;
        width: 400px;
        height: calc(100vh - 75px);
        background: #fff;
        border-left: 1px solid #ddd;
        box-shadow: -2px 0 15px rgba(0, 0, 0, 0.1);
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
        display: flex;
        flex-direction: column;
        transition: right 0.3s ease;
        z-index: 1000;
    }

    .chatbot-sidebar.open {
        right: 0;
    }

    .chatbot-header {
        background: linear-gradient(90deg, #e91e63, #ff4081);
        color: white;
        padding: 15px 50px 15px 20px;
        font-size: 18px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        border-top-left-radius: 12px;
    }

    .chatbot-close {
        background: transparent;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    .chatbot-close:hover {
        transform: scale(1.2);
    }

    .chatbot-body {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        font-size: 14.5px;
        color: #333;
    }

    .chatbot-footer {
        padding: 15px 20px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .chatbot-footer input {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid #ccc;
        border-radius: 20px;
        font-size: 14px;
        outline: none;
        transition: border 0.2s;
    }

    .chatbot-footer input:focus {
        border-color: #e91e63;
    }

    .chatbot-footer button {
        background: #E91E63;
        color: white;
        border: none;
        padding: 10px 14px;
        border-radius: 50%;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s, transform 0.2s;
    }

    .chatbot-footer button:hover {
        background: #d81b60;
        transform: scale(1.05);
    }
</style>

<script>
    const toggleButton = document.querySelector('.chatbot-toggle');
    const sidebar = document.querySelector('.chatbot-sidebar');
    const closeButton = document.querySelector('.chatbot-close');

    toggleButton.addEventListener('click', () => {
        sidebar.classList.add('open');
        toggleButton.style.display = 'none';
    });

    closeButton.addEventListener('click', () => {
        sidebar.classList.remove('open');
        toggleButton.style.display = 'block';
    });
</script>
