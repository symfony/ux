import { Controller } from '@hotwired/stimulus';
import Quill from 'quill';
import * as Options from 'quill/core/quill';

import axios from 'axios';

import ImageUploader from './imageUploader.js'
Quill.register('modules/imageUploader', ImageUploader);

import * as Emoji from 'quill2-emoji';
import 'quill2-emoji/dist/style.css';
Quill.register('modules/emoji', Emoji);

import QuillResizeImage from 'quill-resize-image';
Quill.register('modules/resize', QuillResizeImage);
// allow image resize and position to be reloaded after persist
const Image = Quill.import('formats/image');
const oldFormats = Image.formats;
Image.formats = function (domNode) {
    const formats = oldFormats(domNode);
    if (domNode.hasAttribute('style')) {
        formats.style = domNode.getAttribute('style');
    }
    return formats;
}

Image.prototype.format = function (name, value) {
    if (value) {
        this.domNode.setAttribute(name, value);
    } else {
        this.domNode.removeAttribute(name);
    }
}

type ExtraOptions = {
    theme: string;
    debug: string|null;
    height: string|null;
    placeholder: string|null;
    upload_handler: uploadOptions;
}
type uploadOptions = {
    type: string;
    path: string;
}

export default class extends Controller {
    declare readonly inputTarget: HTMLInputElement;
    declare readonly editorContainerTarget: HTMLDivElement;
    static targets = ['input', 'editorContainer'];

    declare readonly extraOptionsValue: ExtraOptions;
    declare readonly toolbarOptionsValue: HTMLDivElement;
    static values = {
        toolbarOptions: {
            type: Array,
            default: [],
        },
        extraOptions: {
            type: Object,
            default: {},
        }
    }

    connect() {
        const toolbarOptionsValue = this.toolbarOptionsValue;

        const options: Options = {
            debug: this.extraOptionsValue.debug,
            modules: {
                toolbar: toolbarOptionsValue,
                'emoji-toolbar': true,
                resize: {},
            },
            placeholder: this.extraOptionsValue.placeholder,
            theme: this.extraOptionsValue.theme,
        };

        if (this.extraOptionsValue.upload_handler.path !== null && this.extraOptionsValue.upload_handler.type === 'form') {
            Object.assign(options.modules, {
                imageUploader: {
                    upload: file => {
                        return new Promise((resolve, reject) => {
                            const formData = new FormData();
                            formData.append('file', file);

                            axios
                                .post(this.extraOptionsValue.upload_handler.path, formData)
                                .then(response => {
                                    resolve(response.data);
                                })
                                .catch(err => {
                                    reject('Upload failed');
                                    console.log(err)
                                })
                        })
                    }
                }}
            )
        }

        if (this.extraOptionsValue.upload_handler.path !== null && this.extraOptionsValue.upload_handler.type === 'json') {
            Object.assign(options.modules, {
                imageUploader: {
                    upload: file => {
                        return new Promise((resolve, reject) => {
                            const reader = (file) => {
                                return new Promise((resolve) => {
                                    const fileReader = new FileReader();
                                    fileReader.onload = () => resolve(fileReader.result);
                                    fileReader.readAsDataURL(file);
                                });
                            }

                            reader(file).then(result =>
                                axios
                                    .post(this.extraOptionsValue.upload_handler.path, result, {
                                        headers: {
                                            'Content-Type': 'application/json',
                                        }
                                    })
                                    .then(response => {
                                        resolve(response.data);
                                    })
                                    .catch(err => {
                                        reject('Upload failed');
                                        console.log(err)
                                    })
                            );
                        })
                    }
                }}
            )
        }

        const heightDefined = this.extraOptionsValue.height;
        if (null !== heightDefined) {
            this.editorContainerTarget.style.height = heightDefined
        }

        const quill = new Quill(this.editorContainerTarget, options);
        quill.on('text-change', () => {
            const quillContent = quill.root.innerHTML;
            const inputContent = this.inputTarget;
            inputContent.value = quillContent;
        })
    }
}
