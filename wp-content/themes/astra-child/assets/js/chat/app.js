class ChatApp {
    constructor(config) {
        this.config = config;
        this.api = new ChatAPI(config);
        this.ui = new ChatUI();

        this.ws = null;
        this.currentRoomId = null;
        this.roomData = {};
        this.loadingMessages = false;
        this.messageLoadTimeout = null;
        this._currentLoadRoomId = null;
        this._userReactions = {};
        this._reactionControllers = {};
        this.heartbeatInterval = null;
        this.reconnectAttempts = 0;

        this.activeRoomId = localStorage.getItem('chat_active_room') ? parseInt(localStorage.getItem('chat_active_room')) : null;

        window.ChatApp = this;
        this.init();
    }

    init() {
        this.connectWebSocket();
        this.loadRooms();
        this.bindEvents();
        this.setupTyping();
    }

    connectWebSocket() {
        this.ws = new WebSocket(this.config.websocketUrl);
        this.ws.onopen = function() {
            console.log('Connected to chat server');
            if (this.heartbeatInterval) {
                clearInterval(this.heartbeatInterval);
            }
            this.heartbeatInterval = setInterval(() => {
                if (this.ws && this.ws.readyState === WebSocket.OPEN && this.currentRoomId) {
                    this.ws.send(JSON.stringify({ type: 'ping', roomId: this.currentRoomId }));
                }
            }, 25000);

            if (this.currentRoomId) {
                console.log('Re-subscribing to room', this.currentRoomId);
                this.subscribeToRoom(this.currentRoomId);
            }
            this.reconnectAttempts = 0;
        }.bind(this);

        this.ws.onmessage = function(event) {
            this.handleWebSocketMessage(event);
        }.bind(this);

        this.ws.onerror = function(error) {
            console.error('WebSocket error:', error);
        };

        this.ws.onclose = function(event) {
            console.log('WebSocket closed, code:', event.code, 'reason:', event.reason);
            if (this.heartbeatInterval) {
                clearInterval(this.heartbeatInterval);
                this.heartbeatInterval = null;
            }
            const delay = Math.min(3000 + this.reconnectAttempts * 1000, 10000);
            this.reconnectAttempts++;
            setTimeout(function() {
                this.connectWebSocket();
            }.bind(this), delay);
        }.bind(this);
    }

    handleWebSocketMessage(event) {
        var data = JSON.parse(event.data);
        console.log('[WS] Raw message received:', data);

        switch (data.type) {
            case 'new_message':
                console.log('[WS] New message:', data.data);
                console.log('[WS] Current room:', this.currentRoomId);
                console.log('[WS] Message room:', data.data.room_id);
                if (parseInt(data.data.room_id) === parseInt(this.currentRoomId)) {
                    this.appendMessage(data.data);
                    this.markRoomRead(this.currentRoomId);
                } else {
                    console.warn('[WS] Room mismatch – ignoring');
                }
                break;
            case 'new_room':
                this.loadRooms();
                break;
            case 'update_message':
                if (parseInt(data.data.room_id) === parseInt(this.currentRoomId)) {
                    this.updateMessageElement(data.data);
                }
                break;
            case 'delete_message':
                if (parseInt(data.data.room_id) === parseInt(this.currentRoomId)) {
                    this.removeMessageElement(data.data.id, data.data);
                }
                break;
            case 'typing':
                this.ui.showTypingIndicator(data.data);
                break;
            case 'read':
                console.log('[WS] Read receipt:', data.data);
                this.ui.markMessagesRead(data.data.room_id, data.data.user_id, data.data.last_message_id);
                break;
            case 'reaction':
                this.ui.updateReaction(data.data);
                break;
            default:
                console.log('[WS] Unknown message type:', data.type);
        }
    }

    subscribeToRoom(roomId) {
        console.log('[WS] Subscribing to room:', roomId);
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({ type: 'subscribe', roomId: roomId, userId: this.config.userId }));
            console.log('[WS] Subscribe message sent');
        } else {
            console.warn('[WS] WebSocket not open, cannot subscribe');
        }
        this.currentRoomId = roomId;
        this.ui.currentRoomId = roomId;
        localStorage.setItem('chat_active_room', roomId);
    }

    async loadRooms() {
        try {
            var rooms = await this.api.fetchRooms();
            this.roomData = {};
            rooms.forEach(function(room) { this.roomData[room.id] = room; }.bind(this));
            this.ui.renderRoomList(rooms, this.currentRoomId, function(roomId) { this.switchRoom(roomId); }.bind(this));
            if (rooms.length > 0) {
                var targetRoom = this.activeRoomId && rooms.some(function(r) { return r.id == this.activeRoomId; }.bind(this)) ? parseInt(this.activeRoomId) : rooms[0].id;
                if (targetRoom !== this.currentRoomId) {
                    this.switchRoom(targetRoom);
                } else {
                    this.applyHighlight(targetRoom);
                }
            } else {
                this.ui.showNoRooms();
            }
        } catch (err) {
            console.error(err);
        }
    }

    switchRoom(roomId) {
        if (this.currentRoomId === roomId) {
            this.applyHighlight(roomId);
            return;
        }
        this.subscribeToRoom(roomId);
        var room = this.roomData[roomId];
        this.ui.setRoomHeader(room ? (room.name || 'Room ' + room.id) : 'Room');
        this.ui.showLoadingMessages();
        this.loadMessages(roomId);
        this.applyHighlight(roomId);
    }

    applyHighlight(roomId) {
        var self = this;
        requestAnimationFrame(function() {
            self.ui.highlightRoom(roomId);
        });
        setTimeout(function() {
            self.ui.highlightRoom(roomId);
        }, 100);
    }

    loadMessages(roomId) {
        clearTimeout(this.messageLoadTimeout);
        this.loadingMessages = false;
        this._currentLoadRoomId = roomId;
        this.loadingMessages = true;
        this.ui.showLoadingMessages();

        this.messageLoadTimeout = setTimeout(async function() {
            if (this._currentLoadRoomId !== roomId) {
                this.loadingMessages = false;
                return;
            }
            try {
                var response = await this.api.fetchMessages(roomId);
                if (this._currentLoadRoomId !== roomId) return;
                var messages = response.messages || response;
                if (Array.isArray(response)) {
                    messages = response;
                    this.ui.clearMessages();
                    messages.forEach(function(msg) { this.appendMessage(msg); }.bind(this));
                    this.ui.scrollToBottom();
                    this.applyHighlight(roomId);
                    if (messages.length > 0) {
                        var lastId = messages[messages.length - 1].id;
                        this.api.markRead(roomId, lastId).catch(function() {});
                    }
                } else {
                    this.ui.clearMessages();
                    messages.forEach(function(msg) {
                        this.appendMessage(msg);
                        if (msg.reactions && msg.reactions.length) {
                            this.ui.renderExistingReactions(msg.id, msg.reactions);
                        }
                    }.bind(this));
                    this.ui.scrollToBottom();
                    this.applyHighlight(roomId);
                    if (response.last_read_message_id) {
                        this.ui.markMessagesReadUpTo(response.last_read_message_id);
                    }
                    if (messages.length > 0) {
                        var lastId = messages[messages.length - 1].id;
                        this.api.markRead(roomId, lastId).catch(function() {});
                    }
                }
            } catch (err) {
                if (this._currentLoadRoomId === roomId) {
                    console.error(err);
                    this.ui.showError('Failed to load messages');
                }
            } finally {
                if (this._currentLoadRoomId === roomId) {
                    this.loadingMessages = false;
                }
            }
        }.bind(this), 100);
    }

    appendMessage(msg) {
        var onEdit = function(id, div) { this.editMessage(id, div); }.bind(this);
        var onDelete = function(id) { this.deleteMessage(id); }.bind(this);
        var onReaction = function(messageId, reaction) { this.toggleReaction(messageId, reaction); }.bind(this);
        this.ui.appendMessage(msg, this.config.userId, this.config.isAdmin, onEdit, onDelete, onReaction);
    }

    updateMessageElement(msg) {
        var onEdit = function(id, div) { this.editMessage(id, div); }.bind(this);
        var onDelete = function(id) { this.deleteMessage(id); }.bind(this);
        var onReaction = function(messageId, reaction) { this.toggleReaction(messageId, reaction); }.bind(this);
        this.ui.updateMessageElement(msg, this.config.userId, this.config.isAdmin, onEdit, onDelete, onReaction);
    }

    removeMessageElement(messageId, msg) {
        var onUpdate = function(m) { this.updateMessageElement(m); }.bind(this);
        this.ui.removeMessageElement(messageId, this.config.isAdmin, msg, onUpdate);
    }

    async editMessage(id, div) {
        var currentText = this.ui.getMessageText(div);
        var self = this;

        var onSave = async function(newText) {
            try {
                var updatedMsg = await self.api.updateMessage(id, newText);
                self.ui.updateMessageElement(
                    updatedMsg,
                    self.config.userId,
                    self.config.isAdmin,
                    function(msgId, msgDiv) { self.editMessage(msgId, msgDiv); },
                    function(msgId) { self.deleteMessage(msgId); },
                    function(messageId, reaction) { self.toggleReaction(messageId, reaction); }
                );
                self.ui.showEditSuccess();
            } catch (err) {
                self.ui.showError(err.message);
                self.loadMessages(self.currentRoomId);
            }
        };

        var onCancel = function() {};

        this.ui.showEditInput(div, currentText, onSave, onCancel);
    }

    async deleteMessage(id) {
        var self = this;
        this.ui.showConfirmModal(
            'Delete Message',
            'Are you sure you want to delete this message?',
            'Delete',
            'Cancel',
            async function() {
                try {
                    var updatedMsg = await self.api.deleteMessage(id);
                    self.ui.updateMessageElement(
                        updatedMsg,
                        self.config.userId,
                        self.config.isAdmin,
                        function(msgId, msgDiv) { self.editMessage(msgId, msgDiv); },
                        function(msgId) { self.deleteMessage(msgId); },
                        function(messageId, reaction) { self.toggleReaction(messageId, reaction); }
                    );
                    self.ui.showEditSuccess();
                } catch (err) {
                    self.ui.showError(err.message);
                    self.loadMessages(self.currentRoomId);
                }
            }
        );
    }

    async toggleReaction(messageId, reaction) {
        if (this._reactionControllers[messageId]) {
            this._reactionControllers[messageId].abort();
            delete this._reactionControllers[messageId];
        }

        var current = this._userReactions[messageId];
        var willAdd = (current !== reaction);

        var msgDiv = this.ui.elements.messages.querySelector('div[data-message-id="' + messageId + '"]');
        if (!msgDiv) return;

        if (!this.ui.reactionMap[messageId]) this.ui.reactionMap[messageId] = {};
        if (!this.ui.reactionMap[messageId][reaction]) {
            this.ui.reactionMap[messageId][reaction] = new Set();
        }
        if (willAdd) {
            if (current) {
                this.ui.reactionMap[messageId][current].delete(this.config.userId);
                if (this.ui.reactionMap[messageId][current].size === 0) {
                    delete this.ui.reactionMap[messageId][current];
                }
            }
            this.ui.reactionMap[messageId][reaction].add(this.config.userId);
            this._userReactions[messageId] = reaction;
        } else {
            this.ui.reactionMap[messageId][reaction].delete(this.config.userId);
            if (this.ui.reactionMap[messageId][reaction].size === 0) {
                delete this.ui.reactionMap[messageId][reaction];
            }
            delete this._userReactions[messageId];
        }
        this.ui.renderReactions(msgDiv, messageId);

        var controller = new AbortController();
        this._reactionControllers[messageId] = controller;
        try {
            await new Promise((resolve, reject) => {
                var timeout = setTimeout(() => {
                    resolve();
                }, 50);
                controller.signal.addEventListener('abort', function() {
                    clearTimeout(timeout);
                    reject(new Error('Aborted'));
                });
            });

            if (controller.signal.aborted) return;

            if (willAdd) {
                await this.api.addReaction(messageId, reaction);
            } else {
                await this.api.removeReaction(messageId, reaction);
            }
        } catch (err) {
            if (err.message === 'Aborted') return;
            if (err.message && err.message.toLowerCase().includes('not found')) return;
            console.error('Reaction error:', err);
            this.ui.showError('Failed to update reaction');
            this.loadMessages(this.currentRoomId);
        } finally {
            delete this._reactionControllers[messageId];
        }
    }

    async markRoomRead(roomId) {
        var messages = this.ui.elements.messages.querySelectorAll('div[data-message-id]');
        if (messages.length > 0) {
            var lastId = parseInt(messages[messages.length - 1].dataset.messageId);
            try {
                await this.api.markRead(roomId, lastId);
            } catch(e) { /* ignore */ }
        }
    }

    setupTyping() {
        var typingTimeout = null;
        var input = this.ui.elements.messageInput;
        input.addEventListener('input', () => {
            if (this.ws && this.ws.readyState === WebSocket.OPEN && this.currentRoomId) {
                if (!typingTimeout) {
                    this.ws.send(JSON.stringify({
                        type: 'typing',
                        roomId: this.currentRoomId,
                        userName: this.config.userName
                    }));
                }
                clearTimeout(typingTimeout);
                typingTimeout = setTimeout(() => {
                    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                        this.ws.send(JSON.stringify({
                            type: 'stop_typing',
                            roomId: this.currentRoomId
                        }));
                    }
                    typingTimeout = null;
                }, 1000);
            }
        });
    }

    async sendMessage() {
        var msg = this.ui.elements.messageInput.value.trim();
        if (!msg || !this.currentRoomId) return;
        this.ui.elements.sendButton.disabled = true;
        this.ui.elements.sendButton.textContent = 'Sending...';
        try {
            await this.api.postMessage(this.currentRoomId, msg);
            this.ui.elements.messageInput.value = '';
        } catch (err) {
            this.ui.showError(err.message);
        } finally {
            setTimeout(function() {
                this.ui.elements.sendButton.disabled = false;
                this.ui.elements.sendButton.textContent = 'Send';
            }.bind(this), 5000);
        }
    }

    showCreateRoomModal() {
        var modal = document.createElement('div');
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;';

        var panel = document.createElement('div');
        panel.style.cssText = 'background:#fff;padding:20px;border-radius:8px;min-width:400px;max-width:600px;max-height:80vh;overflow:auto;';
        panel.innerHTML = `
            <h3>Create New Group</h3>
            <div style="margin-bottom:10px;">
                <label>Room Name</label>
                <input type="text" id="new-room-name" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;" placeholder="Enter room name">
            </div>
            <div style="margin-bottom:10px;">
                <label>Add Members</label>
                <input type="text" id="user-search" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;" placeholder="Search users by name or email...">
                <div id="user-search-results" style="max-height:150px;overflow-y:auto;border:1px solid #ddd;margin-top:4px;display:none;"></div>
                <div id="selected-users" style="margin-top:8px;display:flex;flex-wrap:wrap;gap:4px;"></div>
            </div>
            <div style="display:flex;gap:10px;margin-top:10px;">
                <button id="modal-create-btn" style="padding:8px 20px;background:#2C2C2C;color:#fff;border:none;border-radius:4px;cursor:pointer;">Create</button>
                <button id="modal-cancel-btn" style="padding:8px 20px;background:#ccc;border:none;border-radius:4px;cursor:pointer;">Cancel</button>
            </div>
        `;
        modal.appendChild(panel);
        document.body.appendChild(modal);

        var nameInput = panel.querySelector('#new-room-name');
        var searchInput = panel.querySelector('#user-search');
        var resultsDiv = panel.querySelector('#user-search-results');
        var selectedDiv = panel.querySelector('#selected-users');
        var selectedUsers = [];

        var renderSelected = () => {
            selectedDiv.innerHTML = selectedUsers.map(u => {
                return '<span style="background:#e0e0e0;padding:2px 8px;border-radius:12px;display:inline-flex;align-items:center;gap:4px;font-size:14px;">' + this.ui.escHtml(u.display_name) + '<span style="cursor:pointer;font-weight:bold;" data-id="' + u.ID + '">×</span></span>';
            }).join('');
            selectedDiv.querySelectorAll('span[data-id]').forEach(el => {
                el.addEventListener('click', (e) => {
                    var id = parseInt(el.dataset.id);
                    var idx = selectedUsers.findIndex(u => u.ID === id);
                    if (idx > -1) {
                        selectedUsers.splice(idx, 1);
                        renderSelected();
                    }
                });
            });
        };

        searchInput.addEventListener('input', async () => {
            var query = searchInput.value.trim();
            if (query.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }
            var users = await this.api.searchUsers(query);
            users = users.filter(u => u.ID !== this.config.userId);
            resultsDiv.style.display = 'block';
            resultsDiv.innerHTML = users.map(u => {
                var already = selectedUsers.some(s => s.ID === u.ID);
                if (already) return '';
                return '<div style="padding:6px;cursor:pointer;border-bottom:1px solid #eee;" data-id="' + u.ID + '" data-name="' + this.ui.escHtml(u.display_name) + '">' + this.ui.escHtml(u.display_name) + ' (' + this.ui.escHtml(u.user_login) + ')</div>';
            }).join('') || '<div style="padding:6px;color:#999;">No users found</div>';
            resultsDiv.querySelectorAll('div[data-id]').forEach(el => {
                el.addEventListener('click', () => {
                    var id = parseInt(el.dataset.id);
                    var name = el.dataset.name;
                    if (!selectedUsers.some(u => u.ID === id)) {
                        selectedUsers.push({ ID: id, display_name: name });
                        renderSelected();
                    }
                    resultsDiv.style.display = 'none';
                    searchInput.value = '';
                });
            });
        });

        document.addEventListener('click', (e) => {
            if (e.target !== searchInput && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });

        panel.querySelector('#modal-create-btn').addEventListener('click', async () => {
            var name = nameInput.value.trim();
            if (!name) {
                alert('Please enter a room name');
                return;
            }
            if (selectedUsers.length === 0) {
                alert('Please select at least one member');
                return;
            }
            var members = selectedUsers.map(u => u.ID);
            try {
                var response = await fetch(this.api.restUrl + '/rooms', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': this.api.nonce
                    },
                    body: JSON.stringify({ name: name, members: members }),
                    credentials: 'include'
                });
                if (response.ok) {
                    var newRoom = await response.json();
                    var newRoomId = newRoom.id;
                    localStorage.setItem('chat_active_room', newRoomId);
                    modal.remove();
                    this.loadRooms();
                } else {
                    var err = await response.json();
                    alert(err.message || 'Failed to create room');
                }
            } catch (err) {
                console.error(err);
                alert('Network error');
            }
        });

        panel.querySelector('#modal-cancel-btn').addEventListener('click', () => {
            modal.remove();
        });
    }

    bindEvents() {
        this.ui.elements.newRoomBtn.addEventListener('click', function() { this.showCreateRoomModal(); }.bind(this));
        this.ui.elements.sendButton.addEventListener('click', function() { this.sendMessage(); }.bind(this));
        this.ui.elements.messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        }.bind(this));
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.ChatConfig !== 'undefined') {
        new ChatApp(window.ChatConfig);
    }
});