<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 02.03.18
 * Time: 15:57
 */

namespace Marlinc\AdminBundle\Color;

class HslColor
{
    /**
     * @var int
     */
    private $hue;

    /**
     * @var int
     */
    private $saturation;

    /**
     * @var int
     */
    private $lightness;

    /**
     * HslColor constructor.
     * @param int $hue
     * @param int $saturation
     * @param int $lightness
     */
    public function __construct(int $hue, int $saturation, int $lightness)
    {
        $this->hue = $hue;
        $this->saturation = $saturation;
        $this->lightness = $lightness;
    }

    /**
     * @return int
     */
    public function getHue(): int
    {
        return $this->hue;
    }

    /**
     * @param int $hue
     * @return HslColor
     */
    public function setHue(int $hue): HslColor
    {
        $this->hue = $hue;

        return $this;
    }

    /**
     * @return int
     */
    public function getSaturation(): int
    {
        return $this->saturation;
    }

    /**
     * @param int $saturation
     * @return HslColor
     */
    public function setSaturation(int $saturation): HslColor
    {
        $this->saturation = $saturation;

        return $this;
    }

    /**
     * @return int
     */
    public function getLightness(): int
    {
        return $this->lightness;
    }

    /**
     * @param int $lightness
     * @return HslColor
     */
    public function setLightness(int $lightness): HslColor
    {
        $this->lightness = $lightness;

        return $this;
    }

    /**
     * @param string $code
     * @return HslColor
     */
    public static function fromHTML(string $code): HslColor
    {
        return self::fromRGB(self::HTMLToRGB($code));
    }

    /**
     * @param int $rgb
     * @return HslColor
     */
    public static function fromRGB(int $rgb): HslColor
    {
        $r = 0xFF & ($rgb >> 0x10);
        $g = 0xFF & ($rgb >> 0x8);
        $b = 0xFF & $rgb;

        $r = ((float)$r) / 255.0;
        $g = ((float)$g) / 255.0;
        $b = ((float)$b) / 255.0;

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

    /**
     * Convert HTML color code to RGB value.
     *
     * @param string $htmlCode
     * @return int
     */
    private static function HTMLToRGB(string $htmlCode)
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