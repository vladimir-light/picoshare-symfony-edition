<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;


class PicoTwigExtension
{
    #[AsTwigFilter('human_filesize')]
    #[AsTwigFunction('human_filesize')]
    public function formatSizeInBytes(int $bytes = 0, bool $space_between = true): string
    {
        if($bytes <= 1023)
        {
            return number_format($bytes, '2', '.', '') . strtoupper('b');
        }

        $ranges = [
            //'kib' => 1 << 10,
            'kb' => 1024,
            //'mib' => 1 << 20,
            'mb' => 1024 ** 2,
            //'gib' => 1 << 30,
            'gb' => 1024 ** 3,
        ];

        $unit = null;
        $divisor = 1;
        foreach (array_reverse($ranges) as $unit => $maxBytes) {
            if ($bytes >= $maxBytes) {
                $divisor = $maxBytes;
                break;
            }
        }


        // gigabytes are max possible units. In case someone uploads a 1gb+ file
        if ($unit !== null && $divisor === 1) {
            $divisor = $ranges['gb'];
        }
        $res = $bytes / $divisor;

        return number_format($res, '2', '.', '') . ($space_between ? ' ' : '') . strtoupper($unit);
    }
}
