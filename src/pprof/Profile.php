<?php

namespace Pakutoma\Phreakscope\pprof;

use Pakutoma\Phreakscope\protobuf\profiles\Profile as PBProfile;

class Profile
{
    /**
     * @var ValueType[]
     */
    public array $sampleTypes;
    /**
     * @var Sample[]
     */
    public array $samples;
    public string $dropFrames;
    public string $keepFrames;
    public int $timeNanos;
    public int $durationNanos;
    public ValueType $periodType;
    public int $period;
    /**
     * @var string[]
     */
    public array $comment;
    public string|null $defaultSampleType;

    /**
     * @var array<string, int>
     */
    private array $mapping_table = [];
    /**
     * @var Protobuf\Mapping[]
     */
    private array $mappings = [];
    /**
     * @var array<string, int>
     */
    private array $location_table = [];
    /**
     * @var Protobuf\Location[]
     */
    private array $locations = [];
    /**
     * @var array<string, int>
     */
    private array $function_table = [];
    /**
     * @var Protobuf\PBFunction[]
     */
    private array $functions = [];
    /**
     * @var array<string, int>
     */
    private array $string_table = ['' => 0];

    public function __construct(
        $samples,
        $time_ns,
        $sample_types = [new ValueType('cpu', 'nanoseconds')],
        $drop_frames = '',
        $keep_frames = '',
        $duration_ns = 1000000000,
        $period_type = new ValueType('cpu', 'nanoseconds'),
        $period = 10000000,
        $comment = [],
        $default_sample_type = null
    )
    {
        $this->sampleTypes = $sample_types;
        $this->samples = $samples;
        $this->dropFrames = $drop_frames;
        $this->keepFrames = $keep_frames;
        $this->timeNanos = $time_ns;
        $this->durationNanos = $duration_ns;
        $this->periodType = $period_type;
        $this->period = $period;
        $this->comment = $comment;
        $this->defaultSampleType = $default_sample_type;
    }

    public function toProtobufBinary(): string
    {
        return $this->toProtobuf()->serializeToString();
    }

    private function toProtobuf(): PBProfile
    {
        $this->mapping_table = [];
        $this->mappings = [];
        $this->location_table = [];
        $this->locations = [];
        $this->function_table = [];
        $this->functions = [];
        $this->string_table = ['' => 0];

        $pb_profile = new PBProfile();
        $sample_types = [];
        foreach ($this->sampleTypes as $sample_type) {
            $this->addToStringTable($sample_type->getStrings());
            $sample_types[] = $sample_type->toProtobuf($this->string_table);
        }
        $pb_profile->setSampleType($sample_types);

        $samples = [];
        foreach ($this->samples as $sample) {
            foreach ($sample->locations as $location) {
                if ($location->mapping !== null) {
                    $this->addToStringTable($location->mapping->getStrings());
                    $this->checkAndAddToMappingTable($location->mapping);
                }
                foreach ($location->lines as $line) {
                    if ($line->function !== null) {
                        $this->addToStringTable($line->function->getStrings());
                        $this->checkAndAddToFunctionTable($line->function);
                    }
                }
                $this->checkAndAddToLocationTable($location);
            }
            foreach ($sample->labels as $label) {
                $this->addToStringTable($label->getStrings());
            }
            $samples[] = $sample->toProtobuf(
                $this->location_table, $this->mapping_table, $this->function_table, $this->string_table
            );
        }
        $pb_profile->setSample($samples);

        $pb_profile->setMapping($this->mappings);
        $pb_profile->setLocation($this->locations);
        $pb_profile->setFunction($this->functions);

        $this->addToStringTable([$this->dropFrames]);
        $pb_profile->setDropFrames($this->string_table[$this->dropFrames]);
        $this->addToStringTable([$this->keepFrames]);
        $pb_profile->setKeepFrames($this->string_table[$this->keepFrames]);
        $pb_profile->setTimeNanos($this->timeNanos);
        $pb_profile->setDurationNanos($this->durationNanos);
        $pb_profile->setPeriodType($this->periodType->toProtobuf($this->string_table));
        $pb_profile->setPeriod($this->period);
        $comments = [];
        foreach ($this->comment as $comment) {
            $this->addToStringTable([$comment]);
            $comments[] = $this->string_table[$comment];
        }
        $pb_profile->setComment($comments);
        if ($this->defaultSampleType !== null) {
            $this->addToStringTable([$this->defaultSampleType]);
            $pb_profile->setDefaultSampleType($this->string_table[$this->defaultSampleType]);
        }
        $pb_profile->setStringTable(array_flip($this->string_table));

        return $pb_profile;
    }

    private function addToStringTable(array $strings): void
    {
        foreach ($strings as $string) {
            if (!array_key_exists($string, $this->string_table)) {
                $this->string_table[$string] = count($this->string_table);
            }
        }
    }

    private function checkAndAddToMappingTable(Mapping $mapping): void
    {
        $serialized = $mapping->serialize();
        if (!array_key_exists($serialized, $this->mapping_table)) {
            $mapping_id = count($this->mapping_table) + 1;
            $this->mapping_table[$serialized] = $mapping_id;
            $this->mappings[$mapping_id] = $mapping->toProtobuf($mapping_id, $this->string_table);
        }
    }

    private function checkAndAddToLocationTable(Location $location): void
    {
        $serialized = $location->serialize($this->mapping_table, $this->function_table);
        if (!array_key_exists($serialized, $this->location_table)) {
            $location_id = count($this->location_table) + 1;
            $this->location_table[$serialized] = $location_id;
            $this->locations[$location_id] = $location->toProtobuf($location_id, $this->mapping_table, $this->function_table);
        }
    }

    private function checkAndAddToFunctionTable(FunctionInfo $function): void
    {
        $serialized = $function->serialize();
        if (!array_key_exists($serialized, $this->function_table)) {
            $function_id = count($this->function_table) + 1;
            $this->function_table[$serialized] = $function_id;
            $this->functions[$function_id] = $function->toProtobuf($function_id, $this->string_table);
        }
    }
}
