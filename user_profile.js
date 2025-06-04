document.addEventListener("DOMContentLoaded", function() {
    const sidebarItems = document.querySelectorAll(".sidebar li");
    const sections = document.querySelectorAll(".section");

    // Yan menü öğeleri arasında geçiş
    sidebarItems.forEach((item, index) => {
        item.addEventListener("click", () => {
            document.querySelector(".sidebar li.active").classList.remove("active");
            item.classList.add("active");

            document.querySelector(".section.active").classList.remove("active");
            sections[index].classList.add("active");
        });
    });

    // Profil Güncelleme: Düzenleme işlemi için tıklama olayları
    const editButtons = document.querySelectorAll("button[onclick^='enableEdit']");
    editButtons.forEach(button => {
        button.addEventListener("click", (event) => {
            const inputField = event.target.previousElementSibling;
            inputField.disabled = !inputField.disabled;  // Düzenleme moduna geçiş
            button.classList.toggle("active"); // Aktiflik belirtme
            if (inputField.disabled) {
                event.target.textContent = "Düzenle";
                button.classList.remove("active"); // Aktiflikten çık
            } else {
                event.target.textContent = "Kaydet";
                inputField.focus();
            }
        });
    });

    // Profil Fotoğrafını Güncelleme
    const profilePhotoInput = document.getElementById("profile-photo-input");
    const profilePhotoPreview = document.getElementById("profile-photo-preview");

    profilePhotoInput.addEventListener("change", (event) => {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profilePhotoPreview.src = e.target.result;  // Yeni fotoğrafı önizle
            };
            reader.readAsDataURL(file);
        }
    });
});

document.getElementById("reset-password-form").addEventListener("submit", function(event) {
    const newPassword = document.getElementById("new-password").value;
    const confirmPassword = document.getElementById("confirm-password").value;

    if (newPassword !== confirmPassword) {
        event.preventDefault();
        alert("Şifreler eşleşmiyor. Lütfen tekrar deneyin.");
    } else {
        alert("Şifre başarıyla sıfırlandı.");
    }
});


document.addEventListener("DOMContentLoaded", function() {
    const sidebarItems = document.querySelectorAll(".sidebar li");
    const sections = document.querySelectorAll(".section");
    const interestForm = document.getElementById("interest-update-form");
    const currentInterestsContainer = document.querySelector(".current-interests");
    const interestsSelect = document.getElementById("interests");

    // Örnek olarak bazı mevcut ilgi alanları
    let userInterests = ["Spor", "Sanat", "Teknoloji"];

    // Mevcut ilgi alanlarını göster ve kaldırma işlevi ekle
    function displayCurrentInterests() {
        currentInterestsContainer.innerHTML = ""; // Önceki içerikleri temizle
        userInterests.forEach((interest, index) => {
            const tag = document.createElement("span");
            tag.classList.add("interest-tag");
            tag.textContent = interest;

            // Çarpı butonu ekle
            const removeBtn = document.createElement("button");
            removeBtn.textContent = "✖";
            removeBtn.classList.add("remove-btn");
            removeBtn.onclick = function() {
                removeInterest(index);
            };
            tag.appendChild(removeBtn);
            currentInterestsContainer.appendChild(tag);
        });
        updateSelectableInterests();
    }

    // İlgi alanı kaldırma
    function removeInterest(index) {
        const removedInterest = userInterests[index];
        userInterests.splice(index, 1); // Seçili ilgi alanını listeden kaldır
        displayCurrentInterests(); // Güncellenmiş listeyi göster
        alert(`'${removedInterest}' ilgi alanınız kaldırıldı.`);
    }

    // Seçilebilir ilgi alanlarını güncelle
    function updateSelectableInterests() {
        Array.from(interestsSelect.options).forEach(option => {
            if (userInterests.includes(option.value)) {
                option.disabled = true; // Mevcut ilgi alanlarını devre dışı bırak
                option.classList.add("disabled-option"); // Gri stil ekle
            } else {
                option.disabled = false;
                option.classList.remove("disabled-option");
            }
        });
    }

    // Yan menü öğeleri arasında geçiş
    sidebarItems.forEach((item, index) => {
        item.addEventListener("click", () => {
            document.querySelector(".sidebar li.active").classList.remove("active");
            item.classList.add("active");

            document.querySelector(".section.active").classList.remove("active");
            sections[index].classList.add("active");
        });
    });

    // İlgi Alanlarını Güncelleme İşlemi
    interestForm.addEventListener("submit", (event) => {
        event.preventDefault();

        // Seçilen ilgi alanlarını al ve mevcut listeye ekle
        const selectedOptions = Array.from(interestsSelect.selectedOptions);
        selectedOptions.forEach(option => {
            if (!userInterests.includes(option.value)) {
                userInterests.push(option.value); // Seçili olmayan ilgi alanını listeye ekle
            }
        });

        // Güncellenmiş ilgi alanlarını göster
        displayCurrentInterests();

        alert("İlgi alanlarınız güncellendi.");
    });

    // Sayfa yüklendiğinde mevcut ilgi alanlarını göster
    displayCurrentInterests();
});


