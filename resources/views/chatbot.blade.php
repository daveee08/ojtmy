<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>CK Virtual Tutor</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #fdfdfd;
    }

    .chatbot-container {
      position: fixed;
      top: 0;
      right: 0;
      height: 100vh;
      z-index: 1000;
    }

    .chatbot-toggle,
    .quiz-toggle {
      position: fixed;
      bottom: 20px;
      background: #E91E63;
      color: white;
      border: none;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      font-size: 22px;
      cursor: pointer;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      z-index: 1001;
    }

    .chatbot-toggle { right: 20px; }
    .quiz-toggle { right: 80px; }

    .chatbot-sidebar {
      position: fixed;
      bottom: 10px;
      right: 30px;
      width: 340px;
      height: 530px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
      display: none;
      flex-direction: column;
      overflow: hidden;
      font-family: 'Segoe UI', sans-serif;
      z-index: 9999;
    }

    .chatbot-header {
      background: #E91E63;
      color: white;
      padding: 16px;
      font-size: 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .chatbot-body {
      flex: 1;
      padding: 15px;
      background: #fafafa;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .chatbot-body p {
      background: #f1f0f0;
      padding: 10px 14px;
      border-radius: 16px;
      max-width: 80%;
      font-size: 14px;
    }

    .chatbot-quick-replies-detached {
      display: flex;
      justify-content: center;
      gap: 8px;
      padding: 8px 12px;
      background: #fafafa;
      margin-top: -6px;
      margin-bottom: 4px;
    }

    .chatbot-quick-replies-detached button {
      background-color: #f1f1f1;
      border: none;
      padding: 8px 14px;
      border-radius: 20px;
      font-size: 13px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .chatbot-quick-replies-detached button:hover {
      background-color: #e0e0e0;
    }

    .chatbot-footer {
      padding: 12px;
      background: #fff;
      display: flex;
      align-items: center;
      gap: 10px;
      border-top: 1px solid #ddd;
      border-bottom-left-radius: 16px;
      border-bottom-right-radius: 16px;
    }

    .chatbot-footer input {
      flex: 1;
      padding: 8px 14px;
      border: 1px solid #ccc;
      border-radius: 20px;
      font-size: 14px;
    }

    .chatbot-footer button {
      background: #E91E63;
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 50%;
      font-size: 16px;
      cursor: pointer;
    }

    .emoji-btn {
      background: none;
      border: none;
      font-size: 18px;
      cursor: pointer;
      color: #E91E63;
    }

    .chatbot-close {
      background: transparent;
      border: none;
      color: white;
      font-size: 20px;
      cursor: pointer;
    }

    .chatbot-restart {
      background: transparent;
      border: none;
      color: white;
      font-size: 15px;
      cursor: pointer;
      margin-left: 75px;
    }

    .chatbot-quiz-section {
      position: fixed;
      top: 75px;
      right: -420px;
      width: 400px;
      height: calc(100vh - 70px);
      background: white;
      border-left: 1px solid #ccc;
      box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-direction: column;
      transition: right 0.3s ease;
      z-index: 1000;
    }

    .chatbot-quiz-section.open {
      right: 0;
    }

    .chatbot-header.quiz {
      background: #E91E63;
      color: white;
      padding: 15px;
      font-size: 18px;
      font-weight: bold;
      text-align: center;
      position: relative;
    }

    .chatbot-close-quiz {
      position: absolute;
      top: 10px;
      right: 15px;
      background: transparent;
      border: none;
      color: white;
      font-size: 20px;
      cursor: pointer;
    }

    .chatbot-body.quiz {
      flex: 1;
      padding: 15px;
      overflow-y: auto;
    }

    .chatbot-footer.quiz {
      padding: 15px;
      border-top: 1px solid #ccc;
      display: flex;
      gap: 10px;
    }

    .quiz-question-block {
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background: #fafafa;
    }

    .quiz-question-block input,
    .quiz-question-block select {
      width: 100%;
      margin-top: 6px;
      padding: 8px;
      font-size: 14px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    #submit-quiz-btn {
      flex: 1;
      background: #E91E63;
      color: white;
      border: none;
      padding: 10px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
    }

    #submit-quiz-btn:hover {
      background: #d81b60;
    }
  </style>
</head>
<body>

<div class="chatbot-container">
  <button class="chatbot-toggle" title="Chat with CK Virtual Tutor">
    <i class="fas fa-robot"></i>
  </button>
  <!-- <button class="quiz-toggle" title="Create Quiz">
    <i class="fas fa-pen"></i>
  </button> -->

  <div class="chatbot-sidebar" id="chatbot-panel">
    <div class="chatbot-header">
      <div style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-robot" style="color: white; font-size: 20px;"></i>
        <div>
          <div style="font-weight: 600;">CK Virtual Tutor</div>
          <div style="font-size: 12px;">
            <span style="color: #4CAF50;">●</span> <span style="color: #fff;">Online</span>
          </div>

        </div>
      </div>
      <button class="chatbot-restart" title="Restart Chat">
  <i class="fas fa-arrows-rotate"></i> <!-- or fa-arrows-rotate -->
</button>

      <button class="chatbot-close" title="Close Chat"><i class="fas fa-times"></i></button>
    </div>

    <div class="chatbot-body" id="chatbot-body">
      <p>Hi there! 👋 Would you like help with your lesson?</p>
    </div>

    <div class="chatbot-quick-replies-detached" id="quick-replies">
      <button onclick="handleQuickReply('Yes, sure!')">Yes, sure!</button>
      <button onclick="handleQuickReply('No, thanks!')">No, thanks!</button>
      <button onclick="handleQuickReply('Explain again')">Explain again</button>
    </div>

    <div class="chatbot-footer">
      <button class="emoji-btn"><i class="far fa-smile"></i></button>
      <input type="text" id="chat-input" placeholder="Type your question...">
      <button id="send-chat"><i class="fas fa-paper-plane"></i></button>
    </div>
  </div>

  <div class="chatbot-quiz-section">
    <div class="chatbot-header quiz">
      Create a Quiz
      <button class="chatbot-close-quiz"><i class="fas fa-times"></i></button>
    </div>
    <div class="chatbot-body quiz" id="quiz-body">
      <!-- Quiz fields go here -->
    </div>
    <div class="chatbot-footer quiz">
      <button id="submit-quiz-btn"><i class="fas fa-paper-plane"></i> Submit</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>

  let sessionId = null;
  const toggleButton = document.querySelector('.chatbot-toggle');
  const chatbotPanel = document.getElementById('chatbot-panel');
  const closeChatButton = document.querySelector('.chatbot-close');
  const restartChatButton = document.querySelector('.chatbot-restart');
  const chatInput = document.getElementById('chat-input');
  const chatBody = document.getElementById('chatbot-body');
  const sendChatBtn = document.getElementById('send-chat');
  const quickReplies = document.getElementById('quick-replies');

  toggleButton.addEventListener('click', () => {
    chatbotPanel.style.display = 'flex';
    toggleButton.style.display = 'none';
    quizToggle.style.display = 'none';
  });

  closeChatButton.addEventListener('click', () => {
    chatbotPanel.style.display = 'none';
    toggleButton.style.display = 'block';
    quizToggle.style.display = 'block';
  });

sendChatBtn.addEventListener('click', async () => {
  const msg = chatInput.value.trim();
  if (!msg) return;

  appendUserMessage(msg);
  chatInput.value = '';
  quickReplies.style.display = 'none';

  const params = new URLSearchParams(window.location.search);
  const book_id = params.get('book_id');
  const unit_id = params.get('unit_id');
  const chapter_id = params.get('chapter_id');
  const lesson_id = params.get('lesson_id');

  // 👇 Typing message setup
  const typingIndicator = document.createElement('p');
  typingIndicator.style.fontStyle = "italic";
  typingIndicator.style.opacity = 0.7;
  typingIndicator.style.alignSelf = 'flex-start';
  typingIndicator.style.background = '#f1f8e9';
  typingIndicator.style.padding = '8px 12px';
  typingIndicator.style.borderRadius = '8px';
  typingIndicator.style.margin = '4px 0';
  typingIndicator.style.maxWidth = '75%';
  typingIndicator.textContent = "🤖 Typing.";
  chatBody.appendChild(typingIndicator);
  chatBody.scrollTop = chatBody.scrollHeight;

  // 👇 Typing animation loop
  const dots = ["Typing.", "Typing..", "Typing..."];
  let dotIndex = 0;
  const typingInterval = setInterval(() => {
    typingIndicator.textContent = `🤖 ${dots[dotIndex++ % dots.length]}`;
  }, 500);

  try {
    const query = `?book_id=${book_id}&unit_id=${unit_id}&chapter_id=${chapter_id}&lesson_id=${lesson_id}`;
    const response = await fetch('/send-rag-message' + query, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        prompt: msg,
        session_id: sessionId || null
      })
    });

    const data = await response.json();

    clearInterval(typingInterval);
    typingIndicator.remove();

    if (data.status === 'success') {
      const mark_message = marked.parse(data.response);
      appendBotMessage(mark_message);

      if (data.session_id) {
      sessionId = data.session_id;
  }
    } else {
      appendBotMessage("⚠️ Failed to get a response.");
      console.error(data.error || data.message);
    }
  } catch (err) {
    clearInterval(typingInterval);
    typingIndicator.remove();
    appendBotMessage("⚠️ An error occurred.");
    console.error(err);
  }
});




  chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendChatBtn.click();
    }
  });






  function handleQuickReply(text) {
    appendUserMessage(text);
    quickReplies.style.display = 'none';
  }

  function appendUserMessage(msg) {
    const userMsg = document.createElement('p');
    userMsg.textContent = msg;
    userMsg.style.background = '#e0f7fa';
    userMsg.style.alignSelf = 'flex-end';
    chatBody.appendChild(userMsg);
    chatBody.scrollTop = chatBody.scrollHeight;
  }

