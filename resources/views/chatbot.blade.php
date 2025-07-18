<div class="chatbot-container">
    <button class="chatbot-toggle">💬</button>
    <div class="chatbot-window">
        <div class="chatbot-header">Ask CK Virtual Tutors</div>
        <div class="chatbot-body">
            <p>Welcome! How can I assist you today?</p>
        </div>
        <div class="chatbot-footer">
            <input type="text" placeholder="Type your question...">
            <button>Send</button>
        </div>
    </div>
</div>

<style>
.chatbot-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}
.chatbot-toggle {
    background: #E91E63; /* Matches your pink theme */
    color: white;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}
.chatbot-window {
    display: none;
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 300px;
    height: 400px;
    background: white;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    flex-direction: column;
}
.chatbot-header {
    background: #E91E63;
    color: white;
    padding: 10px;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    text-align: center;
}
.chatbot-body {
    flex: 1;
    padding: 10px;
    overflow-y: auto;
}
.chatbot-footer {
    padding: 10px;
    border-top: 1px solid #ccc;
    display: flex;
    gap: 5px;
}
.chatbot-footer input {
    flex: 1;
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 5px;
}
.chatbot-footer button {
    background: #E91E63;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
}
</style>

<script>
document.querySelector('.chatbot-toggle').addEventListener('click', () => {
    const window = document.querySelector('.chatbot-window');
    window.style.display = window.style.display === 'none' ? 'flex' : 'none';
});
</script>