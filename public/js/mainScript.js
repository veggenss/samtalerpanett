const alertCon = document.getElementById('alert-container');
const messagesDiv = document.getElementById('messages');
const input = document.getElementById('messageInput');
const sendButton = document.getElementById('sendButton');

const newConvPartyInput = document.getElementById('newConvParticipants');
const newConvPartyAdd = document.getElementById('addParticipantBtn');

const convList = document.getElementById('conv-list');
const globalChat = document.getElementById('global-chat');

const ctxMenu = document.getElementById('msg-ctx-menu');
const ctxDelete = document.getElementById('ctx-delete');
let ctxTargetMsgId = null;

const userId = window.currentUser.id;
const username = window.currentUser.username;
const wsToken = window.currentUser.wsToken;

let activeConvId = null;
let sending = false;
let ws = null;
console.log(userId, username);

const getNewConvPartyCount = () => newConvPartyInput.querySelectorAll('.participant:not(.self)').length;


//dialog methods
function showDialogError(dialog, message) {
    const el = dialog.querySelector('.dialog-error');
    if (!el) return;
    el.querySelector('p').textContent = message;
    el.hidden = false;
}

function clearDialogError(dialog) {
    const el = dialog.querySelector('.dialog-error');
    if(!el) return;
    el.hidden = true;
}

function setupDialog(dialogId, openBtnId, formId, onOpen, onSubmit) {
    const dialog = document.getElementById(dialogId);
    const form = document.getElementById(formId);
    const openBtn = document.getElementById(openBtnId);

    if(!dialog || !form) {
        console.warn(`setupDialog: undefined: "${dialogId}"`);
        return;
    }

    openBtn?.addEventListener('click', () => {
        form.reset();
        clearDialogError(dialog);
        onOpen?.(dialog, form);
        dialog.showModal();
    });

    dialog.addEventListener('click', (e) => {
        if(e.target === dialog) dialog.close();
    });

    dialog.querySelector('.dialog-close')?.addEventListener('click', () => dialog.close());
    dialog.querySelector('.dialog-cancel')?.addEventListener('click', () => dialog.close());

    const fileInput = dialog.querySelector('input[type="file"]');
    const preview = dialog.querySelector('.profile-avatar-preview');
    if(fileInput && preview) {
        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if(file) preview.src = URL.createObjectURL(file);
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearDialogError(dialog);
        const data = Object.fromEntries(new FormData(form));
        await onSubmit(data, dialog, form);
    });
}

//context menus
function showContextMenu(x, y, msgId) {
    ctxTargetMsgId = msgId;
    ctxMenu.style.left = x + 'px';
    ctxMenu.style.top = y + 'px';
    ctxMenu.hidden = false;
}

function hideContextMenu() {
    ctxMenu.hidden = true;
    ctxTargetMsgId = null;
}

//chatStore
const chatStore = {
    public: [],
    private: {},

    upsertMessages(type, convId, msgs){
        if(type === 'public'){
            this.public = this.merge(this.public, msgs);
        }
        else{
            if(!convId) throw new TypeError("Undefined convId");
            this.private[convId] = this.merge(this.private[convId], msgs);
        }
    },

    addMessage(type, convId, msg){
        if(!msg) throw new TypeError("Undefined msg");

        if(type === 'public'){
            this.public = this.insertSorted(this.public, msg);
            return;
        }

        if(!convId) throw new TypeError("Undefined ConvId");

        if(!this.private[convId]) this.private[convId] = [];

        this.private[convId] = this.insertSorted(this.private[convId], msg);
    },

    removeMessage(msgId, convId){
        const filter = arr => arr.filter(m => m.id != msgId);
        if(!convId){
            this.public = filter(this.public);
        }
        else{
            if(this.private[convId]) this.private[convId] = filter(this.private[convId]);
        }
    },

    merge(existing = [], incoming = []){
        const map = new Map();

        [...existing, ...incoming].forEach(msg => {
            map.set(msg.id, msg);
        });

        return this.sort([...map.values()]);
    },

    insertSorted(arr, msg){
        const newArr = [...arr, msg];
        return this.sort(newArr);
    },

    sort(arr){
        return [...arr].sort((a, b) => {
            if(a.date_added === b.date_added){
                return Number(a.id) - Number(b.id);
            }
            return new Date(a.date_sent) - new Date(b.date_sent);
        });
    }
};


