import type { DropzonePreAddEventDetail, DropzoneRejectEventDetail } from '@symfony/ux-dropzone';
import { expectTypeOf, test } from 'vitest';

test('types the detail of dropzone:pre-add', () => {
    expectTypeOf<DropzonePreAddEventDetail['files']>().toEqualTypeOf<readonly File[]>();
    expectTypeOf<DropzonePreAddEventDetail['waitUntil']>().toEqualTypeOf<
        (files: readonly File[] | PromiseLike<readonly File[]>) => void
    >();
});

test('types the detail of dropzone:reject', () => {
    expectTypeOf<DropzoneRejectEventDetail['files']>().toEqualTypeOf<readonly File[]>();
    expectTypeOf<DropzoneRejectEventDetail['reason']>().toEqualTypeOf<'accept' | 'max_files'>();
});

test('does not let a listener change the details', () => {
    const preAdd = {} as DropzonePreAddEventDetail;
    const reject = {} as DropzoneRejectEventDetail;

    // @ts-expect-error
    preAdd.files = [];
    // @ts-expect-error
    preAdd.files.splice(0);
    // @ts-expect-error
    reject.reason = 'accept';
});

test('types a listener', () => {
    const element = document.createElement('div');

    element.addEventListener('dropzone:pre-add', (event) => {
        const { files, waitUntil } = (event as CustomEvent<DropzonePreAddEventDetail>).detail;
        waitUntil(files);
        waitUntil(files.filter((file) => file.size > 0));
        waitUntil(Promise.resolve(files));
        waitUntil({} as PromiseLike<File[]>);
    });
    element.addEventListener('dropzone:reject', (event) => {
        const { files, reason } = (event as CustomEvent<DropzoneRejectEventDetail>).detail;
        expectTypeOf(reason).toEqualTypeOf<'accept' | 'max_files'>();
        expectTypeOf(files).toEqualTypeOf<readonly File[]>();
    });
});
