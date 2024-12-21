<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Commands;

use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Addiks\SymfonyGenerics\SelfValidating;
use Addiks\SymfonyGenerics\SelfValidateTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use ReflectionObject;
use ReflectionMethod;
use ReflectionParameter;
use Webmozart\Assert\Assert;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\Persistence\ObjectManager;
use ReflectionClass;

final class EntityInvokeCommand extends Command implements SelfValidating
{
    use SelfValidateTrait;

    private string $repositoryMethod;

    private string $method;

    private array $arguments;

    private string $name;

    private string $description;

    /** @var array<string, array<string, string>> */
    private array $inputArguments;

    /** @var array<string, array<string, string>> */
    private array $inputOptions;

    public function __construct(
        private ContainerInterface $container,
        private ArgumentCompilerInterface $argumentCompiler,
        private ObjectRepository $entityRepository,
        private ObjectManager $objectManager,
        array $options
    ) {
        Assert::keyExists($options, 'name');

        /** @var array<string, mixed> $defaults */
        $defaults = array(
            'repository-method' => 'findAll',
            'arguments' => [],
            'method' => '__invoke',
            'description' => '',
            'input-options' => [],
            'input-arguments' => [],
        );

        $options = array_merge($defaults, $options);

        Assert::isArray($options['arguments']);

        $this->repositoryMethod = $options['repository-method'];
        $this->method = $options['method'];
        $this->arguments = $options['arguments'];
        $this->name = $options['name'];
        $this->description = $options['description'];
        $this->inputArguments = $options['input-arguments'];
        $this->inputOptions = $options['input-options'];
        
        parent::__construct();
    }

    public function isSelfValid(?string &$reason = null): bool
    {
        try {
            Assert::methodExists($this->entityRepository, $this->method);

            return $this->areArgumentsCompatibleWithReflectionMethod(
                $this->refletionMethod(), 
                $this->arguments, 
                $reason
            );

        } catch (Throwable $exception) {
            $reason = $exception->getMessage();

            return false;
        }
    }
    
    private function reflectionMethod(): ReflectionMethod
    {
        $reflectionClass = new ReflectionClass($this->entityRepository->getClassName());

        /** @var ReflectionMethod $reflectionMethod */
        $reflectionMethod = $reflectionClass->getMethod($this->method);

        return $reflectionMethod;
    }

    protected function buildInvalidMessage(string $reason): string
    {
        return sprintf(
            'Configuration of Entity-Invoke-Command "%s" is invalid: "%s"!',
            $this->name,
            $reason
        );
    }

    protected function configure(): void
    {
        $this->setName($this->name);

        foreach ($this->inputArguments as $name => $config) {
            /** @var int $mode */
            $mode = InputArgument::REQUIRED;

            if ($config['optional'] ?? false) {
                $mode = InputArgument::OPTIONAL;
            }

            if ($config['is-array'] ?? false) {
                $mode = $mode + InputArgument::IS_ARRAY;
            }

            $this->addArgument($name, $mode, $config['description'] ?? '');
        }

        foreach ($this->inputOptions as $name => $config) {
            $mode = InputOption::VALUE_REQUIRED;

            if ($config['flag'] ?? false) {
                $mode = InputOption::VALUE_NONE;

            } elseif ($config['optional'] ?? false) {
                $mode = InputOption::VALUE_OPTIONAL;

            } elseif ($config['is-array'] ?? false) {
                $mode = InputOption::VALUE_IS_ARRAY;
            }

            $this->addOption($name, $config['shortcut'] ?? null, $mode, $config['description'] ?? '');
        }

        if (!empty($this->description)) {
            $this->setDescription($this->description);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var array<array-key, object> $entitie */
        $entities = $this->entityRepository->{$this->repositoryMethod}();
        
        /** @var ReflectionMethod $methodReflection */
        $methodReflection = $this->reflectionMethod();
        
        foreach ($entities as $entity) {
            
            /** @var array<string, mixed> $additionalArguments */
            $additionalArguments = array();

            foreach (array_keys($this->inputArguments) as $name) {
                $additionalArguments[$name] = $input->getArgument($name);
            }

            foreach (array_keys($this->inputOptions) as $name) {
                $additionalArguments[$name] = $input->getOption($name);
            }

            /** @var array $arguments */
            $arguments = $this->argumentCompiler->buildCallArguments(
                $methodReflection,
                $this->arguments,
                [],
                $additionalArguments
            );

            $methodReflection->invokeArgs($entity, $arguments);
        }

        $this->objectManager->flush();

        return 0;
    }

}
