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
use Addiks\SymfonyGenerics\Services\NewArgumentCompiler;
use Addiks\SymfonyGenerics\Arguments\ArgumentContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentFactory;
use Symfony\Component\HttpFoundation\Request;
use Addiks\SymfonyGenerics\Arguments\Argument;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use ReflectionType;
use ReflectionException;

final class NewArgumentCompilerTest extends TestCase
{

    /**
     * @var NewArgumentCompiler
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

    public function setUp()
    {
        $this->argumentFactory = $this->createMock(ArgumentFactory::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->argumentContext = $this->createMock(ArgumentContextInterface::class);

        $this->compiler = new NewArgumentCompiler(
            $this->argumentFactory,
            $this->requestStack,
            $this->argumentContext
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
            $request,
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

        $this->compiler->buildArguments(['lorem' => 'dolor'], $request, []);
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

        $this->compiler->buildArguments(['lorem' => ['dolor']], $request, []);
    }

    /**
     * @test
     */
    public function shouldExpectArgumentToBeArrayOrString()
    {
        $this->expectExceptionMessage("Arguments must be defined as string or array!");

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        /** @var Argument $argument */
        $argument = $this->createMock(Argument::class);

        $this->argumentFactory->method('understandsString')->willReturn(false);

        $this->compiler->buildArguments(['lorem' => 3.1415], $request, []);
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
            $request,
            ['abc' => 'def'],
            ['foo' => 'bar']
        );

        $this->assertEquals($expectedArguments, $actualArguments);
    }

    public function dataProviderForShouldBuildCallArguments()
    {
        /** @var ReflectionParameter $blahParameter */
        $blahParameter = $this->createMock(ReflectionParameter::class);
        $blahParameter->method('hasType')->willReturn(false);
        $blahParameter->method('getName')->willReturn("blah");

        /** @var ReflectionType $requestParameterType */
        $requestParameterType = $this->createMock(ReflectionType::class);
        $requestParameterType->method('__toString')->willReturn(Request::class);

        /** @var ReflectionParameter $requestParameter */
        $requestParameter = $this->createMock(ReflectionParameter::class);
        $requestParameter->method('hasType')->willReturn(true);
        $requestParameter->method('getType')->willReturn($requestParameterType);
        $requestParameter->method('getName')->willReturn("blah");

        return array(
            [[], [], []],
            [
                ['blah' => 'dolor', 'abc' => 'def'],
                ['blah' => $blahParameter, 'abc' => $blahParameter],
                ['blah' => '$ipsum']
            ],
            [
                ['blah' => $this->createMock(Request::class), 'abc' => 'def', 'blubb' => "dolor"],
                ['blah' => $requestParameter, 'abc' => $blahParameter, 'blubb' => $blahParameter,],
                ['blubb' => '$ipsum']
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

        /** @var ReflectionParameter $parameterWithoutDefaultValue */
        $parameterWithoutDefaultValue = $this->createMock(ReflectionParameter::class);
        $parameterWithoutDefaultValue->method('hasType')->willReturn(false);
        $parameterWithoutDefaultValue->method('getName')->willReturn("blah");
        $parameterWithoutDefaultValue->method('getDefaultValue')->will($this->returnCallback(
            function () {
                throw new ReflectionException("We don't have a default value, bro!");
            }
        ));

        /** @var ReflectionFunctionAbstract $routineReflection */
        $routineReflection = $this->createMock(ReflectionFunctionAbstract::class);
        $routineReflection->method('getParameters')->willReturn([$parameterWithoutDefaultValue]);
        $routineReflection->method('getName')->willReturn("doSomething");

        $this->compiler->buildCallArguments(
            $routineReflection,
            [],
            $request,
            [],
            []
        );
    }

}
