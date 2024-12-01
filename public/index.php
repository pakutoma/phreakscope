<?php
declare(strict_types=1);

xhprof_sample_enable();

function save_prof($xhprof_data, string $directory)
{
    $serialized_prof = serialize($xhprof_data);

    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    [$str_time_usec, $str_time_sec] = explode(' ', microtime());
    $time_usec = (int)$str_time_sec * 1_000_000 + (int)substr($str_time_usec, 2, 6);
    $file_name = $directory . '/' . (string)$time_usec . '.xhprof';
    $file = fopen($file_name, 'w');

    if ($file) {
        fwrite($file, $serialized_prof);
        fclose($file);
    } else {
        throw new RuntimeException("Could not open $file_name\n");
    }
}

register_shutdown_function(function () {
    $xhprof_data = xhprof_sample_disable();
    save_prof($xhprof_data, '/tmp/xhprof');
});