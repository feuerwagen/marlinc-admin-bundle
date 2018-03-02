<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 29.06.17
 * Time: 17:30
 */

namespace Marlinc\AdminBundle\Controller;


use Picoss\SonataExtraAdminBundle\Controller\ExtraAdminController;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MarlincAdminController extends ExtraAdminController
{
    /**
     * Export data to specified format.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws AccessDeniedException If access is not granted
     * @throws \RuntimeException     If the export format is invalid
     */
    public function exportAction(Request $request)
    {
        $this->admin->checkAccess('export');
        $adminExporter = $this->get('marlinc.admin.exporter');

        // Get service export name and file format from request
        $filetype = $request->get('filetype');
        $format = $request->get('format');

        // Get the real format and filename to use.
        $exportFormat = $adminExporter->getExportFormat($this->admin, $format);
        $filename = $adminExporter->getExportFilename($this->admin, $exportFormat, $filetype);

        // Build query.
        $datagrid = $this->admin->getDatagrid();
        $datagrid->buildPager();

        $query = $datagrid->getQuery();
        $query->select('DISTINCT '.$query->getRootAlias());

        // Reset page size restrictions
        $query->setFirstResult(null);
        $query->setMaxResults(null);

        if ($query instanceof ProxyQueryInterface) {
            $query->addOrderBy($query->getSortBy(), $query->getSortOrder());
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
     * List action.
     * Overridden to adapt to the needs of the improved export format.
     *
     * @return Response
     *
     * @throws AccessDeniedException If access is not granted
     */
    public function listAction()
    {
        $request = $this->getRequest();

        $this->admin->checkAccess('list');

        $preResponse = $this->preList($request);
        if ($preResponse !== null) {
            return $preResponse;
        }

        if ($listMode = $request->get('_list_mode')) {
            $this->admin->setListMode($listMode);
        }

        $datagrid = $this->admin->getDatagrid();
        $formView = $datagrid->getForm()->createView();

        // Set the theme for the current Admin form.
        $this->get('twig')->getRuntime(FormRenderer::class)->setTheme($formView, $this->admin->getFilterTheme());

        // Get exporter service.
        $exporter = $this->get('marlinc.admin.exporter');

        return $this->renderWithExtraParams($this->admin->getTemplate('list'), array(
            'action' => 'list',
            'form' => $formView,
            'datagrid' => $datagrid,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
            'export_formats' => $exporter->getAvailableFormats($this->admin),
        ), null);
    }

    /**
     * Return the Response object associated to the trash action.
     * Overridden to fix the invocation of the softdeleteable trash filter.
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @return Response
     */
    public function trashAction()
    {
        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->disable('softdeleteable');
        $em->getFilters()->enable('softdeleteabletrash');
        $em->getFilters()->getFilter('softdeleteabletrash')->enableForEntity($this->admin->getClass());

        $datagrid = $this->admin->getDatagrid();
        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getRuntime(FormRenderer::class)->setTheme($formView, $this->admin->getFilterTheme());

        return $this->renderWithExtraParams($this->admin->getTemplate('trash'), array(
            'action'     => 'trash',
            'form'       => $formView,
            'datagrid'   => $datagrid,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
            'export_formats' => [],
        ));
    }
}