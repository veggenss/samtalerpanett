    const profileError = document.getElementById('profile-error');
    const deleteDialog = document.getElementById('password-confirmation');

    function showError(el, msg) {
        el.querySelector('p').textContent = msg;
        el.hidden = false;
    }

    document.getElementById('profile_picture').addEventListener('change', function () {
        const file = this.files[0];
        if (file) document.querySelector('.profile-avatar-preview').src = URL.createObjectURL(file);
    });

    document.getElementById('delete-user-btn').addEventListener('click', () => deleteDialog.showModal());
    deleteDialog.querySelector('.dialog-close').addEventListener('click', () => deleteDialog.close());
    deleteDialog.querySelector('.dialog-cancel').addEventListener('click', () => deleteDialog.close());
    deleteDialog.addEventListener('click', (e) => { if (e.target === deleteDialog) deleteDialog.close(); });

    document.getElementById('my-profile-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        profileError.hidden = true;

        const formData  = new FormData(e.target);
        const fileInput = document.getElementById('profile_picture');
        if (fileInput.files[0]) formData.append('profile_picture', fileInput.files[0]);

        try{
            const res = await fetch('/api/save-profile', { method: 'POST', body: formData });
            const data = await res.json();
            if(data.class === 'error'){
                showError(profileError, data.message);
                return;
            }
            window.location.href = '/chat';
        }
        catch(err){
            console.error('saveProfile:', err);
        }
    });

    document.getElementById('delete-user-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const errEl = deleteDialog.querySelector('.dialog-error');
        const password = document.getElementById('delete-password').value;

        if(!confirm('Er du sikker på at du vil slette kontoen din? Dette kan ikke angres.')) return;

        try{
            const res  = await fetch('/api/delete-user', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ password }),
            });
            const data = await res.json();
            if(data.class === 'error'){
                showError(errEl, data.message);
                return;
            }
            window.location.href = '/logout';
        }
        catch(err){
            console.error('deleteUser:', err);
        }
    });