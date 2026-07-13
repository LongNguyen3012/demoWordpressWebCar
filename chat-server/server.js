const WebSocket = require('ws');
const express = require('express');
const axios = require('axios');
const app = express();
const PORT = 3000;
const WS_PORT = 8080;

const wss = new WebSocket.Server({ port: WS_PORT });
console.log(`WebSocket server running on ws://localhost:${WS_PORT}`);

const roomClients = new Map();

function broadcastToRoom(roomId, data) {
    if (!roomClients.has(roomId)) return;
    roomClients.get(roomId).forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify(data));
        }
    });
}

function broadcastToAll(data) {
    wss.clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify(data));
        }
    });
}

wss.on('connection', (ws) => {
    ws.roomId = null;
    ws.userId = null;
    ws.send(JSON.stringify({ type: 'system', message: 'Connected' }));

    ws.on('message', (data) => {
        try {
            const parsed = JSON.parse(data);
            if (parsed.type === 'subscribe') {
                const roomId = parsed.roomId;
                if (ws.roomId !== null && roomClients.has(ws.roomId)) {
                    roomClients.get(ws.roomId).delete(ws);
                }
                if (!roomClients.has(roomId)) {
                    roomClients.set(roomId, new Set());
                }
                roomClients.get(roomId).add(ws);
                ws.roomId = roomId;
                ws.userId = parsed.userId || null;
                ws.send(JSON.stringify({ type: 'system', message: `Subscribed to room ${roomId}` }));
            } else if (parsed.type === 'typing') {
                if (ws.roomId) {
                    broadcastToRoom(ws.roomId, { type: 'typing', data: { userId: ws.userId, userName: parsed.userName, isTyping: true } });
                }
            } else if (parsed.type === 'stop_typing') {
                if (ws.roomId) {
                    broadcastToRoom(ws.roomId, { type: 'typing', data: { userId: ws.userId, isTyping: false } });
                }
            }
        } catch (e) {}
    });

    ws.on('close', () => {
        if (ws.roomId !== null && roomClients.has(ws.roomId)) {
            roomClients.get(ws.roomId).delete(ws);
        }
    });
});

app.use(express.json());

app.post('/new-message', async (req, res) => {
    const { message_id } = req.body;
    if (!message_id) {
        return res.status(400).json({ error: 'No message_id' });
    }
    const wpUrl = process.env.WP_URL || 'http://localhost:80';
    const endpoint = `${wpUrl}/wp-json/mytheme/v1/chat/message/${message_id}`;
    try {
        const response = await axios.get(endpoint);
        const messageData = response.data;
        const roomId = messageData.room_id;
        if (roomId) {
            broadcastToRoom(roomId, { type: 'new_message', data: messageData });
        }
        res.json({ success: true });
    } catch (error) {
        console.error('Error fetching message:', error.message);
        res.status(500).json({ error: 'Failed to fetch message' });
    }
});

app.post('/new-room', (req, res) => {
    const { room } = req.body;
    if (!room) {
        return res.status(400).json({ error: 'No room data' });
    }
    broadcastToAll({ type: 'new_room', data: room });
    res.json({ success: true });
});

app.post('/update-message', async (req, res) => {
    const { message } = req.body;
    if (!message) return res.status(400).json({ error: 'No message' });
    broadcastToRoom(message.room_id, { type: 'update_message', data: message });
    res.json({ success: true });
});

app.post('/delete-message', async (req, res) => {
    const { message } = req.body;
    if (!message) return res.status(400).json({ error: 'No message' });
    broadcastToRoom(message.room_id, { type: 'delete_message', data: message });
    res.json({ success: true });
});

app.post('/reaction', (req, res) => {
    const { message_id, user_id, reaction, action } = req.body;
    broadcastToAll({ type: 'reaction', data: { message_id, user_id, reaction, action } });
    res.json({ success: true });
});

app.post('/read', (req, res) => {
    const { room_id, user_id, last_message_id } = req.body;
    broadcastToRoom(room_id, { type: 'read', data: { room_id, user_id, last_message_id } });
    res.json({ success: true });
});

app.listen(PORT, () => {
    console.log(`Express server listening on port ${PORT}`);
});