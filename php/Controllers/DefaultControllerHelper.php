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

namespace Addiks\SymfonyGenerics\Controllers;

use Throwable;
use Twig_Environment;
use Psr\Log\LoggerInterface;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The default implementation of the controller-helper.
 */
final class DefaultControllerHelper implements ControllerHelperInterface
{

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

    public function __construct(
        EntityManagerInterface $entityManager,
        Twig_Environment $twig,
        AuthorizationCheckerInterface $authorization,
        UrlGeneratorInterface $urlGenerator,
        Session $session,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->twig = $twig;
        $this->authorization = $authorization;
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function renderTemplate(string $templatePath, array $arguments = array()): Response
    {
        return new Response($this->twig->render($templatePath, $arguments));
    }

    public function findEntity(string $entityClass, string $id)
    {
        return $this->entityManager->find($entityClass, $id);
    }

    public function findEntities(string $entityClass, array $criteria): array
    {
        /** @var ObjectRepository $repository */
        $repository = $this->entityManager->getRepository($entityClass);

        return $repository->findBy($criteria);
    }

    public function persistEntity($entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function removeEntity($entity): void
    {
        $this->entityManager->remove($entity);
    }

    public function flushORM(): void
    {
        $this->entityManager->flush();
    }

    public function handleException(Throwable $exception): void
    {
        $this->logger->log("error", (string)$exception);
    }

    public function addFlashMessage(string $message, string $type = "default"): void
    {
        $this->session->getFlashBag()->add($type, $message);
    }

    public function redirectToRoute(string $route, array $parameters = array(), int $status = 301): Response
    {
        /** @var string $url */
        $url = $this->urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);

        return new RedirectResponse($url, $status);
    }

    public function denyAccessUnlessGranted(string $attribute, $subject): void
    {
        if (!$this->authorization->isGranted($attribute, $subject)) {
            $exception = new AccessDeniedException('Access Denied.');
            $exception->setSubject($subject);
            $exception->setAttributes($attribute);

            throw $exception;
        }
    }

    public function dispatchEvent(string $eventName, Event $event = null): Event
    {
        return $this->eventDispatcher->dispatch($eventName, $event);
    }

}
