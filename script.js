// Get the chat container element
const chatContainer = document.getElementById('chat-container');

// Initialize message list
const messagesList = document.getElementById('messages');
messagesList.innerHTML = '';

// Set up event listener for sending user input
document.querySelector('button[type="submit"]').addEventListener('click', sendMsg);

function sendMsg() {
    // Get user input
    const msgInput = document.getElementById('message-input');
    const userInput = msgInput.value.trim();

    // Send request to server-side PHP script to handle user input and generate response
    fetch('<?php echo $_SERVER['PHP_SELF']; ?>?send=true', {
        method: 'POST',
        body: JSON.stringify({ message: userInput }),
    })
    .then(response => response.json())
    .then(data => {
        // Update message list with new response
        const newMessage = document.createElement('li');
        newMessage.textContent = data.response;
        messagesList.appendChild(newMessage);
        msgInput.value = '';
    });
}

// Set up event listener for retrieving new messages from server-side PHP script
setInterval(() => {
    fetch('<?php echo $_SERVER['PHP_SELF']; ?>?new_messages=true', {
        method: 'GET',
    })
    .then(response => response.json())
    .then(data => {
        // Update message list with new responses
        data.responses.forEach(response => {
            const newMessage = document.createElement('li');
            newMessage.textContent = response;
            messagesList.appendChild(newMessage);
        });
    });
}, 10000); // Retrieve new messages every 10 seconds
