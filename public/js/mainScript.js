const alertCon = document.getElementById('alert-container');
const messagesDiv = document.getElementById('messages');
const input = document.getElementById('messageInput');
const sendButton = document.getElementById('sendButton');
const convList = document.getElementById('conv-list');
const globalChat = document.getElementById('global-chat');
const ctxMenu = document.getElementById('msg-ctx-menu');
const ctxDelete = document.getElementById('ctx-delete');

let ctxTargetMsgId = null;
let activeConvId = null;
let sending = false;
let ws = null;

const userId = window.currentUser.id;
const username = window.currentUser.username;
const wsToken = window.currentUser.wsToken;


function showDialogError(dialog, message){
    const el = dialog.querySelector('.dialog-error');
    if(!el) return;
    el.querySelector('p').textContent = message;
    el.hidden = false;
}

function clearDialogError(dialog){
    const el = dialog.querySelector('.dialog-error');
    if(!el) return;
    el.hidden = true;
}



function showContextMenu(x, y, msgId) {
    ctxTargetMsgId = msgId;
    ctxMenu.style.left = x + 'px';
    ctxMenu.style.top = y + 'px';
    ctxMenu.hidden = false;
}

function hideContextMenu() {
    ctxMenu.hidden     = true;
    ctxTargetMsgId     = null;
}



const chatStore = {
    public:  [],
    private: {},

    upsertMessages(type, convId, msgs){
        if(type === 'public'){
            this.public = this.merge(this.public, msgs);
        }
        else{
            if(!convId) throw new TypeError('Undefined convId');
            this.private[convId] = this.merge(this.private[convId], msgs);
        }
    },

    addMessage(type, convId, msg){
        if(!msg) throw new TypeError('Undefined msg');
        if(type === 'public'){
            this.public = this.insertSorted(this.public, msg);
            return;
        }
        if(!convId) throw new TypeError('Undefined ConvId');
        if(!this.private[convId]) this.private[convId] = [];
        this.private[convId] = this.insertSorted(this.private[convId], msg);
    },

    removeMessage(msgId, convId){
        const filter = arr => arr.filter(m => m.id != msgId);
        if(!convId){
            this.public = filter(this.public);
        }
        else if(this.private[convId]){
            this.private[convId] = filter(this.private[convId]);
        }
    },

    merge(existing = [], incoming = []){
        const map = new Map();
        [...existing, ...incoming].forEach(msg => map.set(msg.id, msg));
        return this.sort([...map.values()]);
    },

    insertSorted(arr, msg){
        return this.sort([...arr, msg]);
    },

    sort(arr){
        return [...arr].sort((a, b) => {
            if(a.date_sent === b.date_sent) return Number(a.id) - Number(b.id);
            return new Date(a.date_sent) - new Date(b.date_sent);
        });
    },
};



