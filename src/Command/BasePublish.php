<?php


namespace MaDnh\LaravelDevHelper\Command;


use MaDnh\LaravelDevHelper\Command\Exceptions\PublishCommandMissingServiceProviderClassName;
use MaDnh\LaravelDevHelper\Command\Traits\PublishAssets;

class BasePublish extends BaseCommand
{
    use PublishAssets;

    protected $signature = 'app:publish {tag?* : Publish tags} {--force : Overwrite any existing files}';
    protected $description = 'Publish assets';
    protected $serviceProviderClass = null;

    public function handle()
    {
        $this->welcome();

        $tags = $this->argument('tag');
        $methodTags = [];
        $commandTags = [];

        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $studlyTag = 'publish' . studly_case($tag);

                if (method_exists($this, $studlyTag)) {
                    $methodTags[] = $studlyTag;
                } else {
                    $commandTags[] = $tag;
                }
            }
        }

        $this->doPublishMethods($methodTags);

        if (!empty($commandTags)) {
            $this->doPublishVendor($commandTags);
        }
    }

    /**
     * Show welcome message
     */
    protected function welcome()
    {
        $this->banner("Publish Assets");
    }

    protected function doPublishMethods($methods)
    {
        foreach ($methods as $method) {
            $this->{$method}();
        }
    }

    protected function doPublishVendor($tags)
    {
        $this->softTitle('Publish Vendor');

        if (empty($this->serviceProviderClass)) {
            throw new PublishCommandMissingServiceProviderClassName('Publish vendor missing service provider class: ' . static::class);
        }

        $callData = [];

        if (!empty($tags)) {
            $callData['--tag'] = $tags;
        }

        $callData['--force'] = $this->option('force');
        $callData['--provider'] = $this->serviceProviderClass;

        $this->call('vendor:publish', $callData);
    }
}