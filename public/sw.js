self.addEventListener('push', function (e) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    if (e.data) {
        let msg = {};
        try {
            msg = e.data.json();
        } catch (err) {
            console.error('Failed to parse push data as JSON', err);
            msg = {
                title: 'GAS-RPA Notification',
                body: e.data.text(),
                icon: '/logo.png'
            };
        }

        e.waitUntil(self.registration.showNotification(msg.title || 'Notifikasi Baru', {
            body: msg.body,
            icon: msg.icon || '/logo.png',
            actions: msg.actions,
            data: msg.data
        }));
    }
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            if (clientList.length > 0) {
                let client = clientList[0];
                for (let i = 0; i < clientList.length; i++) {
                    if (clientList[i].focused) {
                        client = clientList[i];
                    }
                }
                return client.focus();
            }
            return clients.openWindow('/');
        })
    );
});
