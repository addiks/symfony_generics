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
use Twig\Environment;
use Psr\Log\LoggerInterface;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;
use stdClass;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;

/**
 * The default implementation of the controller-helper.
 */
final class DefaultControllerHelper implements ControllerHelperInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Environment $twig,
        private TokenStorageInterface $tokenStorage,
        private AccessDecisionManagerInterface $accessDecisionManager,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher,
        private RequestStack $requestStack
    ) {
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
        try {
            $this->requestStack->getSession()->getFlashBag()->add($type, $message);
            
        } catch (SessionNotFoundException $exception ) {
            # May happen when called in CLI, just dont do anything
        }
    }

    public function redirectToRoute(string $route, array $parameters = array(), int $status = 301): Response
    {
        /** @var string $url */
        $url = $this->urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);

        return new RedirectResponse($url, $status);
    }

    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

    public function getCurrentRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    public function denyAccessUnlessGranted(string|array $attributes, $subject): void
    {
        if (is_string($attributes)) {
            $attributes = [$attributes];
        }

        $token = $this->tokenStorage->getToken();

        if (!$token || !$token->getUser()) {
            $token = new NullToken();
        }

        if (!$this->accessDecisionManager->decide($token, $attributes, $subject)) {
            $exception = new AccessDeniedException('Access Denied.');
            $exception->setSubject($subject);
            $exception->setAttributes($attribute);

            throw $exception;
        }
    }

    public function dispatchEvent(string $eventName, object $event = null): object
    {
        return $this->eventDispatcher->dispatch($event ?? new stdClass(), $eventName);
    }

}
