<?php

namespace App\Support\Translation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;

/**
 * Packages name some locales differently from us: Filament ships Serbian as "sr_Latn" (and "sr_Cyrl"), while the
 * platform uses "sr" (Latin script) in its URLs, its language files and the name_sr columns. For a package's
 * strings, this loader reads the aliased locale first and lets a file of our own under lang/vendor override it.
 */
class LocaleAliasLoader extends FileLoader
{
    /**
     * @param  array<int, string>|string  $path
     * @param  array<string, string>  $aliases  our locale => the package's locale
     */
    public function __construct(Filesystem $files, array|string $path, private readonly array $aliases = [])
    {
        parent::__construct($files, $path);
    }

    /**
     * @return array<mixed>
     */
    protected function loadNamespaced($locale, $group, $namespace)
    {
        $lines = parent::loadNamespaced($locale, $group, $namespace);
        $alias = $this->aliases[$locale] ?? null;

        if ($alias === null || ! isset($this->hints[$namespace])) {
            return $lines;
        }

        $aliased = $this->loadPaths([$this->hints[$namespace]], $alias, $group);

        return array_replace_recursive($aliased, $lines);
    }
}
