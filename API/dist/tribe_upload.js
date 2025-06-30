'use strict';

function tribeUploadButton (selector) {
    const submit =  document.querySelector(selector);

    if (!submit) {
        console.error('tribe upload button: selector invalid');
    }

    submit.addEventListener('click', (e) => {
        e.preventDefault();

        const tribe_fd = new FormData();
        const btnTarget = e.target.dataset.target;

        let up = document.querySelector(btnTarget);

        for (let i = 0; i <= up.files.length; i++) {
            tribe_fd.append('files[]', up.files[i]);
        }

        uploadFile(tribe_fd);
    });
}

async function uploadFile(formData) {
    try {
        const response = await fetch(tribeUploadUrl, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        window.tribeUploadResponse = result;
    } catch (e) {
        console.log(e);
    }
}
