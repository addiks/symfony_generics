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
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionException;
use Symfony\Component\HttpFoundation\RequestStack;
use Addiks\SymfonyGenerics\Arguments\ArgumentContextInterface;
use InvalidArgumentException;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;

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

    public function buildArguments(
        array $argumentsConfiguration,
        array $additionalData = array()
    ): array {
        /** @var array $argumentValues */
        $argumentValues = array();

        $this->argumentContext->clear();
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
        ReflectionFunctionAbstract $routineReflection,
        array $argumentsConfiguration,
        array $predefinedArguments = array(),
        array $additionalData = array()
    ): array {
        /** @var array<int, mixed> $callArguments */
        $callArguments = array();

        $this->argumentContext->clear();
        foreach ($additionalData as $key => $value) {
            $this->argumentContext->set($key, $value);
        }

        foreach ($routineReflection->getParameters() as $index => $parameterReflection) {
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
     * @param array|string|bool|null $argumentConfiguration
     *
     * @return mixed
     */
    private function resolveArgumentConfiguration($argumentConfiguration)
    {
        Assert::oneOf(
            gettype($argumentConfiguration),
            ['string', 'array', 'NULL', 'boolean'],
            "Arguments must be defined as string, array, bool or null!"
        );

        /** @var Argument|null $argument */
        $argument = null;

        if (is_bool($argumentConfiguration) || is_null($argumentConfiguration)) {
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
        /** @var string $parameterName */
        $parameterName = $parameterReflection->getName();

        /** @var string|null $parameterTypeName */
        $parameterTypeName = $this->getTypeNameFromReflectionParameter($parameterReflection);

        if (isset($argumentsConfiguration[$parameterName])) {
            /** @var mixed $value */
            $value = $this->resolveArgumentConfiguration($argumentsConfiguration[$parameterName]);

            if (!empty($parameterTypeName)) {
                if (class_exists($parameterTypeName) && is_scalar($value)) {
                    $value = $this->controllerHelper->findEntity($parameterTypeName, (string)$value);
                }
            }

            return $value;

        } elseif (isset($argumentsConfiguration[$index])) {
            return $this->resolveArgumentConfiguration($argumentsConfiguration[$index]);

        } elseif ($parameterTypeName === Request::class) {
            return $this->requestStack->getCurrentRequest();

        } else {
            return $this->getDefaultValueFromParameterReflectionSafely($parameterReflection);
        }

        return null;
    }

    /**
     * @return mixed
     */
    private function getDefaultValueFromParameterReflectionSafely(ReflectionParameter $parameterReflection)
    {
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
            ));
        }
    }

    private function getTypeNameFromReflectionParameter(ReflectionParameter $parameterReflection): ?string
    {
        /** @var string|null $parameterTypeName */
        $parameterTypeName = null;

        if ($parameterReflection->hasType()) {
            /** @var ReflectionType|null $parameterType */
            $parameterType = $parameterReflection->getType();

            if ($parameterType instanceof ReflectionType) {
                $parameterTypeName = $parameterType->__toString();
            }
        }

        return $parameterTypeName;
    }

}
