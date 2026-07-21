class ChatAPI {
    constructor(config) {
        this.restUrl = config.restUrl;
        this.nonce = config.nonce;
        this.userId = config.userId;
        this.isAdmin = config.isAdmin;
    }

    async fetchRooms() {
        var res = await fetch(this.restUrl + '/rooms', {
            headers: { 'X-WP-Nonce': this.nonce },
            credentials: 'include'
        });
        if (!res.ok) throw new Error('Failed to load rooms');
        return res.json();
    }

    async fetchMessages(roomId, limit = 50) {
        var res = await fetch(this.restUrl + '/rooms/' + roomId + '/messages?limit=' + limit, {
            headers: { 'X-WP-Nonce': this.nonce },
            credentials: 'include'
        });
        if (!res.ok) throw new Error('Failed to load messages');
        return res.json();
    }

        async postMessage(roomId, message, attachment) {
            var payload = { room_id: roomId, message: message };
            if (attachment) {
                payload.attachment = attachment;
            }
            var res = await fetch(this.restUrl + '/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.nonce
                },
                body: JSON.stringify(payload),
                credentials: 'include'
            });
            if (!res.ok) {
                var err = await res.json();
                throw new Error(err.message || 'Failed to send');
            }
            return res.json();
        }

    async updateMessage(messageId, newMessage) {
        var res = await fetch(this.restUrl + '/messages/' + messageId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.nonce
            },
            body: JSON.stringify({ message: newMessage }),
            credentials: 'include'
        });
        if (!res.ok) {
            var err = await res.json();
            throw new Error(err.message || 'Failed to update');
        }
        return res.json();
    }

    async deleteMessage(messageId) {
        var res = await fetch(this.restUrl + '/messages/' + messageId, {
            method: 'DELETE',
            headers: { 'X-WP-Nonce': this.nonce },
            credentials: 'include'
        });
        if (!res.ok) {
            var err = await res.json();
            throw new Error(err.message || 'Failed to delete');
        }
        return res.json();
    }

    async searchUsers(query) {
        if (query.length < 2) return [];
        var res = await fetch(this.restUrl + '/users?search=' + encodeURIComponent(query), {
            headers: { 'X-WP-Nonce': this.nonce },
            credentials: 'include'
        });
        if (!res.ok) return [];
        return res.json();
    }

    async addReaction(messageId, reaction) {
        var res = await fetch(this.restUrl + '/messages/' + messageId + '/reactions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.nonce
            },
            body: JSON.stringify({ reaction: reaction }),
            credentials: 'include'
        });
        if (!res.ok) {
            var err = await res.json();
            throw new Error(err.message || 'Failed to add reaction');
        }
        return res.json();
    }

    async removeReaction(messageId, reaction) {
        var res = await fetch(this.restUrl + '/messages/' + messageId + '/reactions/' + encodeURIComponent(reaction), {
            method: 'DELETE',
            headers: { 'X-WP-Nonce': this.nonce },
            credentials: 'include'
        });
        if (!res.ok) {
            var err = await res.json();
            throw new Error(err.message || 'Failed to remove reaction');
        }
        return res.json();
    }

    async markRead(roomId, lastMessageId) {
        var res = await fetch(this.restUrl + '/rooms/' + roomId + '/read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.nonce
            },
            body: JSON.stringify({ last_message_id: lastMessageId }),
            credentials: 'include'
        });
        if (!res.ok) {
            var err = await res.json();
            throw new Error(err.message || 'Failed to mark read');
        }
        return res.json();
    }

    async getDirectRoom(userId) {
        var res = await fetch(this.restUrl + '/direct/' + userId, {
            headers: { 'X-WP-Nonce': this.nonce },
            credentials: 'include'
        });
        if (!res.ok) throw new Error('Failed to get direct room');
        return res.json();
    }
    
    async inviteUser(roomId, userId) {
        var res = await fetch(this.restUrl + '/rooms/' + roomId + '/invite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.nonce
            },
            body: JSON.stringify({ user_id: userId }),
            credentials: 'include'
        });
        if (!res.ok) {
            var err = await res.json();
            throw new Error(err.message || 'Failed to invite user');
        }
        return res.json();
    }

    async leaveRoom(roomId) {
        var res = await fetch(this.restUrl + '/rooms/' + roomId + '/leave', {
            method: 'POST',
            headers: { 'X-WP-Nonce': this.nonce },
            credentials: 'include'
        });
        if (!res.ok) {
            var err = await res.json();
            throw new Error(err.message || 'Failed to leave room');
        }
        return res.json();
    }

}

window.ChatAPI = ChatAPI;

