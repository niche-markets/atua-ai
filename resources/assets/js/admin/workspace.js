'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';
import { getPlanList } from './helpers';

export function workspaceView() {
    Alpine.data('workspace', (workspace) => ({
        workspace: workspace,
        isProcessing: false,
        plans: [],
        orders: [],

        init() {
            this.getPlans();
            this.getOrders();
        },

        rename(name) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api.patch(`/workspaces/${this.workspace.id}`, { name: name })
                .then(response => {
                    this.workspace = response.data;
                    this.isProcessing = false;

                    toast.show(
                        'Workspace name updated!',
                        'ti ti-square-rounded-check-filled'
                    );

                    window.modal.close();
                }).catch(error => this.isProcessing = false);
        },

        subscribe(plan_id) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api.post(`/subscriptions/`, {
                workspace_id: this.workspace.id,
                plan_id: plan_id
            })
                .then(response => {
                    this.workspace.subscription = response.data;
                    this.isProcessing = false;

                    toast.show(
                        'Subscription created successfully!',
                        'ti ti-square-rounded-check-filled'
                    );

                    window.modal.close();
                }).catch(error => this.isProcessing = false);
        },

        getPlans() {
            let cycles = ['monthly', 'yearly', 'lifetime'];

            getPlanList()
                .then(plans => {
                    plans.forEach(plan => {
                        if (cycles.includes(plan.billing_cycle)) {
                            this.plans.push(plan);
                        }
                    });
                });
        },

        getOrders() {
            api.get(`/orders?workspace=${this.workspace.id}&sort=created_at:desc&limit=3`)
                .then(response => {
                    this.orders = response.data.data;
                });
        }
    }))
}