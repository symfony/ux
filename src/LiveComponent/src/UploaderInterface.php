<?php
declare(strict_types=1);

namespace Symfony\UX\LiveComponent;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 *
 * @experimental
 */
interface UploaderInterface
{
    /**
     * Create full URL for an existing file preview.
     *
     * @param  string  $path
     * @return string
     *
     * @throws NotFoundHttpException
     */
    public function createExistingFilePreviewUrl(string $path): string;

    /**
     * Create a URL to preview temporary file.
     *
     * If the file is in publicly accessible place it might return full URL for a preview,
     * otherwise a URL to an endpoint consuming `previewTemporaryFile` should be returned.
     *
     * @param  File  $file
     * @return string
     *
     * @throws NotFoundHttpException
     */
    public function createTemporaryFilePreviewUrl(File $file): string;

    /**
     * Create binary response for a file in temporary place.
     *
     * Temporary files might not be directly accessible, so preview method needs to return full binary response.
     *
     * @param  File  $file
     * @return BinaryFileResponse
     *
     * @throws FileException
     * @throws NotFoundHttpException
     */
    public function previewTemporaryFile(File $file): BinaryFileResponse;

    /**
     * Save temporary file to persistent storage space.
     *
     * When $constraints are defined it should call `validateFile` before save.
     * When validation fails it should throw *before* saving file.
     * It is advised to validate files when moving to temporary storage.
     * This parameter should only be used when files are stored directly to the persistent storage.
     *
     * The format of `$manipulators` is TBD - it might be based on Glide (current/next),
     * directly intervention image library (current/next - 3.0 looks promising).
     * Leaving for now as a parameter with format to be determined.
     * Maybe another interface to unify different possible solutions and allow easier extension?
     *
     * When `$manipulators` are provided - all the transformations
     * should be applied *before* saving to temporary place.
     *
     * @param  File                   $temporaryFile
     * @param  string                 $pathPattern  Path pattern where the file should be stored.
     *                                              Can contain placeholders for easier usage (e.g. [name], [ulid] or else - TBD)
     * @param  Constraint|array|null  $constraints
     * @param  array|null             $manipulators
     * @return File
     */
    public function saveTemporaryFile(
        File $temporaryFile,
        string $pathPattern,
        Constraint|array|null  $constraints = null,
        ?array $manipulators = null
    ): File;

    /**
     * Save file to a temporary place.
     *
     * When $constraints are defined it should call `validateFile` before save.
     * When validation fails it should throw *before* saving file to the temporary place.
     *
     * The format of `$manipulators` is TBD - it might be based on Glide (current/next),
     * directly intervention image library (current/next - 3.0 looks promising).
     * Leaving for now as a parameter with format to be determined.
     * Maybe another interface to unify different possible solutions and allow easier extension?
     *
     * When `$manipulators` are provided - all the transformations
     * should be applied *before* saving to temporary place.
     *
     * @param  UploadedFile           $uploadedFile
     * @param  Constraint|array|null  $constraints
     * @param  array|null             $manipulators TBD - an array of manipulations to be applied before saving.
     * @return File
     *
     * @throws FileException
     * @throws ValidationFailedException
     */
    public function saveUploadedFileToTemp(
        UploadedFile $uploadedFile,
        Constraint|array|null $constraints = null,
        ?array $manipulators = null
    ): File;

    /**
     * Validates file and throws exception when it fails.
     *
     * It should be called by saveFileToTemp when $constraints parameter is not null,
     * but public method also exists to allow developers for simplified validation.
     *
     * @param  File                     $file
     * @param  Constraint|Constraint[]  $constraints
     * @return void
     *
     * @throws ValidationFailedException
     */
    public function validateFile(File $file, Constraint|array $constraints): void;
}
