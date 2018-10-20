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
use ErrorException;

final class GenericExceptionResponseController
{

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    /**
     * @var object
     */
    private $innerController;

    /**
     * @var string
     */
    private $innerControllerMethod;

    /**
     * @var string|null
     */
    private $successResponse;

    /**
     * @var int
     */
    private $successResponseCode;

    /**
     * @var string|null
     */
    private $successFlashMessage;

    /**
     * @var array<string, array<string, mixed>>
     */
    private $exceptionResponses = array();

    /**
     * @param object $innerController
     */
    private function __construct(
        ControllerHelperInterface $controllerHelper,
        $innerController,
        string $innerControllerMethod,
        array $exceptionResponses = array(),
        string $successResponse = null,
        int $successResponseCode = 200,
        string $successFlashMessage = null
    ) {
        Assert::null($this->controllerHelper);
        Assert::true(is_object($innerController));
        Assert::true(method_exists($innerController, $innerControllerMethod));

        $this->controllerHelper = $controllerHelper;
        $this->innerController = $innerController;
        $this->innerControllerMethod = $innerControllerMethod;
        $this->successResponse = $successResponse;
        $this->successResponseCode = $successResponseCode;
        $this->successFlashMessage = $successFlashMessage;

        foreach ($exceptionResponses as $exceptionClass => $responseData) {
            /** @var array<string, mixed> $responseData */

            Assert::true(
                is_subclass_of($exceptionClass, Exception::class) ||
                is_subclass_of($exceptionClass, Throwable::class)
            );

            $responseData = array_merge([
                'message' => '', # empty => exception message used
                'code' => '500',
                'flash-type' => '', # empty => no message triggered
                'flash-message' => '', # empty => exception message used
            ], $responseData);

            $this->exceptionResponses[(string)$exceptionClass] = [
                'message' => (string)$responseData['message'],
                'code' => (int)$responseData['code'],
                'flash-type' => (string)$responseData['flash-type'],
                'flash-message' => (string)$responseData['flash-message']
            ];
        }
    }

    public static function create(
        ControllerHelperInterface $controllerHelper,
        array $options
    ): GenericExceptionResponseController {
        Assert::keyExists($options, 'inner-controller');
        Assert::keyExists($options, 'inner-controller-method');

        /** @var int $defaultResponseCode */
        $defaultResponseCode = 200;

        /** @var array<string, mixed> $defaults */
        $defaults = array(
            'exception-responses' => [],
            'success-response' => null,
            'success-response-code' => $defaultResponseCode,
            'success-flash-message' => null,
        );

        $options = array_merge($defaults, $options);

        return new GenericExceptionResponseController(
            $controllerHelper,
            $options['inner-controller'],
            $options['inner-controller-method'],
            $options['exception-responses'],
            $options['success-response'],
            $options['success-response-code'],
            $options['success-flash-message']
        );
    }

    public function executeInnerControllerSafely(): Response
    {
        /** @var Response|null $response */
        $response = null;

        /** @var Response|null $innerResponse */
        $innerResponse = null;

        try {
            /** @var array<int, mixed> $arguments */
            $arguments = array();# TODO

            $innerResponse = call_user_func([$this->innerController, $this->innerControllerMethod], $arguments);

            Assert::isInstanceOf($innerResponse, Response::class);

            if (!is_null($this->successFlashMessage)) {
                $this->controllerHelper->addFlashMessage($this->successFlashMessage, "success");
            }

            if (!is_null($this->successResponse)) {
                $response = new Response($this->successResponse, $this->successResponseCode);

            } elseif ($innerResponse instanceof Response) {
                $response = $innerResponse;

            } else {
                throw new ErrorException("Controller did not return an Response object!");
            }

        } catch (Throwable $exception) {
            $this->controllerHelper->handleException($exception);

            /** @var int $responseCode */
            $responseCode = 500;

            /** @var int $responseMessage */
            $responseMessage = "";

            /** @var string $flashType */
            $flashType = "";

            /** @var string $flashMessage */
            $flashMessage = "";

            foreach ($this->exceptionResponses as $exceptionClass => $responseData) {
                if (is_a($exception, $exceptionClass)) {
                    $responseMessage = $responseData['message'];
                    $responseCode = $responseData['code'];
                    $flashType = $responseData['flash-type'];
                    $flashMessage = $responseData['flash-message'];
                    break;
                }
            }

            if (!empty($flashType)) {
                if (strpos($flashMessage, '%s') !== false) {
                    $flashMessage = sprintf($flashMessage, $exception->getMessage());

                } elseif (empty($flashMessage)) {
                    $flashMessage = $exception->getMessage();
                }

                $this->controllerHelper->addFlashMessage($flashMessage, $flashType);
            }

            if (empty($responseMessage)) {
                $responseMessage = $exception->getMessage();
            }

            $response = new Response($responseMessage, $responseCode);
        }

        return $response;
    }

}
