<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Services;

use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentFactory;
use Addiks\SymfonyGenerics\Arguments\Argument;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;
use ErrorException;
use ReflectionType;
use ReflectionNamedType;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionException;
use Symfony\Component\HttpFoundation\RequestStack;
use Addiks\SymfonyGenerics\Arguments\ArgumentContextInterface;
use InvalidArgumentException;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use ValueObjects\ValueObjectInterface;
use Exception;

final class ArgumentCompiler implements ArgumentCompilerInterface
{

    /**
     * @var ArgumentFactory
     */
    private $argumentFactory;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ArgumentContextInterface
     */
    private $argumentContext;

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    public function __construct(
        ArgumentFactory $argumentFactory,
        RequestStack $requestStack,
        ArgumentContextInterface $argumentContext,
        ControllerHelperInterface $controllerHelper
    ) {
        $this->argumentFactory = $argumentFactory;
        $this->requestStack = $requestStack;
        $this->argumentContext = $argumentContext;
        $this->controllerHelper = $controllerHelper;
    }

    public function understandsArgumentString(string $argumentConfiguration): bool
    {
        return $this->argumentFactory->understandsString($argumentConfiguration);
    }

    /** @param array|string $argumentConfiguration */
    public function buildArgument($argumentConfiguration, array $additionalData = array())
    {
        foreach ($additionalData as $key => $value) {
            $this->argumentContext->set($key, $value);
        }

        return $this->resolveArgumentConfiguration($argumentConfiguration);
    }

    public function buildArguments(
        array $argumentsConfiguration,
        array $additionalData = array()
    ): array {
        /** @var array $argumentValues */
        $argumentValues = array();

        foreach ($additionalData as $key => $value) {
            $this->argumentContext->set($key, $value);
        }

        foreach ($argumentsConfiguration as $key => $argumentConfiguration) {
            /** @var array|string $argumentConfiguration */

            $argumentValues[$key] = $this->resolveArgumentConfiguration($argumentConfiguration);
        }

        return $argumentValues;
    }

    public function buildCallArguments(
        ReflectionFunctionAbstract $methodReflection,
        array $argumentsConfiguration,
        array $predefinedArguments = array(),
        array $additionalData = array()
    ): array {
        /** @var array<int, mixed> $callArguments */
        $callArguments = array();

        foreach ($additionalData as $key => $value) {
            $this->argumentContext->set($key, $value);
        }

        foreach ($methodReflection->getParameters() as $index => $parameterReflection) {
            /** @var ReflectionParameter $parameterReflection */

            if (isset($predefinedArguments[$index])) {
                $callArguments[$index] = $predefinedArguments[$index];
                continue;
            }

            $callArguments[$index] = $this->resolveParameterReflection(
                $parameterReflection,
                $argumentsConfiguration,
                $index
            );
        }

        return $callArguments;
    }

    /**
     * @param array|string|bool|object|null $argumentConfiguration
     *
     * @return mixed
     */
    private function resolveArgumentConfiguration($argumentConfiguration)
    {
        Assert::oneOf(
            gettype($argumentConfiguration),
            ['string', 'array', 'NULL', 'boolean', 'object'],
            "Arguments must be defined as string, array, bool, object or null!"
        );

        /** @var Argument|null $argument */
        $argument = null;

        if (is_bool($argumentConfiguration) || is_null($argumentConfiguration) || is_object($argumentConfiguration)) {
            return $argumentConfiguration;

        } else if ($argumentConfiguration === '') {
            return '';

        } elseif (is_array($argumentConfiguration)) {
            Assert::true($this->argumentFactory->understandsArray($argumentConfiguration), sprintf(
                "Argument '%s' could not be understood!",
                preg_replace("/\s+/is", "", var_export($argumentConfiguration, true))
            ));

            $argument = $this->argumentFactory->createArgumentFromArray($argumentConfiguration);

        } else {
            Assert::true($this->argumentFactory->understandsString($argumentConfiguration), sprintf(
                "Argument '%s' could not be understood!",
                $argumentConfiguration
            ));

            $argument = $this->argumentFactory->createArgumentFromString(trim($argumentConfiguration));
        }

        return $argument->resolve();
    }

    /**
     * @return mixed
     */
    private function resolveParameterReflection(
        ReflectionParameter $parameterReflection,
        array $argumentsConfiguration,
        int $index
    ) {
        try {
            /** @var string $parameterName */
            $parameterName = $parameterReflection->getName();

            /** @var string|null $parameterTypeName */
            $parameterTypeName = $this->getTypeNameFromReflectionParameter($parameterReflection);

            /** @var Request|null $request */
            $request = $this->requestStack->getCurrentRequest();

            /** @var mixed $result */
            $result = null;

            if (isset($argumentsConfiguration[$parameterName])) {
                $result = $this->resolveArgumentConfiguration($argumentsConfiguration[$parameterName]);

            } elseif (array_key_exists($index, $argumentsConfiguration)) {
                $result = $this->resolveArgumentConfiguration($argumentsConfiguration[$index]);

            } elseif ($parameterTypeName === Request::class) {
                $result = $request;

            } elseif (is_object($request) && $request->get($parameterName)) {
                $result = $request->get($parameterName);

            } else {
                $result = $this->getDefaultValueFromParameterReflectionSafely($parameterReflection);
            }

            if (!empty($parameterTypeName) && (is_string($result) || is_int($result))) {
                if (is_subclass_of($parameterTypeName, ValueObjectInterface::class)) {
                    $result = call_user_func("{$parameterTypeName}::fromNative", $result);

                } elseif (class_exists($parameterTypeName)) {
                    $result = $this->controllerHelper->findEntity($parameterTypeName, (string)$result);
                }
            }

            return $result;

        } catch (Exception $exception) {
            throw new Exception(sprintf(
                "While resolving parameter-argument '%s' on call to '%s': %s",
                $parameterReflection->getName(),
                $parameterReflection->getDeclaringFunction()->getName(),
                $exception->getMessage()
            ), 0, $exception);
        }
    }

    /**
     * @return mixed
     */
    private function getDefaultValueFromParameterReflectionSafely(ReflectionParameter $parameterReflection)
    {
        if ($parameterReflection->getDeclaringFunction()->isInternal()) {
            return null;
        }

        try {
            return $parameterReflection->getDefaultValue();

        } catch (ReflectionException $exception) {
            /** @var string $parameterName */
            $parameterName = $parameterReflection->getName();

            /** @var ReflectionFunctionAbstract $routineReflection */
            $routineReflection = $parameterReflection->getDeclaringFunction();

            throw new InvalidArgumentException(sprintf(
                "Missing argument '%s' for the call to '%s'!",
                $parameterName,
                $routineReflection->getName()
            ), 0, $exception);
        }
    }

    private function getTypeNameFromReflectionParameter(ReflectionParameter $parameterReflection): ?string
    {
        /** @var string|null $parameterTypeName */
        $parameterTypeName = null;

        if ($parameterReflection->hasType()) {
            /** @var ReflectionType|ReflectionNamedType|null $parameterType */
            $parameterType = $parameterReflection->getType();

            if ($parameterType instanceof ReflectionNamedType) {
                $parameterTypeName = $parameterType->getName();

            } elseif ($parameterType instanceof ReflectionType) {
                $parameterTypeName = $parameterType->__toString();
            }
        }

        return $parameterTypeName;
    }

}
