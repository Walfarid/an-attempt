import { beforeEach, describe, expect, it, vi } from 'vitest';
import { processImage, resetWebpSupportCache } from './useImageProcessor';

// happy-dom does not implement Canvas or createImageBitmap, so we mock both.

function makeFakeImage(): File {
    // A minimal 1x1 PNG as base; dimensions are controlled via the mock.
    const pngBytes = Uint8Array.from(atob(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8'
        + '/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg=='
    ), c => c.charCodeAt(0));

    return new File([pngBytes], 'original-name.png', { type: 'image/png' });
}

describe('processImage', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
        resetWebpSupportCache();
    });

    it('randomizes the filename extension based on output format', async () => {
        // Mock a small image (100x100) — should not be resized, just renamed.
        const file = makeFakeImage();

        // Mock createImageBitmap to return a bitmap with known dimensions.
        vi.stubGlobal('createImageBitmap', vi.fn().mockResolvedValue({
            width: 100,
            height: 100,
            close: vi.fn(),
        }));

        const result = await processImage(file);

        expect(result.name).not.toBe('original-name.png');
        expect(result.name).toMatch(
            /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.(webp|jpg)$/,
        );
    });

    it('does not re-encode images within max dimensions', async () => {
        const file = makeFakeImage();

        const closeSpy = vi.fn();
        vi.stubGlobal('createImageBitmap', vi.fn().mockResolvedValue({
            width: 100,
            height: 100,
            close: closeSpy,
        }));

        const toBlobSpy = vi.fn();
        const originalCreateElement = document.createElement.bind(document);
        vi.spyOn(document, 'createElement').mockImplementation((tag: string) => {
            const el = originalCreateElement(tag);

            if (tag === 'canvas') {
                (el as HTMLCanvasElement).toBlob = toBlobSpy;
            }

            return el;
        });

        await processImage(file);

        // toBlob should NOT be called since no resize is needed.
        expect(toBlobSpy).not.toHaveBeenCalled();
        // Bitmap should still be closed.
        expect(closeSpy).toHaveBeenCalled();
    });

    it('resizes images that exceed max dimension', async () => {
        const file = makeFakeImage();

        vi.stubGlobal('createImageBitmap', vi.fn().mockResolvedValue({
            width: 4000,
            height: 3000,
            close: vi.fn(),
        }));

        let capturedCallback: BlobCallback | null = null;
        const originalCreateElement = document.createElement.bind(document);
        vi.spyOn(document, 'createElement').mockImplementation((tag: string) => {
            const el = originalCreateElement(tag);

            if (tag === 'canvas') {
                (el as HTMLCanvasElement).getContext = vi.fn().mockReturnValue({
                    imageSmoothingEnabled: false,
                    imageSmoothingQuality: 'low',
                    drawImage: vi.fn(),
                });
                (el as HTMLCanvasElement).toBlob = vi.fn((cb: BlobCallback) => {
                    capturedCallback = cb;
                });
            }

            return el;
        });

        const resultPromise = processImage(file, { maxDimension: 2048 });

        // Wait for the microtask queue to flush so toBlob is called.
        await vi.waitFor(() => {
            expect(capturedCallback).not.toBeNull();
        });

        // Simulate toBlob returning a fake blob.
        const fakeBlob = new Blob(['fake'], { type: 'image/webp' });
        capturedCallback!(fakeBlob);

        const result = await resultPromise;

        expect(result.name).toMatch(/\.webp$/);
        expect(result.type).toBe('image/webp');
        expect(result.size).toBeGreaterThan(0);
    });

    it('falls back to JPEG when WebP is not supported', async () => {
        const file = makeFakeImage();

        vi.stubGlobal('createImageBitmap', vi.fn().mockResolvedValue({
            width: 4000,
            height: 3000,
            close: vi.fn(),
        }));

        const originalCreateElement = document.createElement.bind(document);
        vi.spyOn(document, 'createElement').mockImplementation((tag: string) => {
            const el = originalCreateElement(tag);

            if (tag === 'canvas') {
                // toDataURL with webp returns a PNG data URL → WebP not supported.
                (el as HTMLCanvasElement).toDataURL = vi.fn(() => 'data:image/png;base64,abc');
                (el as HTMLCanvasElement).getContext = vi.fn().mockReturnValue({
                    imageSmoothingEnabled: false,
                    imageSmoothingQuality: 'low',
                    drawImage: vi.fn(),
                });
                (el as HTMLCanvasElement).toBlob = vi.fn((cb: BlobCallback) => {
                    cb(new Blob(['fake'], { type: 'image/jpeg' }));
                });
            }

            return el;
        });

        const result = await processImage(file, { maxDimension: 2048 });

        expect(result.name).toMatch(/\.jpg$/);
        expect(result.type).toBe('image/jpeg');
    });

    it('preserves original file bytes for images within max dimensions', async () => {
        const originalBytes = new Uint8Array([1, 2, 3, 4, 5]);
        const file = new File([originalBytes], 'test.png', { type: 'image/png' });

        vi.stubGlobal('createImageBitmap', vi.fn().mockResolvedValue({
            width: 100,
            height: 100,
            close: vi.fn(),
        }));

        const result = await processImage(file);

        const resultBytes = new Uint8Array(await result.arrayBuffer());
        expect(resultBytes).toEqual(originalBytes);
    });
});
