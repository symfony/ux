import export_default from "./upload_controller.js";
import { a as Uploader, c as UploaderOptions, d as InitResponse, f as ProgressResponse, h as UploadResult, i as UploadSuspendedError, l as UploaderXhrFactory, m as UploadError, n as UploadCancelledError, o as UploaderEvents, p as UploadConfig, r as UploadSpeed, s as UploaderFetch, t as IntegrityAlgorithm, u as CompleteResponse } from "./uploader-EqhK8s3O.js";
/**
 * Clipboard extraction utilities for paste-to-upload.
 */
/**
 * Extract File objects from a ClipboardEvent.
 *
 * Checks `clipboardData.files` first (OS file-manager copies), then falls
 * back to `clipboardData.items` (screenshots, browser-pasted images).
 */
declare function extractFilesFromClipboard(event: ClipboardEvent): File[];
/**
 * Rename files with generic/empty names (e.g. OS screenshots) to a
 * timestamped name so upload backends can distinguish them.
 *
 * Files with real names are returned unchanged.
 */
declare function renameAnonymousFile(file: File): File;
export { type CompleteResponse, type InitResponse, type IntegrityAlgorithm, type ProgressResponse, UploadCancelledError, type UploadConfig, type UploadError, type UploadResult, type UploadSpeed, UploadSuspendedError, Uploader, type UploaderEvents, type UploaderFetch, type UploaderOptions, type UploaderXhrFactory, export_default as default, extractFilesFromClipboard, renameAnonymousFile };