function appendMessage(data) {
    const wrapper = document.createElement('div');
    wrapper.classList.add('message');
    wrapper.dataset.msgId = data.id;

    if(data.sender_id == userId) wrapper.classList.add('self');

    if(data.username === '[System]'){
        wrapper.classList.add('system');
        wrapper.textContent = data.message || '';
        messagesDiv.appendChild(wrapper);
        return;
    }

    const avatar = document.createElement('img');
    avatar.classList.add('avatar');
    avatar.src = data.profilePictureUrl || '/assets/icons/default.png';

    const content = document.createElement('div');
    content.classList.add('message-content');

    const usernameSpan = document.createElement('span');
    usernameSpan.classList.add('username');
    usernameSpan.textContent = data.username || data.sender_username || 'Ukjent';

    const textDiv = document.createElement('div');
    textDiv.classList.add('text');
    textDiv.textContent = data.message || '';

    const time = document.createElement('span');
    time.classList.add('timestamp');
    time.textContent = new Date(data.date_sent).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    content.appendChild(usernameSpan);
    content.appendChild(textDiv);
    content.appendChild(time);
    wrapper.appendChild(avatar);
    wrapper.appendChild(content);

    messagesDiv.appendChild(wrapper);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

document.addEventListener('DOMContentLoaded', () => {
    function init() {
        websocketConn();
        getUserLogs();
        setupEventListeners();
        setupDialogs();
    }



    function setupDialogs() {
        const newConvDialog = document.getElementById('create-conversation');
        const newConvForm   = document.getElementById('new-conversation-form');

        newConvDialog.querySelector('.dialog-close')?.addEventListener('click', () => newConvDialog.close());
        newConvDialog.querySelector('.dialog-cancel')?.addEventListener('click', () => newConvDialog.close());
        newConvDialog.addEventListener('click', (e) => { if (e.target === newConvDialog) newConvDialog.close(); });

        newConvDialog.addEventListener('close', () => {
            newConvForm.reset();
            clearDialogError(newConvDialog);
            resetParticipants();
        });

        function resetParticipants() {
            document.getElementById('newConvParticipants').innerHTML = `
                <div class="participant self">
                    <input type="text" value="${username}" disabled>
                </div>
            `;
        }

        document.getElementById('addParticipantBtn')?.addEventListener('click', () => {
            const partyInput = document.getElementById('newConvParticipants');
            if (partyInput.querySelectorAll('.participant:not(.self)').length >= 9) return;

            const wrapper = document.createElement('div');
            wrapper.classList.add('participant');
            wrapper.innerHTML = `
                <input type="text" placeholder="Brukernavn" required>
                <button type="button" class="remove"><i class="fa-solid fa-xmark"></i></button>
            `;
            wrapper.querySelector('.remove').onclick = () => wrapper.remove();
            partyInput.appendChild(wrapper);
        });

        // Open new conversation dialog
        document.getElementById('new-conv')?.addEventListener('click', () => {
            resetParticipants();
            newConvDialog.showModal();
        });

        // Navigate to profile page
        document.getElementById('open-profile')?.addEventListener('click', () => {
            window.location.href = '/chat/profile';
        });

        // New conversation submit
        newConvForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearDialogError(newConvDialog);

            const partyInput   = document.getElementById('newConvParticipants');
            const participants = [...partyInput.querySelectorAll('input')]
                .filter(i => !i.disabled)
                .map(i => i.value.trim())
                .filter(Boolean);

            if (participants.length < 1) {
                showDialogError(newConvDialog, 'Legg til minst én deltaker.');
                return;
            }

            const ok = await makeConversation(document.getElementById('convName').value, participants);
            if (ok) newConvDialog.close();
        });
    }



    function setupEventListeners() {
        sendButton.onclick = sendMessage;

        document.addEventListener('click', hideContextMenu);

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        });

        globalChat.addEventListener('click', () => {
            activeConvId = null;
            messagesDiv.innerHTML = '';
            renderMessages();
            document.querySelectorAll('.conversation, #global-chat').forEach(el => el.classList.remove('active'));
            globalChat.classList.add('active');
        });

        messagesDiv.addEventListener('contextmenu', (e) => {
            const bubble = e.target.closest('.message.self');
            if (!bubble) return;
            e.preventDefault();
            showContextMenu(e.clientX, e.clientY, bubble.dataset.msgId);
        });

        ctxDelete.addEventListener('click', () => {
            if (ctxTargetMsgId) deleteMessage(ctxTargetMsgId, activeConvId);
            hideContextMenu();
        });
    }



    async function getUserLogs() {
        try {
            const req  = await fetch('/api/get-user-logs', { method: 'POST' });
            const data = await req.json();

            chatStore.upsertMessages('public', null, data.public);

            data.conversations.forEach(conv => {
                chatStore.upsertMessages('private', conv.id, conv.messages);
                renderConversationList(conv);
            });

            renderMessages();
        } catch (err) {
            console.error('Failed to getUserLogs:', err);
        }
    }

    async function makeConversation(title, participants) {
        try {
            const req  = await fetch('/api/make-conv', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ title, parties: participants }),
            });
            const data = await req.json();

            if (data.class === 'error') {
                showDialogError(document.getElementById('create-conversation'), data.message);
                return false;
            }

            if (data.conversation) getUserLogs();
            return true;
        } catch (err) {
            console.error('makeConversation:', err);
            return false;
        }
    }



    function renderMessages() {
        messagesDiv.innerHTML = '';
        const messages = activeConvId ? (chatStore.private[activeConvId] || []) : chatStore.public;
        messages.forEach(msg => appendMessage(msg));
    }

    function renderConversationList(data) {
        if (document.getElementById('conversation-' + data.id)) return;

        const convWrapper = document.createElement('div');
        convWrapper.classList.add('conversation');
        convWrapper.id = 'conversation-' + data.id;

        const userWrapper = document.createElement('div');
        userWrapper.classList.add('conversation-user');

        const textWrapper = document.createElement('div');
        textWrapper.classList.add('conversation-userText');

        const nameSpan = document.createElement('span');
        nameSpan.classList.add('conversation-name');
        nameSpan.textContent = data.title;

        const prevStr = document.createElement('span');
        prevStr.classList.add('conversation-prevStr');
        prevStr.textContent = data.latest_message || '';

        const icon = document.createElement('img');
        icon.classList.add('conversation-avatar');
        icon.src = '/assets/icons/default.png';

        userWrapper.appendChild(icon);
        textWrapper.appendChild(nameSpan);
        textWrapper.appendChild(prevStr);
        userWrapper.appendChild(textWrapper);
        convWrapper.appendChild(userWrapper);

        convWrapper.addEventListener('click', () => {
            activeConvId = data.id;
            renderMessages();
            document.querySelectorAll('.conversation, #global-chat').forEach(el => el.classList.remove('active'));
            convWrapper.classList.add('active');
        });

        convList.appendChild(convWrapper);
    }



    function sendMessage() {
        if (sending) return;
        sending = true;

        const text = input.value.trim();
        if (!text) { sending = false; return; }

        if (text.length > 400) {
            sending = false;
            appendMessage({ id: 'sys-' + Date.now(), username: '[System]', message: 'Meldingen er for lang. Maks 400 tegn.', date_sent: new Date().toISOString() });
            return;
        }

        if (ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({ type: 'message', username, sender_id: userId, conv_id: activeConvId, message: text }));
        } else {
            appendMessage({ id: 'sys-' + Date.now(), username: '[System]', message: 'Noe gikk galt!', date_sent: new Date().toISOString() });
        }

        input.value = '';
        setTimeout(() => { sending = false; }, 1000);
    }

    function deleteMessage(msgId) {
        if (ws.readyState !== WebSocket.OPEN) return;
        ws.send(JSON.stringify({ type: 'delete', message_id: msgId, conv_id: activeConvId }));
    }



    function websocketConn() {
        ws = new WebSocket(`ws://127.0.0.1:9501?token=${wsToken}`);

        ws.onopen = () => console.log('WebSocket åpnet');

        ws.onclose = () => {
            console.error('WebSocket lukket');
            appendMessage({ id: 'sys-' + Date.now(), username: '[System]', message: 'Tilkoblingen ble lukket', date_sent: new Date().toISOString() });
        };

        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);

            if (data.type === 'delete') {
                chatStore.removeMessage(data.message_id, data.conv_id);
                document.querySelector(`[data-msg-id="${data.message_id}"]`)?.remove();
                return;
            }

            if (data.conv_id === null) {
                chatStore.addMessage('public', null, data);
            } else {
                chatStore.addMessage('private', data.conv_id, data);
            }

            if ((!data.conv_id && !activeConvId) || data.conv_id === activeConvId) {
                renderMessages();
            }
        };
    }

    init();
});