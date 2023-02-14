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

final class ServiceInvokeCommand extends Command implements SelfValidating
{
    use SelfValidateTrait;

    /** @var ContainerInterface */
    private $container;

    /** @var ArgumentCompilerInterface */
    private $argumentCompiler;

    /** @var string */
    private $serviceId;

    /** @var object|null */
    private $service;

    /** @var string */
    private $method;

    /** @var array */
    private $arguments;

    /** @var string */
    private $name;

    /** @var string */
    private $description;

    /** @var array<string, array<string, string>> */
    private $inputArguments;

    /** @var array<string, array<string, string>> */
    private $inputOptions;

    public function __construct(
        ContainerInterface $container,
        ArgumentCompilerInterface $argumentCompiler,
        array $options
    ) {
        Assert::keyExists($options, 'service');
        Assert::keyExists($options, 'name');

        /** @var array<string, mixed> $defaults */
        $defaults = array(
            'arguments' => [],
            'method' => '__invoke',
            'description' => '',
            'input-options' => [],
            'input-arguments' => [],
        );

        $options = array_merge($defaults, $options);

        Assert::isArray($options['arguments']);

        $this->container = $container;
        $this->argumentCompiler = $argumentCompiler;
        $this->serviceId = $options['service'];
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
            /** @var object $service */
            $service = $this->service();

            $reflectionObject = new ReflectionObject($service);

            /** @var ReflectionMethod $refletionMethod */
            $refletionMethod = $reflectionObject->getMethod($this->method);

            return $this->areArgumentsCompatibleWithReflectionMethod(
                $refletionMethod, 
                $this->arguments, 
                $reason
            );

        } catch (Throwable $exception) {
            $reason = $exception->getMessage();

            return false;
        }
    }

    protected function buildInvalidMessage(string $reason): string
    {
        return sprintf(
            'Configuration of Service-Invoke-Command "%s" is invalid: "%s"!',
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
        /** @var object $service */
        $service = $this->service();

        $methodReflection = new ReflectionMethod($service, $this->method);

        /** @var mixed $additionalArguments */
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

        $methodReflection->invoke($service, $arguments);

        return 0;
    }

    private function service(): object
    {
        if (is_null($this->service)) {
            $this->service = $this->container->get($this->serviceId);
        }

        return $this->service;
    }

}
