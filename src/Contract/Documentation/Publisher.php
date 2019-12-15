<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver\Contract\Documentation;

use Illuminate\Console\Command;
use ReliqArts\Docweaver\Contract\Publisher as BasePublisher;
use ReliqArts\Docweaver\Exception\InvalidDirectory;
use ReliqArts\Docweaver\Result;

interface Publisher extends BasePublisher
{
    /**
     * Publish documentation for a particular product.
     *
     * @param string  $productName    Product Name
     * @param string  $source         Git Repository
     * @param Command $callingCommand Calling Command
     *
     * @throws InvalidDirectory
     */
    public function publish(string $productName, string $source, Command &$callingCommand = null): Result;

    /**
     * Update documentation for a particular product.
     *
     * @param string  $productName    Product Name
     * @param Command $callingCommand Calling Command
     */
    public function update(string $productName, Command &$callingCommand = null): Result;

    /**
     * Update all products.
     *
     * @param Command $callingCommand Calling Command
     */
    public function updateAll(Command &$callingCommand = null): Result;
}
