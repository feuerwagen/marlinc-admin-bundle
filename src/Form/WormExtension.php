<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Form;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Disable a form field once data has been saved.
 */
class WormExtension extends AbstractTypeExtension
{
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

                if ($data instanceof Collection) {
                    if (!$data->isEmpty()) {
                        $this->disableFormField($form, $parent);
                    }
                } elseif ($data && !empty($data)) {
                    $this->disableFormField($form, $parent);
                }
            });
        }
    }

    private function disableFormField(FormInterface $form, FormInterface $parent)
    {
        $options = $form->getConfig()->getOptions();

        // Prevent infinite loop.
        if (isset($options['disabled']) && $options['disabled'] == true) {
            return;
        }
        $options['disabled'] = true;

        $parent->add($form->getName(), get_class($form->getConfig()->getType()->getInnerType()), $options);
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('allow_edit', true);
    }

    /**
     * @inheritdoc
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}