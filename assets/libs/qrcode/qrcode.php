<?php
/**
 * QR Kod oluşturma sınıfı
 * 
 * Bu sınıf QRServer.com API kullanarak QR kodlar oluşturur.
 * Hosting ortamlarında ek kütüphane gerektirmeden çalışabilmek için
 * dış API kullanılmıştır.
 */
class QRCode {
    
    /**
     * QR kod oluşturur ve HTML img etiketi olarak döndürür
     * 
     * @param string $data QR kodun içereceği veri
     * @param int $size QR kod boyutu (pixel)
     * @param string $errorCorrection Hata düzeltme seviyesi (L, M, Q, H)
     * @return string QR kodu içeren HTML img etiketi
     */
    public static function generate($data, $size = 200, $errorCorrection = 'M') {
        // QRServer.com API kullanarak QR kod oluştur
        $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/";
        $params = [
            'size' => $size . 'x' . $size,
            'data' => $data,
            'ecc' => $errorCorrection
        ];
        
        $url = $qrApiUrl . '?' . http_build_query($params);
        
        // HTML img etiketi döndür
        return '<img src="' . $url . '" alt="QR Kod" class="img-fluid" style="max-width: 100%;">';
    }
    
    /**
     * Bilet için QR kod oluşturur
     * 
     * @param int $ticketId Bilet ID
     * @param int $userId Kullanıcı ID
     * @param int $eventId Etkinlik ID
     * @param string $token Doğrulama token'ı
     * @return string QR kodu içeren HTML img etiketi
     */
    public static function generateTicketQR($ticketId, $userId, $eventId, $token) {
        // Bilet doğrulama verisi
        $data = json_encode([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'event_id' => $eventId, 
            'token' => $token
        ]);
        
        // Base64 ile encode edip QR kodu oluştur
        $encodedData = base64_encode($data);
        $verifyUrl = "verify-ticket.php?data=" . urlencode($encodedData);
        
        return self::generate($verifyUrl);
    }
    
    /**
     * QR kod verilerini decode eder
     * 
     * @param string $encodedData Base64 ile encode edilmiş veri
     * @return object Decoded veri objesi veya null
     */
    public static function decodeTicketQR($encodedData) {
        try {
            $jsonData = base64_decode($encodedData);
            return json_decode($jsonData);
        } catch (Exception $e) {
            return null;
        }
    }
}
?> 