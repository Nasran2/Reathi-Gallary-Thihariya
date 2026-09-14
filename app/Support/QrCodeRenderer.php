<?php

namespace App\Support;

class QrCodeRenderer
{
    /**
     * Render a QR code HTML image tag
     *
     * @param string $data
     * @param int $size
     * @return string
     */
    public static function render(string $data, int $size = 80): string
    {
        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data) . '&margin=0';
        return '<img src="' . $url . '" width="' . $size . '" height="' . $size . '" alt="QR Code" style="margin: 0 auto; display: block; max-width: 100%;">';
    }
}
