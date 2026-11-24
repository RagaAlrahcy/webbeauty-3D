<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use RuntimeException;

/**
 * Draws simple packaging illustrations with GD for demo data, so the catalog
 * has fallback photos before real product photography is uploaded in the CMS.
 */
final class PlaceholderImage
{
    private const int SIZE = 1200;

    /**
     * @return string path to a temporary PNG file
     */
    public static function packaging(string $shape, string $hexColor, string $accentHex = '#f4e1dc'): string
    {
        $size = self::SIZE;
        $image = imagecreatetruecolor($size, $size);

        if ($image === false) {
            throw new RuntimeException('GD could not create an image.');
        }

        imagesavealpha($image, true);
        imageantialias($image, true);

        self::gradient($image, '#fffaf7', $accentHex);

        $body = self::color($image, $hexColor);
        $bodyShade = self::color($image, self::shade($hexColor, -0.12));
        $highlight = imagecolorallocatealpha($image, 255, 255, 255, 80);
        $cap = self::color($image, '#d8c3a5');
        $capShade = self::color($image, '#b9a07e');
        $label = imagecolorallocatealpha($image, 255, 252, 249, 18);
        $line = self::color($image, '#a35d6a');
        $shadow = imagecolorallocatealpha($image, 70, 35, 45, 108);

        $cx = intdiv($size, 2);

        imagefilledellipse($image, $cx, 1010, 560, 70, $shadow);

        match ($shape) {
            'jar' => self::jar($image, $cx, $body, $bodyShade, $cap, $capShade, $label, $line, $highlight),
            'tube' => self::tube($image, $cx, $body, $bodyShade, $cap, $capShade, $label, $line, $highlight),
            'dropper' => self::dropper($image, $cx, $body, $bodyShade, $cap, $label, $line, $highlight),
            'compact' => self::compact($image, $cx, $body, $bodyShade, $cap, $capShade, $label, $line),
            default => self::bottle($image, $cx, $body, $bodyShade, $cap, $capShade, $label, $line, $highlight),
        };

        $path = tempnam(sys_get_temp_dir(), 'demo').'.png';
        imagepng($image, $path, 6);
        imagedestroy($image);

        return $path;
    }

    private static function bottle($im, int $cx, int $body, int $shade, int $cap, int $capShade, int $label, int $line, int $hl): void
    {
        self::roundedRect($im, $cx - 210, 400, $cx + 210, 1000, 70, $body);
        imagefilledrectangle($im, $cx + 120, 440, $cx + 200, 960, $shade);
        imagefilledrectangle($im, $cx - 80, 330, $cx + 80, 410, $body);
        self::roundedRect($im, $cx - 95, 160, $cx + 95, 345, 22, $cap);
        imagefilledrectangle($im, $cx + 45, 170, $cx + 85, 335, $capShade);
        self::label($im, $cx - 210, 560, $cx + 210, 840, $label, $line);
        imagefilledrectangle($im, $cx - 170, 430, $cx - 140, 960, $hl);
    }

    private static function jar($im, int $cx, int $body, int $shade, int $cap, int $capShade, int $label, int $line, int $hl): void
    {
        self::roundedRect($im, $cx - 330, 600, $cx + 330, 1000, 60, $body);
        imagefilledrectangle($im, $cx + 220, 630, $cx + 320, 970, $shade);
        self::roundedRect($im, $cx - 345, 440, $cx + 345, 615, 30, $cap);
        imagefilledrectangle($im, $cx + 230, 450, $cx + 330, 605, $capShade);
        self::label($im, $cx - 330, 700, $cx + 330, 900, $label, $line);
        imagefilledrectangle($im, $cx - 290, 630, $cx - 260, 970, $hl);
    }

