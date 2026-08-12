<template>
    <div
        v-if="current"
        class="cp-notification-overlay"
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
        </ui-card>
    </div>
</template>

<script>
export default {
    data() {
        return {
            notices: [],
            loading: false,
        };
    },

    computed: {
        current() {
            return this.notices[0] ?? null;
        },

        position() {
            return this.current ? 1 : 0;
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
</style>
