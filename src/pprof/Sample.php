<?php

namespace Pakutoma\Phreakscope\pprof;

use Pakutoma\Phreakscope\protobuf\profiles\Profile as PBProfile;
use Pakutoma\Phreakscope\protobuf\profiles\Sample as PBSample;

class Sample
{
    /**
     * @var Location[]
     */
    public array $locations;
    /**
     * @var int[]
     */
    public array $values;
    /**
     * @var Label[]
     */
    public array $labels;

    public function __construct(array $locations, array $values, array $labels)
    {
        $this->locations = $locations;
        $this->values = $values;
        $this->labels = $labels;
    }

    public function toProtobuf(array $location_table,
                               array $mapping_table,
                               array $function_table,
                               array $string_table): PBSample
    {
        $pb_sample = new PBSample();
        $location_ids = [];
        foreach ($this->locations as $location) {
            $location_ids[] = $location_table[$location->serialize($mapping_table, $function_table)];
        }
        $pb_sample->setLocationId($location_ids);
        $pb_sample->setValue($this->values);
        $labels = [];
        foreach ($this->labels as $label) {
            $labels[] = $label->toProtobuf($string_table);
        }
        $pb_sample->setLabel($labels);
        return $pb_sample;
    }
}