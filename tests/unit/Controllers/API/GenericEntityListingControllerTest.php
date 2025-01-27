<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Tests\Unit\Controllers\API;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Controllers\API\GenericEntityListingController;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use InvalidArgumentException;
use Addiks\SymfonyGenerics\Tests\Unit\Controllers\SampleEntity;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use ErrorException;

final class GenericEntityListingControllerTest extends TestCase
{

    /**
     * @var GenericEntityListingController
     */
    private $controller;

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentCompiler;

    public function setUp(): void
    {
        $this->controllerHelper = $this->createMock(ControllerHelperInterface::class);
        $this->argumentCompiler = $this->createMock(ArgumentCompilerInterface::class);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenEntityClassIsMissing()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityListingController($this->controllerHelper, $this->argumentCompiler, [
        ]);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenEntityClassIsInvalid()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityListingController($this->controllerHelper, $this->argumentCompiler, [
            'entity-class' => "DoesNotExist"
        ]);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnInvalidNormalizer()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityListingController($this->controllerHelper, $this->argumentCompiler, [
            'entity-class' => SampleEntity::class,
            'normalizer' => false
        ]);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnInvalidEncoder()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityListingController($this->controllerHelper, $this->argumentCompiler, [
            'entity-class' => SampleEntity::class,
            'encoder' => false
        ]);
    }

    /**
     * @test
     */
    public function shouldListEntities()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var string $expectedResponseContent */
        $expectedResponseContent = '[{"lorem":false,"ipsum":"foo"},{"lorem":false,"ipsum":"foo"}]';

        /** @var SampleEntity $entityA */
        $entityA = $this->createMock(SampleEntity::class);

        /** @var SampleEntity $entityB */
        $entityB = $this->createMock(SampleEntity::class);

        $this->controllerHelper->expects($this->once())->method('findEntities')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo(['blah' => 'blubb'])
        )->willReturn([$entityA, $entityB]);

        $this->argumentCompiler->expects($this->once())->method('buildArguments')->with(
            $this->equalTo(['foo' => 'bar'])
        )->willReturn(['blah' => 'blubb']);

        $controller = new GenericEntityListingController($this->controllerHelper, $this->argumentCompiler, [
            'entity-class' => SampleEntity::class,
            'data-template' => [
                'lorem' => 'fooCalled',
                'ipsum' => 'constructArgument'
            ],
            'criteria' => [
                'foo' => 'bar'
            ]
        ]);

        /** @var Response $actualResponse */
        $actualResponse = $controller->listEntities($request);

        $this->assertEquals($expectedResponseContent, $actualResponse->getContent());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfNormalizerReturnsNonArray()
    {
        $this->expectException(ErrorException::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var SampleEntity $entity */
        $entity = $this->createMock(SampleEntity::class);

        $this->controllerHelper->expects($this->once())->method('findEntities')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo([])
        )->willReturn([$entity]);

        /** @var NormalizerInterface $normalizer */
        $normalizer = $this->createMock(NormalizerInterface::class);
        $normalizer->expects($this->once())->method('normalize')->with(
            $this->identicalTo($entity)
        )->willReturn("*non-array*");

        $controller = new GenericEntityListingController($this->controllerHelper, $this->argumentCompiler, [
            'entity-class' => SampleEntity::class,
            'normalizer' => $normalizer,
        ]);

        $controller->listEntities($request);
    }

    /**
     * @test
     */
    public function shouldCheckIfAccessIsGranted()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->expectException(AccessDeniedException::class);

        $this->controllerHelper->expects($this->once())->method('denyAccessUnlessGranted')->with(
            $this->equalTo('some-attribute'),
            $this->identicalTo($request)
        )->will($this->returnCallback(
            function () {
                throw new AccessDeniedException('Lorem ipsum!');
            }
        ));

        $controller = new GenericEntityListingController($this->controllerHelper, $this->argumentCompiler, [
            'entity-class' => SampleEntity::class,
            'authorization-attribute' => 'some-attribute',
        ]);

        $controller->listEntities($request);
    }

    /**
     * @test
     */
    public function shouldBeCallableByInvokingController()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var string $expectedResponseContent */
        $expectedResponseContent = '[{"lorem":false,"ipsum":"foo"},{"lorem":false,"ipsum":"foo"}]';

        /** @var SampleEntity $entityA */
        $entityA = $this->createMock(SampleEntity::class);

        /** @var SampleEntity $entityB */
        $entityB = $this->createMock(SampleEntity::class);

        $this->controllerHelper->expects($this->once())->method('findEntities')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo(['blah' => 'blubb'])
        )->willReturn([$entityA, $entityB]);

        $this->argumentCompiler->expects($this->once())->method('buildArguments')->with(
            $this->equalTo(['foo' => 'bar'])
        )->willReturn(['blah' => 'blubb']);

        $controller = new GenericEntityListingController($this->controllerHelper, $this->argumentCompiler, [
            'entity-class' => SampleEntity::class,
            'data-template' => [
                'lorem' => 'fooCalled',
                'ipsum' => 'constructArgument'
            ],
            'criteria' => [
                'foo' => 'bar'
            ]
        ]);

        $this->controllerHelper->method('getCurrentRequest')->willReturn($request);

        /** @var Response $actualResponse */
        $actualResponse = $controller();

        $this->assertEquals($expectedResponseContent, $actualResponse->getContent());
    }

    /**
     * @test
     */
    public function shouldRejectCallWithoutRequest()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityListingController($this->controllerHelper, $this->argumentCompiler, [
            'entity-class' => SampleEntity::class,
        ]);

        $this->controllerHelper->method('getCurrentRequest')->willReturn(null);

        $controller();
    }

}