//message styling
function appendMessage(data) {
    const wrapper = document.createElement('div');
    wrapper.classList.add('message');
    wrapper.dataset.msgId = data.id;

    if (data.sender_id == userId) {
        wrapper.classList.add('self');
    }

    if (data.username === "[System]") {
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
    textDiv.textContent = data.message || data[0];

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


    //setup dialog
    function setupDialogs() {
        setupDialog(
            'create-conversation',
            'new-conv',
            'new-conversation-form',
            (dialog, form) => {
                newConvPartyInput.innerHTML = `
                    <div class="participant self">
                        <input type="text" value="${username}" disabled>
                    </div>
                `;
            },

            async (data, dialog, form) => {
                const participants = [...newConvPartyInput.querySelectorAll('input')]
                    .filter(i => !i.disabled)
                    .map(i => i.value.trim())
                    .filter(Boolean);

                if (participants.length < 1) {
                    showDialogError(dialog, "Legg til minst én deltaker.");
                    return;
                }

                const ok = await makeConversation(data.convName, participants);
                if (ok) dialog.close();
            }
        );

        newConvPartyAdd.addEventListener('click', () => {
            if (getNewConvPartyCount() >= 9) return;

            const wrapper = document.createElement('div');
            wrapper.classList.add('participant');
            wrapper.innerHTML = `
                <input type="text" placeholder="Brukernavn" required>
                <button type="button" class="remove"><i class="fa-solid fa-xmark"></i></button>
            `;
            wrapper.querySelector('.remove').onclick = () => wrapper.remove();
            newConvPartyInput.appendChild(wrapper);
        });

        setupDialog(
            'my-profile',
            'open-profile',
            'my-profile-form',
            null,
            async (data, dialog, form) => {
                const ok = await saveProfile(data);
                if(ok) dialog.close();
            }
        );

        setupDialog(
            'password-confirmation',
            'delete-user-btn',
            'delete-user-form',
            null,
            async (data, dialog, form) => {
                console.log(data);
                const agree = confirm("Er du sikker at du vil slette brukeren din?");
                if(!agree) return;
                await deleteUser(data.password);
            }
        )

    }


    //event listeners
    function setupEventListeners() {
        sendButton.onclick = sendMessage;

        document.addEventListener('click', hideContextMenu);

        input.addEventListener('keydown', (e) => {
            if(e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        });

        // toggle globalChat
        globalChat.addEventListener('click', () => {
            activeConvId = null;
            messagesDiv.innerHTML = '';
            renderMessages();

            document.querySelectorAll('.conversation, #global-chat').forEach(el => el.classList.remove('active'));
            globalChat.classList.add('active');
        });

        messagesDiv.addEventListener('contextmenu', (e) => {
           const bubble = e.target.closest('.message.self');
           if(!bubble) return;
           e.preventDefault();
           showContextMenu(e.clientX, e.clientY, bubble.dataset.msgId);
        });

        ctxDelete.addEventListener('click', () => {
            if(ctxTargetMsgId) deleteMessage(ctxTargetMsgId, activeConvId);
            hideContextMenu();
        });
    }


    //api calls
    async function getUserLogs() {
        try {
            const req = await fetch('/api/get-user-logs', {method: 'POST'});
            const data = await req.json();
            if(data) console.log("Fetched!", data);

            chatStore.upsertMessages('public', null, data.public);

            data.conversations.forEach(conv => {
                chatStore.upsertMessages('private', conv.id, conv.messages);
                renderConversationList(conv);
            });

            renderMessages();
            console.log("public: ", chatStore.public, "\n", "private: ", chatStore.private);
        }
        catch(err){
            console.error('Failed to getChat ', err);
        }
    }

    async function makeConversation(title, participants) {
        try{
            const req = await fetch('/api/make-conv', {
                method: 'POST',
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({title: title, parties: participants})
            });
            const data = await req.json();

            if (data.class) {
                const dialog = document.getElementById('create-conversation');
                showDialogError(dialog, data.message);
                return false;
            }

            if(data.conversation) getUserLogs();
            return true;
        }
        catch(err){
            console.error('newConversation: ', err);
            return false;
        }
    }

    async function saveProfile(formInput){
        try {
            const formData = new FormData();
            Object.entries(formInput).forEach(([k, v]) => formData.append(k, v));

            const fileInput = document.getElementById('profile_picture');
            if (fileInput.files[0]) formData.append('profile_picture', fileInput.files[0]);

            const req = await fetch('/api/save-profile', {
                method: 'POST',
                body: formData
            });
            const res = await req.json();

            if (res.class === 'error') {
                showDialogError(document.getElementById('my-profile'), res.message);
                return false;
            }
            return true;
        }
        catch(err) {
            console.error('saveProfile:', err);
            return false;
        }
    }

    async function deleteUser(password) {
        try{
            const req = await fetch('/api/delete-user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ password: password })
            });
            const data = await req.json();
            if (data.class === 'error') {
                const dialog = document.getElementById('password-confirmation');
                showDialogError(dialog, data.message);
                return false;
            }
            window.location.href = '/logout';
        }
        catch(err){
            console.error('deleteAccount:', err);
            return false;
        }
    }

    //rendering
    function renderMessages(){
        messagesDiv.innerHTML = '';
        const messages = activeConvId ? (chatStore.private[activeConvId] || []) : chatStore.public;
        messages.forEach(msg => appendMessage(msg));
    }

    function renderConversationList(data) {
        console.log("render", data);
        if (document.getElementById('conversation-' + data.id)) return;

        const convWrapper = document.createElement('div');
        convWrapper.classList.add('conversation');
        convWrapper.id = 'conversation-' + data.id;

        const userWrapper = document.createElement('div');
        userWrapper.classList.add('conversation-user');

        const textWrapper = document.createElement('div');
        textWrapper.classList.add('conversation-userText');

        const username = document.createElement('span');
        username.classList.add('conversation-name');
        username.textContent = data.title;

        const prevStr = document.createElement('span');
        prevStr.classList.add('conversation-prevStr');
        prevStr.textContent = data.latest_message;

        const icon = document.createElement('img');
        icon.classList.add('conversation-avatar');
        icon.src = '/assets/icons/default.png'; //should be data.icon

        userWrapper.appendChild(icon);
        textWrapper.appendChild(username);
        textWrapper.appendChild(prevStr);
        userWrapper.appendChild(textWrapper);
        convWrapper.appendChild(userWrapper);

        convWrapper.addEventListener('click', () => {
            activeConvId = data.id;
            renderMessages();
        });

        convList.appendChild(convWrapper);
    }


    //messaging
    function sendMessage() {
        if (sending) return;
        sending = true;

        const text = input.value.trim();
        if (text === '') {
            sending = false;
            return;
        }

        if (text.length > 400) {
            sending = false;
            appendMessage({
                id: 'sys-' + Date.now(),
                username: "[System]",
                message: "Meldingen er for lang. Maks 400 tegn.",
                date_added: new Date().toISOString()
            });
            return;
        }

        if (ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'message',
                username: username,
                sender_id: userId,
                conv_id: activeConvId,
                message: text,
            }));
        }
        else {
            appendMessage({
                id: 'sys-' + Date.now(),
                username: "[System]",
                message: "Noe Gikk Galt!",
                date_added: new Date().toISOString()
            });
        }

        input.value = '';
        setTimeout(() => { sending = false; }, 1000);
    }

    function deleteMessage(msgId){
        if(ws.readyState !== WebSocket.OPEN) return;

        ws.send(JSON.stringify({
            type: 'delete',
            message_id: msgId,
            conv_id: activeConvId
        }));
    }

    //websocket connection
    function websocketConn() {
        ws = new WebSocket(`ws://127.0.0.1:9501?token=${wsToken}`);
        console.log("token: ", wsToken);
        ws.onopen = () => {
            console.log("Tilkobling til websocket åpnet");
        }

        ws.onclose = () => {
            console.error("Tilkobling til websocket lukket");
            appendMessage({
                id: 'sys-' + Date.now(),
                username: "[System]",
                message: "Tilkoblingen ble lukket",
                date_added: new Date().toISOString()
            });
        }

        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            console.log("Incoming:", data);

            if(data.type === 'delete'){
                chatStore.removeMessage(data.message_id, data.conv_id);
                document.querySelector(`[data-msg-id="${data.message_id}"]`)?.remove();
                return;
            }

            const isGlobalMsg = data.conv_id === null;

            if (isGlobalMsg) {
                chatStore.addMessage('public', null, data);
            }
            else{
                chatStore.addMessage('private', data.conv_id, data);
            }

            if((!data.conv_id && !activeConvId) || data.conv_id === activeConvId){
                renderMessages();
            }
        }
    }

    init();
});
