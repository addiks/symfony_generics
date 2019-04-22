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
use Addiks\SymfonyGenerics\Controllers\API\GenericEntityFetchController;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Addiks\SymfonyGenerics\Tests\Unit\Controllers\SampleEntity;
use Symfony\Component\HttpFoundation\Response;
use ErrorException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

final class GenericEntityFetchControllerTest extends TestCase
{

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    public function setUp()
    {
        $this->controllerHelper = $this->createMock(ControllerHelperInterface::class);
    }

    /**
     * @test
     */
    public function shouldFetchEntity()
    {
        $entity = new SampleEntity();

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo("some-id")
        )->willReturn($entity);

        /** @var string $expectedResponseContent */
        $expectedResponseContent = '{"id":"some_id","fooCalled":false,"constructArgument":"foo"}';

        $controller = new GenericEntityFetchController($this->controllerHelper, [
            'entity-class' => SampleEntity::class
        ]);

        /** @var Response $actualResponse */
        $actualResponse = $controller->fetchEntity("some-id");

        $this->assertEquals($expectedResponseContent, $actualResponse->getContent());
    }

    /**
     * @test
     */
    public function shouldApplyDataTemplate()
    {
        $entity = new SampleEntity();

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo("some-id")
        )->willReturn($entity);

        /** @var string $expectedResponseContent */
        $expectedResponseContent = '{"lorem":false,"ipsum":"foo"}';

        $controller = new GenericEntityFetchController($this->controllerHelper, [
            'entity-class' => SampleEntity::class,
            'data-template' => [
                'lorem' => 'fooCalled',
                'ipsum' => 'constructArgument'
            ]
        ]);

        /** @var Response $actualResponse */
        $actualResponse = $controller->fetchEntity("some-id");

        $this->assertEquals($expectedResponseContent, $actualResponse->getContent());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnInvalidDataTemplateEntry()
    {
        $this->expectException(ErrorException::class);

        $entity = new SampleEntity();

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo("some-id")
        )->willReturn($entity);

        $controller = new GenericEntityFetchController($this->controllerHelper, [
            'entity-class' => SampleEntity::class,
            'data-template' => [
                'lorem' => 'fooCalled',
                'ipsum' => 'constructArgument',
                'dolor' => false,
            ]
        ]);

        $controller->fetchEntity("some-id");
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnInvalidNormalizerResult()
    {
        $this->expectException(ErrorException::class);

        $entity = new SampleEntity();

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo("some-id")
        )->willReturn($entity);

        /** @var NormalizerInterface $normalizer */
        $normalizer = $this->createMock(NormalizerInterface::class);
        $normalizer->expects($this->once())->method('normalize')->with(
            $this->identicalTo($entity)
        )->willReturn('*non-array*');

        $controller = new GenericEntityFetchController($this->controllerHelper, [
            'entity-class' => SampleEntity::class,
            'normalizer' => $normalizer,
        ]);

        $controller->fetchEntity("some-id");
    }

    /**
     * @test
     */
    public function shouldCheckIfAccessIsGranted()
    {
        $this->expectException(AccessDeniedException::class);

        /** @var mixed $entity */
        $entity = new SampleEntity();

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo("some-id")
        )->willReturn($entity);

        $this->controllerHelper->expects($this->once())->method('denyAccessUnlessGranted')->with(
            $this->equalTo('some-attribute'),
            $this->identicalTo($entity)
        )->will($this->returnCallback(
            function () {
                throw new AccessDeniedException('Lorem ipsum!');
            }
        ));

        $controller = new GenericEntityFetchController($this->controllerHelper, [
            'entity-class' => SampleEntity::class,
            'authorization-attribute' => 'some-attribute',
        ]);

        $controller->fetchEntity("some-id");
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenEntityNotFound()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityFetchController($this->controllerHelper, [
            'entity-class' => SampleEntity::class,
        ]);

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo("some-id")
        )->willReturn(null);

        $controller->fetchEntity("some-id");
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenEntityClassDoesNotExist()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityFetchController($this->controllerHelper, [
            'entity-class' => "NotExisting",
        ]);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenMissingEntityClass()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityFetchController($this->controllerHelper, [
        ]);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnInvalidNormalizer()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityFetchController($this->controllerHelper, [
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

        $controller = new GenericEntityFetchController($this->controllerHelper, [
            'entity-class' => SampleEntity::class,
            'encoder' => false
        ]);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenCallingConstructorAgain()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityFetchController($this->controllerHelper, [
            'entity-class' => SampleEntity::class,
        ]);

        $controller->__construct($this->controllerHelper, [
            'entity-class' => SampleEntity::class,
        ]);
    }

    /**
     * @test
     */
    public function shouldBeCallableByInvokingController()
    {
        $entity = new SampleEntity();

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo("some-id")
        )->willReturn($entity);

        /** @var string $expectedResponseContent */
        $expectedResponseContent = '{"id":"some_id","fooCalled":false,"constructArgument":"foo"}';

        $controller = new GenericEntityFetchController($this->controllerHelper, [
            'entity-class' => SampleEntity::class,
        ]);

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->method("get")->willReturn('some-id');

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

        $controller = new GenericEntityFetchController($this->controllerHelper, [
            'entity-class' => SampleEntity::class,
        ]);

        $this->controllerHelper->method('getCurrentRequest')->willReturn(null);

        $controller();
    }

}
