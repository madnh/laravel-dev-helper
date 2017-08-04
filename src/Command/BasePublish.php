<?php


namespace MaDnh\LaravelDevHelper\Command;


use MaDnh\LaravelDevHelper\Command\Exceptions\PublishCommandMissingServiceProviderClassName;
use MaDnh\LaravelDevHelper\Command\Traits\PublishAssets;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Usage:
 * php artisan app:publish foo bar => publish by methods
 * php artisan app:publish vendor | php artisan app:publish => publish all of tags registered in service provider
 * php artisan app:publish vendor --tag=config,assets, publish registered tags
 */
class BasePublish extends BaseCommand
{
    use PublishAssets;

    protected $name = 'app:publish';
    protected $description = 'Publish assets';
    protected $serviceProviderClass = null;

    protected function getArguments()
    {
        $parts = array_map(function ($method) {
            return '- ' . snake_case(substr($method, 7));
        }, array_diff($this->getPublishMethods(), ['publishAll']));

        return [
            ['parts', InputArgument::OPTIONAL, "Parts to publish, supports:\n" . implode("\n", $parts) . "\nAnd <info>all</info> to publish all of parts"]
        ];
    }

    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite any existing files'],
            ['tags', null, InputOption::VALUE_REQUIRED, 'Publish tags (or group) registered in service provider']
        ];
    }


    public function handle()
    {
        $this->welcome();

        $argParts = (array)$this->argument('parts');

        if (empty($argParts)) {
            $this->publishAll();
            return;
        }

        $parts = [];
        $vendorTags = [];
        $publishMethods = array_flip($this->getPublishMethods());

        foreach ($argParts as $argPart) {
            $studlyPart = 'publish' . studly_case($argPart);

            if ($studlyPart === 'publishAll') {
                $this->publishAll();
                return;
            }
            if (array_key_exists($studlyPart, $publishMethods)) {
                $parts[] = $studlyPart;
            } else {
                $vendorTags[] = $argPart;
            }
        }

        foreach ($parts as $method) {
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
        if (!empty($vendorTags)) {
            $vendorTags = explode(',', $vendorTags);
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