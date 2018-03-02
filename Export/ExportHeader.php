<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 29.06.17
 * Time: 16:57
 */

namespace Marlinc\AdminBundle\Export;


class ExportHeader
{
    /**
     * @var array
     */
    private $simpleFields;

    /**
     * @var array
     */
    private $groupFields;

    /**
     * ExportHeader constructor.
     */
    public function __construct()
    {
        $this->simpleFields = [];
        $this->groupFields = [];
    }

    public static function createSimpleHeader(string $name, $color = null, $font = null, $comment = '', $colspan = null) {
        $header = new self();
        $header->addSimpleField($name, $color, $font, $comment, $colspan);

        return $header;
    }

    /**
     * @param string $name
     * @param string $color
     * @param string $font
     * @param string $comment
     * @param int $colspan
     * @return self
     */
    public function addSimpleField($name, $color = null, $font = null, $comment = '', $colspan = null)
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

    /**
     * @param string $name
     * @param string $color
     * @param string $font
     * @param string $comment
     * @param int $colspan
     * @return ExportHeader
     */
    public function addGroupField($name, $color = null, $font = null, $comment = '', $colspan = null) {
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

    public function hasGroupField() {
        return count($this->groupFields) > 0;
    }

    public function getGroupField() {
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

    public function getGroupFields() {
        if (count($this->groupFields) == 0 && count($this->simpleFields) == 0) {
            return [];
        }

        if (count($this->groupFields) <= 1) {
            return [$this->getGroupField()];
        }

        return $this->groupFields;
    }

    public function getSimpleFields() {
        return $this->simpleFields;
    }

    public function getColumnCount() {
        return count($this->simpleFields);
    }
}