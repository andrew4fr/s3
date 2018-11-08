<?php 

namespace S3Bundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController;
use Aws\Resource\Aws;
use Aws\Exception\AwsException;

class BucketViewController extends CRUDController
{
    public function listAction()
    {
        $params = $this->container->getParameterBag();

        $config = [
            'version' => 'latest',
            'region' => $params->get('amazon_s3_region'),
            'credentials' => [
                'key' => $params->get('amazon_s3_key'),
                'secret' => $params->get('amazon_s3_secret'),
            ]
        ];

        $aws = new Aws($config);
        $s3 = $aws->s3;

        $bucketName = $params->get('amazon_s3_bucket');
        $bucket = $s3->bucket($bucketName);

        $folders = [];
        try {
            foreach ($bucket->objects() as $object) {
                $key = $object->getIdentity()['Key'];
                $dirs = explode('/', $key);
                $theLastPath = array_pop($dirs);

                if ($theLastPath == '') {
                    $folders[] = $dirs;
                }
            }
        } catch(AwsException $e) {
            return $this->render('@S3/Default/error.html.twig', ['bucket' => $bucketName, 'error' => $e->getAwsErrorMessage()]);
        }

        $result = [];

        foreach($folders as $folder) {
            $temp = &$result;

            foreach($folder as $name) {
                $temp = &$temp[$name];
            }

            $temp = [];
        }
        return $this->render('@S3/Default/bucket_view.html.twig', ['bucket' => $bucketName, 'folders' => $result]);
    }
}
