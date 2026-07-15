const WebSocket = require('ws');
const express = require('express');
const app = express();
const PORT = 3000;
const WS_PORT = 8080;

process.on('uncaughtException', (err) => console.error('[FATAL]', err));
process.on('unhandledRejection', (reason) => console.error('[FATAL]', reason));

const wss = new WebSocket.Server({ port: WS_PORT });
console.log(`WebSocket server running on ws://localhost:${WS_PORT} (started at ${new Date().toISOString()})`);

const roomClients = new Map();
const userClients = new Map(); // userId -> Set of WebSockets (for notifications)
const messageQueues = new Map();
const pendingDeliveries = new Map();

function broadcastToRoom(roomId, data) {
    const key = String(roomId);
    if (roomClients.has(key) && roomClients.get(key).size > 0) {
        const clients = roomClients.get(key);
        console.log(`[broadcast] Room ${key} has ${clients.size} clients.`);
        clients.forEach(client => {
            if (client.readyState === WebSocket.OPEN) {
                try {
                    client.send(JSON.stringify(data));
                } catch (err) {
                    console.error(`[broadcast] Error: ${err.message}`);
                }
            }
        });
        if (pendingDeliveries.has(key)) {
            clearTimeout(pendingDeliveries.get(key));
            pendingDeliveries.delete(key);
        }
        return;
    }

    console.log(`[broadcast] Room ${key} has no clients. Queuing message.`);
    if (!messageQueues.has(key)) {
        messageQueues.set(key, []);
    }
    messageQueues.get(key).push(data);

    if (!pendingDeliveries.has(key)) {
        const timer = setTimeout(() => {
            console.log(`[broadcast] Attempting to deliver queued messages for room ${key}.`);
            deliverQueuedMessages(key);
            pendingDeliveries.delete(key);
        }, 300);
        pendingDeliveries.set(key, timer);
    }
}

function deliverQueuedMessages(roomId) {
    const key = String(roomId);
    if (roomClients.has(key) && roomClients.get(key).size > 0) {
        if (messageQueues.has(key) && messageQueues.get(key).length > 0) {
            const queue = messageQueues.get(key);
            const clients = roomClients.get(key);
            console.log(`[deliver] Sending ${queue.length} queued messages to ${clients.size} clients.`);
            clients.forEach(client => {
                if (client.readyState === WebSocket.OPEN) {
                    queue.forEach(msg => {
                        try {
                            client.send(JSON.stringify(msg));
                        } catch (err) {
                            console.error(`[deliver] Error: ${err.message}`);
                        }
                    });
                }
            });
            messageQueues.delete(key);
        } else {
            console.log(`[deliver] Room ${key} has clients but no queued messages.`);
        }
    } else {
        console.log(`[deliver] Room ${key} still has no clients. Keeping messages queued.`);
    }
}

function broadcastToAll(data) {
    wss.clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            try {
                client.send(JSON.stringify(data));
            } catch (err) {
                console.error(`[broadcastAll] Error: ${err.message}`);
            }
        }
    });
}

function subscribeClient(ws, roomId, userId) {
    const key = String(roomId);
    if (ws.roomId !== null && roomClients.has(ws.roomId)) {
        roomClients.get(ws.roomId).delete(ws);
    }
    if (!roomClients.has(key)) {
        roomClients.set(key, new Set());
    }
    roomClients.get(key).add(ws);
    ws.roomId = key;
    ws.userId = userId || null;
    console.log(`[ws] Client subscribed to room ${key} (now ${roomClients.get(key).size} clients).`);

    deliverQueuedMessages(key);

    ws.send(JSON.stringify({ type: 'system', message: `Subscribed to room ${key}` }));
}

