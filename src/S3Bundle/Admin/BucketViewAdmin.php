<?php

namespace S3Bundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Route\RouteCollection;

class BucketViewAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'bucket';
    protected $baseRouteName = 'bucket';

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(['list']);
    }
}
