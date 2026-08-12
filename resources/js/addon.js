import NotificationOverlay from './components/NotificationOverlay.vue';

Statamic.booting(() => {
    Statamic.$components.register('cp-notification-overlay', NotificationOverlay);
    Statamic.$components.append('cp-notification-overlay', { props: {} });
});
