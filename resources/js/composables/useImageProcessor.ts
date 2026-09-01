/**
 * Browser-side image processor for uploads.
 *
 * Resizes images that exceed a max dimension, converts them to an efficient
 * format (WebP with JPEG fallback), and assigns a random filename so the
 * original name never reaches the server.
 *
 * Images already within the max dimensions are passed through with only the
 * filename randomized — no unnecessary re-encoding.
 */

export interface ImageProcessorOptions {
    /** Max length of the longest edge in pixels. Default: 2048. */
    maxDimension?: number;
    /** Output quality for lossy formats (0–1). Default: 0.85. */
    quality?: number;
}

let cachedWebpSupported: boolean | null = null;

/**
 * Resets the cached WebP support detection. Used in tests.
 */
export function resetWebpSupportCache(): void {
    cachedWebpSupported = null;
}

/**
 * Detects whether the browser can encode WebP via Canvas.
 * Result is cached after the first call.
 */
function isWebpSupported(): boolean {
    if (cachedWebpSupported !== null) {
        return cachedWebpSupported;
    }

    try {
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        cachedWebpSupported = canvas
            .toDataURL('image/webp')
            .startsWith('data:image/webp');
    } catch {
        cachedWebpSupported = false;
    }

    return cachedWebpSupported;
}

/**
 * Returns the MIME type and file extension for the output format.
 */
function getOutputFormat(): { mime: string; extension: string } {
    if (isWebpSupported()) {
        return { mime: 'image/webp', extension: 'webp' };
    }

    return { mime: 'image/jpeg', extension: 'jpg' };
}

/**
 * Processes an image file: resizes if needed, converts format, and randomizes
 * the filename.
 *
 * Images within max dimensions are returned with only the filename changed
 * (original bytes preserved, no re-encoding).
 */
export async function processImage(
    file: File,
    options: ImageProcessorOptions = {},
): Promise<File> {
    const maxDimension = options.maxDimension ?? 2048;
    const quality = options.quality ?? 0.85;

    const bitmap = await createImageBitmap(file);

    const { width, height } = bitmap;
    const longestEdge = Math.max(width, height);

    // If the image fits within max dimensions, just rename it.
    if (longestEdge <= maxDimension) {
        bitmap.close();
        const { extension } = getOutputFormat();
        const randomName = `${crypto.randomUUID()}.${extension}`;

        return new File([file], randomName, { type: file.type });
    }

    // Calculate new dimensions preserving aspect ratio.
    const scale = maxDimension / longestEdge;
    const newWidth = Math.round(width * scale);
    const newHeight = Math.round(height * scale);

    const canvas = document.createElement('canvas');
    canvas.width = newWidth;
    canvas.height = newHeight;

    const ctx = canvas.getContext('2d');

    if (!ctx) {
        bitmap.close();

        throw new Error('Failed to get canvas 2D context.');
    }

    // High-quality downsampling.
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(bitmap, 0, 0, newWidth, newHeight);
    bitmap.close();

    const { mime, extension } = getOutputFormat();

    const blob = await new Promise<Blob>((resolve, reject) => {
        canvas.toBlob(
            (result) => {
                if (result) {
                    resolve(result);
                } else {
                    reject(new Error('Canvas toBlob returned null.'));
                }
            },
            mime,
            quality,
        );
    });

    const randomName = `${crypto.randomUUID()}.${extension}`;

    return new File([blob], randomName, { type: mime });
}
