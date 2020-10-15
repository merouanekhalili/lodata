<?php

namespace Flat3\Lodata\Helper;

use Illuminate\Support\Facades\File;
use ReflectionClass;

class Traits
{
    public static function getClassesByTrait(string $trait): array
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $classes = [];

        foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            $classes = array_merge(collect(File::allFiles(base_path($path)))
                ->map(function ($item) use ($namespace) {
                    $path = $item->getRelativePathName();
                    return sprintf(
                        '\%s%s',
                        $namespace,
                        strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                    );
                })
                ->filter(function ($class) use ($trait) {
                    $valid = false;
                    if (class_exists($class)) {
                        $reflection = new ReflectionClass($class);
                        $valid = in_array(
                                $trait,
                                array_keys($reflection->getTraits())
                            ) && !$reflection->isAbstract();
                    }
                    return $valid;
                })
                ->values()
                ->toArray(), $classes);
        }

        return $classes;
    }
}