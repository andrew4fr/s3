<?php 

namespace S3Bundle\Controller\Backend;

use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Application\Sonata\MediaBundle\Entity\Media;
use Application\Sonata\MediaBundle\Entity\Gallery;
use Application\Sonata\MediaBundle\Entity\GalleryHasMedia;

class BackendController extends Controller
{
    /**
     * @Route("/galleryUpload")
     * @Method("POST")
     */
    public function galleryUploadAction(Request $request)
    {
        $params = $request->request->all();

        try {
            if ($params['level'] == 3) {
                $this->uploadGallery($params['name'], $params['path'], (int)$params['courseId']);
            } else {
                $this->uploadGalleries((int)$params['courseId']);
            }
        } catch(Exception $e) {
            return new Response($e->getMessage(), 500);
        }

        return $this->render('S3Bundle:Default:upload_button.html.twig', [
            'level' => $params['level'],
            'course_id' => $params['courseId'],
            'action' => 'replace',
            'name' => $params['name'],
            'path' => $params['path'],
        ]);
    }

    private function uploadGallery(string $name, string $path, int $courseId)
    {
        $s3 = $this->container->get('app.amazon.s3');

        $bucketName = $this->container->getParameter('amazon_s3_bucket');
        $fromPath = sprintf('s3://%s/%s/%s', $bucketName, $path, $name);
        $toPath = sprintf('%s/%s', sys_get_temp_dir(), uniqid(microtime(true), true));
        $s3->transfer($fromPath, $toPath); 

        $galleryName = sprintf('%s,%s', $courseId, $name);

        $mediaManager = $this->get('sonata.media.manager.media');
        $galleryManager = $this->get('sonata.media.manager.gallery');

        $gallery = $galleryManager->findOneBy([
            'name' => $galleryName
        ]);

        if ($gallery) {
            $galleryManager->delete($gallery);
        }

        $gallery = new Gallery();
        $gallery->setName($galleryName);
        $gallery->setContext('default');
        $gallery->setEnabled(true);
        $galleryManager->save($gallery);
        $galleryId = $gallery->getId();

        $hasMedias = [];
        $images = array_diff(scandir($toPath), ['..', '.']);
        foreach ($images as $image) {
            $media = new Media();
            $media->setContext('default');
            $media->setProviderName('sonata.media.provider.image');
            $media->setBinaryContent(sprintf('%s/%s', $toPath, $image));
            $mediaManager->save($media);

            $galleryHasMedia =  new GalleryHasMedia();
            $galleryHasMedia->setGallery($gallery);
            $galleryHasMedia->setMedia($media);

            $hasMedias[] = $galleryHasMedia;
        }

        $gallery->setGalleryHasMedias($hasMedias);
        $galleryManager->save($gallery);
    }

    private function uploadGalleries(int $courseId)
    {
        $s3 = $this->container->get('app.amazon.s3');

        $bucketName = $this->container->getParameter('amazon_s3_bucket');
        $bucket = $s3->getBucket($bucketName);
        foreach ($bucket->objects() as $object) {
            $key = $object->getIdentity()['Key'];
            $dirs = explode('/', $key);
            $theLastPath = array_pop($dirs);
            if ($theLastPath == '' && count($dirs) == 3) {
                if (preg_match('@^(\d+)@', $dirs[1], $matches)) {
                    $id = (int)$matches[1];
                    if ($id == $courseId) {
                        $this->uploadGallery($dirs[2], sprintf('%s/%s', $dirs[0], $dirs[1]), $courseId);
                    }
                }
            }
        }
    }
}
