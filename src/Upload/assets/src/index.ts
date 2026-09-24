/**
 * Symfony UX Upload
 *
 * Main entry point for the upload package
 */

// Export Stimulus controller as default
export { default } from './upload_controller';

// Export Uploader class for custom usage
export { Uploader, UploadCancelledError, UploadSuspendedError } from './uploader';
export type {
    IntegrityAlgorithm,
    UploadSpeed,
    UploaderEvents,
    UploaderFetch,
    UploaderOptions,
    UploaderXhrFactory,
} from './uploader';

// Export types
export type {
    InitResponse,
    UploadConfig,
    ProgressResponse,
    CompleteResponse,
    UploadResult,
    UploadError,
} from './types';

// Export clipboard utilities
export { extractFilesFromClipboard, renameAnonymousFile } from './paste-utils';
