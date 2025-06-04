// Etkinlik konumunun koordinatları (Taksim Meydanı, İstanbul)
const eventLocation = { lat: 41.0369, lng: 28.9850 };
let map, directionsService, directionsRenderer;

// Haritayı başlatma
function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        center: eventLocation,
        zoom: 13,
    });

    // Etkinlik konumunu işaretleme
    new google.maps.Marker({
        position: eventLocation,
        map: map,
        title: "Etkinlik Konumu: Taksim Meydanı",
    });

    // Directions API için servis ve render başlatma
    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer();
    directionsRenderer.setMap(map);
}

// Kullanıcının konumunu alma
function locateUser() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(showPosition, showError);
    } else {
        alert("Tarayıcınız konum desteği sağlamıyor.");
    }
}

// Kullanıcı konumunu gösterme ve rota önerisi oluşturma
function showPosition(position) {
    const userLocation = {
        lat: position.coords.latitude,
        lng: position.coords.longitude,
    };

    // Kullanıcı konumunu işaretleme
    new google.maps.Marker({
        position: userLocation,
        map: map,
        title: "Mevcut Konumunuz",
    });

    // Rota talebi oluşturma
    const request = {
        origin: userLocation,
        destination: eventLocation,
        travelMode: google.maps.TravelMode.DRIVING,
    };

    directionsService.route(request, (result, status) => {
        if (status === google.maps.DirectionsStatus.OK) {
            directionsRenderer.setDirections(result);

            // Mesafe ve süre bilgilerini gösterme
            const route = result.routes[0].legs[0];
            const distance = route.distance.text;
            const duration = route.duration.text;
            document.getElementById("route-suggestion").textContent = `Mesafe: ${distance}, Tahmini Süre: ${duration}`;
        } else {
            document.getElementById("route-suggestion").textContent = "Rota önerisi alınamadı.";
        }
    });
}

// Konum hatası durumunda hata mesajı gösterme
function showError(error) {
    alert("Konum alınamadı: " + error.message);
}
