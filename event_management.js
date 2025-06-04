// Modal açma fonksiyonu
function openModal() {
    document.getElementById("editModal").style.display = "flex";
}

// Modal kapama fonksiyonu
function closeModal() {
    document.getElementById("editModal").style.display = "none";
}

// Düzenle butonuna tıklanınca modal açılır
document.querySelectorAll('.btn-edit').forEach(button => {
    button.addEventListener('click', openModal);
});

// Modal kapatma işlevi için "X" işaretine tıklama
document.querySelector('.close-btn').addEventListener('click', closeModal);

// Modal dışına tıklanınca kapatma
window.addEventListener('click', function(event) {
    const modal = document.getElementById("editModal");
    if (event.target == modal) {
        closeModal();
    }
});
