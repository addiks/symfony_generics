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
use Psr\Container\ContainerInterface;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use ErrorException;
use ReflectionObject;
use ReflectionMethod;

final class GenericServiceInvokeController
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
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var string|null
     */
    private $authorizationAttribute;

    /**
     * @var string|null
     */
    private $successRedirectRoute;

    /**
     * @var array
     */
    private $successRedirectArguments;

    /**
     * @var integer
     */
    private $successRedirectStatus;

    /**
     * @var bool
     */
    private $sendReturnValueInResponse = false;

    /**
     * @var string
     */
    private $successFlashMessage;

    /**
     * @var array<string, string>
     */
    private $successResponseHeader = array();

    public function __construct(
        ControllerHelperInterface $controllerHelper,
        ArgumentCompilerInterface $argumentCompiler,
        ContainerInterface $container,
        array $options
    ) {
        Assert::null($this->controllerHelper);
        Assert::keyExists($options, 'service');
        Assert::keyExists($options, 'method');

        /** @var int $defaultRedirectStatus */
        $defaultRedirectStatus = 303;

        $options = array_merge([
            'arguments' => [],
            'authorization-attributes' => null,
            'success-redirect' => null,
            'success-redirect-arguments' => [],
            'success-redirect-status' => $defaultRedirectStatus,
            'success-flash-message' => "",
            'send-return-value-in-response' => false,
            'success-response-header' => [],
        ], $options);

        $this->controllerHelper = $controllerHelper;
        $this->argumentCompiler = $argumentCompiler;
        $this->container = $container;
        $this->serviceId = $options['service'];
        $this->method = $options['method'];
        $this->arguments = $options['arguments'];
        $this->authorizationAttribute = $options['authorization-attributes'];
        $this->successRedirectRoute = $options['success-redirect'];
        $this->successRedirectArguments = $options['success-redirect-arguments'];
        $this->successRedirectStatus = $options['success-redirect-status'];
        $this->successFlashMessage = $options['success-flash-message'];
        $this->sendReturnValueInResponse = $options['send-return-value-in-response'];
        $this->successResponseHeader = $options['success-response-header'];
    }

    public function __invoke(): Response
    {
        /** @var Request $request */
        $request = $this->controllerHelper->getCurrentRequest();

        Assert::isInstanceOf($request, Request::class, "Cannot use controller outside of request-scope!");

        return $this->callService($request);
    }

    public function callService(Request $request): Response
    {
        if (!is_null($this->authorizationAttribute)) {
            $this->controllerHelper->denyAccessUnlessGranted($this->authorizationAttribute, $request);
        }

        /** @var object|null $service */
        $service = $this->container->get($this->serviceId);

        if (is_null($service)) {
            throw new ErrorException(sprintf(
                "Could not find service '%s'!",
                $this->serviceId
            ));
        }

        $reflectionObject = new ReflectionObject($service);

        /** @var ReflectionMethod $reflectionMethod */
        $reflectionMethod = $reflectionObject->getMethod($this->method);

        /** @var array $arguments */
        $arguments = $this->argumentCompiler->buildCallArguments(
            $reflectionMethod,
            $this->arguments
        );

        /** @var mixed $returnValue */
        $returnValue = $reflectionMethod->invokeArgs($service, $arguments);

        $this->controllerHelper->flushORM();

        if (!empty($this->successFlashMessage)) {
            $this->controllerHelper->addFlashMessage($this->successFlashMessage, "success");
        }

        if (!empty($this->successRedirectRoute)) {
            /** @var array $redirectArguments */
            $redirectArguments = $this->argumentCompiler->buildArguments($this->successRedirectArguments);

            return $this->controllerHelper->redirectToRoute(
                $this->successRedirectRoute,
                $redirectArguments,
                $this->successRedirectStatus
            );
        }

        /** @var Response $response */
        $response = null;

        if ($this->sendReturnValueInResponse) {
            $response = new Response((string)$returnValue);

        } else {
            $response = new Response("Service call completed");
        }

        $response->headers->add($this->successResponseHeader);

        return $response;
    }

}
