class ChatUI {
    constructor() {
        this.elements = {
            messages: document.getElementById('chat-messages'),
            messageInput: document.getElementById('message-input'),
            sendButton: document.getElementById('send-btn'),
            roomList: document.getElementById('rooms'),
            newRoomBtn: document.getElementById('new-room-btn'),
            newDirectBtn: document.getElementById('new-direct-btn'),
            roomHeader: document.getElementById('room-header'),
        };
        this.elements.messages.style.paddingTop = '40px';
        this.typingIndicator = null;
        this.reactionMap = {};
        this._typingTimeout = null;
        this.userId = window.ChatConfig ? window.ChatConfig.userId : null;
        this.currentRoomId = null;
    }

    escHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    scrollToBottom() {
        this.elements.messages.scrollTop = this.elements.messages.scrollHeight;
    }

    clearMessages() {
        this.elements.messages.innerHTML = '';
        this.reactionMap = {};
    }

    setRoomHeader(name) {
        this.elements.roomHeader.textContent = name || 'Select a room';
    }

    renderRoomList(rooms, currentRoomId, onSwitch) {
        this.elements.roomList.innerHTML = '';
        rooms.forEach(function(room) {
            var li = document.createElement('li');
            var label = room.name || 'Room ' + room.id;
            if (room.type === 'direct') {
                label = '💬 ' + label;
            }
            li.textContent = label;
            li.dataset.roomId = room.id;
            li.className = 'room-item';
            li.style.cssText = 'cursor:pointer; padding:8px 12px; margin:4px 0; border-radius:6px; transition:all 0.2s; border-left:5px solid transparent; background:transparent; font-weight:normal;';
            li.addEventListener('click', function() { onSwitch(room.id); });
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
            this.elements.roomList.appendChild(li);
        }.bind(this));
        this.highlightRoom(currentRoomId);
    }

    highlightRoom(roomId) {
        if (!roomId) return;
        var targetId = Number(roomId);
        var items = this.elements.roomList.querySelectorAll('.room-item');
        items.forEach(function(li) {
            var liId = Number(li.dataset.roomId);
            var isActive = (liId === targetId);
            li.classList.toggle('active', isActive);
            li.style.fontWeight = isActive ? 'bold' : 'normal';
            li.style.backgroundColor = isActive ? '#d0d0d0' : 'transparent';
            li.style.borderLeftColor = isActive ? '#2C2C2C' : 'transparent';
            li.style.boxShadow = isActive ? 'inset 0 0 0 1px #ccc' : 'none';
        });
    }

    showLoadingMessages() {
        this.elements.messages.innerHTML = '<div style="display:flex;justify-content:center;align-items:center;height:100%;color:#999;font-size:1.2rem;">Loading messages...</div>';
    }

    showTypingIndicator(data) {
        if (!this.typingIndicator) {
            this.typingIndicator = document.createElement('div');
            this.typingIndicator.style.cssText = 'padding:4px 10px; color:#999; font-style:italic; font-size:0.9rem;';
            this.elements.messages.appendChild(this.typingIndicator);
        }
        if (data.isTyping) {
            this.typingIndicator.textContent = data.userName + ' is typing...';
        } else {
            clearTimeout(this._typingTimeout);
            this._typingTimeout = setTimeout(() => {
                if (this.typingIndicator) {
                    this.typingIndicator.textContent = '';
                }
            }, 3000);
        }
    }

    updateReadReceipts(data) {
        var readDiv = this.elements.messages.querySelector('.chat-read-receipt');
        if (!readDiv) {
            readDiv = document.createElement('div');
            readDiv.className = 'chat-read-receipt';
            readDiv.style.cssText = 'text-align:right; font-size:0.8rem; color:#999; padding:4px 10px;';
            this.elements.messages.appendChild(readDiv);
        }
        readDiv.textContent = '✓ Seen';
    }

