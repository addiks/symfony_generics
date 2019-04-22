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
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Webmozart\Assert\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class GenericTemplateRenderController
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
    private $templatePath;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var string|null
     */
    private $authorizationAttribute;

    public function __construct(
        ControllerHelperInterface $controllerHelper,
        ArgumentCompilerInterface $argumentCompiler,
        array $options
    ) {
        Assert::null($this->controllerHelper);
        Assert::keyExists($options, 'template');

        $options = array_merge([
            'arguments' => [],
            'authorization-attribute' => null,
        ], $options);

        $this->controllerHelper = $controllerHelper;
        $this->argumentCompiler = $argumentCompiler;
        $this->templatePath = $options['template'];
        $this->arguments = $options['arguments'];
        $this->authorizationAttribute = $options['authorization-attribute'];
    }

    public function __invoke(): Response
    {
        /** @var Request $request */
        $request = $this->controllerHelper->getCurrentRequest();

        Assert::isInstanceOf($request, Request::class, "Cannot use controller outside of request-scope!");

        return $this->renderTemplate($request);
    }

    public function renderTemplate(Request $request): Response
    {
        if (!is_null($this->authorizationAttribute)) {
            $this->controllerHelper->denyAccessUnlessGranted($this->authorizationAttribute, $request);
        }

        /** @var array $arguments */
        $arguments = $this->argumentCompiler->buildArguments($this->arguments, $request);

        return $this->controllerHelper->renderTemplate($this->templatePath, $arguments);
    }

}
