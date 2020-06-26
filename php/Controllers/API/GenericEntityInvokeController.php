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
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Webmozart\Assert\Assert;
use InvalidArgumentException;
use ReflectionObject;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Addiks\SymfonyGenerics\Events\EntityInteractionEvent;

final class GenericEntityInvokeController
{

    /** @var ControllerHelperInterface */
    private $controllerHelper;

    /** @var ArgumentCompilerInterface */
    private $argumentCompiler;

    /** @var string */
    private $entityClass;

    /** @var string */
    private $entityIdKey;

    /** @var string */
    private $entityIdSource;

    /** @var string */
    private $methodName;

    /** @var array */
    private $arguments;

    /** @var string|null */
    private $denyAccessAttribute;

    /** @var string */
    private $successMessage;

    /** @var string */
    private $successFlashMessage;

    /** @var string|null */
    private $redirectRoute;

    /** @var array */
    private $redirectRouteParameters;

    /** @var int */
    private $redirectStatus;

    /** @var bool */
    private $sendReturnValueInResponse = false;

    public function __construct(
        ControllerHelperInterface $controllerHelper,
        ArgumentCompilerInterface $argumentCompiler,
        array $options
    ) {
        Assert::null($this->controllerHelper);
        Assert::keyExists($options, 'entity-class');
        Assert::keyExists($options, 'method');

        $options = array_merge([
            'arguments' => [],
            'deny-access-attribute' => null,
            'success-message' => "Entity method invoked!",
            'success-flash-message' => "",
            'redirect-route' => null,
            'redirect-route-parameters' => [],
            'redirect-status' => 301,
            'entity-id-key' => 'entityId',
            'entity-id-source' => 'request',
            'send-return-value-in-response' => false,
        ], $options);

        Assert::classExists($options['entity-class']);
        Assert::methodExists($options['entity-class'], $options['method']);
        Assert::isArray($options['arguments'], 'Method-arguments must be array!');
        Assert::oneOf($options['entity-id-source'], ['request', 'argument']);

        $this->controllerHelper = $controllerHelper;
        $this->argumentCompiler = $argumentCompiler;
        $this->entityClass = $options['entity-class'];
        $this->entityIdKey = $options['entity-id-key'];
        $this->entityIdSource = $options['entity-id-source'];
        $this->methodName = $options['method'];
        $this->arguments = $options['arguments'];
        $this->denyAccessAttribute = $options['deny-access-attribute'];
        $this->successMessage = $options['success-message'];
        $this->successFlashMessage = $options['success-flash-message'];
        $this->redirectRoute = $options['redirect-route'];
        $this->redirectStatus = $options['redirect-status'];
        $this->redirectRouteParameters = $options['redirect-route-parameters'];
        $this->sendReturnValueInResponse = $options['send-return-value-in-response'];
    }

    public function __invoke(): Response
    {
        /** @var Request $request */
        $request = $this->controllerHelper->getCurrentRequest();

        Assert::isInstanceOf($request, Request::class, "Cannot use controller outside of request-scope!");

        /** @var Response $response */
        $response = null;

        if ($this->entityIdSource === 'request') {
            /** @var string $entityId */
            $entityId = $request->get($this->entityIdKey);

            $response = $this->invokeEntityMethod($entityId);

        } elseif ($this->entityIdSource === 'argument') {
            $response = $this->invokeEntityMethod('');
        }

        return $response;

    }

    public function invokeEntityMethod(string $entityId): Response
    {
        /** @var object|null $entity */
        $entity = null;

        if ($this->entityIdSource === 'request') {
            $entity = $this->controllerHelper->findEntity($this->entityClass, $entityId);
            Assert::object($entity, sprintf("Entity with id '%s' not found!", $entityId));

        } elseif ($this->entityIdSource === 'argument') {
            $entity = $this->argumentCompiler->buildArgument($this->entityIdKey);
            Assert::object($entity, "Entity not found!");
        }

        Assert::isInstanceOf($entity, $this->entityClass, sprintf(
            "Found entity is not of expected class '%s', but of class '%s' instead!",
            $this->entityClass,
            get_class($entity)
        ));

        if (!empty($this->denyAccessAttribute)) {
            $this->controllerHelper->denyAccessUnlessGranted($this->denyAccessAttribute, $entity);
        }

        $reflectionObject = new ReflectionObject($entity);

        /** @var ReflectionMethod $reflectionMethod */
        $reflectionMethod = $reflectionObject->getMethod($this->methodName);

        /** @var array $callArguments */
        $callArguments = $this->argumentCompiler->buildCallArguments(
            $reflectionMethod,
            $this->arguments
        );

        $this->controllerHelper->dispatchEvent("symfony_generics.entity_interaction", new EntityInteractionEvent(
            $this->entityClass,
            $entityId,
            $entity,
            $this->methodName,
            $callArguments
        ));

        /** @var mixed $result */
        $result = $reflectionMethod->invokeArgs($entity, $callArguments);

        $this->controllerHelper->flushORM();

        if (!empty($this->successFlashMessage)) {
            $this->controllerHelper->addFlashMessage($this->successFlashMessage, "success");
        }

        /** @var Response $response */
        $response = null;

        if ($this->sendReturnValueInResponse) {
            return new Response((string)$result);

        } elseif (is_null($this->redirectRoute)) {
            $response = new Response($this->successMessage);

        } else {
            $response = $this->controllerHelper->redirectToRoute(
                $this->redirectRoute,
                $this->argumentCompiler->buildArguments($this->redirectRouteParameters, [
                    'result' => $result
                ]),
                $this->redirectStatus
            );
        }

        return $response;
    }

}
