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

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentCompiler;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var string
     */
    private $methodName;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var string|null
     */
    private $denyAccessAttribute;

    /**
     * @var string
     */
    private $successMessage;

    /**
     * @var string|null
     */
    private $redirectRoute;

    /**
     * @var array
     */
    private $redirectRouteParameters;

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
            'redirect-route' => null,
            'redirect-route-parameters' => [],
        ], $options);

        Assert::classExists($options['entity-class']);
        Assert::methodExists($options['entity-class'], $options['method']);
        Assert::isArray($options['arguments'], 'Method-arguments must be array!');

        $this->controllerHelper = $controllerHelper;
        $this->argumentCompiler = $argumentCompiler;
        $this->entityClass = $options['entity-class'];
        $this->methodName = $options['method'];
        $this->arguments = $options['arguments'];
        $this->denyAccessAttribute = $options['deny-access-attribute'];
        $this->successMessage = $options['success-message'];
        $this->redirectRoute = $options['redirect-route'];
        $this->redirectRouteParameters = $options['redirect-route-parameters'];
    }

    public function invokeEntityMethod(Request $request, string $entityId): Response
    {
        /** @var object|null $entity */
        $entity = $this->controllerHelper->findEntity($this->entityClass, $entityId);

        if (is_null($entity)) {
            throw new InvalidArgumentException(sprintf(
                "Entity with id '%s' not found!",
                $entityId
            ));
        }

        if (!empty($this->denyAccessAttribute)) {
            $this->controllerHelper->denyAccessUnlessGranted($this->denyAccessAttribute, $entity);
        }

        $reflectionObject = new ReflectionObject($entity);

        /** @var ReflectionMethod $reflectionMethod */
        $reflectionMethod = $reflectionObject->getMethod($this->methodName);

        /** @var array $callArguments */
        $callArguments = $this->argumentCompiler->buildCallArguments(
            $reflectionMethod,
            $this->arguments,
            $request
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

        /** @var Response $response */
        $response = null;

        if (is_null($this->redirectRoute)) {
            $response = new Response($this->successMessage);

        } else {
            $response = $this->controllerHelper->redirectToRoute(
                $this->redirectRoute,
                $this->argumentCompiler->buildArguments($this->redirectRouteParameters, $request, [
                    'result' => $result
                ])
            );
        }

        return $response;
    }

}
