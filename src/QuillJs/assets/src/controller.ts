import { Controller } from '@hotwired/stimulus';
import Quill from 'quill';

import ImageUploader from 'quill-image-uploader';
import axios from 'axios';
import 'quill-image-uploader/dist/quill.imageUploader.min.css';
Quill.register('modules/imageUploader', ImageUploader);

import "quill-emoji/dist/quill-emoji.css";
import * as Emoji from "quill-emoji";
Quill.register("modules/emoji", Emoji);

import BlotFormatter from 'quill-blot-formatter'
Quill.register('modules/blotFormatter', BlotFormatter);
import ImageFormat from "./customImage";
Quill.register(ImageFormat, true);

type ExtraOptions = {
    theme: string;
    debug: string|null;
    height: string|null;
    placeholder: string|null;
    upload_handler: uploadOptions;
}
type uploadOptions = {
    type: string;
    path; string
}

export default class extends Controller {
    readonly inputTarget: HTMLInputElement;
    readonly editorContainerTarget: HTMLDivElement;
    static targets = ['input', 'editorContainer'];

    readonly extraOptionsValue: ExtraOptions;
    readonly toolbarOptionsValue: HTMLDivElement;
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

        const options = {
            debug: this.extraOptionsValue.debug,
            modules: {
                toolbar: toolbarOptionsValue,
                "emoji-toolbar": true,
                "emoji-shortname": true,
                blotFormatter: {
                    overlay: {
                        style: {
                            border: '2px solid red',
                        }
                    }
                }
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

        if (typeof this.extraOptionsValue.height === "string") {
            this.editorContainerTarget.style.height = this.extraOptionsValue.height
        }

        const quill = new Quill(this.editorContainerTarget, options);
        quill.on('text-change', () => {
            const quillContent = quill.root.innerHTML;
            const inputContent = this.inputTarget;
            inputContent.value = quillContent;
        })
    }
}
