<!DOCTYPE html>
<html lang="en">

<head>
    <title>Samtaler på nett | Min Profil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="/css/mainStyle.css" />
    <link rel="icon" href="/assets/icons/logo.ico" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
</head>

<body class="profile-body">
    <div class="profile-page">
        <div class="dialog-inner">
            <div class="dialog-header">
                <h2><a href="/chat" class="btn-ghost" aria-label="Tilbake"><i class="fa-solid fa-arrow-left"></i></a> Min Profil</h2>
            </div>

            <?php if (isset($_SESSION['flash'])): ?>
                <div class="<?= htmlspecialchars($_SESSION['flash']['class']) ?>" style="margin: 16px 22px 0;">
                    <p><?= htmlspecialchars($_SESSION['flash']['message']) ?></p>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <form id="my-profile-form">
                <div class="dialog-error" id="profile-error" hidden><p></p></div>

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
                        placeholder="<?= htmlspecialchars($_SESSION['user']['username']) ?>"
                        value="<?= htmlspecialchars($_SESSION['user']['username']) ?>">
                </div>

                <div class="form-group">
                    <label for="prof-email">E-post</label>
                    <input type="email" id="prof-email" name="email"
                        placeholder="<?= htmlspecialchars($_SESSION['user']['email']) ?>"
                        value="<?= htmlspecialchars($_SESSION['user']['email']) ?>">
                </div>

                <div class="form-group">
                    <label for="prof-password">Nytt Passord</label>
                    <input type="password" id="prof-password" name="password" placeholder="La stå tom for å beholde nåværende">
                </div>

                <div class="danger-zone">
                    <p>Farlig Sone</p>
                    <button type="button" id="delete-user-btn" class="btn-danger">
                        <i class="fa-solid fa-trash-can"></i> Slett Konto
                    </button>
                </div>

                <div class="dialog-actions">
                    <a href="/chat" class="btn-ghost">Avbryt</a>
                    <button type="submit">Lagre</button>
                </div>
            </form>
        </div>
    </div>

    <dialog id="password-confirmation">
        <div class="dialog-inner">
            <div class="dialog-header">
                <h2>Passord Konfirmasjon</h2>
                <button class="dialog-close" type="button" aria-label="Lukk"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="delete-user-form">
                <div class="dialog-error" hidden><p></p></div>
                <div class="form-group">
                    <label for="delete-password">Skriv passord for å slette konto</label>
                    <input type="password" id="delete-password" name="password" placeholder="Ditt passord" required>
                </div>
                <div class="dialog-actions">
                    <button type="button" class="btn-ghost dialog-cancel">Avbryt</button>
                    <button type="submit" class="btn-danger">Slett Konto</button>
                </div>
            </form>
        </div>
    </dialog>


    <script src="/js/profile.js"></script>
</body>
</html>