<?php

namespace Pakutoma\Phreakscope\pprof;

use \Pakutoma\Phreakscope\protobuf\profiles\Label as PBLabel;


class Label
{
    public string $key;
    public string $str;
    public int $num;
    public string $numUnit;

    public function __construct(string $key, string $str = '', int $num = 0, string $num_unit = '')
    {
        $this->key = $key;
        $this->str = $str;
        $this->num = $num;
        $this->numUnit = $num_unit;
    }

    public function toProtobuf(array $string_table): PBLabel
    {
        $pb_label = new PBLabel();
        $pb_label->setKey($string_table[$this->key]);
        $pb_label->setStr($string_table[$this->str]);
        $pb_label->setNum($this->num);
        $pb_label->setNumUnit($string_table[$this->numUnit]);
        return $pb_label;
    }

    public function getStrings(): array
    {
        return [$this->key, $this->str, $this->numUnit];
    }
}