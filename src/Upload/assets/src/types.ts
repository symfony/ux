/**
 * Types for Symfony UX Upload
 */

export interface InitResponse {
    uploadId: string;
    uploadUrl: string;
    config: UploadConfig;
    resumeToken?: string;
}

export interface UploadConfig {
    chunkSize: number;
    totalChunks: number;
    compression: boolean;
    parallel: number;
}

export interface ProgressResponse {
    progress: {
        storedChunks: number;
        chunkIndices: number[];
        percentComplete: number;
    } | null;
}

export interface PublicMetadata {
    filename: string;
    mimeType: string;
    size: number;
}

export interface TokenEntry {
    token: string;
    meta: PublicMetadata;
}

export interface CompleteResponse {
    success: boolean;
    uploadId?: string;
    token: string;
    meta: PublicMetadata;
}

export interface UploadResult {
    uploadId?: string;
    token: string;
    metadata: PublicMetadata;
}

export interface UploadError {
    success: false;
    error: string;
}
