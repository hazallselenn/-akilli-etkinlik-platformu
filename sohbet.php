<?php
include 'inc/config.php';

// Kullanıcının katıldığı etkinlikleri getir
$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT e.* FROM etkinlikler e 
                     INNER JOIN katilimcilar k ON e.id = k.etkinlik_id 
                     WHERE k.kullanici_id = ? AND e.durum = 1
                     ORDER BY e.tarih DESC");
$stmt->execute([$user_id]);
$katildigi_etkinlikler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Seçili etkinliğin bilgilerini ve mesajlarını getir
if (isset($_GET['etkinlik_id'])) {
    $etkinlik_id = $_GET['etkinlik_id'];

    // Etkinlik bilgilerini getir
    $stmt = $db->prepare("SELECT * FROM etkinlikler WHERE id = ?");
    $stmt->execute([$etkinlik_id]);
    $current_etkinlik = $stmt->fetch(PDO::FETCH_ASSOC);

    // Mesajları getir
    $stmt = $db->prepare("SELECT m.*, CONCAT(k.ad, ' ', k.soyad) as ad_soyad, k.profil_fotografi, k.son_gorulme 
                         FROM mesajlar m
                         INNER JOIN kullanicilar k ON m.gonderen_id = k.id 
                         WHERE m.etkinlik_id = ?
                         ORDER BY m.tarih ASC");
    $stmt->execute([$etkinlik_id]);
    $mesajlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Etkinlik katılımcılarını getir
    $stmt = $db->prepare("SELECT k.id, CONCAT(k.ad, ' ', k.soyad) as ad_soyad, k.profil_fotografi, k.son_gorulme
                         FROM kullanicilar k
                         INNER JOIN katilimcilar ka ON k.id = ka.kullanici_id
                         WHERE ka.etkinlik_id = ?");
    $stmt->execute([$etkinlik_id]);
    $katilimcilar = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// AJAX mesaj gönderme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'send_message' && isset($_POST['mesaj'])) {
        $mesaj = trim($_POST['mesaj']);
        $etkinlik_id = $_POST['etkinlik_id'];

        if (!empty($mesaj)) {
            $stmt = $db->prepare("INSERT INTO mesajlar (etkinlik_id, gonderen_id, mesaj, tarih) 
                                VALUES (?, ?, ?, NOW())");
            $stmt->execute([$etkinlik_id, $user_id, $mesaj]);

            // Yeni mesajı hemen döndür
            $stmt = $db->prepare("SELECT m.*, CONCAT(k.ad, ' ', k.soyad) as ad_soyad, k.profil_fotografi 
                                FROM mesajlar m
                                INNER JOIN kullanicilar k ON m.gonderen_id = k.id 
                                WHERE m.id = LAST_INSERT_ID()");
            $stmt->execute();
            $yeni_mesaj = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode($yeni_mesaj);
            exit;
        }
    } else if ($_POST['action'] == 'get_messages') {
        $etkinlik_id = $_POST['etkinlik_id'];
        $son_mesaj_id = $_POST['son_mesaj_id'];

        $stmt = $db->prepare("SELECT m.*, CONCAT(k.ad, ' ', k.soyad) as ad_soyad, k.profil_fotografi 
                            FROM mesajlar m
                            INNER JOIN kullanicilar k ON m.gonderen_id = k.id 
                            WHERE m.etkinlik_id = ? AND m.id > ?
                            ORDER BY m.tarih ASC");
        $stmt->execute([$etkinlik_id, $son_mesaj_id]);
        $yeni_mesajlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($yeni_mesajlar);
        exit;
    }
}

// Çevrimiçi kullanıcıları belirle (son 5 dakika içinde aktif olanlar)
$online_kullanicilar = [];
if (isset($katilimcilar)) {
    foreach ($katilimcilar as $k) {
        $son_gorulme = strtotime($k['son_gorulme']);
        $simdi = time();
        $k['cevrimici'] = ($simdi - $son_gorulme) < 300; // 5 dakika
        $online_kullanicilar[] = $k;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sohbet | Etkinlik Platformu</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f0f2f5;
            height: 100vh;
            display: flex;
        }

        .chat-container {
            display: flex;
            width: 100%;
            max-width: 1400px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            height: calc(100vh - 4rem);
        }

        .sidebar {
            width: 300px;
            background: #fff;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
        }

        .chat-list {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .chat-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .chat-item:hover {
            background: #f8f9fa;
        }

        .chat-item.active {
            background: #e3f2fd;
        }

        .chat-item img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            margin-right: 1rem;
            object-fit: cover;
        }

        .chat-info h4 {
            color: #1a1a1a;
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .chat-info p {
            color: #666;
            font-size: 0.85rem;
        }

        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .chat-header {
            padding: 1rem 2rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .online-users {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .online-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4CAF50;
            margin-right: 5px;
        }

        .chat-messages {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message {
            display: flex;
            margin-bottom: 1.5rem;
            align-items: flex-start;
            animation: fadeIn 0.3s ease;
        }

        .message.sent {
            flex-direction: row-reverse;
        }

        .message-content {
            max-width: 60%;
            padding: 1rem;
            border-radius: 16px;
            position: relative;
            margin: 0 1rem;
        }

        .message.received .message-content {
            background: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .message.sent .message-content {
            background: #0084ff;
            color: #fff;
        }

        .message-input {
            padding: 1.5rem;
            border-top: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #fff;
        }

        .message-input input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 24px;
            background: #f0f2f5;
            font-size: 0.95rem;
        }

        .message-input button {
            background: #0084ff;
            color: #fff;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .message-input button:hover {
            background: #0073e6;
            transform: scale(1.05);
        }

        .search-box {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 24px;
            background: #f0f2f5;
            font-size: 0.9rem;
        }

        .chat-header-actions button {
            background: none;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.5rem;
            color: #666;
        }

        .chat-header-actions button:hover {
            background: #f0f2f5;
            color: #0084ff;
            transform: scale(1.05);
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <header class="main-header">
        <?php include 'inc/header.php'; ?>
    </header>


    <div class="chat-container" style="margin-top: 100px;">
        <div class="sidebar">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Etkinlik ara..." onkeyup="searchEvents()">
            </div>
            <div class="chat-list">
                <?php foreach ($katildigi_etkinlikler as $etkinlik): ?>
                    <a href="sohbet.php?etkinlik_id=<?php echo $etkinlik['id']; ?>" style="text-decoration: none;" class="event-item">
                        <div class="chat-item <?php echo (isset($_GET['etkinlik_id']) && $_GET['etkinlik_id'] == $etkinlik['id']) ? 'active' : ''; ?>">
                            <div class="chat-info">
                                <h4><?php echo htmlspecialchars($etkinlik['etkinlik_adi']); ?></h4>
                                <p><?php echo date('d.m.Y', strtotime($etkinlik['tarih'])); ?></p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="chat-main">
            <?php if (isset($_GET['etkinlik_id']) && $current_etkinlik): ?>
                <div class="chat-header">
                    <div class="chat-header-info">
                        <h3><?php echo htmlspecialchars($current_etkinlik['etkinlik_adi']); ?></h3>
                        <div class="online-users">
                            <?php
                            $online_count = array_filter($online_kullanicilar, function ($k) {
                                return $k['cevrimici'];
                            });
                            ?>
                            <span><i class="fas fa-circle" style="color: #4CAF50; font-size: 10px;"></i> <?php echo count($online_count); ?> çevrimiçi</span>
                        </div>
                    </div>
                    <div class="chat-header-actions">
                        <button onclick="showEventDetails()" title="Etkinlik Detayları"><i class="fas fa-info-circle"></i></button>
                        <button onclick="showParticipants()" title="Katılımcılar"><i class="fas fa-users"></i></button>
                    </div>
                </div>

                <div class="chat-messages" id="messageBox">
                    <?php foreach ($mesajlar as $mesaj): ?>
                        <div class="message <?php echo ($mesaj['gonderen_id'] == $user_id) ? 'sent' : 'received'; ?>">
                            <img src="<?php echo !empty($mesaj['profil_fotografi']) ? 'uploads/profile_pictures/' . $mesaj['profil_fotografi'] : 'assets/images/default-avatar.png'; ?>"
                                alt="<?php echo htmlspecialchars($mesaj['ad_soyad']); ?>"
                                style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            <div class="message-content">
                                <?php if ($mesaj['gonderen_id'] != $user_id): ?>
                                    <small style="font-size: 0.8em; color: #666;"><?php echo htmlspecialchars($mesaj['ad_soyad']); ?></small>
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars($mesaj['mesaj']); ?></p>
                                <small style="opacity: 0.7"><?php echo date('H:i', strtotime($mesaj['tarih'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form class="message-input" id="messageForm" onsubmit="sendMessage(event)">
                    <input type="hidden" name="etkinlik_id" value="<?php echo $_GET['etkinlik_id']; ?>">
                    <input type="text" name="mesaj" placeholder="Mesajınızı yazın..." required autocomplete="off">
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
            <?php else: ?>
                <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                    <div style="text-align: center; color: #666;">
                        <i class="fas fa-comments" style="font-size: 48px; margin-bottom: 1rem;"></i>
                        <h3>Sohbete Başlayın</h3>
                        <p>Sol menüden bir etkinlik seçerek sohbete başlayabilirsiniz.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const messageBox = document.getElementById('messageBox');
        let lastMessageId = <?php echo !empty($mesajlar) ? end($mesajlar)['id'] : 0; ?>;

        if (messageBox) {
            messageBox.scrollTop = messageBox.scrollHeight;
        }

        function searchEvents() {
            const searchInput = document.getElementById('searchInput');
            const filter = searchInput.value.toLowerCase();
            const eventItems = document.getElementsByClassName('event-item');

            Array.from(eventItems).forEach(item => {
                const eventName = item.querySelector('h4').textContent.toLowerCase();
                item.style.display = eventName.includes(filter) ? '' : 'none';
            });
        }

        async function sendMessage(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('action', 'send_message');

            try {
                const response = await fetch('sohbet.php', {
                    method: 'POST',
                    body: formData
                });
                const newMessage = await response.json();

                appendMessage(newMessage);
                form.reset();
                messageBox.scrollTop = messageBox.scrollHeight;
            } catch (error) {
                console.error('Mesaj gönderilirken hata oluştu:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Mesaj gönderilemedi. Lütfen tekrar deneyin.'
                });
            }
        }

        function appendMessage(message) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${message.gonderen_id == <?php echo $user_id; ?> ? 'sent' : 'received'}`;

            messageDiv.innerHTML = `
                <img src="${message.profil_fotografi ? 'uploads/profile_pictures/' + message.profil_fotografi : 'assets/images/default-avatar.png'}"
                     alt="${message.ad_soyad}"
                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                <div class="message-content">
                    ${message.gonderen_id != <?php echo $user_id; ?> ? `<small style="font-size: 0.8em; color: #666;">${message.ad_soyad}</small>` : ''}
                    <p>${message.mesaj}</p>
                    <small style="opacity: 0.7">${new Date(message.tarih).toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'})}</small>
                </div>
            `;

            messageBox.appendChild(messageDiv);
            lastMessageId = message.id;
        }

        // Yeni mesajları kontrol et
        setInterval(async function() {
            if (messageBox) {
                const formData = new FormData();
                formData.append('action', 'get_messages');
                formData.append('etkinlik_id', <?php echo isset($_GET['etkinlik_id']) ? $_GET['etkinlik_id'] : 0; ?>);
                formData.append('son_mesaj_id', lastMessageId);

                try {
                    const response = await fetch('sohbet.php', {
                        method: 'POST',
                        body: formData
                    });
                    const newMessages = await response.json();

                    newMessages.forEach(message => {
                        appendMessage(message);
                    });

                    if (newMessages.length > 0) {
                        messageBox.scrollTop = messageBox.scrollHeight;
                    }
                } catch (error) {
                    console.error('Yeni mesajlar kontrol edilirken hata oluştu:', error);
                }
            }
        }, 3000);

        function showEventDetails() {
            Swal.fire({
                title: '<?php echo isset($current_etkinlik) ? htmlspecialchars($current_etkinlik['etkinlik_adi']) : ''; ?>',
                html: `
                    <p><strong>Tarih:</strong> <?php echo isset($current_etkinlik) ? date('d.m.Y', strtotime($current_etkinlik['tarih'])) : ''; ?></p>
                    <p><strong>Konum:</strong> <?php echo isset($current_etkinlik) ? htmlspecialchars($current_etkinlik['konum']) : ''; ?></p>
                    <p><strong>Açıklama:</strong> <?php echo isset($current_etkinlik) ? htmlspecialchars($current_etkinlik['aciklama']) : ''; ?></p>
                `,
                confirmButtonText: 'Kapat'
            });
        }

        function showParticipants() {
            const participants = <?php echo json_encode($katilimcilar ?? []); ?>;
            const participantsList = participants.map(p => `
                <div style="display: flex; align-items: center; margin: 10px 0;">
                    <img src="${p.profil_fotografi ? 'uploads/profile_pictures/' + p.profil_fotografi : 'assets/images/default-avatar.png'}"
                         style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">
                    <span>${p.ad_soyad}</span>
                    ${p.cevrimici ? '<i class="fas fa-circle" style="color: #4CAF50; font-size: 8px; margin-left: 5px;"></i>' : ''}
                </div>
            `).join('');

            Swal.fire({
                title: 'Katılımcılar',
                html: participantsList,
                confirmButtonText: 'Kapat'
            });
        }
    </script>
</body>

</html>