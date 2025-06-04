// Sohbeti başlatma fonksiyonu
document.addEventListener("DOMContentLoaded", () => {
    const messageInput = document.getElementById("messageInput");
    const messageBox = document.querySelector(".message-box");
    const sendButton = document.getElementById("sendButton");

    // Mesaj gönderme butonuna ve Enter tuşuna basıldığında mesaj gönder
    sendButton.addEventListener("click", sendMessage);
    messageInput.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            sendMessage();
        }
    });

    // Mesaj gönderme işlevi
    function sendMessage() {
        const messageText = messageInput.value.trim();
        if (messageText !== "") {
            const messageElement = createMessageElement(messageText, "user-message", "Siz");
            messageBox.appendChild(messageElement);
            messageBox.scrollTop = messageBox.scrollHeight;

            messageInput.value = "";
            setTimeout(() => simulateReply("Bu bir simüle edilmiş cevaptır.", "Karşı Taraf"), 1500);
        }
    }

    // Mesaj baloncuğu oluşturma işlevi
    function createMessageElement(text, messageClass, sender) {
        const messageElement = document.createElement("div");
        messageElement.classList.add("message", messageClass);

        const messageContent = document.createElement("div");
        messageContent.classList.add("message-content");
        messageContent.textContent = text;
        messageElement.appendChild(messageContent);

        const messageInfo = document.createElement("div");
        messageInfo.classList.add("message-info");

        const timestamp = new Date();
        const formattedTime = `${timestamp.toLocaleDateString()} ${timestamp.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})}`;
        messageInfo.textContent = `${sender} - ${formattedTime}`;

        messageElement.appendChild(messageInfo);

        return messageElement;
    }

    // Simüle edilmiş cevap işlevi
    function simulateReply(replyText, sender) {
        const replyElement = createMessageElement(replyText, "other-message", sender);
        messageBox.appendChild(replyElement);
        messageBox.scrollTop = messageBox.scrollHeight;
    }
});

// Etkinlik seçildiğinde bilgileri gösterme işlevi
function selectEvent(name, date, time, location, category, description) {
    document.getElementById("event-name").textContent = name;
    document.getElementById("event-date").textContent = `Tarih: ${date}`;
    document.getElementById("event-time").textContent = `Saat: ${time}`;
    document.getElementById("event-location").textContent = `Konum: ${location}`;
    document.getElementById("event-category").textContent = `Kategori: ${category}`;
    document.getElementById("event-description").textContent = `Açıklama: ${description}`;
}
