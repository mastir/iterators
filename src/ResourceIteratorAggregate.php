<?php

declare(strict_types=1);

namespace loophp\iterators;

use Closure;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;

use function is_resource;

/**
 * @template T
 *
 * @implements IteratorAggregate<int, string|T>
 */
final class ResourceIteratorAggregate implements IteratorAggregate
{
    /**
     * @var null|non-negative-int
     */
    private ?int $length = null;

    /**
     * @var resource
     */
    private $resource;

    /**
     * @param false|resource $resource
     * @param null|non-negative-int $length
     * @param Closure(resource): T $consumer
     */
    public function __construct($resource, private bool $closeResource = false, ?int $length = null, private ?Closure $consumer = null)
    {
        if (!is_resource($resource) || 'stream' !== get_resource_type($resource)) {
            throw new InvalidArgumentException('Invalid resource type.');
        }

        $this->resource = $resource;
        $this->length = $length;
    }

    /**
     * @return Generator<int, string|T>
     */
    public function getIterator(): Generator
    {
        $closeResource = $this->closeResource;
        $length = $this->length;
        $resource = $this->resource;

        $fgetc =
            /**
             * @param resource $resource
             */
            static fn ($resource): false|string => fgetc($resource);

        $fgets =
            /**
             * @param resource $resource
             */
            static fn ($resource): false|string => fgets($resource, $length);

        $function = $this->consumer ?? ((null === $length) ? $fgetc : $fgets);

        try {
            while (false !== $chunk = $function($resource)) {
                yield $chunk;
            }
        } finally {
            if ($closeResource) {
                fclose($resource);
            }
        }
    }
}
