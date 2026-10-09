<?php
final class Captcha
{
    const TTL = 300; // Cinco minutos; cada envío consume el desafío.

    public static function invalidate(array &$session)
    {
        unset($session['captcha_text'], $session['captcha_issued_at']);
    }

    public static function consume(array &$session, $input, $now = null)
    {
        $answer = $session['captcha_text'] ?? null;
        $issued = $session['captcha_issued_at'] ?? null;
        self::invalidate($session);
        $now = $now ?? time();
        return is_string($input) && preg_match('/^[A-Za-z2-9]{6}$/D', $input)
            && is_string($answer) && preg_match('/^[A-Z2-9]{6}$/D', $answer)
            && is_int($issued) && $issued <= $now && $now - $issued < self::TTL
            && hash_equals($answer, strtoupper($input));
    }

    public static function issue(array &$session, $now = null, callable $renderer = null)
    {
        self::invalidate($session);
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $answer = '';
        for ($i = 0; $i < 6; $i++) {
            $answer .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $image = $renderer ? $renderer($answer) : self::render($answer);
        if (!is_string($image) || !preg_match('#^data:image/png;base64,[A-Za-z0-9+/]+={0,2}$#D', $image)) {
            throw new RuntimeException('No se pudo generar el CAPTCHA');
        }
        // Publicar el desafío sólo después de renderizar correctamente.
        $session['captcha_text'] = $answer;
        $session['captcha_issued_at'] = $now ?? time();
        return $image;
    }

    private static function render($answer)
    {
        if (!extension_loaded('gd')) {
            throw new RuntimeException('GD no disponible');
        }
        $image = null;
        $level = ob_get_level();
        set_error_handler(function ($severity, $message) { throw new RuntimeException($message); });
        try {
            $image = imagecreatetruecolor(160, 44);
            if (!$image) { throw new RuntimeException('Error de imagen'); }
            $background = imagecolorallocate($image, 255, 255, 255);
            $foreground = imagecolorallocate($image, 0, 0, 0);
            if ($background === false || $foreground === false
                || !imagefill($image, 0, 0, $background)
                || !imagestring($image, 5, 53, 14, $answer, $foreground)) {
                throw new RuntimeException('Error de renderizado');
            }
            ob_start();
            if (!imagepng($image)) { throw new RuntimeException('Error de PNG'); }
            $png = ob_get_contents();
            if (!is_string($png) || $png === '') { throw new RuntimeException('PNG vacío'); }
            return 'data:image/png;base64,' . base64_encode($png);
        } finally {
            while (ob_get_level() > $level) { ob_end_clean(); }
            // GD libera automáticamente GdImage; imagedestroy está obsoleto en PHP 8.5.
            $image = null;
            restore_error_handler();
        }
    }
}
