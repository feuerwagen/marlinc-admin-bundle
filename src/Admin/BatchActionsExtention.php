<?php
namespace Marlinc\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdminExtension;
use Sonata\AdminBundle\Admin\AdminInterface;

class BatchActionsExtention extends AbstractAdminExtension
{
    public function configureBatchActions(AdminInterface $admin, array $actions): array
    {
        $actions=$admin->getBatchActions();

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
                'translation_domain' => 'PicossSonataExtraAdminBundle',
                'ask_confirmation' => true, // by default always true
            ];
        }

        return $actions;
    }
}