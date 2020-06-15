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

namespace Addiks\SymfonyGenerics\Arguments;

use Addiks\SymfonyGenerics\Arguments\Argument;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class RequestFileArgument implements Argument
{

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $property;

    public function __construct(
        RequestStack $requestStack,
        string $key,
        string $property
    ) {
        $this->requestStack = $requestStack;
        $this->key = $key;
        $this->property = $property;
    }

    public function resolve()
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        Assert::isInstanceOf(
            $request,
            Request::class,
            "Cannot resolve request-argument without active request!"
        );

        /** @var FileBag $files */
        $files = $request->files;

        /** @var UploadedFile $file */
        $file = $files->get($this->key);

        Assert::isInstanceOf($file, UploadedFile::class, sprintf(
            "Missing uploaded file '%s'!",
            $this->key
        ));

        return [
            'object' => $file,
            'originalname' => $file->getClientOriginalName(),
            'filename' => $file->getFilename(),
            'content' => file_get_contents($file->getPathname()),
            'mimetype' => $file->getMimeType(),
        ][$this->property];
    }

}
