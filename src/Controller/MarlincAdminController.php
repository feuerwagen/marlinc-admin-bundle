<?php
declare(strict_types=1);

namespace Marlinc\AdminBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Marlinc\AdminBundle\Admin\AbstractAdmin;
use Marlinc\AdminBundle\Bridge\AdminExporter;
use Marlinc\AdminBundle\Handler\SortableHandler;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\Model\AuditManagerInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sonata\AdminBundle\Controller\CRUDController;

class MarlincAdminController extends CRUDController
{
    public static function getSubscribedServices(): array
    {
        return [
            AdminExporter::class,
        ] + parent::getSubscribedServices();
    }

    /**
     * Move element to a new position.
     * TODO: Test this functionality - does it even work?
     */
    public function moveAction(Request $request, SortableHandler $sortableHandler, int $id, ?int $childId, int $position): Response
    {
        $objectId = $childId !== null ? $childId : $id;

        $object = $this->admin->getObject($objectId);

        $lastPosition = $sortableHandler->getLastPosition($object);
        $position = $sortableHandler->getPosition($object, $position, $lastPosition);

        $object->setPosition($position);
        $this->admin->update($object);

        if ($this->isXmlHttpRequest($request)) {
            return $this->renderJson(array(
                'result' => 'ok',
                'objectId' => $this->admin->getNormalizedIdentifier($object)
            ));
        }
        $this->addFlash('sonata_flash_success', $this->get('translator')->trans('flash_position_updated_successfully', array(), 'MarlincAdminBundle'));

        return new RedirectResponse($this->admin->generateUrl('list', $this->admin->getFilterParameters()));
    }

    /**
     * Revert entity history to a specified revision.
     */
    public function historyRevertAction(Request $request, AuditManagerInterface $manager, int $id, int $revision): Response
    {
        $id = $request->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        if ($request->getMethod() == 'POST') {
            // check the csrf token
            $this->validateCsrfToken($request, 'sonata.history.revert');

            try {
                if (!$manager->hasReader($this->admin->getClass())) {
                    throw new NotFoundHttpException(sprintf('unable to find the audit reader for class : %s', $this->admin->getClass()));
                }

                $reader = $manager->getReader($this->admin->getClass());
                $reader->revert($object, $revision);

                if ($this->isXmlHttpRequest($request)) {
                    return $this->renderJson(array('result' => 'ok'));
                }

                $this->addFlash('sonata_flash_success', $this->get('translator')->trans('flash_history_revert_successfull', array(), 'MarlincAdminBundle'));

            } catch (ModelManagerException $e) {
                if ($this->isXmlHttpRequest($request)) {
                    return $this->renderJson(array('result' => 'error'));
                }

                $this->addFlash('sonata_flash_error', $this->get('translator')->trans('flash_history_revert_error', array(), 'MarlincAdminBundle'));
            }

            return new RedirectResponse($this->admin->generateUrl('list'));
        }

        return $this->renderWithExtraParams($this->admin->getTemplate('history_revert'), array(
            'object' => $object,
            'revision' => $revision,
            'action' => 'revert',
            'csrf_token' => $this->getCsrfToken('sonata.history.revert')
        ));
    }

