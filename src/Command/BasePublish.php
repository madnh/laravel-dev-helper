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

    protected $signature = 'app:publish {methods?* : Publish methods} {--force : Overwrite any existing files} {--tag= : Publish tags when publish by vendor method}';
    protected $description = 'Publish assets';
    protected $serviceProviderClass = null;

    public function handle()
    {
        $this->welcome();

        $argMethods = (array)$this->argument('methods');

        if (empty($argMethods)) {
            $this->publishVendor();
            return;
        }

        $methods = [];
        $vendorTags = [];

        foreach ($argMethods as $argMethod) {
            $studlyMethod = 'publish' . studly_case($argMethod);

            if (method_exists($this, $studlyMethod)) {
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
     * Show welcome message
     */
    protected function welcome()
    {
        $this->banner("Publish Assets");
    }

    protected function publishVendor($tags = [])
    {
        if (empty($this->serviceProviderClass)) {
            throw new PublishCommandMissingServiceProviderClassName('Publish vendor missing service provider class: ' . static::class);
        }

        $callData = [];

        $vendorTags = $this->option('tags');
        $vendorTags = explode(',', $vendorTags);
        if (!empty($vendorTags)) {
            $tags = !empty($tags) ? array_merge($tags, $vendorTags) : $vendorTags;
        }
        if (!empty($tags)) {
            $callData['--tag'] = array_unique($tags);
        }

        $callData['--force'] = $this->option('force');
        $callData['--provider'] = $this->serviceProviderClass;

        $this->softTitle('Publish Vendor of "<info>' . (!empty($tags) ? implode(', ', $tags) : 'all') . '</info>"');
        $this->call('vendor:publish', $callData);
    }
}