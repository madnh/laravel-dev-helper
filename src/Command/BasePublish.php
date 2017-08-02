<?php


namespace MaDnh\LaravelDevHelper\Command;


use MaDnh\LaravelDevHelper\Command\Exceptions\PublishCommandMissingServiceProviderClassName;
use MaDnh\LaravelDevHelper\Command\Exceptions\PublishCommandWithUndefinedTag;
use MaDnh\LaravelDevHelper\Command\Traits\PublishAssets;

class BasePublish extends BaseCommand
{
    use PublishAssets;

    protected $signature = 'app:publish {methods?* : Publish methods} {--force : Overwrite any existing files} {--tag= : Publish tags when publish by vendor method}';
    protected $description = 'Publish assets';
    protected $serviceProviderClass = null;

    public function handle()
    {
        $this->welcome();

        $methods = [];
        $undefinedMethods = [];
        $argMethods = (array)$this->argument('methods');

        foreach ($argMethods as $argMethod) {
            $studlyMethod = 'publish' . studly_case($argMethod);

            if (method_exists($this, $studlyMethod)) {
                $methods[] = $studlyMethod;
            } else {
                $undefinedMethods[] = $argMethod;
            }
        }
        if (!empty($undefinedMethods)) {
            throw new PublishCommandWithUndefinedTag('Publish assets with an undefined tag: ' . implode(', ', $undefinedMethods));
        }

        foreach ($methods as $method) {
            $this->{$method}();
        }
    }

    /**
     * Show welcome message
     */
    protected function welcome()
    {
        $this->banner("Publish Assets");
    }

    protected function publishVendor()
    {
        if (empty($this->serviceProviderClass)) {
            throw new PublishCommandMissingServiceProviderClassName('Publish vendor missing service provider class: ' . static::class);
        }

        $this->softTitle('Publish Vendor of "<info>' . $this->serviceProviderClass . '</info>"');

        $callData = [];

        $tags = $this->option('tags');
        $tags = explode(',', $tags);

        if (!empty($tags)) {
            $callData['--tag'] = $tags;
        }

        $callData['--force'] = $this->option('force');
        $callData['--provider'] = $this->serviceProviderClass;

        $this->call('vendor:publish', $callData);
    }
}