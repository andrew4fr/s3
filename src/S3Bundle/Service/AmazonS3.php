<?php

namespace S3Bundle\Service;

use Aws\{
    Resource\Aws,
    Resource\Resource,
    S3\S3Client,
    S3\Transfer
};

class AmazonS3
{
    private $s3Client;
    private $s3Resource;

    public function __construct(string $region, string $key, string $secret) {
        $config = [
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ]
        ];
        $aws = new Aws($config);
        $this->s3Resource = $aws->s3;
        $this->s3Client = new S3Client($config);
    }

    public function getBucket(string $name): Resource {
        return $this->s3Resource->bucket($name);
    }


    public function transfer(string $name, string $path) {
        $manager = new Transfer($this->s3Client, $name, $path);
        $manager->transfer();
    }
}
