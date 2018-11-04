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
use Psr\Container\ContainerInterface;
use Addiks\SymfonyGenerics\Services\EntityRepositoryInterface;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use ReflectionParameter;
use ReflectionType;
use stdClass;
use InvalidArgumentException;
use Serializable;

final class ArgumentCompilerTest extends TestCase
{

    /**
     * @var ArgumentCompiler
     */
    private $argumentCompiler;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityRepositoryInterface
     */
    private $entityRepository;

    public function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);

        $this->argumentCompiler = new ArgumentCompiler($this->container, $this->entityRepository);
    }

    /**
     * @test
     */
    public function shouldBuildRouteArguments()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->method('get')->will($this->returnValueMap([
            ['reqFoo', null, 'ipsum'],
        ]));

        $someObject = new stdClass();

        /** @var Serializable $someService */
        $someService = $this->createMock(Serializable::class);
        $someService->method('serialize')->willReturn($someObject);

        $this->container->method('get')->will($this->returnValueMap([
            ['some.service', $someService],
        ]));

        /** @var array<string, mixed> $expectedRouteArguments */
        $expectedRouteArguments = array(
            'foo' => 'ipsum',
            'bar' => $someObject
        );

        /** @var array<string, mixed> $actualRouteArguments */
        $actualRouteArguments = $this->argumentCompiler->buildRouteArguments([
            'foo' => '$reqFoo',
            'bar' => '@some.service::serialize',
        ], $request);

        $this->assertEquals($expectedRouteArguments, $actualRouteArguments);
    }

    /**
     * @test
     */
    public function shouldBuildCallArguments()
    {
        /** @var ReflectionMethod $methodReflection */
        $methodReflection = $this->createMock(ReflectionMethod::class);

        /** @var ReflectionParameter $parameterFooReflection */
        $parameterFooReflection = $this->createMock(ReflectionParameter::class);
        $parameterFooReflection->method('getName')->willReturn("foo");

        /** @var ReflectionParameter $parameterBarReflection */
        $parameterBarReflection = $this->createMock(ReflectionParameter::class);
        $parameterBarReflection->method('getName')->willReturn("bar");

        /** @var ReflectionParameter $parameterBazReflection */
        $parameterBazReflection = $this->createMock(ReflectionParameter::class);
        $parameterBazReflection->method('getName')->willReturn("baz");

        /** @var ReflectionType $parameterType */
        $parameterType = $this->createMock(ReflectionType::class);
        $parameterType->method('__toString')->willReturn(stdClass::class);

        /** @var ReflectionParameter $parameterBazReflection */
        $parameterFazReflection = $this->createMock(ReflectionParameter::class);
        $parameterFazReflection->method('getName')->willReturn("faz");
        $parameterFazReflection->method('hasType')->willReturn(true);
        $parameterFazReflection->method('getType')->willReturn($parameterType);

        $methodReflection->method("getParameters")->willReturn([
            $parameterFooReflection,
            $parameterBarReflection,
            $parameterBazReflection,
            $parameterFazReflection
        ]);

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->method('get')->will($this->returnValueMap([
            ['lorem', null, 'ipsum'],
            ['bar', null, 'blah'],
        ]));

        /** @var array<string, mixed> $argumentsConfiguration */
        $argumentsConfiguration = array(
            "foo" => '$lorem',
            "baz" => '@some.service',
            "faz" => '$lorem'
        );

        /** @var object $someService */
        $someService = new stdClass();

        $this->container->method('get')->will($this->returnValueMap([
            ['some.service', $someService],
        ]));

        $this->entityRepository->expects($this->once())->method('findEntity')->with(
            $this->equalTo(stdClass::class),
            $this->equalTo('ipsum')
        )->willReturn($someService);

        /** @var array<int, mixed> $expectedCallArguments */
        $expectedCallArguments = array(
            'ipsum',
            'blah',
            $someService,
            $someService
        );

        /** @var array<int, mixed> $actualCallArguments */
        $actualCallArguments = $this->argumentCompiler->buildCallArguments(
            $methodReflection,
            $argumentsConfiguration,
            $request
        );

        $this->assertEquals($expectedCallArguments, $actualCallArguments);
    }

    /**
     * @test
     */
    public function shouldRejectInvalidArgumentConfiguration()
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var array $argumentsConfiguration */
        $argumentsConfiguration = array(
            'foo' => false
        );

        /** @var ReflectionParameter $parameterFooReflection */
        $parameterFooReflection = $this->createMock(ReflectionParameter::class);
        $parameterFooReflection->method('getName')->willReturn("foo");

        /** @var ReflectionMethod $methodReflection */
        $methodReflection = $this->createMock(ReflectionMethod::class);

        $methodReflection->method("getParameters")->willReturn([
            $parameterFooReflection,
        ]);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->argumentCompiler->buildCallArguments(
            $methodReflection,
            $argumentsConfiguration,
            $request
        );

    }

    /**
     * @test
     */
    public function shouldRejectNonExistingFactoryMethod()
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var Serializable $someService */
        $someService = $this->createMock(Serializable::class);

        $this->container->method('get')->will($this->returnValueMap([
            ['some.service', $someService],
        ]));

        $this->argumentCompiler->buildRouteArguments([
            'foo' => '$reqFoo',
            'bar' => '@some.service::doesNotExist',
        ], $request);
    }

}
