<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 29.06.17
 * Time: 17:30
 */

namespace Marlinc\AdminBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Marlinc\AdminBundle\Admin\AbstractAdmin;
use Marlinc\AdminBundle\Bridge\AdminExporter;
use Marlinc\SonataExtraAdminBundle\Controller\ExtraAdminController;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Marlinc\SonataExtraAdminBundle\Model\TrashManager;

class MarlincAdminController extends ExtraAdminController
{
    public static function getSubscribedServices(): array
    {
        return [
            AdminExporter::class,
        ] + parent::getSubscribedServices();
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
     * Return the Response object associated to the trash action.
     * Overridden to fix the invocation of the softdeleteable trash filter.
     *
     * @return Response
     * @throws AccessDeniedException|\Twig\Error\RuntimeError
     */
    public function trashAction()
    {
        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
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
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function untrashAction(Request $request, $id,TrashManager $trashManager)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->disable('softdeleteable');
        $em->getFilters()->enable('softdeleteabletrash');

        $id = $request->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        if ($request->getMethod() == 'POST') {
            // check the csrf token
            $this->validateCsrfToken('sonata.untrash');

            try {
                $object->setDeletedAt(null);
                $object->setDeletedBy(null);
                $this->admin->update($object);

                if ($this->isXmlHttpRequest()) {
                    return $this->renderJson(['result' => 'ok']);
                }

                $this->addFlash('sonata_flash_info', $this->get('translator')->trans('flash_untrash_successfull', [], 'PicossSonataExtraAdminBundle'));

            } catch (ModelManagerException $e) {

                if ($this->isXmlHttpRequest()) {
                    return $this->renderJson(['result' => 'error']);
                }

                $this->addFlash('sonata_flash_info', $this->get('translator')->trans('flash_untrash_error', [], 'PicossSonataExtraAdminBundle'));
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
     * Delete action.
     *
     * @param int|string|null $id
     *
     * @return Response|RedirectResponse
     * @throws AccessDeniedException If access is not granted
     * @throws \Exception
     *
     * @throws NotFoundHttpException If the object does not exist
     */
    public function realdeleteAction($id)
    {
        $request = $this->getRequest();
        $id = $request->get($this->admin->getIdParameter());

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->disable('softdeleteable');
        $em->getFilters()->enable('softdeleteabletrash');

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

        if ('DELETE' == $this->getRestMethod()) {
            // check the csrf token
            $this->validateCsrfToken('sonata.realdelete');

            $objectName = $this->admin->toString($object);

            try {
                $this->admin->delete($object);

                if ($this->isXmlHttpRequest()) {
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

                if ($this->isXmlHttpRequest()) {
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

            return $this->redirectTo($object);
        }

        return $this->renderWithExtraParams($this->admin->getTemplate('realdelete'), [
            'object' => $object,
            'action' => 'delete',
            'csrf_token' => $this->getCsrfToken('sonata.realdelete'),
        ], null);
    }

    public function batchTrashAction()
    {
        if ($this->admin instanceof AbstractAdmin) {
            $this->admin->setDatagridMode(AbstractAdmin::MODE_TRASH);
        }

        return $this->batchAction();
    }

    /**
     * Execute a batch delete while in trash.
     *
     * @param ProxyQueryInterface $query
     * @return RedirectResponse
     * @throws \Exception
     */
    public function batchActionRealdelete(ProxyQueryInterface $query)
    {
        $this->admin->checkAccess('batchDelete');

        $modelManager = $this->admin->getModelManager();

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
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
     * Execute a batch delete while in trash.
     *
     * @param ProxyQueryInterface $query
     * @return RedirectResponse
     * @throws \Exception
     */
    public function batchActionUntrash(ProxyQueryInterface $query)
    {
        $this->admin->checkAccess('edit');

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
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
            } catch (DBALException $e) {
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
     * Redirects the user to the list view.
     *
     * @return RedirectResponse
     */
    final protected function redirectToTrash()
    {
        $parameters = [];

        if ($filter = $this->admin->getFilterParameters()) {
            $parameters['filter'] = $filter;
        }

        return $this->redirect($this->admin->generateUrl('trash', $parameters));
    }

    private function checkParentChildAssociation(Request $request, $object)
    {
        if (!($parentAdmin = $this->admin->getParent())) {
            return;
        }

        $parentId = $request->get($parentAdmin->getIdParameter());

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyPath = new PropertyPath($this->admin->getParentAssociationMapping());

        if ($parentAdmin->getObject($parentId) !== $propertyAccessor->getValue($object, $propertyPath)) {
            // NEXT_MAJOR: make this exception
            @trigger_error("Accessing a child that isn't connected to a given parent is deprecated since 3.34"
                . " and won't be allowed in 4.0.",
                E_USER_DEPRECATED
            );
        }
    }
}