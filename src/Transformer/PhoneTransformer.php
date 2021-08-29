<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Transformer;


use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Marlinc\AdminBundle\Export\ExportColumn;

class PhoneTransformer implements TransformerInterface
{
    /**
     * TODO: Replace string values with class constants.
     * @var string|int One of the format constants in {@see PhoneNumberFormat}
     */
    private int $format;

    public function __construct($format = PhoneNumberFormat::E164)
    {
        $this->format = $format;
    }

    /**
     * @inheritdoc
     */
    public function transform(string $name, int $type, array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value instanceof PhoneNumber) {
                if (is_numeric($this->format)) {
                    $data[$key] = PhoneNumberUtil::getInstance()->format($value, $this->format);
                } elseif ($this->format == 'carrier_nat') {
                    $parts = explode(' ', PhoneNumberUtil::getInstance()->format($value, PhoneNumberFormat::NATIONAL));
                    $data[$key] = $parts[0];
                } elseif ($this->format == 'carrier_int') {
                    $parts = explode(' ', PhoneNumberUtil::getInstance()->format($value, PhoneNumberFormat::INTERNATIONAL));
                    $data[$key] = $parts[0].' '.$parts[1];
                } elseif ($this->format == 'local') {
                    $parts = explode(' ', PhoneNumberUtil::getInstance()->format($value, PhoneNumberFormat::NATIONAL));
                    array_shift($parts);
                    $data[$key] = implode('', $parts);
                } elseif ($this->format == 'nat_hyphen') {
                    $parts = explode(' ', PhoneNumberUtil::getInstance()->format($value, PhoneNumberFormat::NATIONAL));
                    $carrier = array_shift($parts);
                    $data[$key] = $carrier.'-'.implode('', $parts);
                }
            } else {
                $data[$key] = (string) $value;
            }
        }

        if ($type == ExportColumn::TYPE_SINGLE) {
            return [$name => implode(', ', $data)];
        }

        return $data;
    }

}