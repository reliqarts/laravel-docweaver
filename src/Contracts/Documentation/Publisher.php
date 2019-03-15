<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\Contracts\Documentation;

use Illuminate\Console\Command;
use ReliQArts\Docweaver\Contracts\Publisher as BasePublisher;
use ReliQArts\Docweaver\Exceptions\InvalidDirectory;
use ReliQArts\Docweaver\VO\Result;

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
     *
     * @return Result
     */
    public function publish(string $productName, string $source, Command &$callingCommand = null): Result;

    /**
     * Update documentation for a particular product.
     *
     * @param string  $productName    Product Name
     * @param Command $callingCommand Calling Command
     *
     * @return Result
     */
    public function update(string $productName, Command &$callingCommand = null): Result;

    /**
     * Update all products.
     *
     * @param Command $callingCommand Calling Command
     *
     * @return Result
     */
    public function updateAll(Command &$callingCommand = null): Result;
}
