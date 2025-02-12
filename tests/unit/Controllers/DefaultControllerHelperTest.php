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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Symfony\Contracts\EventDispatcher\Event;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

final class DefaultControllerHelperTest extends TestCase
{

    private DefaultControllerHelper $controllerHelper;

    private EntityManagerInterface $entityManager;

    private Environment $twig;

    private TokenStorageInterface $tokenStorage;

    private AccessDecisionManagerInterface $accessDecisionManager;

    private UrlGeneratorInterface $urlGenerator;

    private Session $session;

    private LoggerInterface $logger;

    private EventDispatcherInterface $eventDispatcher;

    private RequestStack $requestStack;

    public function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        
        $this->requestStack->method('getSession')->willReturn($this->session);

        $this->controllerHelper = new DefaultControllerHelper(
            $this->entityManager,
            $this->twig,
            $this->tokenStorage,
            $this->accessDecisionManager,
            $this->urlGenerator,
            $this->logger,
            $this->eventDispatcher,
            $this->requestStack
        );
    }

    /**
     * @test
     */
    public function shouldRenderTemplate()
    {
        $this->twig->expects($this->once())->method('render')->with(
            $this->equalTo('some-template'),
            $this->equalTo(['foo', 'bar'])
        )->willReturn('some-response');

        /** @var Response $actualResult */
        $actualResult = $this->controllerHelper->renderTemplate('some-template', ['foo', 'bar']);

        $this->assertEquals(new Response('some-response'), $actualResult);
    }

    /**
     * @test
     */
    public function shouldFindEntity()
    {
        $this->entityManager->expects($this->once())->method('find')->with(
            $this->equalTo('some-class'),
            $this->equalTo('foo')
        )->willReturn('some-entity');

        /** @var mixed $actualResult */
        $actualResult = $this->controllerHelper->findEntity('some-class', 'foo');

        $this->assertEquals('some-entity', $actualResult);
    }

    /**
     * @test
     */
    public function shouldFindEntities()
    {
        /** @var ObjectRepository $repository */
        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('findBy')->with(
            $this->equalTo(['foo' => 'bar'])
        )->willReturn(['some-entity', 'some-other-entity']);

        $this->entityManager->expects($this->once())->method('getRepository')->with(
            $this->equalTo('some-class')
        )->willReturn($repository);

        /** @var mixed $actualResult */
        $actualResult = $this->controllerHelper->findEntities('some-class', ['foo' => 'bar']);

        $this->assertEquals(['some-entity', 'some-other-entity'], $actualResult);
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
    public function shouldHaveRequestStack()
    {
        $this->assertSame($this->requestStack, $this->controllerHelper->getRequestStack());
    }

    /**
     * @test
     */
    public function shouldHaveCurrentRequest()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->requestStack->method("getCurrentRequest")->willReturn($request);

        $this->assertSame($request, $this->controllerHelper->getCurrentRequest());
    }

    /**
     * @test
     */
    public function shouldDispatchEvent()
    {
        /** @var Event $event */
        $event = $this->createMock(Event::class);

        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(
            $this->equalTo($event),
            $this->equalTo('foo')
        )->willReturn($event);

        $this->assertSame($event, $this->controllerHelper->dispatchEvent("foo", $event));
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
    public function shouldDenyAccessWhenNotGranted()
    {
        $this->expectException(AccessDeniedException::class);

        /** @var stdClass $subject */
        $subject = $this->createMock(stdClass::class);

        $this->accessDecisionManager->expects($this->once())->method('decide')->willReturn(false);

        try {
            $this->controllerHelper->denyAccessUnlessGranted("foo", $subject);

        } catch (AccessDeniedException $exception) {
            $this->assertEquals(["foo"], $exception->getAttributes());
            $this->assertSame($subject, $exception->getSubject());

            throw $exception;
        }
    }

    /**
     * @test
     */
    public function shouldNotDenyAccessWhenGranted()
    {
        /** @var stdClass $subject */
        $subject = $this->createMock(stdClass::class);

        $this->accessDecisionManager->expects($this->once())->method('decide')->willReturn(true);

        $this->controllerHelper->denyAccessUnlessGranted("foo", $subject);
    }

}
