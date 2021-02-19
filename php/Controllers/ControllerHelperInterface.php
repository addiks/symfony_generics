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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

interface ControllerHelperInterface
{

    /**
     * @param class-string $entityClass
     *
     * @return object|null
     */
    public function findEntity(string $entityClass, string $id);

    /**
     * @param class-string $entityClass
     *
     * @return array<object>
     */
    public function findEntities(string $entityClass, array $criteria): array;

    /**
     * @param object $entity
     */
    public function persistEntity($entity): void;

    /**
     * @param object $entity
     */
    public function removeEntity($entity): void;

    public function flushORM(): void;

    public function handleException(Throwable $exception): void;

    public function getRequestStack(): RequestStack;

    public function getCurrentRequest(): ?Request;

    public function addFlashMessage(string $message, string $type = "default"): void;

    public function redirectToRoute(string $route, array $parameters = array(), int $status = 301): Response;

    public function renderTemplate(string $templatePath, array $arguments = array()): Response;

    /**
     * @param object $subject
     */
    public function denyAccessUnlessGranted(string $attribute, $subject): void;

    public function dispatchEvent(string $eventName, object $event = null): object;

}
