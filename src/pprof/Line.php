<?php

namespace Pakutoma\Phreakscope\pprof;

use Pakutoma\Phreakscope\protobuf\profiles\Line as PBLine;

class Line
{
    public FunctionInfo|null $function;
    public int $line;

    public function __construct(FunctionInfo $function, int $line)
    {
        $this->function = $function;
        $this->line = $line;
    }

    public function toProtobuf(array $function_table): PBLine
    {
        $pb_line = new PBLine();
        $pb_line->setFunctionId($function_table[$this->function->serialize()]);
        $pb_line->setLine($this->line);
        return $pb_line;
    }
}