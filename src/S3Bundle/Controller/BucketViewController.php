<?php 

namespace S3Bundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController;
use Aws\Exception\AwsException;

class BucketViewController extends CRUDController
{
    public function listAction()
    {
        $s3 = $this->container->get('app.amazon.s3');

        $bucketName = $this->container->getParameter('amazon_s3_bucket');
        $bucket = $s3->getBucket($bucketName);

        $folders = [];
        try {
            foreach ($bucket->objects() as $object) {
                $key = $object->getIdentity()['Key'];
                $dirs = explode('/', $key);
                $theLastPath = array_pop($dirs);

                if ($theLastPath == '' && count($dirs) == 3) {
                    $folders[] = $dirs;
                }
            }
        } catch(AwsException $e) {
            return $this->render('@S3/Default/error.html.twig', ['bucket' => $bucketName, 'error' => $e->getAwsErrorMessage()]);
        }

        $em = $this->getDoctrine()->getManager();
        $galleries = $em->getRepository('ApplicationSonataMediaBundle:Gallery')->findAll();
        $existingGalleries = [];
        foreach ($galleries as $gallery) {
            if (preg_match('@^(\d+),(.+)@', $gallery->getName(), $matches)) {
                $existingGalleries[$matches[1]][] = $matches[2];
            }
        }

        $result = [];
        foreach ($folders as $folder) {
            if (preg_match('@^(\d+)@', $folder[1], $matches)) {
                $courseId = $matches[1];

                $exists = isset($existingGalleries[$courseId]); 
                $level2Action = $exists ? 'replace' : 'import';
                $level3Action = $exists && in_array($folder[2], $existingGalleries[$courseId]) ? 'replace' : 'import';
                $level3Path = join('/', [$folder[0], $folder[1]]);

                $result[$folder[0]]['children'][$folder[1]]['children'][] = ['path' => $level3Path, 'action' => $level3Action, 'name' => $folder[2]];
                $result[$folder[0]]['children'][$folder[1]]['action'] = $level2Action;
                $result[$folder[0]]['children'][$folder[1]]['course_id'] = $courseId;
                $result[$folder[0]]['children'][$folder[1]]['path'] = $folder[0];
            }
        }

        return $this->render('@S3/Default/bucket_view.html.twig', ['bucket' => $bucketName, 'folders' => $result]);
    }
}