wss.on('connection', (ws) => {
    console.log('[ws] New client connected.');
    ws.roomId = null;
    ws.userId = null;
    ws.isAlive = true;
    ws.userIdForNotifications = null;

    const interval = setInterval(() => {
        if (ws.readyState === WebSocket.OPEN) {
            ws.ping(() => {});
        } else {
            clearInterval(interval);
        }
    }, 25000);

    ws.on('pong', () => {
        ws.isAlive = true;
    });

    ws.on('message', (data) => {
        try {
            const parsed = JSON.parse(data);
            if (parsed.type === 'subscribe') {
                subscribeClient(ws, parsed.roomId, parsed.userId);
            } else if (parsed.type === 'subscribe_notifications') {
                const userId = parsed.userId;
                if (userId) {
                    if (!userClients.has(userId)) {
                        userClients.set(userId, new Set());
                    }
                    userClients.get(userId).add(ws);
                    ws.userIdForNotifications = userId;
                    ws.send(JSON.stringify({ type: 'system', message: 'Subscribed to notifications' }));
                }
            } else if (parsed.type === 'ping') {
                if (parsed.roomId) {
                    subscribeClient(ws, parsed.roomId, ws.userId);
                }
            } else if (parsed.type === 'typing') {
                if (parsed.roomId) {
                    subscribeClient(ws, parsed.roomId, ws.userId);
                }
                if (ws.roomId) {
                    broadcastToRoom(ws.roomId, {
                        type: 'typing',
                        data: { userId: ws.userId, userName: parsed.userName, isTyping: true }
                    });
                }
            } else if (parsed.type === 'stop_typing') {
                if (parsed.roomId) {
                    subscribeClient(ws, parsed.roomId, ws.userId);
                }
                if (ws.roomId) {
                    broadcastToRoom(ws.roomId, {
                        type: 'typing',
                        data: { userId: ws.userId, isTyping: false }
                    });
                }
            }
        } catch (e) {
            console.error('[ws] Error parsing message:', e);
        }
    });

    ws.on('close', () => {
        clearInterval(interval);
        if (ws.roomId !== null && roomClients.has(ws.roomId)) {
            roomClients.get(ws.roomId).delete(ws);
            console.log(`[ws] Client left room ${ws.roomId} (now ${roomClients.get(ws.roomId).size} clients).`);
        }
        if (ws.userIdForNotifications && userClients.has(ws.userIdForNotifications)) {
            userClients.get(ws.userIdForNotifications).delete(ws);
        }
    });

    ws.on('error', (err) => {
        console.error('[ws] WebSocket error:', err.message);
    });
});

app.use(express.json());

app.post('/new-message', (req, res) => {
    const { message } = req.body;
    if (!message) {
        return res.status(400).json({ error: 'No message' });
    }
    const roomId = String(message.room_id);
    if (roomId) {
        console.log(`[new-message] Broadcasting message ${message.id} to room ${roomId}.`);
        broadcastToRoom(roomId, { type: 'new_message', data: message });
    } else {
        console.log('[new-message] No room_id in message.');
    }
    res.json({ success: true });
});

app.post('/new-room', (req, res) => {
    const { room } = req.body;
    if (!room) {
        return res.status(400).json({ error: 'No room data' });
    }
    broadcastToAll({ type: 'new_room', data: room });
    res.json({ success: true });
});

app.post('/update-message', (req, res) => {
    const { message } = req.body;
    if (!message) return res.status(400).json({ error: 'No message' });
    broadcastToRoom(String(message.room_id), { type: 'update_message', data: message });
    res.json({ success: true });
});

app.post('/delete-message', (req, res) => {
    const { message } = req.body;
    if (!message) return res.status(400).json({ error: 'No message' });
    broadcastToRoom(String(message.room_id), { type: 'delete_message', data: message });
    res.json({ success: true });
});

app.post('/reaction', (req, res) => {
    const { message_id, user_id, reaction, action } = req.body;
    broadcastToAll({ type: 'reaction', data: { message_id, user_id, reaction, action } });
    res.json({ success: true });
});

app.post('/read', (req, res) => {
    const { room_id, user_id, last_message_id } = req.body;
    broadcastToRoom(String(room_id), { type: 'read', data: { room_id, user_id, last_message_id } });
    res.json({ success: true });
});

app.post('/member-added', (req, res) => {
    const { room_id, user_id } = req.body;
    broadcastToRoom(String(room_id), { type: 'member_added', data: { room_id, user_id } });
    res.json({ success: true });
});

app.post('/member-removed', (req, res) => {
    const { room_id, user_id } = req.body;
    broadcastToRoom(String(room_id), { type: 'member_removed', data: { room_id, user_id } });
    res.json({ success: true });
});

// Notification endpoint
app.post('/new-notification', (req, res) => {
    const { user_id, notification } = req.body;
    if (!user_id || !notification) {
        return res.status(400).json({ error: 'Missing user_id or notification' });
    }
    const userId = String(user_id);
    if (userClients.has(userId)) {
        userClients.get(userId).forEach(client => {
            if (client.readyState === WebSocket.OPEN) {
                try {
                    client.send(JSON.stringify({ type: 'notification', data: notification }));
                } catch (err) {
                    console.error(`[notif] Error sending to user ${userId}: ${err.message}`);
                }
            }
        });
    }
    res.json({ success: true });
});

app.listen(PORT, () => {
    console.log(`Express server listening on port ${PORT}`);
});