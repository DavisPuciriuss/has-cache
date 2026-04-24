<?php

namespace Bunkuris\Console;

use Bunkuris\Contracts\CacheKeyManagerContract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use SplFileInfo;

class ListCacheKeyManagersCommand extends Command
{
    protected $signature = 'cache:list:managers {name? : Filter managers by name}';

    protected $description = 'List all cache key managers.';

    public function handle(): int
    {
        $basePath = $this->laravel->basePath(
            Config::string('has-cache.managers_path', 'app/Support/Cache')
        );

        if (!File::isDirectory($basePath)) {
            $this->components->warn('No cache key managers found.');

            return self::SUCCESS;
        }

        $search = $this->argument('name');

        if (! \is_string($search)) {
            $search = null;
        }

        /** @var array<int, array{name: string, path: string}> $managers */
        $managers = [];

        foreach (File::allFiles($basePath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $fqcn = $this->classFromFile($file);

            if ($fqcn === null || ! class_exists($fqcn)) {
                continue;
            }

            $reflection = new ReflectionClass($fqcn);

            if (! $reflection->isInstantiable() || ! $reflection->implementsInterface(CacheKeyManagerContract::class)) {
                continue;
            }

            $name = class_basename($fqcn);

            if ($search !== null && $search !== '' && ! Str::contains(Str::lower($name), Str::lower($search))) {
                continue;
            }

            $managers[] = [
                'name' => $name,
                'path' => $file->getPathname(),
            ];
        }

        if (! \count($managers)) {
            $message = 'No cache key managers found';

            if ($search) {
                $message .= " matching [{$search}]";
            }

            $this->components->warn($message);

            return self::SUCCESS;
        }

        $this->components->twoColumnDetail('<fg=green;options=bold>Name</>', '<fg=green;options=bold>Path</>');

        foreach ($managers as $manager) {
            $this->components->twoColumnDetail($manager['name'], $manager['path']);
        }

        $this->newLine();

        return self::SUCCESS;
    }

    protected function classFromFile(SplFileInfo $file): ?string
    {
        $contents = file_get_contents($file->getPathname());

        if ($contents === false) {
            return null; // @codeCoverageIgnore
        }

        $tokens = token_get_all($contents);

        $namespace = '';
        $class = null;

        for ($i = 0, $len = \count($tokens); $i < $len; $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                $i += 2;
                while (isset($tokens[$i]) && ! \in_array($tokens[$i], [';', '{'], true)) {
                    $namespace .= \is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                    $i++;
                }
            }

            if (\in_array($tokens[$i][0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                for ($j = $i + 1; $j < $len; $j++) {
                    if ($tokens[$j][0] === T_WHITESPACE) {
                        continue;
                    }
                    if ($tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                    }
                    break;
                }
                break;
            }
        }

        if (! $class) {
            return null;
        }

        return $namespace !== ''
            ? ltrim($namespace, '\\') . '\\' . $class
            : $class;
    }
}
