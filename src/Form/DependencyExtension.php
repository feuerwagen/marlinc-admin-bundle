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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
            'depending-value' => null,
            'depending-comparison' => null
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

            foreach ($view->parent->children as $child) {
                if ($child->vars['name'] == $dependentOn->getName()) {
                    $dependentId = $child->vars['id'];
                }
            }

            switch ($dependentOnClass) {
                case ChoiceType::class:
                    $attributes['data-type'] = 'select';
                    break;
                case NumberType::class:
                case IntegerType::class:
                    $attributes['data-type'] = 'number';
                    break;
                case TextType::class:
                    $attributes['data-type'] = 'text';
                    break;
                case CheckboxType::class:
                    $attributes['data-type'] = 'checkbox';
                    break;
            }

            if (isset($attributes['data-type'])) {
                $attributes['data-source'] = '#'.$dependentId;
                $attributes['data-value'] = (string) $options['depending-value'];
                $attributes['data-comparison'] = $options['depending-comparison'];

                $view->vars['attr'] = array_merge_recursive($view->vars['attr'], $attributes);
            }
        }
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}