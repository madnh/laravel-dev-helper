<?php

namespace MaDnh\LaravelDevHelper\Command\Traits;

use Symfony\Component\Finder\SplFileInfo;

trait PublishAssets
{
    public function doPublishDir($src, $dest, $replace = [], $debug = true)
    {
        $src = realpath($src);
        $dest = realpath($dest);

        if ($debug) {
            $basePathLength = strlen(base_path());
            $relativeSrc = substr($src, $basePathLength);
            $relativeDest = substr($dest, $basePathLength);

            if (empty($relativeSrc)) {
                $relativeSrc = '/';
            }
            if (empty($relativeDest)) {
                $relativeDest = '/';
            }

            $this->info('Publish directory <comment>[' . $this->beautyPath($relativeSrc) . ']</comment> To <comment>[' . $this->beautyPath($relativeDest) . ']</comment>');
        }

        /**
         * @var SplFileInfo[] $files
         */
        $files = \File::allFiles($src);
        foreach ($files as $file) {
            $target = $dest . DIRECTORY_SEPARATOR . $file->getRelativePathname();

            $this->doPublishFile($file->getRealPath(), $target, $replace, $debug);
        }

    }

    /**
     * @param SplFileInfo|string $file
     * @param string $target
     * @param array $replace
     * @param bool $debug
     */
    public function doPublishFile($file, $target, $replace = [], $debug = true)
    {
        $file = realpath($file);
        $content = file_get_contents($file);

        if (!empty($replace)) {
            $content = str_replace(array_keys($replace), array_values($replace), $content);
        }
        $dir = dirname($target);
        if (!is_dir($dir)) {
            \File::makeDirectory($dir, 0755, true);
        }
        file_put_contents($target, $content);

        if ($debug) {
            $basePathLength = strlen(base_path());
            $relativeSrc = substr($file, $basePathLength);
            $relativeDest = substr($target, $basePathLength);
            $this->info($this->getListIndex() . 'Publish file <comment>[' . $this->beautyPath($relativeSrc) . ']</comment> To <comment>[' . $this->beautyPath($relativeDest) . ']</comment>');
        }
    }

    protected function beautyPath($path)
    {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    }
}