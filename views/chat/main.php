<!DOCTYPE html>
<html lang="en">

<head>
    <title>Samtaler på nett | Main</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="/css/mainStyle.css" />
    <link rel="icon" href="/assets/icons/logo.ico" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
</head>

<body>
    <div class="spn">
        <div class="panel-left">
            <h4>General</h4>
            <div class="top-buttons">
                <button id="global-chat"><i class="fa-regular fa-message"></i> Global</button>
                <button id="open-profile"><i class="fa-regular fa-user"></i> Min Profil</button>
                <button onclick="window.location.href='/chat/friends';"><i class="fa-regular fa-face-smile"></i> Venner</button>
            </div>

            <div class="separator"></div>

            <h4>Dine Samtaler</h4>
            <button id="new-conv" class="new-conv-button"><i class="fa-solid fa-plus"></i> Ny Samtale</button>

            <div id="conv-list"></div>
        </div>

        <div id="msg-ctx-menu" class="context-menu" hidden>
            <button id="ctx-delete"><i class="fa-regular fa-trash-can"></i> Slett Melding</button>
        </div>

        <div class="chat">
            <div id="alert-container"></div>
            <div id="messages"></div>
            <div class="message-inputs">
                <input type="text" id="messageInput" placeholder="Skriv melding...">
                <button id="sendButton">Send</button>
            </div>
        </div>

        <div class="panel-right">
            <h3>Detaljer</h3>
            <div id="chat-details">
                <p id="chat-name"></p>
                <div id="chat-participants"></div>
                <div id="conv-actions" hidden>
                    <button id="conv-rename-btn"><i class="fa-solid fa-pencil"></i> Endre Samtale Navn</button>
                    <button id="conv-leave-btn" class="btn-danger"><i class="fa-solid fa-right-from-bracket"></i> Forlat Samtale</button>
                </div>
            </div>
        </div>
    </div>

    <dialog id="create-conversation">
        <div class="dialog-inner">
            <div class="dialog-header">
                <h2>Ny Samtale</h2>
                <button class="dialog-close" type="button" aria-label="Lukk"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="new-conversation-form">
                <div class="dialog-error" hidden><p></p></div>
                <div class="form-group">
                    <label for="convName">Samtale Navn</label>
                    <input type="text" id="convName" name="convName" placeholder="Navn på samtale" required>
                </div>
                <div class="form-group">
                    <label>Deltakere</label>
                    <div id="newConvParticipants"></div>
                    <button type="button" id="addParticipantBtn"><i class="fa-solid fa-plus"></i> Legg til Deltaker</button>
                </div>
                <div class="dialog-actions">
                    <button type="button" class="btn-ghost dialog-cancel">Avbryt</button>
                    <button type="submit">Opprett</button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        window.currentUser = {
            id: "<?php echo htmlspecialchars((string)$_SESSION['user']['id']); ?>",
            username: "<?php echo htmlspecialchars($_SESSION['user']['username']); ?>",
            wsToken: "<?php echo htmlspecialchars($_SESSION['user']['wsToken']); ?>"
        }
    </script>
    <script src="/js/mainScript.js"></script>
</body>
</html>