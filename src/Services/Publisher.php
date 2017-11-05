<?php

namespace ReliQArts\Docweaver\Services;

use Log;
use Exception;
use ReflectionException;
use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use ReliQArts\Docweaver\Models\Product;
use ReliQArts\Docweaver\Traits\Timeable;
use ReliQArts\Docweaver\ViewModels\Result;
use ReliQArts\Docweaver\Traits\FileHandler;
use ReliQArts\Docweaver\Traits\VariableOutput;
use ReliQArts\Docweaver\Helpers\CoreHelper as Helper;
use ReliQArts\Docweaver\Exceptions\ImplementationException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use ReliQArts\Docweaver\Contracts\Publisher as PublisherContract;

/**
 * Publisher service. For publishing or updating documentation.
 */
class Publisher implements PublisherContract
{
    use FileHandler, Timeable, VariableOutput;

    /**
     * Documentation configuration array.
     *
     * @var Cache
     */
    protected $config;

    /**
     * Documentation resource directory.
     *
     * @var string
     */
    protected $docsDir;

    /**
     * Working directory.
     *
     * @var string
     */
    protected $workingDir;

    /**
     * Result of operation.
     *
     * @var \ReliQArts\Scavenger\ViewModels\Result
     */
    public $result = null;

    /**
     * Create a new Publisher.
     *
     * @param  Filesystem  $files
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        $this->result = new Result;
        $this->config = Helper::getConfig();
        $this->docsDir = Helper::getDocsDir();
        $this->workingDir = base_path($this->docsDir);

        if (!$this->readyResourceDir()) {
            throw new ImplementationException("Could not ready document resource directory ({$this->docsDir}). Please ensure file system is writable.");
        }
    }
    /**
     * Publish documentation for a particular product.
     *
     * @param string $name
     * @param string $source Git Repository
     * @param \Illuminate\Console\Command $callingCommand
     *
     * @return Result
     */
    public function publish($name, $source, &$callingCommand = null)
    {
        $result = $this->result;
        $startTime = microtime(true);
        $productDir = "{$this->workingDir}/$name";
        
        if ($this->readyResourceDir($productDir)) {
            $result = $this->publishVersions($name, $productDir, $source, $callingCommand);
        } else {
            $result->error = "Product directory {$productDir} is not writable.";
        }

        // add identity to result error
        if ($result->error) {
            $result->error = "Could not publish product ({$name}) because:\n{$result->error}";
        }

        // finalize extra details
        if (empty($result->extra)) {
            $result->extra = (object) [];
        }
        $result->extra->executionTime = $this->secondsSince($startTime).'s';
        return $result;
    }

    /**
     * Publish doc versions of a product.
     *
     * @param string $dir Product directory.
     * @param string $source Git repository.
     * @param \Illuminate\Console\Command $callingCommand
     * @return void
     */
    private function publishVersions($name, $dir, $source, &$callingCommand = null)
    {
        $this->callingCommand = $callingCommand;
        $result = $this->result;
        $masterExists = false;

        if ($productDir = realpath($dir)) {
            $masterDir = "{$productDir}/master";
            $versionsPublished = $versionsUpdated = 0;

            // ensure master exists
            if ($this->files->isDirectory($masterDir)) {
                $masterExists = true;
                $product = new Product($productDir);
                if ($this->updateProductVersion($product, 'master')) {
                    $versionsUpdated++;
                }
            } else {
                $cloneMaster = new Process("git clone --branch master \"{$source}\" master");
                $cloneMaster->setWorkingDirectory($productDir);

                try {
                    $cloneMaster->mustRun();
                    $this->tell("Successfully published master.");
                    $masterExists = true;
                    $versionsPublished++;
                } catch (ProcessFailedException $e) {
                    $result->message = $result->error = $e->getMessage();
                }
            }

            // master must exist to proceed
            if ($masterExists) {
                $product = empty($product) ? new Product($productDir) : $product;
                $product->publishAssets('master');
                // publish the different tags
                $listTags = new Process("git tag", $masterDir);
                
                try {
                    $listTags->mustRun();
                    $tags = array_map('trim', preg_split("[\n\r]", $listTags->getOutput()));
                    $result->messages = [];

                    // publish each tag
                    foreach ($tags as $tag) {
                        if (!$this->files->isDirectory("$masterDir/../$tag")) {
                            $cloneTag = new Process("git clone --branch $tag \"{$source}\" ../$tag", $masterDir);
                            $cloneTag->mustRun();
                            // publish assets
                            $product->publishAssets($tag);
                            $this->tell("Successfully published tag $tag.");
                            // increment
                            $versionsPublished++;
                        } else {
                            $message = "Version $tag already exists.";
                            $result->messages[] = $message;
                            Log::info($message, ['state' => $this, 'product' => $product]);
                            $this->tell($message);
                        }
                    }
                } catch (ProcessFailedException $e) {
                    $result->message = $result->error = $e->getMessage();
                }

                // success check
                if (!$result->error) {
                    $result->success = true;
                    $result->message = "{$product->getName()} was successfully published.";
                    $result->extra = (object) [
                        'versions' => count($tags) + 1,
                        'versionsPublished' => $versionsPublished,
                        'versionsUpdated' => $versionsUpdated,
                    ];
                }
            }
        } else {
            $result->error = "Product directory ({$dir}) is not writable.";
        }
        
        return $result;
    }

