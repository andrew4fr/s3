<?php

namespace Application\Sonata\MediaBundle\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sonata\CoreBundle\Form\Type\CollectionType;
use Sonata\MediaBundle\Admin\GalleryAdmin as SonataGalleryAdmin;

class GalleryAdmin extends SonataGalleryAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        // define group zoning
        $formMapper
            ->with('Gallery', ['class' => 'col-md-9'])->end()
            ->with('Options', ['class' => 'col-md-3'])->end()
        ;

        $context = $this->getPersistentParameter('context');

        if (!$context) {
            $context = $this->pool->getDefaultContext();
        }

        $formats = [];
        foreach ((array) $this->pool->getFormatNamesByContext($context) as $name => $options) {
            $formats[$name] = $name;
        }

        $contexts = [];
        foreach ((array) $this->pool->getContexts() as $contextItem => $format) {
            $contexts[$contextItem] = $contextItem;
        }

        $formMapper
            ->with('Options')
                ->add('context', ChoiceType::class, ['choices' => $contexts])
                ->add('enabled', null, ['required' => false])
                ->add('name')
                ->ifTrue($formats)
                    ->add('defaultFormat', ChoiceType::class, ['choices' => $formats])
                ->ifEnd()
            ->end()
            ->with('Gallery')
                ->add('galleryHasMedias', CollectionType::class, ['btn_add' => false])
            ->end()
        ;
    }
}
