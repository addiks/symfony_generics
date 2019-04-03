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
use Addiks\SymfonyGenerics\Tests\Unit\Services\SampleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->argumentCompiler = new ArgumentCompiler($this->container, $this->entityManager);
    }

    /**
     * @test
     */
    public function shouldBuildArguments()
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
        $actualRouteArguments = $this->argumentCompiler->buildArguments([
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
        $parameterType->method('__toString')->willReturn(SampleService::class);

        /** @var ReflectionParameter $parameterFazReflection */
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
            "faz" => [
                'service-id' => 'some.service',
                'method' => 'someCall',
                'arguments' => [
                    'foo' => '$lorem'
                ]
            ]
        );

        $someService = new SampleService();

        $this->container->method('get')->will($this->returnValueMap([
            ['some.service', $someService],
        ]));

        $this->entityManager->expects($this->once())->method('find')->with(
            $this->equalTo(SampleService::class),
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
        $this->assertEquals('ipsum', $someService->foo);
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

        $this->argumentCompiler->buildArguments([
            'foo' => '$reqFoo',
            'bar' => '@some.service::doesNotExist',
        ], $request);
    }

    /**
     * @test
     */
    public function shouldRejectNonExistingAdditionalDataKey()
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->argumentCompiler->buildArguments([
            'foo' => '%keyFoo',
        ], $request);
    }

    /**
     * @test
     */
    public function shouldGetAdditionalDataKey()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var array $actualResult */
        $actualResult = $this->argumentCompiler->buildArguments([
            'foo' => '%keyFoo',
        ], $request, [
            'keyFoo' => 'bar'
        ]);

        $this->assertEquals(['foo' => 'bar'], $actualResult);
    }

    /**
     * @test
     */
    public function shouldRejectMissingMethodOnAdditionalDataKey()
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->argumentCompiler->buildArguments([
            'foo' => '%keyFoo.doSomethingImpossible',
        ], $request, [
            'keyFoo' => $this->createMock(stdClass::class)
        ]);
    }

    /**
     * @test
     */
    public function shouldRejectMissingUploadedFile()
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->files = $this->createMock(ParameterBagInterface::class);

        $this->argumentCompiler->buildArguments([
            'foo' => '$files.something_missing.content',
        ], $request);
    }

    /**
     * @test
     */
    public function shouldGetRequestPayload()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getContent')->with(
            $this->equalTo(false)
        );

        $this->argumentCompiler->buildArguments([
            'foo' => '$',
        ], $request);
    }

    /**
     * @test
     */
    public function shouldResolveLiteralString()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var array $actualResult */
        $actualResult = $this->argumentCompiler->buildArguments([
            'foo' => '\'bar\'',
        ], $request);

        $this->assertEquals(['foo' => 'bar'], $actualResult);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenFetchingUnknownService()
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var ReflectionMethod $methodReflection */
        $methodReflection = $this->createMock(ReflectionMethod::class);

        /** @var ReflectionParameter $parameterFazReflection */
        $parameterFazReflection = $this->createMock(ReflectionParameter::class);
        $parameterFazReflection->method('getName')->willReturn("faz");

        $methodReflection->method("getParameters")->willReturn([
            $parameterFazReflection
        ]);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var array<string, mixed> $argumentsConfiguration */
        $argumentsConfiguration = array(
            "faz" => [
                'service-id' => 'some.non-existing.service',
            ]
        );

        /** @var array<int, mixed> $actualCallArguments */
        $actualCallArguments = $this->argumentCompiler->buildCallArguments(
            $methodReflection,
            $argumentsConfiguration,
            $request
        );
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenCallArgumentIsMissing()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing argument 'foo' for the call to 'someCall'!");

        /** @var ReflectionMethod $methodReflection */
        $methodReflection = $this->createMock(ReflectionMethod::class);

        /** @var ReflectionParameter $parameterFazReflection */
        $parameterFazReflection = $this->createMock(ReflectionParameter::class);
        $parameterFazReflection->method('getName')->willReturn("faz");

        $methodReflection->method("getParameters")->willReturn([
            $parameterFazReflection
        ]);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $someService = new SampleService();

        $this->container->method('get')->will($this->returnValueMap([
            ['some.service', $someService],
        ]));

        /** @var array<string, mixed> $argumentsConfiguration */
        $argumentsConfiguration = array(
            "faz" => [
                'service-id' => 'some.service',
                'method' => 'someCall',
            ]
        );

        /** @var array<int, mixed> $actualCallArguments */
        $actualCallArguments = $this->argumentCompiler->buildCallArguments(
            $methodReflection,
            $argumentsConfiguration,
            $request
        );
    }

}
