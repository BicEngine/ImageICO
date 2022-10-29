<?php

declare(strict_types=1);

namespace Bic\Image\ICO;

use Bic\Binary\Endianness;
use Bic\Binary\StreamInterface;
use Bic\Binary\TypedStream;
use Bic\Image\BMP\BitMapDecoder;
use Bic\Image\BMP\Exception\BitMapBitDepthException;
use Bic\Image\BMP\Exception\DdsCompressionException;
use Bic\Image\BMP\Metadata\BitMapCompression;
use Bic\Image\Compression;
use Bic\Image\ICO\Exception\ICOException;
use Bic\Image\ICO\Metadata\ICODirectoryEntry;
use Bic\Image\DecoderInterface;
use Bic\Image\PixelFormat;
use Bic\Image\Image;
use Bic\Image\ImageInterface;
use Bic\Image\Reader;

final class ICODecoder implements DecoderInterface
{
    /**
     * {@inheritDoc}
     */
    public function decode(StreamInterface $stream): ?iterable
    {
        if ($stream->read(4) === "\x00\x00\x01\x00") {
            return $this->read($stream);
        }

        return null;
    }

    /**
     * @param StreamInterface $stream
     *
     * @return iterable<ImageInterface>
     * @throws BitMapBitDepthException
     * @throws DdsCompressionException
     * @throws IcoException
     * @throws \Throwable
     *
     * @psalm-suppress ArgumentTypeCoercion
     */
    private function read(StreamInterface $stream): iterable
    {
        $stream = new TypedStream($stream, Endianness::LITTLE);

        /** @var array<ICODirectoryEntry> $directories */
        $directories = [];

        // --- ICO Header ---
        //  - uint16: Always "\x00\x00" (reserved section)
        //  - uint16: Always "\x01\x00" (image type)
        //            The "\x02\x00" header means that image is cursor.
        //  - uint16: Specifies number of images in the file.
        $images = $stream->uint16();

        // Read list of ICO directories
        for ($i = 0; $i < $images; ++$i) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $directories[] = $directory = new ICODirectoryEntry(
                width: $stream->int8(),
                height: $stream->int8(),
                colors: $stream->int8(),
                reserved: $stream->int8(),
                colorPlanes: $stream->uint16(),
                bitsPerPixel: $stream->uint16(),
                size: $stream->uint32(),
                offset: $stream->uint32(),
            );

            if ($directory->colors > 0) {
                throw new IcoException('Indexed colors not supported');
            }
        }

        // Read image data
        foreach ($directories as $ico) {
            // Seek to start of image data section
            $stream->seek($ico->offset);

            // Read BMP Header (40 bytes)
            $info = BitMapDecoder::readInfoHeader($stream);

            // Only RGB images is supported
            if ($info->compression !== BitMapCompression::RGB) {
                throw DdsCompressionException::fromUnsupportedCompression($info->compression);
            }

            // Detect image format
            $format = match ($info->bitCount) {
                24 => PixelFormat::B8G8R8,
                32 => PixelFormat::B8G8R8A8,
                default => throw BitMapBitDepthException::fromUnsupportedBits($info->bitCount, [24, 32]),
            };

            // Bytes per line
            $bytes = $format->getBytesPerPixel();

            [$width, $height] = [$ico->width ?: 256, $ico->height ?: 256];

            // Read image data
            $data = $info->width >= 0
                ? Reader::bottomUp($stream, $width, $height, $bytes)
                : Reader::topDown($stream, $width, $height, $bytes);

            yield new Image(
                format: $format,
                width: $width,
                height: $height,
                contents: $data,
                compression: Compression::NONE,
                metadata: new ICOMetadata(
                    entry: $ico,
                    info: $info,
                ),
            );
        }
    }
}
