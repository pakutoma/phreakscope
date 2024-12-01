<?php

namespace Pakutoma\Phreakscope\pprof;

use Pakutoma\Phreakscope\protobuf\Profiles\PBFunction;

class FunctionInfo
{
    public string $name;
    public string $systemName;
    public string $filename;
    public int $startLine;

    private string $serialized = '';

    public function __construct(string $name, string $filename, int $start_line, string $system_name = '')
    {
        $this->name = $name;
        $this->systemName = $system_name;
        $this->filename = $filename;
        $this->startLine = $start_line;
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
        return [$this->name, $this->filename, $this->systemName];
    }

    public function toProtobuf(int $function_id, array $string_table): PBFunction
    {
        $pb_function = new PBFunction();
        $pb_function->setId($function_id);
        $pb_function->setName($string_table[$this->name]);
        $pb_function->setSystemName($string_table[$this->systemName]);
        $pb_function->setFilename($string_table[$this->filename]);
        $pb_function->setStartLine($this->startLine);
        return $pb_function;
    }
}