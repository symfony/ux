import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { SignedUploadClient } from '../src/upload/SignedUploadClient.js';

describe('SignedUploadClient', () => {
  beforeEach(() => { vi.stubGlobal('fetch', vi.fn()); });
  afterEach(() => { vi.unstubAllGlobals(); });

  it('POSTs multipart and returns JSON on 200', async () => {
    (fetch as any).mockResolvedValue(new Response(JSON.stringify({ url: '/u/abc.png' }), { status: 200 }));
    const c = new SignedUploadClient('/_ux_editor/upload/body?token=t', { field: 'body' });
    const r = await c.upload(new Blob(['xx'], { type: 'image/png' }), 'i.png');
    expect(r.url).toBe('/u/abc.png');
    const call = (fetch as any).mock.calls[0];
    expect(call[0]).toBe('/_ux_editor/upload/body?token=t');
    expect(call[1].method).toBe('POST');
    expect(call[1].body).toBeInstanceOf(FormData);
  });

  it('throws on non-2xx with structured error', async () => {
    (fetch as any).mockResolvedValue(new Response(JSON.stringify({ error: 'unsupported_file', message: 'bad mime' }), { status: 422 }));
    const c = new SignedUploadClient('/u?token=t', { field: 'body' });
    await expect(c.upload(new Blob(['x']), 'x.exe'))
      .rejects.toMatchObject({ status: 422, code: 'unsupported_file', message: 'bad mime' });
  });
});
