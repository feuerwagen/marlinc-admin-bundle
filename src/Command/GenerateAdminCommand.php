<?php


namespace Marlinc\AdminBundle\Command;

use Marlinc\AdminBundle\Generator\AdminGenerator;
use Marlinc\AdminBundle\Generator\ControllerGenerator;
use Sonata\AdminBundle\Command\QuestionableCommand;
use Sonata\AdminBundle\Command\Validators;
use Sonata\AdminBundle\Manipulator\ServicesManipulator;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * TODO: Replace with a maker command
 */
class GenerateAdminCommand extends QuestionableCommand
{
    /**
     * @var string[]
     */
    private $managerTypes;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('marlinc:admin:generate')
            ->setDescription('Generates an admin class based on the given model class')
            ->addArgument('model', InputArgument::REQUIRED, 'The fully qualified model class')
            ->addOption('bundle', 'b', InputOption::VALUE_OPTIONAL, 'The bundle name')
            ->addOption('admin', 'a', InputOption::VALUE_OPTIONAL, 'The admin class basename')
            ->addOption('controller', 'c', InputOption::VALUE_OPTIONAL, 'The controller class basename')
            ->addOption('manager', 'm', InputOption::VALUE_OPTIONAL, 'The model manager type')
            ->addOption('services', 'y', InputOption::VALUE_OPTIONAL, 'The services YAML file', 'services.yml')
            ->addOption('id', 'i', InputOption::VALUE_OPTIONAL, 'The admin service ID')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return class_exists('Sensio\\Bundle\\GeneratorBundle\\SensioGeneratorBundle');
    }

    /**
     * @param string $managerType
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function validateManagerType($managerType)
    {
        $managerTypes = $this->getAvailableManagerTypes();

        if (!isset($managerTypes[$managerType])) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid manager type "%s". Available manager types are "%s".',
                $managerType,
                implode('", "', $managerTypes)
            ));
        }

        return $managerType;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $modelClass = Validators::validateClass($input->getArgument('model'));
        $modelClassBasename = current(array_slice(explode('\\', $modelClass), -1));
        $bundle = $this->getBundle($input->getOption('bundle') ?: $this->getBundleNameFromClass($modelClass));
        $adminClassBasename = $input->getOption('admin') ?: $modelClassBasename.'Admin';
        $adminClassBasename = Validators::validateAdminClassBasename($adminClassBasename);
        $managerType = $input->getOption('manager') ?: $this->getDefaultManagerType();
        $modelManager = $this->getModelManager($managerType);
        $skeletonDirectory = __DIR__.'/../Resources/skeleton';
        $adminGenerator = new AdminGenerator($modelManager, $skeletonDirectory);

        // Create admin class from template.
        try {
            $adminGenerator->generate($bundle, $adminClassBasename, $modelClass);
            $output->writeln(sprintf(
                '%sThe admin class "<info>%s</info>" has been generated under the file "<info>%s</info>".',
                PHP_EOL,
                $adminGenerator->getClass(),
                realpath($adminGenerator->getFile())
            ));
        } catch (\Exception $e) {
            $this->writeError($output, $e->getMessage());
        }

        // Create controller class from template if needed.
        if ($controllerClassBasename = $input->getOption('controller')) {
            $controllerClassBasename = Validators::validateControllerClassBasename($controllerClassBasename);
            $controllerGenerator = new ControllerGenerator($skeletonDirectory);

            try {
                $controllerGenerator->generate($bundle, $controllerClassBasename);
                $output->writeln(sprintf(
                    '%sThe controller class "<info>%s</info>" has been generated under the file "<info>%s</info>".',
                    PHP_EOL,
                    $controllerGenerator->getClass(),
                    realpath($controllerGenerator->getFile())
                ));
            } catch (\Exception $e) {
                $this->writeError($output, $e->getMessage());
            }
        }

        // Add admin service to config file if needed.
        if ($servicesFile = $input->getOption('services')) {
            $adminClass = $adminGenerator->getClass();
            $file = sprintf('%s/Resources/config/%s', $bundle->getPath(), $servicesFile);
            $servicesManipulator = new ServicesManipulator($file);
            $controllerName = $controllerClassBasename
                ? sprintf('%s:%s', $bundle->getName(), substr($controllerClassBasename, 0, -10))
                : 'MarlincAdminBundle:MarlincAdmin'
            ;

            try {
                $id = $input->getOption('id') ?: $this->getAdminServiceId($bundle->getName(), $adminClassBasename);
                $servicesManipulator->addResource($id, $modelClass, $adminClass, $controllerName, $managerType);
                $output->writeln(sprintf(
                    '%sThe service "<info>%s</info>" has been appended to the file <info>"%s</info>".',
                    PHP_EOL,
                    $id,
                    realpath($file)
                ));
            } catch (\Exception $e) {
                $this->writeError($output, $e->getMessage());
            }
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to the Sonata admin generator');
        $modelClass = $this->askAndValidate(
            $input,
            $output,
            'The fully qualified model class',
            $input->getArgument('model'),
            'Sonata\AdminBundle\Command\Validators::validateClass'
        );
        $modelClassBasename = current(array_slice(explode('\\', $modelClass), -1));
        $bundleName = $this->askAndValidate(
            $input,
            $output,
            'The bundle name',
            $input->getOption('bundle') ?: $this->getBundleNameFromClass($modelClass),
            'Sensio\Bundle\GeneratorBundle\Command\Validators::validateBundleName'
        );
        $adminClassBasename = $this->askAndValidate(
            $input,
            $output,
            'The admin class basename',
            $input->getOption('admin') ?: $modelClassBasename.'Admin',
            'Sonata\AdminBundle\Command\Validators::validateAdminClassBasename'
        );

        if (count($this->getAvailableManagerTypes()) > 1) {
            $managerType = $this->askAndValidate(
                $input,
                $output,
                'The manager type',
                $input->getOption('manager') ?: $this->getDefaultManagerType(),
                [$this, 'validateManagerType']
            );
            $input->setOption('manager', $managerType);
        }

        if ($this->askConfirmation($input, $output, 'Do you want to generate a controller', 'no', '?')) {
            $controllerClassBasename = $this->askAndValidate(
                $input,
                $output,
                'The controller class basename',
                $input->getOption('controller') ?: $modelClassBasename.'AdminController',
                'Sonata\AdminBundle\Command\Validators::validateControllerClassBasename'
            );
            $input->setOption('controller', $controllerClassBasename);
        }

        if ($this->askConfirmation($input, $output, 'Do you want to update the services YAML configuration file', 'yes', '?')) {
            $path = $this->getBundle($bundleName)->getPath().'/Resources/config/';
            $servicesFile = $this->askAndValidate(
                $input,
                $output,
                'The services YAML configuration file',
                is_file($path.'admin.yml') ? 'admin.yml' : 'services.yml',
                'Sonata\AdminBundle\Command\Validators::validateServicesFile'
            );
            $id = $this->askAndValidate(
                $input,
                $output,
                'The admin service ID',
                $this->getAdminServiceId($bundleName, $adminClassBasename),
                'Sonata\AdminBundle\Command\Validators::validateServiceId'
            );
            $input->setOption('services', $servicesFile);
            $input->setOption('id', $id);
        } else {
            $input->setOption('services', false);
        }

        $input->setArgument('model', $modelClass);
        $input->setOption('admin', $adminClassBasename);
        $input->setOption('bundle', $bundleName);
    }

    /**
     * @param string $class
     *
     * @return string|null
     *
     * @throws \InvalidArgumentException
     */
    private function getBundleNameFromClass($class)
    {
        $application = $this->getApplication();
        /* @var $application Application */

        foreach ($application->getKernel()->getBundles() as $bundle) {
            if (0 === strpos($class, $bundle->getNamespace().'\\')) {
                return $bundle->getName();
            }
        }

        return;
    }

    /**
     * @param string $name
     *
     * @return BundleInterface
     */
    private function getBundle($name)
    {
        return $this->getKernel()->getBundle($name);
    }

    /**
     * @param OutputInterface $output
     * @param string          $message
     */
    private function writeError(OutputInterface $output, $message)
    {
        $output->writeln(sprintf("\n<error>%s</error>", $message));
    }

    /**
     * @return string
     *
     * @throws \RuntimeException
     */
    private function getDefaultManagerType()
    {
        if (!$managerTypes = $this->getAvailableManagerTypes()) {
            throw new \RuntimeException('There are no model managers registered.');
        }

        return current($managerTypes);
    }

    /**
     * @param string $managerType
     *
     * @return ModelManagerInterface
     */
    private function getModelManager($managerType)
    {
        return $this->getContainer()->get('sonata.admin.manager.'.$managerType);
    }

    /**
     * @param string $bundleName
     * @param string $adminClassBasename
     *
     * @return string
     */
    private function getAdminServiceId($bundleName, $adminClassBasename)
    {
        $prefix = 'Bundle' == substr($bundleName, -6) ? substr($bundleName, 0, -6) : $bundleName;
        $suffix = 'Admin' == substr($adminClassBasename, -5) ? substr($adminClassBasename, 0, -5) : $adminClassBasename;
        $suffix = str_replace('\\', '.', $suffix);

        return Container::underscore(sprintf(
            '%s.admin.%s',
            $prefix,
            $suffix
        ));
    }

    /**
     * @return string[]
     */
    private function getAvailableManagerTypes()
    {
        $container = $this->getContainer();

        if (!$container instanceof Container) {
            return [];
        }

        if (null === $this->managerTypes) {
            $this->managerTypes = [];

            foreach ($container->getServiceIds() as $id) {
                if (0 === strpos($id, 'sonata.admin.manager.')) {
                    $managerType = substr($id, 21);
                    $this->managerTypes[$managerType] = $managerType;
                }
            }
        }

        return $this->managerTypes;
    }

    /**
     * @return KernelInterface
     */
    private function getKernel()
    {
        /* @var $application Application */
        $application = $this->getApplication();

        return $application->getKernel();
    }
}
