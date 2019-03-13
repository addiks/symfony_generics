<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Controllers\DefaultControllerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Twig_Environment;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use stdClass;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class DefaultControllerHelperTest extends TestCase
{

    /**
     * @var DefaultControllerHelper
     */
    private $controllerHelper;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorization;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function setUp()
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->twig = $this->createMock(Twig_Environment::class);
        $this->authorization = $this->createMock(AuthorizationCheckerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->controllerHelper = new DefaultControllerHelper(
            $this->entityManager,
            $this->twig,
            $this->authorization,
            $this->urlGenerator,
            $this->session,
            $this->logger,
            $this->eventDispatcher
        );
    }

    /**
     * @test
     */
    public function shouldPersistEntity()
    {
        /** @var stdClass $entity */
        $entity = $this->createMock(stdClass::class);

        $this->entityManager->expects($this->once())->method('persist')->with($this->identicalTo($entity));

        $this->controllerHelper->persistEntity($entity);
    }

    /**
     * @test
     */
    public function shouldRemoveEntity()
    {
        /** @var stdClass $entity */
        $entity = $this->createMock(stdClass::class);

        $this->entityManager->expects($this->once())->method('remove')->with($this->identicalTo($entity));

        $this->controllerHelper->removeEntity($entity);
    }

    /**
     * @test
     */
    public function shouldFlushORM()
    {
        $this->entityManager->expects($this->once())->method('flush');
        $this->controllerHelper->flushORM();
    }

    /**
     * @test
     */
    public function shouldHandleException()
    {
        /** @var Exception $exception */
        $exception = $this->createMock('Exception');
        $exception->method('__toString')->willReturn("some-string");

        $this->logger->expects($this->once())->method('log')->with(
            $this->equalTo('error'),
            $this->identicalTo('some-string')
        );

        $this->controllerHelper->handleException($exception);
    }

    /**
     * @test
     */
    public function shouldAddFlashMessage()
    {
        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())->method('add')->with(
            $this->equalTo('some-type'),
            $this->equalTo("Lorem ipsum dolor sit amet!")
        );

        $this->session->method('getFlashBag')->willReturn($flashBag);

        $this->controllerHelper->addFlashMessage("Lorem ipsum dolor sit amet!", "some-type");
    }

    /**
     * @test
     */
    public function shouldUseDefaultCodeWhenRedirectingToRoute()
    {
        $this->urlGenerator->expects($this->once())->method('generate')->with(
            $this->equalTo('some_route'),
            $this->equalTo(['foo' => 'bar']),
            $this->equalTo(UrlGeneratorInterface::ABSOLUTE_URL)
        )->willReturn("*this-is-some-url*");

        $expectedResponse = new RedirectResponse("*this-is-some-url*", 301);

        /** @var RedirectResponse $actualResponse */
        $actualResponse = $this->controllerHelper->redirectToRoute("some_route", ['foo' => 'bar']);

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    /**
     * @test
     */
    public function shouldRedirectToRoute()
    {
        $this->urlGenerator->expects($this->once())->method('generate')->with(
            $this->equalTo('some_route'),
            $this->equalTo(['foo' => 'bar']),
            $this->equalTo(UrlGeneratorInterface::ABSOLUTE_URL)
        )->willReturn("*this-is-some-url*");

        $expectedResponse = new RedirectResponse("*this-is-some-url*", 302);

        /** @var RedirectResponse $actualResponse */
        $actualResponse = $this->controllerHelper->redirectToRoute("some_route", ['foo' => 'bar'], 302);

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    /**
     * @test
     */
    public function shouldDenyAccessUnlessGranted()
    {
        $this->expectException(AccessDeniedException::class);

        /** @var stdClass $subject */
        $subject = $this->createMock(stdClass::class);

        $this->authorization->expects($this->once())->method('isGranted')->with(
            $this->equalTo("foo"),
            $this->equalTo($subject)
        )->willReturn(false);

        try {
            $this->controllerHelper->denyAccessUnlessGranted("foo", $subject);

        } catch (AccessDeniedException $exception) {
            $this->assertEquals(["foo"], $exception->getAttributes());
            $this->assertSame($subject, $exception->getSubject());

            throw $exception;
        }
    }

}
