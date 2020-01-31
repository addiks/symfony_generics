<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Services\ArgumentCompiler;
use Addiks\SymfonyGenerics\Arguments\ArgumentContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentFactory;
use Symfony\Component\HttpFoundation\Request;
use Addiks\SymfonyGenerics\Arguments\Argument;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionType;
use ReflectionException;
use Closure;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;

final class ArgumentCompilerTest extends TestCase
{

    /**
     * @var ArgumentCompiler
     */
    private $compiler;

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

    public function setUp()
    {
        $this->argumentFactory = $this->createMock(ArgumentFactory::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->argumentContext = $this->createMock(ArgumentContextInterface::class);
        $this->controllerHelper = $this->createMock(ControllerHelperInterface::class);

        $this->compiler = new ArgumentCompiler(
            $this->argumentFactory,
            $this->requestStack,
            $this->argumentContext,
            $this->controllerHelper
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldBuildArguments
     */
    public function shouldBuildArguments($expectedArguments, array $argumentsConfiguration)
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->argumentContext->expects($this->once())->method('clear');
        $this->argumentContext->expects($this->once())->method('set')->with(
            $this->equalTo('foo'),
            $this->equalTo('bar')
        );

        /** @var Argument $argument */
        $argument = $this->createMock(Argument::class);
        $argument->method('resolve')->willReturn('dolor');

        $this->argumentFactory->method('understandsString')->willReturn(true);
        $this->argumentFactory->method('createArgumentFromString')->willReturn($argument);
        $this->argumentFactory->method('understandsArray')->willReturn(true);
        $this->argumentFactory->method('createArgumentFromArray')->willReturn($argument);

        /** @var mixed $actualArguments */
        $actualArguments = $this->compiler->buildArguments(
            $argumentsConfiguration,
            ['foo' => 'bar']
        );

        $this->assertEquals($expectedArguments, $actualArguments);
    }

    public function dataProviderForShouldBuildArguments(): array
    {
        return array(
            [[], []],
            [['lorem' => 'dolor'], ['lorem' => '$ipsum']],
            [['lorem' => 'dolor'], ['lorem' => ['$ipsum']]],
        );
    }

    /**
     * @test
     */
    public function shouldExpectArgumentFactoryToUnderstandString()
    {
        $this->expectExceptionMessage("Argument 'dolor' could not be understood!");

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        /** @var Argument $argument */
        $argument = $this->createMock(Argument::class);

        $this->argumentFactory->method('understandsString')->willReturn(false);

        $this->compiler->buildArguments(['lorem' => 'dolor'], []);
    }

    /**
     * @test
     */
    public function shouldExpectArgumentFactoryToUnderstandArray()
    {
        $this->expectExceptionMessage("Argument 'array(0=>'dolor',)' could not be understood!");

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        /** @var Argument $argument */
        $argument = $this->createMock(Argument::class);

        $this->argumentFactory->method('understandsString')->willReturn(false);

        $this->compiler->buildArguments(['lorem' => ['dolor']], []);
    }

    /**
     * @test
     */
    public function shouldExpectArgumentToBeArrayOrString()
    {
        $this->expectExceptionMessage("Arguments must be defined as string, array, bool, object or null!");

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        /** @var Argument $argument */
        $argument = $this->createMock(Argument::class);

        $this->argumentFactory->method('understandsString')->willReturn(false);

        $this->compiler->buildArguments(['lorem' => 3.1415], []);
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldBuildCallArguments
     */
    public function shouldBuildCallArguments($expectedArguments, array $parameters, array $argumentsConfiguration)
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->argumentContext->expects($this->once())->method('clear');
        $this->argumentContext->expects($this->once())->method('set')->with(
            $this->equalTo('foo'),
            $this->equalTo('bar')
        );

        /** @var ReflectionFunctionAbstract $routineReflection */
        $routineReflection = $this->createMock(ReflectionFunctionAbstract::class);
        $routineReflection->method('getParameters')->willReturn($parameters);

        /** @var Argument $argument */
        $argument = $this->createMock(Argument::class);
        $argument->method('resolve')->willReturn('dolor');

        $this->argumentFactory->method('understandsString')->willReturn(true);
        $this->argumentFactory->method('createArgumentFromString')->willReturn($argument);
        $this->argumentFactory->method('understandsArray')->willReturn(true);
        $this->argumentFactory->method('createArgumentFromArray')->willReturn($argument);

        /** @var mixed $actualArguments */
        $actualArguments = $this->compiler->buildCallArguments(
            $routineReflection,
            $argumentsConfiguration,
            [1 => 'def'],
            ['foo' => 'bar']
        );

        $this->assertEquals($expectedArguments, $actualArguments);
    }

    public function dataProviderForShouldBuildCallArguments()
    {
        /** @var TestCase $testCase */
        $testCase = $this;

        /** @var Closure $buildParameter */
        $buildParameter = function (string $name, bool $hasType = false, $parameterTypeName = null) use ($testCase) {

            /** @var ReflectionType $parameterType */
            $parameterType = $testCase->createMock(ReflectionType::class);
            $parameterType->method('__toString')->willReturn($parameterTypeName);

            /** @var ReflectionParameter $parameter */
            $parameter = $testCase->createMock(ReflectionParameter::class);
            $parameter->method('hasType')->willReturn($hasType);
            $parameter->method('getType')->willReturn(is_string($parameterTypeName) ?$parameterType :null);
            $parameter->method('getName')->willReturn($name);

            return $parameter;
        };

        return array(
            [[], [], []],
            [
                ['dolor', 'def'],
                [$buildParameter("blah"), $buildParameter("blah")],
                [0 => '$ipsum']
            ],
            [
                ['dolor'],
                [$buildParameter("blah")],
                ['blah' => 'asd']
            ],
            [
                [$this->createMock(Request::class), 'def', "dolor"],
                [$buildParameter("blah", true, Request::class), $buildParameter("blah"), $buildParameter("blah")],
                [2 => '$ipsum']
            ],
        );
    }

    /**
     * @test
     */
    public function shouldCatchReflectionException()
    {
        $this->expectExceptionMessage("Missing argument 'blah' for the call to 'doSomething'!");

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var ReflectionFunctionAbstract $routineReflection */
        $routineReflection = $this->createMock(ReflectionFunctionAbstract::class);

        /** @var ReflectionParameter $parameterWithoutDefaultValue */
        $parameterWithoutDefaultValue = $this->createMock(ReflectionParameter::class);
        $parameterWithoutDefaultValue->method('hasType')->willReturn(false);
        $parameterWithoutDefaultValue->method('getName')->willReturn("blah");
        $parameterWithoutDefaultValue->method('getDeclaringFunction')->willReturn($routineReflection);
        $parameterWithoutDefaultValue->method('getDefaultValue')->will($this->returnCallback(
            function () {
                throw new ReflectionException("We don't have a default value, bro!");
            }
        ));

        $routineReflection->method('getParameters')->willReturn([$parameterWithoutDefaultValue]);
        $routineReflection->method('getName')->willReturn("doSomething");

        $this->compiler->buildCallArguments(
            $routineReflection,
            [],
            [],
            []
        );
    }

}
