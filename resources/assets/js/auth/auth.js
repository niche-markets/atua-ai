'use strict';

import Alpine from 'alpinejs';
import api from './api';
import jwt_decode from 'jwt-decode';
import helper from '../redirect';
import { toast } from '../base/toast';
import { ApiError } from '../api';

export function authView() {
    Alpine.data('auth', (view) => ({
        required: [],
        isProcessing: false,
        isSubmitable: false,
        success: false,

        init() {
            this.$refs.form.querySelectorAll('[required]').forEach((element) => {
                this.required.push(element);

                element.addEventListener('input', () => this.checkIsSubmitable());
            });

            this.checkIsSubmitable();
            helper.saveRedirectPath();
        },

        async submit() {
            if (!this.isSubmitable || this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            let fd = new FormData(this.$refs.form);
            this.$refs.form.querySelectorAll('input[type="checkbox"]').forEach((element) => {
                fd.append(element.name, element.checked ? '1' : '0');
            });

            fd.append('locale', document.documentElement.lang);

            try {
                let ipinfo = await this.getIpinfo();

                fd.append('ip', ipinfo.ipAddress);
                fd.append('country_code', ipinfo.countryCode);
                fd.append('city_name', ipinfo.cityName);
            } catch (error) {
            }

            const data = {};
            fd.forEach((value, key) => (data[key] = value));

            api.post(this.$refs.form.dataset.apiPath, data)
                .then(response => {
                    if (response.data.jwt) {
                        const jwt = response.data.jwt;
                        const payload = jwt_decode(jwt);

                        // Save the JWT to local storage 
                        // to be used for future api requests
                        localStorage.setItem('jwt', jwt);

                        // Redirect user to the app or admin dashboard
                        let path = payload.is_admin ? '/admin' : '/app';

                        // If the user was redirected to the login page
                        let redirectPath = helper.getRedirectPath();
                        if (redirectPath) {
                            // Redirect the user to the path they were trying to access
                            path = redirectPath;

                            // Remove the redirect path from local storage
                            helper.clearRedirectPath();
                        }

                        // Redirect the user to the path
                        window.location.href = path;

                        // Response should include the user cookie (autosaved) 
                        // for authenticatoin of the UI GET requests
                    } else {
                        this.isProcessing = true;
                        this.success = true;
                    }
                })
                .catch(error => {
                    if (error instanceof ApiError && error.response.status == 401) {
                        let msg = "Authentication failed! Please check your credentials and try again!";
                        toast.error(msg)
                    }

                    this.isProcessing = false;
                });
        },

        checkIsSubmitable() {
            for (let i = 0; i < this.required.length; i++) {
                const el = this.required[i];

                if (!el.value) {
                    this.isSubmitable = false;
                    return;
                }
            }

            this.isSubmitable = true;
        },

        async ipinfo() {
            try {
                let info = await this.getIpinfo();

                let data = {
                    ip: info.ipAddress,
                    country_code: info.countryCode,
                    city_name: info.cityName
                };

                document.cookie = `ipinfo=${JSON.stringify(data)};path=/`;
            } catch (error) {
            }
        },

        async getIpinfo() {
            let resp = await fetch('https://freeipapi.com/api/json/');
            return await resp.json();
        }
    }));
}