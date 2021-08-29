<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Color;

/**
 * Defines a HSL color value
 */
final class HslColor
{
    public int $hue;

    public int $saturation;

    public int $lightness;

    public function __construct(int $hue, int $saturation, int $lightness)
    {
        $this->hue = $hue;
        $this->saturation = $saturation;
        $this->lightness = $lightness;
    }

    /**
     * @param string $code RGB Hex color code
     */
    public static function fromHex(string $code): self
    {
        return self::fromDecimal(self::hexToInt($code));
    }

    public static function fromRGB(int $red, int $green, int $blue): self
    {
        $r = ((float)$red) / 255.0;
        $g = ((float)$green) / 255.0;
        $b = ((float)$blue) / 255.0;

        $maxC = max($r, $g, $b);
        $minC = min($r, $g, $b);

        $l = ($maxC + $minC) / 2.0;

        if($maxC == $minC) {
            $s = 0;
            $h = 0;
        } else {
            if($l < .5) {
                $s = ($maxC - $minC) / ($maxC + $minC);
            } else {
                $s = ($maxC - $minC) / (2.0 - $maxC - $minC);
            }

            if($r == $maxC)
                $h = ($g - $b) / ($maxC - $minC);
            if($g == $maxC)
                $h = 2.0 + ($b - $r) / ($maxC - $minC);
            if($b == $maxC)
                $h = 4.0 + ($r - $g) / ($maxC - $minC);

            $h = $h / 6.0;
        }

        $h = (int) round(255.0 * $h);
        $s = (int) round(255.0 * $s);
        $l = (int) round(255.0 * $l);

        return new HslColor($h, $s, $l);
    }

    private static function fromDecimal(int $rgb): self
    {
        $r = 0xFF & ($rgb >> 0x10);
        $g = 0xFF & ($rgb >> 0x8);
        $b = 0xFF & $rgb;

        return self::fromRGB($r, $g, $b);
    }

    /**
     * Convert HTML color code to RGB value.
     *
     * @param string $htmlCode The RGB hex color code
     * @return int RGB decimal representation as a single number with shifted values for red and blue.
     */
    private static function hexToInt(string $htmlCode): int
    {
        // Truncate leading #.
        if ($htmlCode[0] == '#') {
            $htmlCode = substr($htmlCode, 1);
        }

        // Expand short codes.
        if (strlen($htmlCode) == 3) {
            $htmlCode = $htmlCode[0] . $htmlCode[0] . $htmlCode[1] . $htmlCode[1] . $htmlCode[2] . $htmlCode[2];
        }

        // Decode to decimal notation.
        $r = hexdec($htmlCode[0] . $htmlCode[1]);
        $g = hexdec($htmlCode[2] . $htmlCode[3]);
        $b = hexdec($htmlCode[4] . $htmlCode[5]);

        return $b + ($g << 0x8) + ($r << 0x10);
    }
}