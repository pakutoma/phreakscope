<?php

namespace Pakutoma\Phreakscope;

use FilesystemIterator;
use pprof\XhprofConverter;

class ProfileEndpoint
{
    const XHPROF_DIR = '/tmp/xhprof';
    const PPROF_DEFAULT_SECONDS = 30;

    /*
     * リクエストが来た時点でそれまでのプロファイルを削除する。
     * リクエスト秒数だけ待ち、その後にプロファイルを取得する。
     * すると、リクエストされた秒数の間に終了したリクエストのプロファイルが取得できる。
     * このとき、プロファイル取得のリクエストが来た時に動いていなかった分のサンプルは削除する。
     * これにより、取り逃がされるプロファイルは
     * "リクエスト終了までに終了しなかったリクエストの、全サンプル”
     * と、
     * "リクエスト開始時に既に開始していたリクエストの、リクエスト開始までのサンプル"
     * の2種類になる。
     */
    function run(string $seconds): void
    {
        $start_ns = self::getNanotime();

        if ((int)$seconds > 0) {
            $duration_ns = (int)$seconds * 1_000_000_000;
        } else {
            $duration_ns = self::PPROF_DEFAULT_SECONDS * 1_000_000_000;
        }

        $converter = new XhprofConverter();
        self::removeXhprofFiles(self::XHPROF_DIR);
        $duration_us = $duration_ns / 1_000;
        usleep($duration_us);
        $pprof = $converter->convertToPprof(self::XHPROF_DIR, $start_ns, $duration_ns);

        $gz_pprof = gzencode($pprof);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="profile"');
        header('Content-Encoding: gzip');
        echo $gz_pprof;
    }

    public static function removeXhprofFiles(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $fs_iter = new FilesystemIterator($directory,
            FilesystemIterator::KEY_AS_FILENAME |
            FilesystemIterator::CURRENT_AS_FILEINFO |
            FilesystemIterator::SKIP_DOTS
        );
        foreach ($fs_iter as $file) {
            unlink($file->getPathname());
        }
    }

    public static function getNanotime(): int
    {
        [$str_time_usec, $str_time_sec] = explode(' ', microtime());
        return (int)$str_time_sec * 1_000_000_000 + (int)substr($str_time_usec, 2, 6) * 1000;
    }
}