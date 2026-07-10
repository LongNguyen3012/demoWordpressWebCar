(function() {
    const { restUrl, nonce, websocketUrl, userId, userName, isAdmin } = chatSettings;
    const messagesContainer = document.getElementById('chat-messages');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-btn');
    const roomList = document.getElementById('rooms');
    const newRoomBtn = document.getElementById('new-room-btn');
    const roomHeader = document.getElementById('room-header');

    let currentRoomId = null;
    let ws = null;
    let activeRoomId = localStorage.getItem('chat_active_room') ? parseInt(localStorage.getItem('chat_active_room')) : null;
    let loadingMessages = false;
    let messageLoadTimeout = null;
    let roomData = {};

    function connectWebSocket() {
        ws = new WebSocket(websocketUrl);
        ws.onopen = () => console.log('Connected to chat server');
        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (data.type === 'new_message') {
                if (data.data.room_id === currentRoomId) {
                    appendMessage(data.data);
                }
            } else if (data.type === 'new_room') {
                loadRooms();
            } else if (data.type === 'update_message') {
                if (data.data.room_id === currentRoomId) {
                    updateMessageElement(data.data);
                }
            } else if (data.type === 'delete_message') {
                if (data.data.room_id === currentRoomId) {
                    removeMessageElement(data.data.id, data.data);
                }
            }
        };
        ws.onerror = (error) => console.error('WebSocket error:', error);
        ws.onclose = () => {
            setTimeout(connectWebSocket, 3000);
        };
    }

    function subscribeToRoom(roomId) {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({ type: 'subscribe', roomId: roomId }));
        }
        currentRoomId = roomId;
        localStorage.setItem('chat_active_room', roomId);
    }

    async function loadRooms() {
        try {
            const response = await fetch(restUrl + '/rooms', {
                headers: { 'X-WP-Nonce': nonce },
                credentials: 'include',
            });
            if (!response.ok) throw new Error('Failed to load rooms');
            const rooms = await response.json();
            roomList.innerHTML = '';
            rooms.forEach(room => {
                roomData[room.id] = room;
                const li = document.createElement('li');
                li.textContent = room.name || 'Room ' + room.id;
                li.dataset.roomId = room.id;
                li.style.cssText = `
                    cursor: pointer;
                    padding: 8px 12px;
                    margin: 4px 0;
                    border-radius: 6px;
                    transition: all 0.2s;
                    border-left: 5px solid transparent;
                    background: transparent;
                    font-weight: normal;
                `;
                li.addEventListener('click', () => {
                    switchRoom(room.id);
                });
                li.addEventListener('mouseenter', function() {
                    if (parseInt(this.dataset.roomId) !== currentRoomId) {
                        this.style.backgroundColor = '#e8e8e8';
                    }
                });
                li.addEventListener('mouseleave', function() {
                    if (parseInt(this.dataset.roomId) !== currentRoomId) {
                        this.style.backgroundColor = 'transparent';
                    }
                });
                roomList.appendChild(li);
            });
            if (rooms.length > 0) {
                let targetRoom = activeRoomId && rooms.some(r => r.id === activeRoomId) ? activeRoomId : rooms[0].id;
                switchRoom(targetRoom);
            } else {
                messagesContainer.innerHTML = '<p>No rooms yet. Create one!</p>';
                roomHeader.textContent = 'No rooms';
            }
        } catch (err) {
            console.error(err);
        }
    }

    function loadMessages(roomId) {
        if (loadingMessages) return;
        loadingMessages = true;
        clearTimeout(messageLoadTimeout);
        messageLoadTimeout = setTimeout(async () => {
            try {
                const response = await fetch(restUrl + '/rooms/' + roomId + '/messages?limit=50', {
                    headers: { 'X-WP-Nonce': nonce },
                    credentials: 'include',
                });
                if (!response.ok) throw new Error('Failed to load messages');
                const messages = await response.json();
                messagesContainer.innerHTML = '';
                messages.forEach(msg => appendMessage(msg));
                scrollToBottom();
            } catch (err) {
                console.error(err);
            } finally {
                loadingMessages = false;
            }
        }, 100);
    }

    function switchRoom(roomId) {
        if (currentRoomId === roomId) return;
        subscribeToRoom(roomId);
        loadMessages(roomId);

        // Update room header
        const room = roomData[roomId];
        roomHeader.textContent = room ? (room.name || 'Room ' + room.id) : 'Room';

        // Update room list highlighting
        roomList.querySelectorAll('li').forEach(li => {
            const isActive = (parseInt(li.dataset.roomId) === roomId);
            li.style.fontWeight = isActive ? 'bold' : 'normal';
            li.style.backgroundColor = isActive ? '#d0d0d0' : 'transparent';
            li.style.borderLeftColor = isActive ? '#2C2C2C' : 'transparent';
            li.style.borderLeftWidth = isActive ? '5px' : '5px';
            if (isActive) {
                li.style.boxShadow = 'inset 0 0 0 1px #ccc';
            } else {
                li.style.boxShadow = 'none';
            }
        });
    }

    async function sendMessage(message) {
        sendButton.disabled = true;
        sendButton.textContent = 'Sending...';
        try {
            const response = await fetch(restUrl + '/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce,
                },
                body: JSON.stringify({ room_id: currentRoomId, message: message }),
                credentials: 'include',
            });
            if (!response.ok) {
                const errorData = await response.json();
                alert(errorData.message || 'Failed to send message');
            } else {
                messageInput.value = '';
            }
        } catch (err) {
            console.error(err);
            alert('Network error');
        } finally {
            setTimeout(() => {
                sendButton.disabled = false;
                sendButton.textContent = 'Send';
            }, 5000);
        }
    }

    function appendMessage(msg) {
        const div = document.createElement('div');
        div.dataset.messageId = msg.id;
        div.style.marginBottom = '8px';
        const time = new Date(msg.created_at).toLocaleTimeString();
        let content = msg.message;
        if (msg.deleted_at) {
            if (isAdmin) {
                content = `<span style="color:#999;text-decoration:line-through;">${escHtml(msg.message)}</span> <span style="color:#d63638;">(Deleted)</span>`;
            } else {
                content = '<span style="color:#999;font-style:italic;">Message deleted</span>';
            }
        } else if (msg.edited_at) {
            content = escHtml(msg.message) + ' <span style="color:#999;font-size:0.7rem;">(edited)</span>';
        } else {
            content = escHtml(msg.message);
        }
        const isOwner = (parseInt(msg.user_id) === parseInt(userId));
        const canEdit = isOwner || isAdmin;
        const actions = canEdit && !msg.deleted_at ? `<span style="float:right;font-size:0.8rem;">
            <a href="#" class="chat-edit" data-id="${msg.id}" style="margin-right:5px;">✎</a>
            <a href="#" class="chat-delete" data-id="${msg.id}">✕</a>
        </span>` : '';
        div.innerHTML = `<strong>${escHtml(msg.user_name)}</strong> <span style="color:#999;font-size:0.8rem;">${time}</span>${actions}: ${content}`;
        messagesContainer.appendChild(div);
        scrollToBottom();
        div.querySelectorAll('.chat-edit').forEach(el => {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                const id = parseInt(this.dataset.id);
                editMessage(id, div);
            });
        });
        div.querySelectorAll('.chat-delete').forEach(el => {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                const id = parseInt(this.dataset.id);
                if (confirm('Delete this message?')) {
                    deleteMessage(id);
                }
            });
        });
    }

    function updateMessageElement(msg) {
        const div = messagesContainer.querySelector(`div[data-message-id="${msg.id}"]`);
        if (!div) return;
        const oldActions = div.querySelector('span[style*="float:right"]');
        if (oldActions) oldActions.remove();
        const textNodes = [];
        div.childNodes.forEach(node => {
            if (node.nodeType === 3) textNodes.push(node);
        });
        textNodes.forEach(node => node.remove());
        let content = msg.message;
        if (msg.deleted_at) {
            if (isAdmin) {
                content = `<span style="color:#999;text-decoration:line-through;">${escHtml(msg.message)}</span> <span style="color:#d63638;">(Deleted)</span>`;
            } else {
                content = '<span style="color:#999;font-style:italic;">Message deleted</span>';
            }
        } else if (msg.edited_at) {
            content = escHtml(msg.message) + ' <span style="color:#999;font-size:0.7rem;">(edited)</span>';
        } else {
            content = escHtml(msg.message);
        }
        const isOwner = (parseInt(msg.user_id) === parseInt(userId));
        const canEdit = isOwner || isAdmin;
        if (canEdit && !msg.deleted_at) {
            const actions = document.createElement('span');
            actions.style.cssText = 'float:right;font-size:0.8rem;';
            actions.innerHTML = `<a href="#" class="chat-edit" data-id="${msg.id}" style="margin-right:5px;">✎</a>
                                 <a href="#" class="chat-delete" data-id="${msg.id}">✕</a>`;
            div.appendChild(actions);
            actions.querySelector('.chat-edit').addEventListener('click', function(e) {
                e.preventDefault();
                editMessage(msg.id, div);
            });
            actions.querySelector('.chat-delete').addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Delete this message?')) {
                    deleteMessage(msg.id);
                }
            });
        }
        div.innerHTML += content;
        scrollToBottom();
    }

    function removeMessageElement(messageId, msg) {
        const div = messagesContainer.querySelector(`div[data-message-id="${messageId}"]`);
        if (!div) return;
        if (isAdmin) {
            updateMessageElement(msg);
        } else {
            div.innerHTML = `<span style="color:#999;font-style:italic;">Message deleted</span>`;
        }
    }

    async function editMessage(id, div) {
        const currentText = div.textContent.replace(/\(edited\)/g, '').replace(/✎✕/g, '').trim();
        const newText = prompt('Edit message:', currentText);
        if (newText === null || newText === currentText) return;
        try {
            const response = await fetch(restUrl + '/messages/' + id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce,
                },
                body: JSON.stringify({ message: newText }),
                credentials: 'include',
            });
            if (!response.ok) {
                const err = await response.json();
                alert(err.message || 'Failed to update');
            }
        } catch (err) {
            console.error(err);
            alert('Network error');
        }
    }

    async function deleteMessage(id) {
        try {
            const response = await fetch(restUrl + '/messages/' + id, {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': nonce,
                },
                credentials: 'include',
            });
            if (!response.ok) {
                const err = await response.json();
                alert(err.message || 'Failed to delete');
            }
        } catch (err) {
            console.error(err);
            alert('Network error');
        }
    }

    function escHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    async function searchUsers(query) {
        if (!query || query.length < 2) return [];
        const response = await fetch(restUrl + '/users?search=' + encodeURIComponent(query), {
            headers: { 'X-WP-Nonce': nonce },
            credentials: 'include',
        });
        if (!response.ok) return [];
        return await response.json();
    }

    function showCreateRoomModal() {
        const modal = document.createElement('div');
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;';
        const panel = document.createElement('div');
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

        const nameInput = panel.querySelector('#new-room-name');
        const searchInput = panel.querySelector('#user-search');
        const resultsDiv = panel.querySelector('#user-search-results');
        const selectedDiv = panel.querySelector('#selected-users');
        const selectedUsers = [];

        function renderSelected() {
            selectedDiv.innerHTML = selectedUsers.map(u => 
                `<span style="background:#e0e0e0;padding:2px 8px;border-radius:12px;display:inline-flex;align-items:center;gap:4px;font-size:14px;">
                    ${escHtml(u.display_name)}
                    <span style="cursor:pointer;font-weight:bold;" data-id="${u.ID}">×</span>
                </span>`
            ).join('');
            selectedDiv.querySelectorAll('span[data-id]').forEach(el => {
                el.addEventListener('click', function(e) {
                    const id = parseInt(this.dataset.id);
                    const idx = selectedUsers.findIndex(u => u.ID === id);
                    if (idx > -1) {
                        selectedUsers.splice(idx, 1);
                        renderSelected();
                    }
                });
            });
        }

        searchInput.addEventListener('input', async function() {
            const query = this.value.trim();
            if (query.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }
            const users = await searchUsers(query);
            resultsDiv.style.display = 'block';
            resultsDiv.innerHTML = users.map(u => {
                const already = selectedUsers.some(s => s.ID === u.ID);
                if (already) return '';
                return `<div style="padding:6px;cursor:pointer;border-bottom:1px solid #eee;" data-id="${u.ID}" data-name="${escHtml(u.display_name)}">${escHtml(u.display_name)} (${escHtml(u.user_login)})</div>`;
            }).join('') || '<div style="padding:6px;color:#999;">No users found</div>';
            resultsDiv.querySelectorAll('div[data-id]').forEach(el => {
                el.addEventListener('click', function() {
                    const id = parseInt(this.dataset.id);
                    const name = this.dataset.name;
                    if (!selectedUsers.some(u => u.ID === id)) {
                        selectedUsers.push({ ID: id, display_name: name });
                        renderSelected();
                    }
                    resultsDiv.style.display = 'none';
                    searchInput.value = '';
                });
            });
        });

        document.addEventListener('click', function(e) {
            if (e.target !== searchInput && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });

        panel.querySelector('#modal-create-btn').addEventListener('click', async function() {
            const name = nameInput.value.trim();
            if (!name) {
                alert('Please enter a room name');
                return;
            }
            if (selectedUsers.length === 0) {
                alert('Please select at least one member');
                return;
            }
            const members = selectedUsers.map(u => u.ID);
            try {
                const response = await fetch(restUrl + '/rooms', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': nonce,
                    },
                    body: JSON.stringify({ name: name, members: members }),
                    credentials: 'include',
                });
                if (response.ok) {
                    modal.remove();
                    loadRooms();
                } else {
                    const err = await response.json();
                    alert(err.message || 'Failed to create room');
                }
            } catch (err) {
                console.error(err);
                alert('Network error');
            }
        });

        panel.querySelector('#modal-cancel-btn').addEventListener('click', function() {
            modal.remove();
        });
    }

    newRoomBtn.addEventListener('click', showCreateRoomModal);

    sendButton.addEventListener('click', () => {
        const msg = messageInput.value.trim();
        if (msg && currentRoomId) sendMessage(msg);
    });
    messageInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendButton.click();
        }
    });

    connectWebSocket();
    loadRooms();
})();