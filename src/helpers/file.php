<?php

declare(strict_types=1);

namespace sqrd\ApisCP\Webapps\Bedrock\Helpers;

use WebFu\Dot\Dot;

class File
{
    public static function read_json(string $path, $property = null)
    {
        $data = null;

        $contents = silence(static function () use ($path) {
            return file_get_contents($path);
        });

        if (false !== $contents) {
            $data = (array)json_decode($contents, true);

            if (!is_null($property)) {
                $dot = new Dot($data);
                $data = $dot->get($property, null);
            }
        }

        return $data;
    }
}
