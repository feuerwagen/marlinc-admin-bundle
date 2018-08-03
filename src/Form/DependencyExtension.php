<?php
/**
 * Created by PhpStorm.
 * User: em
 * Date: 03.08.18
 * Time: 13:32
 */

namespace Marlinc\AdminBundle\Form;


use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DependencyExtension extends AbstractTypeExtension
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'depending-on' => null,
            'depending-value' => null
        ]);
    }

    /**
     * @inheritDoc
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['depending-on'] !== null && $form->getParent()->has($options['depending-on'])) {
            $dependentOn = $form->getParent()->get($options['depending-on']);
            $dependentOnClass = get_class($dependentOn->getConfig()->getType()->getInnerType());
            $attributes = [];

            if (!isset($view->vars['attr'])) {
                $view->vars['attr'] = [];
            }

            dump($view->vars);

            switch ($dependentOnClass) {
                case ChoiceType::class:
                    break;
                case TextType::class:
                case NumberType::class:
                    $attributes['data-source'] = '';
                    break;
                case CheckboxType::class:
                    break;
            }

            $view->vars['attr'] = array_merge_recursive($view->vars['attr'], $attributes);
        }
    }
}