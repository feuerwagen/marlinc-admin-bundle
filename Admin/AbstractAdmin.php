<?php
/**
 * Created by PhpStorm.
 * User: elias
 * Date: 21.03.17
 * Time: 15:43
 */

namespace MarlincUtils\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin as BaseAdmin;
use Symfony\Component\Intl\Intl;

abstract class AbstractAdmin extends BaseAdmin
{
    /**
     * The default number of results to display in the list.
     *
     * @var int
     */
    protected $maxPerPage = 20;

    /**
     * Predefined per page options.
     *
     * @var array
     */
    protected $perPageOptions = array(10, 20, 50, 100, 200);

    protected function getFilteredLanguages() {
        $languages = array_flip($this->getConfigurationPool()->getContainer()->getParameter('marlinc_languages'));

        return array_intersect_key(Intl::getLanguageBundle()->getLanguageNames(), $languages);
    }

    protected function getFilteredCountries() {
        $countries = array_flip($this->getConfigurationPool()->getContainer()->getParameter('marlinc_countries'));

        return array_intersect_key(Intl::getRegionBundle()->getCountryNames(), $countries);
    }
}