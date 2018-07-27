<?php

namespace Marlinc\AdminBundle\Datagrid;

use Marlinc\AdminBundle\Admin\AdminWithTrash;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;

class TrashMapper extends ListMapper
{
    /**
     * @inheritdoc
     */
    public function add($name, $type = null, array $fieldDescriptionOptions = [])
    {
        // Change deprecated inline action "view" to "show"
        if ('_action' == $name && 'actions' == $type) {
            if (isset($fieldDescriptionOptions['actions']['view'])) {
                @trigger_error(
                    'Inline action "view" is deprecated since version 2.2.4 and will be removed in 4.0. '
                    .'Use inline action "show" instead.',
                    E_USER_DEPRECATED
                );

                $fieldDescriptionOptions['actions']['show'] = $fieldDescriptionOptions['actions']['view'];

                unset($fieldDescriptionOptions['actions']['view']);
            }
        }

        // Ensure batch and action pseudo-fields are tagged as virtual
        if (in_array($type, ['actions', 'batch', 'select'])) {
            $fieldDescriptionOptions['virtual_field'] = true;
        }

        if ($name instanceof FieldDescriptionInterface) {
            $fieldDescription = $name;
            $fieldDescription->mergeOptions($fieldDescriptionOptions);
        } elseif (is_string($name)) {
            if (($this->admin instanceof AdminWithTrash && $this->admin->hasTrashFieldDescription($name)) || (!$this->admin instanceof AdminWithTrash && $this->admin->hasListFieldDescription($name))) {
                throw new \RuntimeException(sprintf(
                    'Duplicate field name "%s" in list mapper. Names should be unique.',
                    $name
                ));
            }

            $fieldDescription = $this->admin->getModelManager()->getNewFieldDescriptionInstance(
                $this->admin->getClass(),
                $name,
                $fieldDescriptionOptions
            );
        } else {
            throw new \RuntimeException(
                'Unknown field name in list mapper. '
                .'Field name should be either of FieldDescriptionInterface interface or string.'
            );
        }

        if (null === $fieldDescription->getLabel()) {
            $fieldDescription->setOption(
                'label',
                $this->admin->getLabelTranslatorStrategy()->getLabel($fieldDescription->getName(), 'list', 'label')
            );
        }

        // add the field with the FormBuilder
        $this->builder->addField($this->list, $type, $fieldDescription, $this->admin);

        return $this;
    }
}
