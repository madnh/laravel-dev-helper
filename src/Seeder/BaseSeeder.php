<?php

namespace MaDnh\LaravelDevHelper\Seeder;


use Illuminate\Database\Seeder;

abstract class BaseSeeder extends Seeder
{
    public function getFilesPath($sub_path = null)
    {
        return database_path('seeds' . DIRECTORY_SEPARATOR . 'files' . ($sub_path ? DIRECTORY_SEPARATOR . $sub_path : ''));
    }

    public function getFakeData($fakeFile)
    {
        $file = $this->getFilesPath($fakeFile);

        if (!file_exists($file)) {
            throw new \Exception('Fake file data not found: ' . $fakeFile);
        }
        if (ends_with($file, '.json')) {
            return json_decode(file_get_contents($file), true);
        }
        if (ends_with($file, '.php')) {
            $data = require $file;
            return (array)$data;
        }

        return file_get_contents($file);
    }
}