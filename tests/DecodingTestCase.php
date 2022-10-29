<?php

declare(strict_types=1);

namespace Bic\Image\Ico;

use Bic\Image\Factory;
use Bic\Image\FactoryInterface;
use Bic\Image\PixelFormat;
use Bic\Image\Ico\Tests\TestCase;
use Bic\Image\ImageInterface;

final class DecodingTestCase extends TestCase
{
    private readonly FactoryInterface $images;

    public function setUp(): void
    {
        $this->images = new Factory([new IcoDecoder()]);

        parent::setUp();
    }

    public function iconsDataProvider(): array
    {
        return [
            '32bit (1 icon)' => [__DIR__ . '/stubs/32bit.ico', 1],
            '24bit (1 icon)' => [__DIR__ . '/stubs/24bit.ico', 1],
        ];
    }

    /**
     * @dataProvider iconsDataProvider
     */
    public function testImagesCount(string $pathname, int $count): void
    {
        $images = $this->images->fromPathname($pathname);

        $this->assertInstanceOf(\Traversable::class, $images);
        $this->assertCount($count, $images);
    }

    public function test32Bit(): void
    {
        /** @var ImageInterface $image */
        [$image] = [...$this->images->fromPathname(__DIR__ . '/stubs/32bit.ico')];

        $this->assertSame(PixelFormat::B8G8R8A8, $image->getFormat());
        $this->assertSame(\file_get_contents(__DIR__ . '/stubs/32bit.bin'), $image->getContents());
    }

    public function test24Bit(): void
    {
        /** @var ImageInterface $image */
        [$image] = [...$this->images->fromPathname(__DIR__ . '/stubs/24bit.ico')];

        $this->assertSame(PixelFormat::B8G8R8, $image->getFormat());
        $this->assertSame(\file_get_contents(__DIR__ . '/stubs/24bit.bin'), $image->getContents());
    }
}
