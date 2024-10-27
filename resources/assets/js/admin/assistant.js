'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';

export function assistantView() {
    Alpine.data('assistant', (assistant) => ({
        assistant: {},
        model: {},
        isProcessing: false,

        init() {
            this.assistant = assistant;
            this.model = { ...this.model, ...this.assistant };
        },

        async submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            let data = { ... this.model }
            data.status = this.model.status ? 1 : 0;

            if (this.model.file) {
                data.avatar = await this.readFileAsBase64(this.model.file);
                delete data.file;
            }

            this.assistant.id ? this.update(data) : this.create(data);
        },

        update(data) {
            api.patch(`/assistants/${this.assistant.id}`, data)
                .then(response => {
                    this.assistant = response.data;
                    this.model = { ...this.assistant };

                    this.isProcessing = false;

                    toast.success('Assistant has been updated successfully!');
                })
                .catch(error => this.isProcessing = false);
        },

        create(data) {
            api.post('/assistants', data)
                .then(response => {
                    toast.defer('Assistant has been created successfully!');
                    window.location = `/admin/assistants/`;
                })
                .catch(error => this.isProcessing = false);
        },

        readFileAsBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const base64String = e.target.result.replace("data:", "").replace(/^.+,/, "");
                    resolve(base64String);
                };
                reader.onerror = function (error) {
                    reject(error);
                };
                reader.readAsDataURL(file);
            });
        }
    }))
}