// server/index.js
// Run: npm install express socket.io cors
// Start: node index.js

const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

const app = express();
app.use(cors());

const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*", // Allow all origins for simplicity in this demo
        methods: ["GET", "POST"]
    }
});

const PORT = process.env.PORT || 3000;

io.on('connection', (socket) => {
    console.log(`User Connected: ${socket.id}`);

    // User Data
    socket.userData = {};

    socket.on('join_scenario', (data) => {
        // Data: { scenarioId, user, channel }
        const room = `scenario_${data.scenarioId}`;
        socket.join(room);

        socket.userData = {
            room: room,
            user: data.user,
            channel: data.channel
        };

        // Notify others in room
        socket.to(room).emit('peer_joined', {
            socketId: socket.id,
            user: data.user
        });

        console.log(`User ${data.user.username} joined ${room}`);
    });

    socket.on('signal', (data) => {
        // Data: { type, sdp/candidate, to }
        io.to(data.to).emit('signal', {
            type: data.type,
            sdp: data.sdp,
            candidate: data.candidate,
            from: socket.id
        });
    });

    socket.on('channel_update', (data) => {
        if (socket.userData.room) {
            socket.userData.channel = data.channel;
            // Broadcast to room so others update their routing table
            socket.to(socket.userData.room).emit('channel_update', {
                socketId: socket.id,
                channel: data.channel
            });
        }
    });

    socket.on('tx_state', (data) => {
        if (socket.userData.room) {
            socket.to(socket.userData.room).emit('tx_state', {
                socketId: socket.id,
                isTalking: data.isTalking
            });
        }
    });

    socket.on('disconnect', () => {
        console.log(`User Disconnected: ${socket.id}`);
        if (socket.userData.room) {
            socket.to(socket.userData.room).emit('peer_left', socket.id);
        }
    });
});

server.listen(PORT, () => {
    console.log(`Signaling Server running on port ${PORT}`);
});
