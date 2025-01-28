<?php

namespace App\Services\Filesystem;

use Aws\S3\S3ClientInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use League\Flysystem\AwsS3V3\VisibilityConverter;
use League\Flysystem\FilesystemException;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use Throwable;

class CustomS3Adapter extends AwsS3V3Adapter
{
    /**
     * @var string[]
     */
    private const EXTRA_METADATA_FIELDS = [
        'Metadata',
        'StorageClass',
        'ETag',
        'VersionId',
    ];
    private PathPrefixer $prefixer;
    private PortableVisibilityConverter|VisibilityConverter $visibility;
    private FinfoMimeTypeDetector|MimeTypeDetector $mimeTypeDetector;

    public function __construct(
        private S3ClientInterface $client,
        private string $bucket,
        string $prefix = '',
        VisibilityConverter $visibility = null,
        MimeTypeDetector $mimeTypeDetector = null,
        private array $options = [],
        private bool $streamReads = true,
        private array $forwardedOptions = self::AVAILABLE_OPTIONS,
        private array $metadataFields = self::EXTRA_METADATA_FIELDS,
        private array $multipartUploadOptions = self::MUP_AVAILABLE_OPTIONS,
    ) {
        parent::__construct($client, $bucket, $prefix, $visibility, $mimeTypeDetector, $options, $streamReads, $forwardedOptions, $metadataFields, $multipartUploadOptions);

        $this->prefixer = new PathPrefixer($prefix);
        $this->visibility = $visibility ?: new PortableVisibilityConverter();
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector();
    }

//    /**
//     * Determine if a file or directory exists.
//     *
//     * @param string $path
//     * @return bool
//     * @throws Throwable
//     * @throws FilesystemException
//     */
//    public function exists($path): bool
//    {
//        try {
//            return $this->driver->has($path);
//        } catch (UnableToCheckExistence|UnableToCheckFileExistence $e) {
//            Log::error($e);
//            return false;
//        } catch (\Throwable $exception) {
//            throw $exception;
//        }
//    }

    public function fileExists(string $path): bool
    {
        try {
            return $this->client->doesObjectExistV2($this->bucket, $this->prefixer->prefixPath($path), false, $this->options);
        } catch (Throwable $exception) {
            Log::error($exception);
            return false;
//            throw UnableToCheckFileExistence::forLocation($path, $exception);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            $prefix = $this->prefixer->prefixDirectoryPath($path);
            $options = ['Bucket' => $this->bucket, 'Prefix' => $prefix, 'MaxKeys' => 1, 'Delimiter' => '/'];
            $command = $this->client->getCommand('ListObjectsV2', $options);
            $result = $this->client->execute($command);

            return $result->hasKey('Contents') || $result->hasKey('CommonPrefixes');
        } catch (Throwable $exception) {
            Log::error($exception);
            return false;
//            throw UnableToCheckDirectoryExistence::forLocation($path, $exception);
        }
    }
}
