/**
 * Etkinlik Bilet Sistemi
 * Ana JavaScript Dosyası
 */

$(document).ready(function() {
    const defaultTitle = "Yaklaşan Etkinlikler";
    const nearestTitle = "En Yakın Etkinlikler";
    const eventsTitle = $('#events-title');
    const allEventsContainer = $('#all-events-container');
    const nearestEventsContainer = $('#nearest-events-container');
    const eventsLoader = $('#events-loader');
    const eventFilters = $('#event-filters');

    // "Yaklaşan Etkinlikler" butonu
    $('#all-events-btn').on('click', function() {
        if (!$(this).hasClass('active')) {
            $(this).addClass('active').removeClass('btn-outline-primary').addClass('btn-primary');
            $('#nearest-events-btn').removeClass('active').addClass('btn-outline-primary').removeClass('btn-primary');
            
            eventsTitle.text(defaultTitle);
            nearestEventsContainer.addClass('d-none');
            allEventsContainer.removeClass('d-none');
        }
    });

    // "En Yakın Etkinlikler" butonu
    $('#nearest-events-btn').on('click', function() {
        if (!$(this).hasClass('active')) {
            const hasLocation = eventFilters.data('has-location');
            if (!hasLocation) {
                alert("En yakın etkinlikleri görmek için önce konum izni vermeniz gerekmektedir.");
                return;
            }
            
            $(this).addClass('active').removeClass('btn-outline-primary').addClass('btn-primary');
            $('#all-events-btn').removeClass('active').addClass('btn-outline-primary').removeClass('btn-primary');

            eventsTitle.text(nearestTitle);
            allEventsContainer.addClass('d-none');
            nearestEventsContainer.removeClass('d-none');
            fetchNearestEvents();
        }
    });
    
    // En yakın etkinlikleri getiren fonksiyon
    function fetchNearestEvents() {
        eventsLoader.show();
        nearestEventsContainer.empty();

        fetch('get_events.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                eventsLoader.hide();
                if (data.error) {
                    nearestEventsContainer.html(`<div class="col-12"><div class="alert alert-warning">${data.error}</div></div>`);
                    return;
                }
                
                if (data.events && data.events.length > 0) {
                    let html = '';
                    data.events.forEach(event => {
                         html += `
                            <div class="col-md-4 mb-4">
                                <div class="card event-card">
                                    <img src="https://via.placeholder.com/350x180?text=${encodeURIComponent(event.title)}" class="card-img-top" alt="${event.title}">
                                    <div class="card-body">
                                        <h5 class="card-title">${event.title}</h5>
                                        <div class="event-date">
                                            <i class="far fa-calendar-alt"></i>
                                            <span>${event.formatted_date}</span>
                                        </div>
                                        <div class="event-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span>${event.location}</span>
                                        </div>
                                         <div class="event-distance mt-1 mb-2">
                                             <i class="fas fa-route text-primary"></i>
                                             <strong>${parseFloat(event.distance).toFixed(1)} km uzaklıkta</strong>
                                         </div>
                                        <div class="event-price mt-2">${event.formatted_price}</div>
                                        <a href="event.php?id=${event.id}" class="btn btn-sm btn-primary mt-2">Detaylar</a>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    nearestEventsContainer.html(html);
                } else {
                    nearestEventsContainer.html('<div class="col-12"><div class="alert alert-info">Yakınınızda (5 km) uygun bir etkinlik bulunamadı.</div></div>');
                }
            })
            .catch(error => {
                eventsLoader.hide();
                console.error('Error fetching nearest events:', error);
                nearestEventsContainer.html('<div class="col-12"><div class="alert alert-danger">Etkinlikler yüklenirken bir hata oluştu. Lütfen tekrar deneyin.</div></div>');
            });
    }

    // Konum isteme ve güncelleme
    function handleLocationRequest(update = false) {
        const statusDiv = $('#location-status');
        statusDiv.html('<div class="text-info"><i class="fas fa-spinner fa-spin"></i> Konumunuz alınıyor...</div>');
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                statusDiv.html('<div class="text-success"><i class="fas fa-check-circle"></i> Konum alındı, sunucuya gönderiliyor...</div>');

                $.post('update_location.php', { latitude: lat, longitude: lng })
                    .done(function(response) {
                        if (response.success) {
                            statusDiv.html(`<div class="alert alert-success mt-2">${response.message}</div>`);
                            // Sayfayı yeniden yükleyerek durumu güncelle
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                             statusDiv.html(`<div class="alert alert-danger mt-2">${response.message}</div>`);
                        }
                    })
                    .fail(function() {
                        statusDiv.html('<div class="alert alert-danger mt-2">Konum güncellenirken bir sunucu hatası oluştu.</div>');
                    });
            },
            (error) => {
                let message = 'Konum alınırken bir hata oluştu.';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message = "Konum izni reddedildi. Özellikleri kullanmak için tarayıcı ayarlarından izin vermelisiniz.";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = "Konum bilgisi şu anda mevcut değil.";
                        break;
                    case error.TIMEOUT:
                        message = "Konum alma isteği zaman aşımına uğradı.";
                        break;
                }
                statusDiv.html(`<div class="alert alert-danger mt-2">${message}</div>`);
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    $('#request-location').on('click', function() {
        handleLocationRequest(false);
    });

    $('#update-location').on('click', function() {
        handleLocationRequest(true);
    });
});

/**
 * Etkinlik kartları için hover ve diğer efektleri başlatır
 */
function initEventCardEffects() {
    // Gerekirse eklenecek
}

/**
 * Bilet sayfası için özellikleri başlatır
 */
function initTicketActions() {
    // Bilet print butonları
    const printButtons = document.querySelectorAll('.btn-print-ticket');
    if (printButtons.length > 0) {
        printButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const ticketId = this.getAttribute('data-ticket-id');
                printTicket(ticketId);
            });
        });
    }
}

/**
 * Bileti yazdırır
 */
function printTicket(ticketId) {
    // Bileti içeren elementleri seç
    const ticketElement = document.getElementById('ticket-' + ticketId);
    if (!ticketElement) return;
    
    // Yazdırma penceresi açma
    const printWindow = window.open('', '', 'width=800,height=600');
    
    // HTML içeriği hazırla
    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Bilet #${ticketId}</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { font-family: 'Arial', sans-serif; padding: 20px; }
                .ticket-card { border: 1px solid #ddd; border-radius: 10px; overflow: hidden; margin: 20px auto; max-width: 800px; }
                .ticket-info { padding: 20px; }
                .ticket-qr { padding: 20px; text-align: center; border-top: 2px dashed #ddd; }
                .event-title { font-size: 22px; font-weight: bold; margin-bottom: 15px; }
                .event-detail { margin-bottom: 5px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="ticket-card">
                    ${ticketElement.innerHTML}
                </div>
                <div class="text-center mt-4">
                    <p>Bu bilet Etkinlik Bilet Sistemi tarafından oluşturulmuştur.</p>
                </div>
            </div>
            <script>
                window.onload = function() { window.print(); }
            </script>
        </body>
        </html>
    `);
    
    printWindow.document.close();
}

/**
 * Admin paneli özelliklerini başlatır
 */
function initAdminFeatures() {
    // Etkinlik silme işlemleri
    const deleteButtons = document.querySelectorAll('.btn-delete');
    if (deleteButtons.length > 0) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Bu öğeyi silmek istediğinizden emin misiniz?')) {
                    window.location.href = this.getAttribute('href');
                }
            });
        });
    }
} 