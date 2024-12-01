<?php

namespace Pakutoma\Phreakscope\pprof;

use Pakutoma\Phreakscope\protobuf\profiles\Profile as PBProfile;
use Pakutoma\Phreakscope\protobuf\profiles\ValueType as PBValueType;

class ValueType
{
    public string $type;
    public string $unit;

    public function __construct($type, $unit)
    {
        $this->type = $type;
        $this->unit = $unit;
    }

    public function getStrings(): array
    {
        return [$this->type, $this->unit];
    }

    public function toProtobuf(array $string_table): PBValueType
    {
        $pb_value_type = new PBValueType();
        $pb_value_type->setType($string_table[$this->type]);
        $pb_value_type->setUnit($string_table[$this->unit]);
        return $pb_value_type;
    }
}