<?php


namespace MaDnh\LaravelDevHelper\Command;


use MaDnh\LaravelDevHelper\Command\Exceptions\PublishCommandMissingServiceProviderClassName;
use MaDnh\LaravelDevHelper\Command\Traits\PublishAssets;

/**
 * Usage:
 * php artisan app:publish foo bar => publish by methods
 * php artisan app:publish vendor | php artisan app:publish => publish all of tags registered in service provider
 * php artisan app:publish vendor --tag=config,assets, publish registered tags
 */
class BasePublish extends BaseCommand
{
    use PublishAssets;

    protected $signature = 'app:publish {methods?* : Publish methods} {--force : Overwrite any existing files} {--tags= : Publish tags when publish by vendor method}';
    protected $description = 'Publish assets';
    protected $serviceProviderClass = null;

    public function handle()
    {
        $this->welcome();

        $argMethods = (array)$this->argument('methods');

        if (empty($argMethods)) {
            $this->publishAll();
            return;
        }

        $methods = [];
        $vendorTags = [];
        $publishMethods = array_flip($this->getPublishMethods());

        foreach ($argMethods as $argMethod) {
            $studlyMethod = 'publish' . studly_case($argMethod);

            if ($studlyMethod === 'publishAll') {
                $this->publishAll();
                return;
            }
            if (array_key_exists($studlyMethod, $publishMethods)) {
                $methods[] = $studlyMethod;
            } else {
                $vendorTags[] = $argMethod;
            }
        }

        foreach ($methods as $method) {
            $this->{$method}();
        }
        if (!empty($vendorTags)) {
            $this->publishVendor($vendorTags);
        }
    }

    /**
     * @return array
     */
    protected function getPublishMethods()
    {
        $publishMethods = [];
        $methods = (new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $methodName = $method->getName();

            if (starts_with($methodName, 'publish')) {
                $publishMethods[] = $method->getName();
            }
        }

        return $publishMethods;
    }

    /**
     * Show welcome message
     */
    protected function welcome()
    {
        $this->banner("Publish Assets");
    }

    public function publishAll()
    {
        $publishMethods = array_diff($this->getPublishMethods(), ['publishVendor', 'publishAll']);

        foreach ($publishMethods as $method) {
            $this->{$method}();
        }

        $this->publishVendor();
    }

    public function publishVendor($tags = [])
    {
        $vendorTags = $this->option('tags');
        $vendorTags = explode(',', $vendorTags);
        if (!empty($vendorTags)) {
            $tags = !empty($tags) ? array_merge($tags, $vendorTags) : $vendorTags;
        }

        if (empty($this->serviceProviderClass)) {
            if (!empty($tags)) {
                throw new PublishCommandMissingServiceProviderClassName('Publish vendor missing service provider class: ' . static::class);
            } else {
                $this->info('Publish vendor service provider class is undefined, ignore');
            }
        }

        $callData = [];


        if (!empty($tags)) {
            $callData['--tag'] = array_unique($tags);
        }

        $callData['--force'] = $this->option('force');
        $callData['--provider'] = $this->serviceProviderClass;

        if (empty($tags)) {
            $this->softTitle('Publish all vendor\' tags');
        } else {
            $this->softTitle('Publish vendor tags: <info>' . implode(', ', $tags) . '</info>');
        }
        
        $this->call('vendor:publish', $callData);
    }
}