<template>
    <div
        v-if="current"
        class="cp-notification-overlay"
        data-testid="cp-notification-current"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cp-notification-title"
    >
        <div class="cp-notification-overlay__backdrop" />
        <ui-card class="cp-notification-overlay__panel">
            <div class="cp-notification-overlay__heading">
                <ui-badge :color="badgeColor">{{ current.severity }}</ui-badge>
                <span>{{ position }} of {{ notices.length }}</span>
            </div>
            <h1 id="cp-notification-title">{{ current.title }}</h1>
            <div class="cp-notification-overlay__body">{{ body }}</div>
            <div class="cp-notification-overlay__actions">
                <label class="cp-notification-overlay__confirmation">
                    <input v-model="confirmed" type="checkbox" :disabled="submitting">
                    <span>I have read and understand</span>
                </label>
                <div class="cp-notification-overlay__buttons">
                    <ui-button
                        v-if="canSnooze"
                        text="Snooze for 24 hours"
                        :disabled="submitting"
                        @click="snooze"
                    />
                    <ui-button
                        text="Confirm"
                        variant="primary"
                        :disabled="!confirmed || submitting"
                        @click="confirm"
                    />
                </div>
            </div>
        </ui-card>
    </div>
</template>

<script>
export default {
    data() {
        return {
            notices: [],
            loading: false,
            submitting: false,
            confirmed: false,
        };
    },

    computed: {
        current() {
            // Later notices intentionally remain inaccessible until index zero clears.
            return this.notices[0] ?? null;
        },

        position() {
            return this.current ? 1 : 0;
        },

        canSnooze() {
            return Boolean(this.current?.snoozeable && !this.current?.blocking);
        },

        badgeColor() {
            return {
                critical: 'red',
                warning: 'yellow',
                info: 'blue',
            }[this.current?.severity] ?? 'blue';
        },

        body() {
            if (typeof this.current?.body === 'string') return this.current.body;

            return this.current?.body ? JSON.stringify(this.current.body) : '';
        },
    },

    mounted() {
        this.refresh();
    },

    methods: {
        async snooze() {
            if (!this.canSnooze || this.submitting) return;

            this.submitting = true;

            try {
                await fetch(cp_url(`cp-notifications/api/notifications/${this.current.id}/snooze`), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': Statamic.$config.get('csrfToken'),
                    },
                });
            } finally {
                this.submitting = false;
            }
        },

        async confirm() {
            if (!this.current || !this.confirmed || this.submitting) return;

            this.submitting = true;

            try {
                await fetch(cp_url(`cp-notifications/api/notifications/${this.current.id}/acknowledge`), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': Statamic.$config.get('csrfToken'),
                    },
                    body: JSON.stringify({ confirmed: true }),
                });
            } finally {
                this.submitting = false;
            }
        },

        async refresh() {
            this.loading = true;

            try {
                const response = await fetch(cp_url('cp-notifications/api/stack'), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (response.ok) {
                    const payload = await response.json();
                    this.notices = payload.data ?? [];
                }
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped>
.cp-notification-overlay {
    position: fixed;
    inset: 0;
    z-index: 10000;
    display: grid;
    place-items: center;
    padding: 1.5rem;
}

.cp-notification-overlay__backdrop {
    position: absolute;
    inset: 0;
    background: rgb(15 23 42 / 65%);
}

.cp-notification-overlay__panel {
    position: relative;
    width: min(42rem, 100%);
    max-height: calc(100vh - 3rem);
    overflow: auto;
    padding: 2rem;
}

.cp-notification-overlay__heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.cp-notification-overlay__panel h1 {
    margin-bottom: 1rem;
    font-size: 1.5rem;
    font-weight: 700;
}

.cp-notification-overlay__body {
    white-space: pre-wrap;
}

.cp-notification-overlay__actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 2rem;
}

.cp-notification-overlay__confirmation {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}

.cp-notification-overlay__buttons {
    display: flex;
    gap: 0.75rem;
}
</style>
