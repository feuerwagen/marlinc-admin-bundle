<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Export;

/**
 * Defines the header row(s) of an @see ExportColumn.
 * Each header has at least one (or more) simple field(s) and can have one group field.
 * If a group field is set, the header will be displayed in two rows with
 * the group field on top and the simple fields below.
 */
class ExportHeader
{
    private array $simpleFields;

    private array $groupFields;

    /**
     * ExportHeader constructor.
     */
    public function __construct()
    {
        $this->simpleFields = [];
        $this->groupFields = [];
    }

    public static function createSimpleHeader(string $name, $color = null, $font = null, string $comment = '', int $colspan = null): self
    {
        $header = new self();
        $header->addSimpleField($name, $color, $font, $comment, $colspan);

        return $header;
    }

    public function addSimpleField(string $name, $color = null, $font = null, string $comment = '', int $colspan = null): self
    {
        $field = [
            'name' => $name,
            'comment' => $comment,
            'color' => $color,
            'font' => $font
        ];

        if ($colspan != null) {
            $field['colspan'] = $colspan;
        }

        $this->simpleFields[] = $field;

        return $this;
    }

    public function addGroupField(string $name, $color = null, $font = null, string $comment = '', int $colspan = null): self
    {
        $field = [
            'name' => $name,
            'comment' => $comment,
            'color' => $color,
            'font' => $font
        ];

        if ($colspan != null) {
            $field['colspan'] = $colspan;
        }

        $this->groupFields[] = $field;

        return $this;
    }

    public function hasGroupField(): bool
    {
        return count($this->groupFields) > 0;
    }

    public function getGroupField(): array
    {
        if (count($this->groupFields) == 0) {
            $field = [
                'name' => '',
                'comment' => '',
                'color' => null,
                'font' => null
            ];
        } else {
            $field = current($this->groupFields);
        }

        if (!array_key_exists('colspan', $field)) {
            $field['colspan'] = $this->getColumnCount();
        }

        return $field;
    }

    public function getGroupFields(): array
    {
        if (count($this->groupFields) == 0 && count($this->simpleFields) == 0) {
            return [];
        }

        if (count($this->groupFields) <= 1) {
            return [$this->getGroupField()];
        }

        return $this->groupFields;
    }

    public function getSimpleFields(): array
    {
        return $this->simpleFields;
    }

    public function getColumnCount(): int
    {
        return count($this->simpleFields);
    }
}