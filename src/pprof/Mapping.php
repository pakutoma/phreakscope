<?php

namespace Pakutoma\Phreakscope\pprof;

use Pakutoma\Phreakscope\protobuf\profiles\Mapping as PBMapping;

class Mapping
{
    public int $memoryStart;
    public int $memoryLimit;
    public int $fileOffset;
    public string $filename;
    public string $buildId;
    public bool $hasFunctions;
    public bool $hasFilenames;
    public bool $hasLineNumbers;
    public bool $hasInlineFrames;

    private string $serialized = '';

    public function __construct(
        int    $memory_start,
        int    $memory_limit,
        int    $file_offset,
        string $filename,
        string $build_id,
        bool   $has_functions,
        bool   $has_filenames,
        bool   $has_line_numbers,
        bool   $has_inline_frames
    )
    {
        $this->memoryStart = $memory_start;
        $this->memoryLimit = $memory_limit;
        $this->fileOffset = $file_offset;
        $this->filename = $filename;
        $this->buildId = $build_id;
        $this->hasFunctions = $has_functions;
        $this->hasFilenames = $has_filenames;
        $this->hasLineNumbers = $has_line_numbers;
        $this->hasInlineFrames = $has_inline_frames;
    }

    public function serialize(): string
    {
        if ($this->serialized !== '') {
            return $this->serialized;
        }
        $this->serialized = json_encode($this);
        return $this->serialized;
    }

    public function getStrings(): array
    {
        return [$this->filename, $this->buildId];
    }

    public function toProtobuf(int $mapping_id, array $string_table): PBMapping
    {
        $pb_mapping = new PBMapping();
        $pb_mapping->setId($mapping_id);
        $pb_mapping->setMemoryStart($this->memoryStart);
        $pb_mapping->setMemoryLimit($this->memoryLimit);
        $pb_mapping->setFileOffset($this->fileOffset);
        $pb_mapping->setFilename($string_table[$this->filename]);
        $pb_mapping->setBuildId($string_table[$this->buildId]);
        $pb_mapping->setHasFunctions($this->hasFunctions);
        $pb_mapping->setHasFilenames($this->hasFilenames);
        $pb_mapping->setHasLineNumbers($this->hasLineNumbers);
        $pb_mapping->setHasInlineFrames($this->hasInlineFrames);
        return $pb_mapping;
    }
}