    markMessagesRead(roomId, userId, lastMessageId) {
        if (parseInt(userId) === parseInt(this.userId)) return;
        if (parseInt(roomId) !== parseInt(this.currentRoomId)) return;

        var messages = this.elements.messages.querySelectorAll('div[data-message-id]');
        var ownMessages = [];
        messages.forEach(function(div) {
            var senderId = parseInt(div.dataset.userId);
            if (senderId === this.userId) {
                ownMessages.push(div);
            }
        }.bind(this));

        ownMessages.sort(function(a, b) {
            return parseInt(b.dataset.messageId) - parseInt(a.dataset.messageId);
        });

        ownMessages.forEach(function(div) {
            var statusSpan = div.querySelector('.read-status');
            if (statusSpan) {
                statusSpan.remove();
            }
        });

        if (ownMessages.length > 0) {
            var latestDiv = ownMessages[0];
            var statusSpan = document.createElement('span');
            statusSpan.className = 'read-status';
            statusSpan.style.cssText = 'margin-left:8px; font-size:0.8rem; color:#4fc3f7;';
            statusSpan.textContent = '✓✓';
            latestDiv.appendChild(statusSpan);
        }
    }

    markMessagesReadUpTo(lastReadMessageId) {
        var messages = this.elements.messages.querySelectorAll('div[data-message-id]');
        messages.forEach(function(div) {
            var id = parseInt(div.dataset.messageId);
            if (id <= lastReadMessageId) {
                var readMark = div.querySelector('.read-mark');
                if (!readMark) {
                    readMark = document.createElement('span');
                    readMark.className = 'read-mark';
                    readMark.style.cssText = 'margin-left:6px; color:#0a7e3c; font-size:0.8rem;';
                    readMark.textContent = '✓';
                    var timeSpan = div.querySelector('span[style*="color:#999;font-size:0.8rem;"]');
                    if (timeSpan) {
                        timeSpan.parentNode.insertBefore(readMark, timeSpan.nextSibling);
                    } else {
                        div.appendChild(readMark);
                    }
                }
            }
        });
    }

    updateReaction(data) {
        var msgDiv = this.elements.messages.querySelector('div[data-message-id="' + data.message_id + '"]');
        if (!msgDiv) return;
        var reactionContainer = msgDiv.querySelector('.reaction-container');
        if (!reactionContainer) {
            reactionContainer = document.createElement('div');
            reactionContainer.className = 'reaction-container';
            reactionContainer.style.cssText = 'margin-top:2px; display:flex; gap:4px; flex-wrap:wrap;';
            msgDiv.appendChild(reactionContainer);
        }
        if (!this.reactionMap[data.message_id]) this.reactionMap[data.message_id] = {};
        if (!this.reactionMap[data.message_id][data.reaction]) {
            this.reactionMap[data.message_id][data.reaction] = new Set();
        }
        if (data.action === 'add') {
            this.reactionMap[data.message_id][data.reaction].add(data.user_id);
        } else {
            this.reactionMap[data.message_id][data.reaction].delete(data.user_id);
            if (this.reactionMap[data.message_id][data.reaction].size === 0) {
                delete this.reactionMap[data.message_id][data.reaction];
            }
        }
        this.renderReactions(msgDiv, data.message_id);
    }

