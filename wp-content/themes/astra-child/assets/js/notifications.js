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
            const data = JSON.parse(event.data);
            if (data.type === 'notification') {
                this.addNotification(data.data);
                this.updateUI();
            }
        };
        this.ws.onclose = () => {
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
        if (!notif.is_read) this.unreadCount++;
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
            const notif = this.notifications.find(n => n.id === id);
            if (notif && !notif.is_read) {
                notif.is_read = true;
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
            this.notifications.forEach(n => n.is_read = true);
            this.unreadCount = 0;
            this.renderList();
            this.updateUI();
        } catch (err) {
            console.error('[Notifications] Mark all read error', err);
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
            const cls = n.is_read ? '' : 'unread';
            const link = n.link || '#';
            html += `<li class="${cls}" data-id="${n.id}" data-link="${link}">
                <div class="notif-content">${n.content}</div>
                <small>${new Date(n.created_at).toLocaleString()}</small>
            </li>`;
        });
        this.list.innerHTML = html;

        this.list.querySelectorAll('li[data-id]').forEach(el => {
            el.addEventListener('click', (e) => {
                const id = parseInt(el.dataset.id);
                const link = el.dataset.link;
                // Mark as read
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

        const footer = document.createElement('li');
        footer.className = 'notif-footer';
        footer.innerHTML = `<button id="notif-mark-all">Mark all as read</button>`;
        this.list.appendChild(footer);
        document.getElementById('notif-mark-all').addEventListener('click', (e) => {
            e.stopPropagation();
            this.markAllRead();
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