document.addEventListener("DOMContentLoaded", function() {
    const filterBtns = document.querySelectorAll(".filter-btn");
    const notificationsList = document.querySelector(".notifications-list");
    const notificationCards = document.querySelectorAll(".notification-card");

    // Filtre butonlarına tıklama olayları
    filterBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelector(".filter-btn.active").classList.remove("active");
            btn.classList.add("active");
            filterNotifications(btn.id);
        });
    });

    // Bildirimleri filtreleme fonksiyonu
    function filterNotifications(filter) {
        notificationCards.forEach(card => {
            if (filter === "all-btn") {
                card.style.display = "flex";
            } else if (filter === "unread-btn" && card.classList.contains("unread")) {
                card.style.display = "flex";
            } else if (filter === "read-btn" && card.classList.contains("read")) {
                card.style.display = "flex";
            } else {
                card.style.display = "none";
            }
        });
    }

    // Tüm bildirimleri okundu olarak işaretleme
    document.getElementById("mark-all-read").addEventListener("click", () => {
        notificationCards.forEach(card => {
            card.classList.remove("unread");
            card.classList.add("read");
        });
    });

    // Tüm bildirimleri silme
    document.getElementById("delete-all").addEventListener("click", () => {
        notificationsList.innerHTML = "";
    });

    // Tek bildirim okundu olarak işaretleme
    notificationsList.addEventListener("click", (e) => {
        if (e.target.classList.contains("mark-read-btn")) {
            const card = e.target.closest(".notification-card");
            card.classList.remove("unread");
            card.classList.add("read");
            e.target.remove();
        }
    });

    // Tek bildirim silme
    notificationsList.addEventListener("click", (e) => {
        if (e.target.classList.contains("delete-btn")) {
            const card = e.target.closest(".notification-card");
            card.remove();
        }
    });
});


document.addEventListener("DOMContentLoaded", function() {
    const eventHistoryContainer = document.querySelector(".event-history");

    const pastEvents = [
        { name: "Film Festivali", date: "2023-10-10", location: "Açıkhava Sineması, İstanbul" },
        { name: "Sanat Sergisi", date: "2023-11-20", location: "İstanbul Modern Sanat Müzesi" },
        { name: "Kitap Fuarı", date: "2023-09-15", location: "İstanbul Kongre Merkezi" },
        { name: "Müzik Festivali", date: "2023-08-05", location: "Harbiye, İstanbul" }
    ];

    function displaySortedEvents() {
        // Etkinlikleri tarihe göre en yeni en üstte olacak şekilde sıralar
        pastEvents.sort((a, b) => new Date(b.date) - new Date(a.date));

        // İçeriği temizleyip her etkinliği sıralı olarak ekler
        eventHistoryContainer.innerHTML = "";
        pastEvents.forEach(event => {
            const eventCard = document.createElement("div");
            eventCard.classList.add("event-card", "past-event");
            eventCard.innerHTML = `
                <h4>${event.name}</h4>
                <p>Tarih: ${new Date(event.date).toLocaleDateString()}</p>
                <p>Konum: ${event.location}</p>
            `;
            eventHistoryContainer.appendChild(eventCard);
        });
    }

    displaySortedEvents();
});


document.addEventListener("DOMContentLoaded", function() {
    const feedbackForm = document.getElementById("feedback-form");
    const feedbackSuccess = document.getElementById("feedback-success");
    const feedbackMessages = document.getElementById("feedback-messages");

    feedbackForm.addEventListener("submit", function(event) {
        event.preventDefault();

        // Form verilerini al
        const subject = document.getElementById("subject").value;
        const message = document.getElementById("message").value;

        // Kullanıcı adı bilgisi (örnek: giriş yapmış kullanıcıdan alınacak)
        const userName = "Kullanıcı Adı"; // Bu bilgi oturum açmış kullanıcıdan çekilecek

        // Yeni geri bildirim mesajını listeye ekle
        const newMessage = document.createElement("li");
        newMessage.classList.add("feedback-message");
        newMessage.innerHTML = `
            <div class="message-header">${userName} - ${subject}</div>
            <div class="message-body">${message}</div>
        `;
        feedbackMessages.prepend(newMessage);

        // Başarı mesajını göster ve formu sıfırla
        feedbackSuccess.classList.remove("hidden");
        feedbackForm.reset();

        // Mesajı bir süre sonra gizle
        setTimeout(() => {
            feedbackSuccess.classList.add("hidden");
        }, 3000);
    });
});
