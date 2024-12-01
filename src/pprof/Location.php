<?php

namespace Pakutoma\Phreakscope\pprof;

use Pakutoma\Phreakscope\protobuf\profiles\Location as PBLocation;

class Location
{
    public Mapping|null $mapping;
    public int $address;
    /**
     * @var Line[]
     */
    public array $lines;
    public bool $isFolded;

    private string $serialized = '';

    public function __construct(Mapping|null $mapping, int $address, array $lines, bool $is_folded = false)
    {
        $this->mapping = $mapping;
        $this->address = $address;
        $this->lines = $lines;
        $this->isFolded = $is_folded;
    }

    public function serialize(array $mapping_table, array $function_table): string
    {
        if ($this->serialized !== '') {
            return $this->serialized;
        }
        $mapping_id = $this->mapping !== null ? $mapping_table[$this->mapping->serialize()] : null;
        $serialized_lines = [];
        foreach ($this->lines as $line) {
            $serialized_lines[] = "{$function_table[$line->function->serialize()]}:{$line->line}";
        }
        $this->serialized = json_encode([$mapping_id, $this->address, $serialized_lines, $this->isFolded]);
        return $this->serialized;
    }

    public function toProtobuf(int $location_id, array $mapping_table, array $function_table): PBLocation
    {
        $pb_location = new PBLocation();
        $pb_location->setId($location_id);
        if ($this->mapping !== null) {
            $pb_location->setMappingId($mapping_table[$this->mapping->serialize()]);
        }
        $pb_location->setAddress($this->address);
        $pb_lines = [];
        foreach ($this->lines as $line) {
            $pb_lines[] = $line->toProtobuf($function_table);
        }
        $pb_location->setLine($pb_lines);
        $pb_location->setIsFolded($this->isFolded);
        return $pb_location;
    }
}