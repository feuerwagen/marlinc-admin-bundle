<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdminExtension;
use Sonata\AdminBundle\Admin\AdminInterface;

/**
 * Adds batch actions for trash view (if current entity is SoftDeletable) to restore entities or delete for real.
 */
class BatchActionsExtension extends AbstractAdminExtension
{
    public function configureBatchActions(AdminInterface $admin, array $actions): array
    {
        if ($admin->hasRoute('realdelete') && $admin->hasAccess('delete')) {
            $actions['realdelete'] = [
                'label' => 'action_real_delete',
                'translation_domain' => 'MarlincAdminBundle',
                'ask_confirmation' => true, // by default always true
            ];
        }

        if ($admin->hasRoute('untrash') && $admin->hasAccess('edit')) {
            $actions['untrash'] = [
                'label' => 'action_restore',
                'translation_domain' => 'MarlincAdminBundle',
                'ask_confirmation' => true, // by default always true
            ];
        }

        return $actions;
    }
}