    /**
     * Update documentation for a particular product.
     *
     * @param string $name
     * @param \Illuminate\Console\Command $callingCommand
     *
     * @return Result
     */
    public function update($name, &$callingCommand = null)
    {
        $result = $this->result;
        $startTime = microtime(true);
        $productDir = "{$this->workingDir}/$name";
        
        if ($this->readyResourceDir($productDir)) {
            $result = $this->updateVersions($name, $productDir, $callingCommand);
        } else {
            $result->error = "Product directory {$productDir} is not writable.";
        }

        // add identity to result error
        if ($result->error) {
            $result->error = "Could not update product ({$name}) because:\n{$result->error}";
        }

        // finalize extra details
        if (empty($result->extra)) {
            $result->extra = (object) [];
        }
        $result->extra->executionTime = $this->secondsSince($startTime).'s';

        return $result;
    }

    /**
     * Update all products.
     *
     * @return Result
     */
    public function updateAll(&$callingCommand = null)
    {
        $result = $this->result;
        $result->extra = (object) [];
        $startTime = microtime(true);
        $productDirs = $this->files->directories($this->workingDir);
        $productResults = [];
        $productsUpdated = 0;

        foreach ($productDirs as $productDir) {
            $productName = basename($productDir);
            $this->tell("Updating $productName ", 'flat');
            $productResult = $this->update($productName, $callingCommand);
            $productResults[$productName] = $productResult;

            if ($productResult->success) {
                $productsUpdated++;
            }
        }

        // add extra details
        $result->extra = (object) [
            'products' => count($productDirs),
            'productsUpdated' => $productsUpdated,
        ];
        $result->extra->executionTime = $this->secondsSince($startTime).'s';

        return $result;
    }

    /**
     * Update a particular product version.
     *
     * @param Product $product
     * @param string $version Version to update.
     *
     * @return bool
     */
    private function updateProductVersion($product, $version)
    {
        $updated = true;

        try {
            $updateVer = new Process("git pull", "{$product->getDir()}/$version");
            $updateVer->mustRun();
            $this->tell("Successfully updated version $version.");
        } catch (ProcessFailedException $e) {
            $this->tell("Assets republished for version $version.");
        }
        
        // publish assets
        $product->publishAssets($version);

        return $updated;
    }

    /**
     * Publish doc versions of a product.
     *
     * @param string $name
     * @param string $dir Product directory.
     * @param \Illuminate\Console\Command $callingCommand
     * @return void
     */
    private function updateVersions($name, $dir, &$callingCommand = null)
    {
        $this->callingCommand = $callingCommand;
        $result = $this->result;

        if ($productDir = realpath($dir)) {
            try {
                $product = new Product($productDir);
                $versions = $product->getVersions();
                $result->messages = [];
                $versionsUpdated = 0;

                foreach ($versions as $version) {
                    if ($this->updateProductVersion($product, $version)) {
                        $versionsUpdated++;
                    }
                }
                
                // success check
                if (!$result->error) {
                    $result->success = true;
                    $result->message = "{$product->getName()} was successfully updated.";
                    $result->extra = (object) [
                        'versions' => count($versions),
                        'versionsUpdated' => $versionsUpdated,
                    ];
                }
            } catch (BadProductException $e) {
                $result->error = "Failed to initialize product from ({$productDir}).";
            }
        } else {
            $result->error = "Product directory ({$dir}) is not writable.";
        }
        
        return $result;
    }

    /**
     * Ensure documentation resource directory exists and is writable.
     *
     * @param string $dir
     *
     * @return bool
     */
    private function readyResourceDir($dir = null)
    {
        $dir = empty($dir) ? $this->workingDir : $dir;

        if (!$this->files->isDirectory($dir)) {
            $this->files->makeDirectory($dir, 0777, true);
        }

        return $this->files->isWritable($dir);
    }
}