    /**
     * @inheritdoc
     */
    public function listAction(Request $request): Response
    {
        $this->assertObjectExists($request);

        $this->admin->checkAccess('list');

        $preResponse = $this->preList($request);
        if (null !== $preResponse) {
            return $preResponse;
        }

        $listMode = $request->get('_list_mode');

        if (null !== $listMode) {
            $this->admin->setListMode($listMode);
        }

        $datagrid = $this->admin->getDatagrid();
        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        $this->setFormTheme($formView, $this->admin->getFilterTheme());

        $template = $this->admin->getTemplateRegistry()->getTemplate('list');

        if ($this->has(AdminExporter::class)) {
            $exporter = $this->get(AdminExporter::class);
            \assert($exporter instanceof AdminExporter);
            $exportFormats = $exporter->getAvailableFormats($this->admin);
        }

        return $this->renderWithExtraParams($template, [
            'action' => 'list',
            'form' => $formView,
            'datagrid' => $datagrid,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
            'export_formats' => $exportFormats ?? $this->admin->getExportFormats(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function exportAction(Request $request): Response
    {
        $this->admin->checkAccess('export');
        $adminExporter = $this->get(AdminExporter::class);

        // Get service export name and file format from request
        $format = $request->get('format');

        // Get the real format and filename to use.
        $exportFormat = $adminExporter->getExportFormat($this->admin, $format);
        $filetype = $exportFormat->getFileType();

        $filename = $adminExporter->getExportFilename($this->admin, $exportFormat, $filetype);

        // Build query.
        $datagrid = $this->admin->getDatagrid();
        $datagrid->buildPager();

        $query = $datagrid->getQuery();
        $query->select('DISTINCT ' . $query->getRootAlias());

        // Reset page size restrictions
        $query->setFirstResult(null);
        $query->setMaxResults(null);

        if ($query instanceof ProxyQueryInterface) {
            if ($query->getSortBy() !== NULL) {
                $query->addOrderBy($query->getSortBy(), $query->getSortOrder());
            }

            $query = $query->getQuery();
        }

        return $adminExporter->getResponse(
            $filetype,
            $filename,
            $exportFormat,
            $exportFormat->getSourceIterator($query)
        );
    }

    /**
     * List all soft deleted entities.
     */
    public function trashAction(EntityManagerInterface $em): Response
    {
        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        $em->getFilters()->disable('softdeleteable');
        $em->getFilters()->enable('softdeleteabletrash');

        if ($this->admin instanceof AbstractAdmin) {
            $this->admin->setDatagridMode(AbstractAdmin::MODE_TRASH);
        }

        $datagrid = $this->admin->getDatagrid();
        $formView = $datagrid->getForm()->createView();

        // Set the theme for the current Admin Form
        $this->get('twig')->getRuntime(FormRenderer::class)->setTheme($formView, $this->admin->getFilterTheme());

        // Get exporter service.
        $exporter = $this->get(AdminExporter::class);

        return $this->renderWithExtraParams($this->admin->getTemplate('trash'), [
            'action' => 'trash',
            'form' => $formView,
            'datagrid' => $datagrid,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
            'export_formats' => $exporter->getAvailableFormats($this->admin),
        ]);
    }

    /**
     * Undelete an entity from the trash.
     */
    public function untrashAction(Request $request, EntityManagerInterface $em): Response
    {
        $em->getFilters()->disable('softdeleteable');
        $em->getFilters()->enable('softdeleteabletrash');

        $id = $request->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        if ($request->getMethod() == Request::METHOD_POST) {
            // check the csrf token
            $this->validateCsrfToken($request, 'sonata.untrash');

            try {
                $object->setDeletedAt(null);
                $object->setDeletedBy(null);
                $this->admin->update($object);

                if ($this->isXmlHttpRequest($request)) {
                    return $this->renderJson(['result' => 'ok']);
                }

                $this->addFlash('sonata_flash_info', $this->get('translator')->trans('flash_untrash_successfull', [], 'MarlincAdminBundle'));

            } catch (ModelManagerException $e) {

                if ($this->isXmlHttpRequest($request)) {
                    return $this->renderJson(['result' => 'error']);
                }

                $this->addFlash('sonata_flash_info', $this->get('translator')->trans('flash_untrash_error', [], 'MarlincAdminBundle'));
            }

            return new RedirectResponse($this->admin->generateUrl('list'));
        }

        return $this->renderWithExtraParams($this->admin->getTemplate('untrash'), [
            'object' => $object,
            'action' => 'untrash',
            'csrf_token' => $this->getCsrfToken('sonata.untrash')
        ]);
    }

    /**
     * Delete an entity for real.
     */
    public function realdeleteAction(Request $request, EntityManagerInterface $em): Response
    {
        $em->getFilters()->disable('softdeleteable');
        $em->getFilters()->enable('softdeleteabletrash');

        $id = $request->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id: %s', $id));
        }

        $this->checkParentChildAssociation($request, $object);

        $this->admin->checkAccess('delete', $object);

        $preResponse = $this->preDelete($request, $object);
        if (null !== $preResponse) {
            return $preResponse;
        }

        if ($request->getMethod() === Request::METHOD_DELETE) {
            // check the csrf token
            $this->validateCsrfToken($request, 'sonata.realdelete');

            $objectName = $this->admin->toString($object);

            try {
                $this->admin->delete($object);

                if ($this->isXmlHttpRequest($request)) {
                    return $this->renderJson(['result' => 'ok'], 200, []);
                }

                $this->addFlash(
                    'sonata_flash_success',
                    $this->trans(
                        'flash_delete_success',
                        ['%name%' => $this->escapeHtml($objectName)],
                        'SonataAdminBundle'
                    )
                );
            } catch (ModelManagerException $e) {
                $this->handleModelManagerException($e);

                if ($this->isXmlHttpRequest($request)) {
                    return $this->renderJson(['result' => 'error'], 200, []);
                }

                $this->addFlash(
                    'sonata_flash_error',
                    $this->trans(
                        'flash_delete_error',
                        ['%name%' => $this->escapeHtml($objectName)],
                        'SonataAdminBundle'
                    )
                );
            }

            return $this->redirectToTrash();
        }

        return $this->renderWithExtraParams($this->admin->getTemplate('realdelete'), [
            'object' => $object,
            'action' => 'delete',
            'csrf_token' => $this->getCsrfToken('sonata.realdelete'),
        ]);
    }

    /**
     * Move a batch of entities to the trash.
     */
    public function batchTrashAction(Request $request): Response
    {
        if ($this->admin instanceof AbstractAdmin) {
            $this->admin->setDatagridMode(AbstractAdmin::MODE_TRASH);
        }

        return $this->batchAction($request);
    }

    /**
     * Execute a batch delete while in trash.
     */
    public function batchActionRealdelete(ProxyQueryInterface $query, EntityManagerInterface $em): Response
    {
        $this->admin->checkAccess('batchDelete');

        $modelManager = $this->admin->getModelManager();

        $em->getFilters()->disable('softdeleteable');
        $em->getFilters()->enable('softdeleteabletrash');

        try {
            $modelManager->batchDelete($this->admin->getClass(), $query);
            $this->addFlash(
                'sonata_flash_success',
                $this->trans('flash_batch_delete_success', [], 'SonataAdminBundle')
            );
        } catch (ModelManagerException $e) {
            $this->handleModelManagerException($e);
            $this->addFlash(
                'sonata_flash_error',
                $this->trans('flash_batch_delete_error', [], 'SonataAdminBundle')
            );
        }

        return $this->redirectToTrash();
    }

    /**
     * Undelete a batch of entities while in trash.
     */
    public function batchActionUntrash(ProxyQueryInterface $query, EntityManagerInterface $em): Response
    {
        $this->admin->checkAccess('edit');

        $em->getFilters()->disable('softdeleteable');
        $em->getFilters()->enable('softdeleteabletrash');

        try {
            $query->select('DISTINCT ' . current($query->getRootAliases()));

            try {
                $i = 0;

                foreach ($query->getQuery()->iterate() as $pos => $object) {
                    $object[0]->setDeletedAt(null);
                    $object[0]->setDeletedBy(null);

                    if (0 == (++$i % 20)) {
                        $em->flush();
                        $em->clear();
                    }
                }

                $em->flush();
                $em->clear();
            } catch (\PDOException $e) {
                throw new ModelManagerException('', 0, $e);
            }

            $this->addFlash(
                'sonata_flash_success',
                $this->trans('flash_batch_untrash_success', [], 'MarlincAdminBundle')
            );
        } catch (ModelManagerException $e) {
            $this->handleModelManagerException($e);
            $this->addFlash(
                'sonata_flash_error',
                $this->trans('flash_batch_untrash_error', [], 'MarlincAdminBundle')
            );
        }

        return $this->redirectToTrash();
    }

    /**
     * Redirects the user to the trash list view.
     */
    final protected function redirectToTrash(): RedirectResponse
    {
        $parameters = [];

        if ($filter = $this->admin->getFilterParameters()) {
            $parameters['filter'] = $filter;
        }

        return $this->redirect($this->admin->generateUrl('trash', $parameters));
    }
}