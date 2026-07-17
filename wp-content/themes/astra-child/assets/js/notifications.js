class NotificationSystem {
    constructor(config) {
        this.userId = config.userId;
        this.nonce = config.nonce;
        this.restUrl = config.restUrl;
        this.wsUrl = config.wsUrl;
        this.ws = null;
        this.notifications = [];
        this.unreadCount = 0;
        this.dropdownOpen = false;
        this.bell = document.getElementById('notification-bell');
        this.badge = document.getElementById('notification-badge');
        this.dropdown = document.getElementById('notification-dropdown');
        this.list = document.getElementById('notification-list');

        if (!this.bell) return;

        this.initWebSocket();
        this.fetchNotifications();
        this.bindEvents();
    }

    initWebSocket() {
        if (!this.wsUrl) return;
        this.ws = new WebSocket(this.wsUrl);
        this.ws.onopen = () => {
            console.log('[Notifications] Connected');
            if (this.userId) {
                this.ws.send(JSON.stringify({
                    type: 'subscribe_notifications',
                    userId: this.userId
                }));
            }
        };
        this.ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                if (data.type === 'notification') {
                    this.addNotification(data.data);
                    this.updateUI();
                }
            } catch (e) {
                console.error('[Notifications] WS message parse error', e);
            }
        };
        this.ws.onclose = () => {
            console.log('[Notifications] Disconnected, reconnecting...');
            setTimeout(() => this.initWebSocket(), 3000);
        };
        this.ws.onerror = (err) => console.error('[Notifications] Error', err);
    }

    async fetchNotifications() {
        try {
            const res = await fetch(this.restUrl + '/notifications', {
                headers: { 'X-WP-Nonce': this.nonce },
                credentials: 'include'
            });
            if (!res.ok) throw new Error('Failed to fetch notifications');
            const data = await res.json();
            this.notifications = data.notifications || [];
            this.unreadCount = data.unread_count || 0;
            this.renderList();
            this.updateUI();
        } catch (err) {
            console.error('[Notifications] Fetch error', err);
        }
    }

    addNotification(notif) {
        this.notifications.unshift(notif);
        if (notif.is_read == 0) this.unreadCount++;
        this.renderList();
        this.updateUI();
    }

    async markRead(id) {
        try {
            const res = await fetch(this.restUrl + '/notifications/' + id + '/read', {
                method: 'POST',
                headers: { 'X-WP-Nonce': this.nonce },
                credentials: 'include'
            });
            if (!res.ok) throw new Error('Failed to mark read');
            const notif = this.notifications.find(n => parseInt(n.id) === id);
            if (notif && notif.is_read == 0) {
                notif.is_read = 1;
                this.unreadCount--;
                this.renderList();
                this.updateUI();
            }
        } catch (err) {
            console.error('[Notifications] Mark read error', err);
        }
    }

    async markAllRead() {
        try {
            const res = await fetch(this.restUrl + '/notifications/read-all', {
                method: 'POST',
                headers: { 'X-WP-Nonce': this.nonce },
                credentials: 'include'
            });
            if (!res.ok) throw new Error('Failed to mark all read');
            this.notifications.forEach(n => n.is_read = 1);
            this.unreadCount = 0;
            this.renderList();
            this.updateUI();
        } catch (err) {
            console.error('[Notifications] Mark all read error', err);
        }
    }

    async deleteNotification(id) {
        try {
            const res = await fetch(this.restUrl + '/notifications/' + id, {
                method: 'DELETE',
                headers: { 'X-WP-Nonce': this.nonce },
                credentials: 'include'
            });
            if (!res.ok) throw new Error('Failed to delete');
            const index = this.notifications.findIndex(n => parseInt(n.id) === id);
            if (index !== -1) {
                const removed = this.notifications[index];
                this.notifications.splice(index, 1);
                if (removed.is_read == 0) this.unreadCount--;
                this.renderList();
                this.updateUI();
            }
        } catch (err) {
            console.error('[Notifications] Delete error', err);
        }
    }

    async clearReadNotifications() {
        try {
            const res = await fetch(this.restUrl + '/notifications/clear-read', {
                method: 'POST',
                headers: { 'X-WP-Nonce': this.nonce },
                credentials: 'include'
            });
            if (!res.ok) throw new Error('Failed to clear read');
            const data = await res.json();
            this.notifications = this.notifications.filter(n => n.is_read == 0);
            this.unreadCount = this.notifications.filter(n => n.is_read == 0).length;
            this.renderList();
            this.updateUI();
            console.log(`[Notifications] Cleared ${data.deleted_count} read notifications`);
        } catch (err) {
            console.error('[Notifications] Clear read error', err);
        }
    }

    renderList() {
        if (!this.list) return;
        if (this.notifications.length === 0) {
            this.list.innerHTML = '<li style="padding:10px;color:#999;text-align:center;">No notifications</li>';
            return;
        }
        let html = '';
        this.notifications.slice(0, 20).forEach(n => {
            const isUnread = (n.is_read == 0);
            const cls = isUnread ? 'unread' : '';
            const link = n.link || '#';
            html += `<li class="${cls}" data-id="${n.id}" data-link="${link}">
                <div class="notif-content">${n.content}</div>
                <small>${new Date(n.created_at).toLocaleString()}</small>
                <button class="notif-delete" data-id="${n.id}" title="Delete notification">✕</button>
            </li>`;
        });
        this.list.innerHTML = html;

        // Bind click to each notification (to mark read and navigate)
        this.list.querySelectorAll('li[data-id]').forEach(el => {
            el.addEventListener('click', (e) => {
                if (e.target.classList.contains('notif-delete')) return;
                const id = parseInt(el.dataset.id);
                const link = el.dataset.link;
                this.markRead(id);
                if (link && link !== '#') {
                    setTimeout(() => {
                        window.location.href = link;
                    }, 200);
                }
                this.dropdown.style.display = 'none';
                this.dropdownOpen = false;
            });
        });

        this.list.querySelectorAll('.notif-delete').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = parseInt(btn.dataset.id);
                this.deleteNotification(id);
            });
        });

        const footer = document.createElement('li');
        footer.className = 'notif-footer';
        footer.innerHTML = `
            <button id="notif-mark-all">Mark all as read</button>
            <button id="notif-clear-read">Clear read</button>
        `;
        this.list.appendChild(footer);

        document.getElementById('notif-mark-all').addEventListener('click', (e) => {
            e.stopPropagation();
            this.markAllRead();
        });
        document.getElementById('notif-clear-read').addEventListener('click', (e) => {
            e.stopPropagation();
            this.clearReadNotifications();
        });
    }

    updateUI() {
        if (this.badge) {
            this.badge.textContent = this.unreadCount > 0 ? this.unreadCount : '';
            this.badge.style.display = this.unreadCount > 0 ? 'inline-block' : 'none';
        }
        if (this.unreadCount > 0) {
            this.bell.style.color = '#d63638';
        } else {
            this.bell.style.color = '#333';
        }
    }

    bindEvents() {
        if (!this.bell) return;
        this.bell.addEventListener('click', (e) => {
            e.preventDefault();
            this.dropdownOpen = !this.dropdownOpen;
            this.dropdown.style.display = this.dropdownOpen ? 'block' : 'none';
            if (this.dropdownOpen) {
                this.renderList();
            }
        });
        document.addEventListener('click', (e) => {
            if (this.dropdownOpen && !this.bell.contains(e.target) && !this.dropdown.contains(e.target)) {
                this.dropdown.style.display = 'none';
                this.dropdownOpen = false;
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.notificationSettings !== 'undefined') {
        window.notificationSystem = new NotificationSystem(window.notificationSettings);
    }
});