    renderReactions(msgDiv, messageId) {
        var container = msgDiv.querySelector('.reaction-container');
        if (!container) return;
        var reactions = this.reactionMap[messageId] || {};
        container.innerHTML = '';
        for (var reaction in reactions) {
            if (reactions[reaction].size > 0) {
                var span = document.createElement('span');
                span.className = 'reaction-pill';
                span.dataset.messageId = messageId;
                span.dataset.reaction = reaction;
                var hasUser = reactions[reaction].has(this.userId);
                if (hasUser) span.classList.add('active');
                span.style.cssText = `
                    background: ${hasUser ? '#d4e3fd' : '#f0f0f0'};
                    border-radius: 12px;
                    padding: 2px 8px;
                    font-size: 0.9rem;
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    cursor: pointer;
                    transition: background 0.15s;
                    border: 1px solid ${hasUser ? '#8ab4f8' : 'transparent'};
                `;
                span.textContent = reaction + ' ' + reactions[reaction].size;
                span.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (window.ChatApp && window.ChatApp.toggleReaction) {
                        window.ChatApp.toggleReaction(messageId, reaction);
                    }
                });
                container.appendChild(span);
            }
        }
    }

    renderExistingReactions(messageId, reactions) {
        var msgDiv = this.elements.messages.querySelector('div[data-message-id="' + messageId + '"]');
        if (!msgDiv) return;
        var container = msgDiv.querySelector('.reaction-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'reaction-container';
            container.style.cssText = 'margin-top:2px; display:flex; gap:4px; flex-wrap:wrap;';
            msgDiv.appendChild(container);
        }
        if (!this.reactionMap[messageId]) this.reactionMap[messageId] = {};
        reactions.forEach(function(r) {
            this.reactionMap[messageId][r.reaction] = new Set();
            for (var i = 0; i < r.count; i++) {
                this.reactionMap[messageId][r.reaction].add('user_' + i);
            }
        }.bind(this));
        this.renderReactions(msgDiv, messageId);
    }

    appendMessage(msg, userId, isAdmin, onEdit, onDelete, onReaction) {
        var div = document.createElement('div');
        div.dataset.messageId = msg.id;
        div.dataset.userId = msg.user_id;
        div.style.marginBottom = '12px';
        div.style.position = 'relative';
        div.style.padding = '4px 8px';
        div.style.borderRadius = '6px';
        div.style.transition = 'background 0.15s';

        var time = new Date(msg.created_at).toLocaleTimeString();
        var content = msg.message;
        if (msg.deleted_at) {
            if (isAdmin) {
                content = '<span style="color:#999;text-decoration:line-through;">' + this.escHtml(msg.message) + '</span> <span style="color:#d63638;">(Deleted)</span>';
            } else {
                content = '<span style="color:#999;font-style:italic;">Message deleted</span>';
            }
        } else if (msg.edited_at) {
            content = this.escHtml(msg.message) + ' <span style="color:#999;font-size:0.7rem;">(edited)</span>';
        } else {
            content = this.escHtml(msg.message);
        }
        var isOwner = (msg.user_id == userId);
        var canEdit = isOwner || isAdmin;
        var actions = canEdit && !msg.deleted_at ? '<span style="float:right;font-size:0.8rem;"><a href="#" class="chat-edit" data-id="' + msg.id + '" style="margin-right:5px;">✎</a><a href="#" class="chat-delete" data-id="' + msg.id + '">✕</a></span>' : '';
        div.innerHTML = '<strong>' + this.escHtml(msg.user_name) + '</strong> <span style="color:#999;font-size:0.8rem;">' + time + '</span>' + actions + ': ' + content;

        var reactionBar = document.createElement('div');
        reactionBar.className = 'reaction-bar';
        reactionBar.style.cssText = `
            position: absolute;
            bottom: 100%;
            left: 0;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 4px 8px;
            display: flex;
            gap: 4px;
            opacity: 0;
            transition: opacity 0.2s, transform 0.2s;
            transform: translateY(4px);
            pointer-events: none;
            z-index: 10;
        `;
        var emojis = ['👍', '❤️', '😂', '😮', '😢', '😡'];
        emojis.forEach(function(emoji) {
            var btn = document.createElement('button');
            btn.textContent = emoji;
            btn.type = 'button';
            btn.style.cssText = `
                border: none;
                background: transparent;
                cursor: pointer;
                font-size: 1.2rem;
                padding: 2px 6px;
                border-radius: 4px;
                transition: background 0.15s;
            `;
            btn.addEventListener('mouseenter', function() { this.style.background = '#e8e8e8'; });
            btn.addEventListener('mouseleave', function() { this.style.background = 'transparent'; });
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                if (onReaction) onReaction(msg.id, emoji);
            });
            reactionBar.appendChild(btn);
        });
        div.appendChild(reactionBar);

        var reactionContainer = document.createElement('div');
        reactionContainer.className = 'reaction-container';
        reactionContainer.style.cssText = 'margin-top:2px; display:flex; gap:4px; flex-wrap:wrap;';
        div.appendChild(reactionContainer);

        div.addEventListener('mouseenter', function() {
            reactionBar.style.opacity = '1';
            reactionBar.style.transform = 'translateY(0)';
            reactionBar.style.pointerEvents = 'auto';
        });
        div.addEventListener('mouseleave', function() {
            reactionBar.style.opacity = '0';
            reactionBar.style.transform = 'translateY(4px)';
            reactionBar.style.pointerEvents = 'none';
        });

        this.elements.messages.appendChild(div);
        this.scrollToBottom();

        div.querySelectorAll('.chat-edit').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                onEdit(parseInt(el.dataset.id), div);
            });
        });
        div.querySelectorAll('.chat-delete').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                onDelete(parseInt(el.dataset.id));
            });
        });
        return div;
    }

    updateMessageElement(msg, userId, isAdmin, onEdit, onDelete, onReaction) {
        var div = this.elements.messages.querySelector('div[data-message-id="' + msg.id + '"]');
        if (!div) return;

        div.dataset.userId = msg.user_id;

        var time = new Date(msg.created_at).toLocaleTimeString();
        var content = msg.message;
        if (msg.deleted_at) {
            if (isAdmin) {
                content = '<span style="color:#999;text-decoration:line-through;">' + this.escHtml(msg.message) + '</span> <span style="color:#d63638;">(Deleted)</span>';
            } else {
                content = '<span style="color:#999;font-style:italic;">Message deleted</span>';
            }
        } else if (msg.edited_at) {
            content = this.escHtml(msg.message) + ' <span style="color:#999;font-size:0.7rem;">(edited)</span>';
        } else {
            content = this.escHtml(msg.message);
        }

        var isOwner = (msg.user_id == userId);
        var canEdit = isOwner || isAdmin;
        var actions = canEdit && !msg.deleted_at ? '<span style="float:right;font-size:0.8rem;"><a href="#" class="chat-edit" data-id="' + msg.id + '" style="margin-right:5px;">✎</a><a href="#" class="chat-delete" data-id="' + msg.id + '">✕</a></span>' : '';

        div.innerHTML = '<strong>' + this.escHtml(msg.user_name) + '</strong> <span style="color:#999;font-size:0.8rem;">' + time + '</span>' + actions + ': ' + content;

        var existingStatus = div.querySelector('.read-status');
        if (existingStatus) {
            existingStatus.remove();
        }

        var reactionBar = div.querySelector('.reaction-bar');
        if (!reactionBar) {
            reactionBar = document.createElement('div');
            reactionBar.className = 'reaction-bar';
            reactionBar.style.cssText = `
                position: absolute;
                bottom: 100%;
                left: 0;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                padding: 4px 8px;
                display: flex;
                gap: 4px;
                opacity: 0;
                transition: opacity 0.2s, transform 0.2s;
                transform: translateY(4px);
                pointer-events: none;
                z-index: 10;
            `;
            var emojis = ['👍', '❤️', '😂', '😮', '😢', '😡'];
            emojis.forEach(function(emoji) {
                var btn = document.createElement('button');
                btn.textContent = emoji;
                btn.type = 'button';
                btn.style.cssText = `
                    border: none;
                    background: transparent;
                    cursor: pointer;
                    font-size: 1.2rem;
                    padding: 2px 6px;
                    border-radius: 4px;
                    transition: background 0.15s;
                `;
                btn.addEventListener('mouseenter', function() { this.style.background = '#e8e8e8'; });
                btn.addEventListener('mouseleave', function() { this.style.background = 'transparent'; });
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    if (onReaction) onReaction(msg.id, emoji);
                });
                reactionBar.appendChild(btn);
            });
            div.appendChild(reactionBar);
        }

        var reactionContainer = div.querySelector('.reaction-container');
        if (!reactionContainer) {
            reactionContainer = document.createElement('div');
            reactionContainer.className = 'reaction-container';
            reactionContainer.style.cssText = 'margin-top:2px; display:flex; gap:4px; flex-wrap:wrap;';
            div.appendChild(reactionContainer);
        }

        div.querySelectorAll('.chat-edit').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                onEdit(parseInt(el.dataset.id), div);
            });
        });
        div.querySelectorAll('.chat-delete').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                onDelete(parseInt(el.dataset.id));
            });
        });

        this.scrollToBottom();
    }

    showEditInput(messageDiv, currentText, onSave, onCancel) {
        messageDiv._originalHtml = messageDiv.innerHTML;
        var editContainer = document.createElement('div');
        editContainer.style.cssText = 'margin-top:4px;';
        var input = document.createElement('input');
        input.type = 'text';
        input.value = currentText;
        input.style.cssText = 'width:100%;padding:6px 10px;border:1px solid #ccc;border-radius:4px;font-size:14px;';
        var buttonContainer = document.createElement('div');
        buttonContainer.style.cssText = 'margin-top:4px;display:flex;gap:8px;';
        var saveBtn = document.createElement('button');
        saveBtn.textContent = 'Save';
        saveBtn.style.cssText = 'padding:4px 16px;background:#2C2C2C;color:#fff;border:none;border-radius:4px;cursor:pointer;';
        var cancelBtn = document.createElement('button');
        cancelBtn.textContent = 'Cancel';
        cancelBtn.style.cssText = 'padding:4px 16px;background:#ccc;color:#333;border:none;border-radius:4px;cursor:pointer;';
        buttonContainer.appendChild(saveBtn);
        buttonContainer.appendChild(cancelBtn);
        editContainer.appendChild(input);
        editContainer.appendChild(buttonContainer);
        messageDiv.innerHTML = '';
        messageDiv.appendChild(editContainer);
        input.focus();
        input.select();

        var handleSave = function() {
            var newText = input.value.trim();
            if (newText && newText !== currentText) {
                onSave(newText);
            } else {
                onCancel();
            }
        };

        var handleCancel = function() {
            messageDiv.innerHTML = messageDiv._originalHtml || '';
            onCancel();
        };

        saveBtn.addEventListener('click', handleSave);
        cancelBtn.addEventListener('click', handleCancel);
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleSave();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                handleCancel();
            }
        });
    }

    showEditSuccess() {
        var notification = document.createElement('div');
        notification.style.cssText = 'position:fixed;bottom:80px;right:20px;background:#0a7e3c;color:#fff;padding:12px 24px;border-radius:6px;font-size:14px;box-shadow:0 2px 10px rgba(0,0,0,0.2);z-index:10000;opacity:0;transition:opacity 0.3s;';
        notification.textContent = '✓ Message updated';
        document.body.appendChild(notification);
        requestAnimationFrame(function() {
            notification.style.opacity = '1';
        });
        setTimeout(function() {
            notification.style.opacity = '0';
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 2000);
    }

    showConfirmModal(title, message, confirmText, cancelText, onConfirm) {
        var overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10001;';
        var modal = document.createElement('div');
        modal.style.cssText = 'background:#fff;padding:24px;border-radius:8px;min-width:320px;max-width:400px;box-shadow:0 4px 20px rgba(0,0,0,0.3);text-align:center;';
        modal.innerHTML = `
            <h3 style="margin:0 0 8px 0;">${this.escHtml(title)}</h3>
            <p style="margin:0 0 20px 0;color:#555;">${this.escHtml(message)}</p>
            <div style="display:flex;gap:12px;justify-content:center;">
                <button id="modal-cancel" style="padding:8px 20px;background:#ccc;color:#333;border:none;border-radius:4px;cursor:pointer;">${this.escHtml(cancelText || 'Cancel')}</button>
                <button id="modal-confirm" style="padding:8px 20px;background:#d63638;color:#fff;border:none;border-radius:4px;cursor:pointer;">${this.escHtml(confirmText || 'Delete')}</button>
            </div>
        `;
        overlay.appendChild(modal);
        document.body.appendChild(overlay);
        overlay.querySelector('#modal-cancel').addEventListener('click', function() { overlay.remove(); });
        overlay.querySelector('#modal-confirm').addEventListener('click', function() {
            overlay.remove();
            onConfirm();
        });
    }

    removeMessageElement(messageId, msg) {
        var div = this.elements.messages.querySelector('div[data-message-id="' + messageId + '"]');
        if (!div) return;
        var userId = window.ChatConfig.userId;
        var isAdmin = window.ChatConfig.isAdmin;
        var onEdit = function() {};
        var onDelete = function() {};
        var onReaction = function() {};
        this.updateMessageElement(msg, userId, isAdmin, onEdit, onDelete, onReaction);
    }

    getMessageText(div) {
        var text = div.textContent;
        text = text.replace(/^[^:]+:\s*/, '');
        text = text.replace(/\d{1,2}:\d{2}(:\d{2})?\s*(AM|PM)?\s*/, '');
        text = text.replace(/✎✕/g, '');
        text = text.replace(/\(edited\)/g, '');
        text = text.replace(/\(Deleted\)/g, '');
        text = text.replace(/✓✓?/g, '');
        return text.trim();
    }

    showNoRooms() {
        this.elements.messages.innerHTML = '<p>No rooms yet. Create one!</p>';
        this.elements.roomHeader.textContent = 'No rooms';
    }

    showLoadingRooms() {
        this.elements.messages.innerHTML = '<p>Loading rooms...</p>';
    }

    showError(message) {
        alert(message);
    }
}

window.ChatUI = ChatUI;