    private static function tube($im, int $cx, int $body, int $shade, int $cap, int $capShade, int $label, int $line, int $hl): void
    {
        imagefilledpolygon($im, [$cx - 230, 180, $cx + 230, 180, $cx + 150, 830, $cx - 150, 830], $body);
        imagefilledpolygon($im, [$cx + 150, 200, $cx + 225, 200, $cx + 145, 820, $cx + 100, 820], $shade);
        imagefilledrectangle($im, $cx - 245, 150, $cx + 245, 200, $body);
        self::roundedRect($im, $cx - 130, 820, $cx + 130, 1000, 24, $cap);
        imagefilledrectangle($im, $cx + 70, 830, $cx + 120, 990, $capShade);
        self::label($im, $cx - 190, 330, $cx + 190, 640, $label, $line);
        imagefilledrectangle($im, $cx - 190, 220, $cx - 165, 800, $hl);
    }

    private static function dropper($im, int $cx, int $body, int $shade, int $cap, int $label, int $line, int $hl): void
    {
        self::roundedRect($im, $cx - 190, 520, $cx + 190, 1000, 60, $body);
        imagefilledrectangle($im, $cx + 110, 560, $cx + 180, 960, $shade);
        imagefilledrectangle($im, $cx - 70, 440, $cx + 70, 530, $body);
        self::roundedRect($im, $cx - 90, 360, $cx + 90, 450, 16, $cap);
        imagefilledellipse($im, $cx, 250, 150, 250, self::color($im, '#2a2124'));
        self::label($im, $cx - 190, 660, $cx + 190, 880, $label, $line);
        imagefilledrectangle($im, $cx - 150, 550, $cx - 125, 960, $hl);
    }

    private static function compact($im, int $cx, int $body, int $shade, int $cap, int $capShade, int $label, int $line): void
    {
        imagefilledellipse($im, $cx, 760, 760, 300, $shade);
        imagefilledrectangle($im, $cx - 380, 640, $cx + 380, 760, $body);
        imagefilledellipse($im, $cx, 640, 760, 300, $capShade);
        imagefilledellipse($im, $cx, 620, 760, 300, $cap);
        imagefilledellipse($im, $cx, 620, 520, 200, $label);
        imageellipse($im, $cx, 620, 470, 170, $line);
    }

    private static function label($im, int $x1, int $y1, int $x2, int $y2, int $fill, int $line): void
    {
        imagefilledrectangle($im, $x1, $y1, $x2, $y2, $fill);
        imagesetthickness($im, 3);
        imagerectangle($im, $x1 + 24, $y1 + 24, $x2 - 24, $y2 - 24, $line);
        $mid = intdiv($y1 + $y2, 2);
        $cx = intdiv($x1 + $x2, 2);
        imagefilledrectangle($im, $cx - 90, $mid - 30, $cx + 90, $mid - 18, $line);
        imagefilledrectangle($im, $cx - 60, $mid + 8, $cx + 60, $mid + 16, $line);
        imagesetthickness($im, 1);
    }

    private static function roundedRect($im, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$x, $y]) {
            imagefilledellipse($im, $x, $y, $r * 2, $r * 2, $color);
        }
    }

    private static function gradient($im, string $from, string $to): void
    {
        [$r1, $g1, $b1] = self::rgb($from);
        [$r2, $g2, $b2] = self::rgb($to);

        for ($y = 0; $y < self::SIZE; $y++) {
            $t = $y / self::SIZE;
            $color = imagecolorallocate($im, (int) ($r1 + ($r2 - $r1) * $t), (int) ($g1 + ($g2 - $g1) * $t), (int) ($b1 + ($b2 - $b1) * $t));
            imageline($im, 0, $y, self::SIZE, $y, $color);
        }
    }

    private static function color($im, string $hex): int
    {
        [$r, $g, $b] = self::rgb($hex);

        return imagecolorallocate($im, $r, $g, $b);
    }

    /** @return array{int, int, int} */
    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function shade(string $hex, float $amount): string
    {
        return '#'.implode('', array_map(
            fn (int $channel): string => str_pad(dechex((int) max(0, min(255, $channel + 255 * $amount))), 2, '0', STR_PAD_LEFT),
            self::rgb($hex),
        ));
    }
}
