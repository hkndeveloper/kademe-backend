<?php

namespace App\Services;

class QrService
{
    /**
     * Generate a QR code URL using qrserver.com
     * 
     * @param string $data The content of the QR code
     * @param int $size Size in pixels (e.g. 150)
     * @return string
     */
    public function generateUrl(string $data, int $size = 150): string
    {
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);
    }
}
