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

namespace Addiks\SymfonyGenerics\Controllers\API;

use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Webmozart\Assert\Assert;
use Symfony\Component\HttpFoundation\Response;
use InvalidArgumentException;
use Addiks\SymfonyGenerics\Events\EntityInteractionEvent;
use Symfony\Component\HttpFoundation\Request;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;

final class GenericEntityRemoveController
{

    private ControllerHelperInterface $controllerHelper;

    private ArgumentCompilerInterface $argumentBuilder;

    /** @var class-string */
    private string $entityClass;

    private string $entityIdKey;

    private ?string $authorizationAttribute;

    private string $successResponse;

    private ?string $successRedirectRoute;

    private array $successRedirectArguments;
    
    private int $successRedirectStatus;

    public function __construct(
        ControllerHelperInterface $controllerHelper,
        ArgumentCompilerInterface $argumentBuilder,
        array $options
    ) {
        Assert::keyExists($options, 'entity-class');

        /** @var int $defaultRedirectStatus */
        $defaultRedirectStatus = 303;

        $options = array_merge([
            'authorization-attribute' => null,
            'entity-id-key' => 'entityId',
            'success-response' => "Entity removed!",
            'success-redirect' => null,
            'success-redirect-arguments' => [],
            'success-redirect-status' => $defaultRedirectStatus,
        ], $options);

        $this->controllerHelper = $controllerHelper;
        $this->argumentBuilder = $argumentBuilder;
        $this->entityClass = $options['entity-class'];
        $this->entityIdKey = $options['entity-id-key'];
        $this->authorizationAttribute = $options['authorization-attribute'];
        $this->successResponse = $options['success-response'];
        $this->successRedirectRoute = $options['success-redirect'];
        $this->successRedirectArguments = $options['success-redirect-arguments'];
        $this->successRedirectStatus = $options['success-redirect-status'];
    }

    public function __invoke(): Response
    {
        /** @var Request $request */
        $request = $this->controllerHelper->getCurrentRequest();

        Assert::isInstanceOf($request, Request::class, "Cannot use controller outside of request-scope!");

        /** 
         * @var string $entityId  
         * @psalm-suppress InternalMethod 
         */
        $entityId = $request->get($this->entityIdKey);

        return $this->removeEntity($entityId);
    }

    public function removeEntity(string $entityId): Response
    {
        /** @var object|null $entity */
        $entity = $this->controllerHelper->findEntity($this->entityClass, $entityId);

        if (is_null($entity)) {
            throw new InvalidArgumentException(sprintf(
                "Entity with id %s not found!",
                $entityId
            ));
        }

        if (!empty($this->authorizationAttribute)) {
            $this->controllerHelper->denyAccessUnlessGranted($this->authorizationAttribute, $entity);
        }

        $this->controllerHelper->dispatchEvent("symfony_generics.entity_interaction", new EntityInteractionEvent(
            $this->entityClass,
            $entityId,
            $entity,
            "__destruct"
        ));

        $this->controllerHelper->removeEntity($entity);
        $this->controllerHelper->flushORM();

        if (!empty($this->successRedirectRoute)) {
            /** @var array $redirectArguments */
            $redirectArguments = $this->argumentBuilder->buildArguments(
                $this->successRedirectArguments
            );
            $this->controllerHelper->addFlashMessage($this->successResponse, 'success');

            return $this->controllerHelper->redirectToRoute(
                $this->successRedirectRoute,
                $redirectArguments,
                $this->successRedirectStatus
            );
        }

        return new Response($this->successResponse);
    }

}
