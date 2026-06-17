<!DOCTYPE html>
<html lang="en">

<head>
    <title>Samtaler på nett | Main</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="/css/mainStyle.css" />
    <link rel="icon" href="/assets/icons/logo.ico" />

    <!-- ikoner fra font awesome og google fonts-->
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

             <!-- if conv: all users apart of the conv. If global chat: every user who has sent a message -->
             <div id="chat-participants"></div>
             <!-- leave conv button -->

             <div id="conv-actions" hidden>
                <button id="conv-rename-btn"><i class="fa-solid fa-pencil"></i> Endre Samtale Navn</button>
                <button id="conv-leave-btn" class="btn-danger"><i class="fa-solid fa-right-from-bracket"></i> Forlat Samtale</button>
             </div>
          </div>
      </div>
   </div>

   <!-- ny samtale -->
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

   <!-- min profil -->
   <dialog id="my-profile">
      <div class="dialog-inner">
         <div class="dialog-header">
            <h2>Min Profil</h2>
            <button class="dialog-close" type="button" aria-label="Lukk"><i class="fa-solid fa-xmark"></i></button>
         </div>

         <form id="my-profile-form">
            <div class="dialog-error" hidden><p></p></div>

            <div class="profile-avatar-row">
               <img src="/assets/icons/default.png" class="profile-avatar-preview" alt="Profilbilde">
               <div>
                  <label for="profile_picture" class="btn-upload">Endre bilde</label>
                  <input type="file" name="profile_picture" id="profile_picture" accept="image/*" hidden>
               </div>
            </div>

            <div class="form-group">
               <label for="prof-username">Brukernavn</label>
               <input type="text" id="prof-username" name="username"
                  placeholder="<?php echo htmlspecialchars($_SESSION['user']['username']); ?>"
                  value="<?php echo htmlspecialchars($_SESSION['user']['username']); ?>">
            </div>

            <div class="form-group">
               <label for="prof-email">E-post</label>
               <input type="email" id="prof-email" name="email"
                  placeholder="<?php echo htmlspecialchars($_SESSION['user']['email']); ?>"
                  value="<?php echo htmlspecialchars($_SESSION['user']['email']); ?>">
            </div>

            <div class="form-group">
               <label for="prof-password">Nytt Passord</label>
               <input type="password" id="prof-password" name="password" placeholder="La stå tom for å beholde nåværende">
            </div>

            <div class="danger-zone">
               <p>Farlig Sone</p>
               <button type="button" id="delete-user-btn" class="btn-danger"><i class="fa-solid fa-trash-can"></i> Slett Konto</button>
            </div>

            <div class="dialog-actions">
               <button type="button" class="btn-ghost dialog-cancel">Avbryt</button>
               <button type="submit">Lagre</button>
            </div>
         </form>
      </div>
   </dialog>

   <!--delete user confirmation-->
   <dialog id="password-confirmation">
       <div class="dialog-inner">
           <div class="dialog-header">
              <h2>Passord Konfirmasjon</h2>
              <button class="dialog-close" type="button" aria-label="Lukk"><i class="fa-solid fa-xmark"></i></button>
           </div>
           <form id="delete-user-form">
               <div class="form-group">
                  <label for="prof-password">Skriv Passord for å Slette Bruker</label>
                  <input type="password" id="prof-password" name="password" placeholder="Ditt Passord" required>
               </div>

               <div class="dialog-actions">
                  <button type="button" class="btn-ghost dialog-cancel">Avbryt</button>
                  <button type="submit">Fortsett</button>
               </div>
           </form>
       </div>
   </dialog>
</body>

<script>
window.currentUser = {
    id: "<?php echo $_SESSION['user']['id']?>",
    username: "<?php echo $_SESSION['user']['username']?>",
    wsToken: "<?php echo $_SESSION['user']['wsToken']?>"
}
</script>
<!--<script src="/js/wsInit.js"></script>-->
<script src="/js/mainScript.js"></script>
</html>