//   function appendBotMessage(msg) {
//   const botMsg = document.createElement('p');
//   // botMsg.textContent = msg;
//   botMsg.innerHTML = msg;
//   botMsg.style.background = '#f1f8e9';       // light green background for bot
//   botMsg.style.alignSelf = 'flex-start';     // align to left
//   botMsg.style.padding = '8px 12px';
//   botMsg.style.borderRadius = '8px';
//   botMsg.style.margin = '4px 0';
//   botMsg.style.maxWidth = '75%';

//   chatBody.appendChild(botMsg);
//   chatBody.scrollTop = chatBody.scrollHeight;
// }

function appendBotMessage(msg) {
  const botMsg = document.createElement('p');
  if (msg === "🤖 Typing...") {
    botMsg.style.fontStyle = "italic";
    botMsg.style.opacity = 0.7;
  }

  botMsg.innerHTML = msg;
  botMsg.style.background = '#f1f8e9';
  botMsg.style.alignSelf = 'flex-start';
  botMsg.style.padding = '8px 12px';
  botMsg.style.borderRadius = '8px';
  botMsg.style.margin = '4px 0';
  botMsg.style.maxWidth = '75%';

  chatBody.appendChild(botMsg);
  chatBody.scrollTop = chatBody.scrollHeight;
  return botMsg;
}
// document.getElementById('restart-chat').addEventListener('click', () => {
//   // Clear messages
//   chatBody.innerHTML = '';

//   // Show greeting again
//   const greeting = document.createElement('p');
//   greeting.textContent = "Hi there! 👋 Would you like help with your lesson?";
//   chatBody.appendChild(greeting);
//   chatBody.scrollTop = chatBody.scrollHeight;

//   // Show quick replies
//   quickReplies.style.display = 'flex';

//   // ✅ Reset session
//   sessionId = null;
// });

restartChatButton.addEventListener('click', () => {
  // Clear messages
  chatBody.innerHTML = '';

  // Show greeting again
  const greeting = document.createElement('p');
  greeting.textContent = "Hi there! 👋 Would you like help with your lesson?";
  chatBody.appendChild(greeting);
  chatBody.scrollTop = chatBody.scrollHeight;

  // Show quick replies
  quickReplies.style.display = 'flex';

  // ✅ Reset session
  sessionId = null;
});

</script>

</body>
</html>
