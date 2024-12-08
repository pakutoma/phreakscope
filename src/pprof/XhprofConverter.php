<?php

namespace Pakutoma\Phreakscope\pprof;

use FilesystemIterator;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use SplFileInfo;

class XhprofConverter
{
    /**
     * @var FunctionInfo[]
     */
    private array $functions = [];
    private int $sampling_interval_ns;

    public function __construct()
    {
        $this->sampling_interval_ns = ini_get('xhprof.sampling_interval') * 1000;
    }

    public function convertToPprof(string $directory, int $start_ns, int $duration_ns): string
    {
        $fs_iter = new FilesystemIterator($directory,
            FilesystemIterator::KEY_AS_FILENAME |
            FilesystemIterator::CURRENT_AS_FILEINFO |
            FilesystemIterator::SKIP_DOTS
        );
        $this->functions = [];
        $samples = [];
        foreach ($fs_iter as $file) {
            /** @var SplFileInfo $file */
            $raw_xhprof_samples = unserialize(file_get_contents($file->getPathname()));
            $timestamp_us = (int)substr($file->getFilename(), 0, -7); // remove .xhprof
            $xhprof_samples = [];
            $sample_timestamp_ns = $timestamp_us * 1000;
            foreach (array_reverse($raw_xhprof_samples) as $xhprof_sample) {
                if ($sample_timestamp_ns < $start_ns) {
                    break;
                }
                $xhprof_samples[] = $xhprof_sample;
                $sample_timestamp_ns -= $this->sampling_interval_ns;
            }
            $xhprof_samples = array_reverse($xhprof_samples);

            $elapsed_time = $this->sampling_interval_ns;
            $current_xhprof_sample = '';
            foreach ($xhprof_samples as $next_xhprof_sample) {
                if ($next_xhprof_sample === $current_xhprof_sample) {
                    $elapsed_time += $this->sampling_interval_ns;
                    continue;
                }
                if ($current_xhprof_sample === '') {
                    $current_xhprof_sample = $next_xhprof_sample;
                    continue;
                }

                $xhprof_sample = $current_xhprof_sample;
                $info_pos = strrpos($xhprof_sample, '#');
                $info = '';
                if ($info_pos !== false) {
                    $call_tree = substr($xhprof_sample, 0, $info_pos);
                    $info = substr($xhprof_sample, $info_pos + 1);
                    $labels = [new Label('xhprof_info', $info)];
                } else {
                    $call_tree = $xhprof_sample;
                    $labels = [];
                }
                $locations = $this->extractLocations($call_tree, $info);
                $samples[] = new Sample(
                    $locations,
                    [$elapsed_time],
                    $labels
                );
                $elapsed_time = $this->sampling_interval_ns;
                $current_xhprof_sample = $next_xhprof_sample;
            }
        }
        $profile = new Profile($samples, $start_ns, duration_ns: $duration_ns);
        return $profile->toProtobufBinary();
    }

    /**
     * @param string $call_tree xhprof's sample
     *
     * @return Location[]
     */
    private function extractLocations(string $call_tree, string $info): array
    {
        $function_names = explode('==>', $call_tree);
        $locations = [];
        foreach ($function_names as $function_name) {
            if (!isset($this->functions[$function_name])) {
                try {
                    $reflection_function = $this->getReflection($function_name);
                    if ($reflection_function instanceof ReflectionMethod) {
                        $class_name = $reflection_function->getDeclaringClass()->getName();
                        $long_function_name = $class_name . '::' . $reflection_function->getName() . ($info ? '#' . $info : '');
                    } else {
                        $long_function_name = $reflection_function->getName() . ($info ? '#' . $info : '');
                    }
                    $this->functions[$function_name] = new FunctionInfo(
                        $long_function_name,
                        $reflection_function->getFileName(),
                        $reflection_function->getStartLine()
                    );
                } catch (ReflectionException) {
                    $this->functions[$function_name] = new FunctionInfo($function_name . '(unknown)', '', 0);
                }
            }
            $location = new Location(
                null,
                0,
                [
                    new Line(
                        $this->functions[$function_name],
                        $this->functions[$function_name]->startLine,
                    )
                ]
            );
            $locations[] = $location;
        }
        return array_reverse($locations);
    }

    /**
     * @throws ReflectionException
     */
    function getReflection(string $function_name): ReflectionFunctionAbstract
    {
        $at_pos = strrpos($function_name, '@');
        if ($at_pos !== false) {
            $function_name = substr($function_name, 0, $at_pos);
        }
        if (str_contains($function_name, '::')) {
            return new ReflectionMethod($function_name);
        } else {
            $brackets_pos = strrpos($function_name, '()');
            if ($brackets_pos !== false) {
                $function_name = substr($function_name, 0, $brackets_pos);
            }
            return new ReflectionFunction($function_name);
        }
    }
}