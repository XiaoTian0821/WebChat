// WebConnect - Profile Module (additional interactions)
document.addEventListener('DOMContentLoaded', () => {
    // Avatar preview
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', e => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = ev => {
                    document.getElementById('avatarPreview').innerHTML =
                        `<img src="${ev.target.result}" style="width:100px;height:100px;border-radius:50%;object-fit:cover;">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
