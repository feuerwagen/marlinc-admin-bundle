<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 02.08.18
 * Time: 16:19
 */

namespace Marlinc\AdminBundle\Form;


use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WormExtension extends AbstractTypeExtension
{
    /**
     * @inheritDoc
     */
    public function getExtendedType()
    {
        return FormType::class;
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['allow_edit'] === false) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();
                $parent = $form->getParent();

                // check if the object is "new"
                // If you didn't pass any data to the form, the data is "null".
                // This should be considered a new object
                if ($data && !empty($data)) {
                    $options = $form->getConfig()->getOptions();

                    // Prevent infinte loop.
                    if (isset($options['disabled']) && $options['disabled'] == true) {
                        return;
                    }
                    $options['disabled'] = true;

                    $parent->add($form->getName(), get_class($form->getConfig()->getType()->getInnerType()), $options);
                }
            });
        }
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('allow_edit', true);
    }
}