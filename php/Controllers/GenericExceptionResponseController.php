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

use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Webmozart\Assert\Assert;
use Exception;
use Throwable;
use Symfony\Component\HttpFoundation\Response;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Symfony\Component\HttpFoundation\Request;
use ReflectionMethod;

final class GenericExceptionResponseController
{

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentBuilder;

    /**
     * @var object
     */
    private $innerController;

    /**
     * @var string
     */
    private $innerControllerMethod;

    /**
     * @var array
     */
    private $innerControllerArgumentsConfiguration;

    /**
     * @var string|null
     */
    private $successResponse;

    /**
     * @var int
     */
    private $successResponseCode;

    /**
     * @var string
     */
    private $successFlashMessage;

    /**
     * @var array<string, array<string, mixed>>
     */
    private $exceptionResponses = array();

    public function __construct(
        ControllerHelperInterface $controllerHelper,
        ArgumentCompilerInterface $argumentBuilder,
        array $options
    ) {
        /** @var int $defaultResponseCode */
        $defaultResponseCode = 200;

        /** @var array<string, mixed> $defaults */
        $defaults = array(
            'inner-controller-method' => '__invoke',
            'arguments' => [],
            'exception-responses' => [],
            'success-response' => null,
            'success-response-code' => $defaultResponseCode,
            'success-flash-message' => "",
        );

        /** @var mixed $options */
        $options = array_merge($defaults, $options);

        Assert::null($this->controllerHelper);
        Assert::true(is_object($options['inner-controller']));
        Assert::isArray($options['arguments']);

        $this->controllerHelper = $controllerHelper;
        $this->argumentBuilder = $argumentBuilder;
        $this->innerController = $options['inner-controller'];
        $this->innerControllerMethod = $options['inner-controller-method'];
        $this->innerControllerArgumentsConfiguration = $options['arguments'];
        $this->successResponse = $options['success-response'];
        $this->successResponseCode = $options['success-response-code'];
        $this->successFlashMessage = $options['success-flash-message'];

        foreach ($options['exception-responses'] as $exceptionClass => $responseData) {
            /** @var array<string, mixed> $responseData */

            /** @var string $responseCode */
            $responseCode = '500';

            if (isset($responseData['redirect-route'])) {
                $responseCode = '303';
            }

            $responseData = array_merge([
                'exception-class' => is_int($exceptionClass) ?null :$exceptionClass,
                'message' => '', # empty => exception message used
                'code' => $responseCode,
                'flash-type' => '', # empty => no message triggered
                'flash-message' => '%s', # empty => exception message used
                'redirect-route' => null,
                'redirect-route-parameters' => [],
                'filter' => '',
            ], $responseData);

            Assert::true(
                is_a($responseData['exception-class'], Throwable::class, true) ||
                empty($responseData['exception-class'])
            );

            $this->exceptionResponses[] = $responseData;
        }
    }

    public function __invoke(): Response
    {
        /** @var Request $request */
        $request = $this->controllerHelper->getCurrentRequest();

        Assert::isInstanceOf($request, Request::class, "Cannot use controller outside of request-scope!");

        return $this->executeInnerControllerSafely($request);
    }

    public function executeInnerControllerSafely(Request $request): Response
    {
        /** @var Response|null $response */
        $response = null;

        try {
            $response = $this->executeInnerControllerUnsafely($request);

        } catch (Throwable $exception) {
            $this->controllerHelper->handleException($exception);

            foreach ($this->exceptionResponses as $responseData) {
                /** @var array<string, mixed> $responseData */

                if ($this->shouldApplyResponseDataForException($exception, $responseData)) {
                    $response = $this->applyResponseDataForException($request, $exception, $responseData);
                    break;
                }
            }

            if (is_null($response)) {
                throw $exception;
            }
        }

        return $response;
    }

    private function executeInnerControllerUnsafely(Request $request): Response
    {
        $methodReflection = new ReflectionMethod($this->innerController, $this->innerControllerMethod);

        /** @var array<int, mixed> $arguments */
        $arguments = $this->argumentBuilder->buildCallArguments(
            $methodReflection,
            $this->innerControllerArgumentsConfiguration,
            $request
        );

        /** @var Response $innerResponse */
        $innerResponse = call_user_func_array([$this->innerController, $this->innerControllerMethod], $arguments);

        Assert::isInstanceOf($innerResponse, Response::class, "Controller did not return an Response object!");

        if (!empty($this->successFlashMessage)) {
            $this->controllerHelper->addFlashMessage($this->successFlashMessage, "success");
        }

        if (!is_null($this->successResponse)) {
            $response = new Response($this->successResponse, $this->successResponseCode);

        } else {
            $response = $innerResponse;
        }

        return $response;
    }

    private function shouldApplyResponseDataForException(Throwable $exception, array $responseData): bool
    {
        if (!empty($responseData['exception-class']) && !is_a($exception, $responseData['exception-class'])) {
            return false;
        }

        if (!empty($responseData['filter'])) {
            if (!preg_match("/" . $responseData['filter'] . "/is", $exception->getMessage())) {
                return false;
            }
        }

        return true;
    }

    private function createRedirectResponseForException(Request $request, array $responseData): Response
    {
        /** @var array $redirectRouteParameters */
        $redirectRouteParameters = array_merge(
            $request->attributes->get('_route_params'),
            $this->argumentBuilder->buildArguments(
                $responseData['redirect-route-parameters'],
                $request
            )
        );

        return $this->controllerHelper->redirectToRoute(
            $responseData['redirect-route'],
            $redirectRouteParameters,
            $responseData['code']
        );
    }

    private function applyResponseDataForException(Request $request, Throwable $exception, array $responseData): Response
    {
        $this->handleFlashMessageForException($exception, $responseData);

        if (!empty($responseData['redirect-route'])) {
            $response = $this->createRedirectResponseForException($request, $responseData);

        } else {
            $response = $this->createMessageResponseForException($exception, $responseData);
        }

        return $response;
    }

    private function createMessageResponseForException(Throwable $exception, array $responseData): Response
    {
        /** @var string $responseMessage */
        $responseMessage = $responseData['message'];

        if (empty($responseMessage)) {
            $responseMessage = $exception->getMessage();
        }

        return new Response($responseMessage, $responseData['code']);
    }

    private function handleFlashMessageForException(Throwable $exception, array $responseData): void
    {
        if (!empty($responseData['flash-type'])) {
            /** @var string $flashMessage */
            $flashMessage = sprintf($responseData['flash-message'], $exception->getMessage());

            $this->controllerHelper->addFlashMessage($flashMessage, $responseData['flash-type']);
        }
    }

}
