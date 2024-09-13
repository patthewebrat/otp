<template>
    <div id="app" v-cloak>
        <img src="https://indulge.digital/sites/all/themes/indulgev4/images/logo-light.svg" alt="Logo" class="logo" />
        <h1>Securely share a password</h1>

        <div v-if="state === 'loading'">
            <!-- Loading state -->
        </div>

        <div v-else-if="state === 'input'">
            <textarea v-model="password" placeholder="Enter the password"></textarea>
            <div>
                <label for="expiry">Set Expiry Time (in minutes): </label>
                <input type="number" v-model="expiry" min="1" />
            </div>
            <button @click="submitPassword">Submit</button>
        </div>

        <div v-else-if="state === 'generated'">
            <p>Your one-time password link: <a :href="generatedUrl" target="_blank">{{ generatedUrl }}</a></p>
            <button @click="copyToClipboard">Copy to Clipboard</button>
        </div>

        <div v-else-if="state === 'ready'">
            <p>This password will self-destruct after you have viewed it.</p>
            <button @click="viewPassword">Click to view</button>
        </div>

        <div v-else-if="state === 'viewed'">
            <p>The password is: <span class="display-password"><strong>{{ decryptedPassword }}</strong></span></p>
        </div>

        <div v-else-if="state === 'not-found'">
            <p>Sorry, this password does not exist. It has either expired or never existed in the first place.</p>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';

const password = ref('');
const expiry = ref(5);
const generatedUrl = ref('');
const decryptedPassword = ref('');
const state = ref('loading');

const route = useRoute();
const router = useRouter();

onMounted(async () => {
    const { tokenBase64 } = getTokenAndKeyFromFragment();
    if (tokenBase64) {
        await checkPasswordExists();
    } else {
        state.value = 'input';
    }
});

watch(
    () => route.fullPath,
    async () => {
        const { tokenBase64 } = getTokenAndKeyFromFragment();
        if (tokenBase64) {
            state.value = 'loading';
            await checkPasswordExists();
        } else {
            state.value = 'input';
        }
    }
);

const submitPassword = async () => {
    try {
        state.value = 'loading';

        // Generate a random encryption key (AES-128-GCM)
        const key = await crypto.subtle.generateKey(
            {
                name: "AES-GCM",
                length: 128, // 128-bit key for AES-128-GCM
            },
            true,
            ["encrypt", "decrypt"]
        );

        // Export the key and encode it in URL-safe Base64
        const exportedKey = await crypto.subtle.exportKey("raw", key);
        const encryptionKeyBase64 = btoa(String.fromCharCode(...new Uint8Array(exportedKey)))
            .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');

        // Encode the password to an ArrayBuffer
        const enc = new TextEncoder();
        const passwordBuffer = enc.encode(password.value);

        // Generate a random IV (Initialization Vector)
        const iv = crypto.getRandomValues(new Uint8Array(12)); // 12 bytes for AES-GCM

        // Encrypt the password
        const encryptedPasswordBuffer = await crypto.subtle.encrypt(
            {
                name: "AES-GCM",
                iv: iv,
            },
            key,
            passwordBuffer
        );

        // Convert encrypted password and IV to URL-safe Base64
        const encryptedPasswordBase64 = btoa(String.fromCharCode(...new Uint8Array(encryptedPasswordBuffer)))
            .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
        const ivBase64 = btoa(String.fromCharCode(...iv))
            .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');

        // Generate a shorter token (8 bytes)
        const tokenBytes = crypto.getRandomValues(new Uint8Array(8));
        const tokenBase64 = btoa(String.fromCharCode(...tokenBytes))
            .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');

        // Send the encrypted password, IV, and token to the server
        await axios.post('/api/otp/create', {
            token: tokenBase64,
            encryptedPassword: encryptedPasswordBase64,
            iv: ivBase64,
            expiry: expiry.value,
        });

        // Construct the URL with the token and key in the fragment
        generatedUrl.value = `${window.location.origin}/otp#${tokenBase64}.${encryptionKeyBase64}`;

        state.value = 'generated';
    } catch (error) {
        console.error('Error submitting password:', error);
        state.value = 'input';
    }
};

const copyToClipboard = () => {
    const el = document.createElement('textarea');
    el.value = generatedUrl.value;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    alert('URL copied to clipboard');
};

const checkPasswordExists = async () => {
    const { tokenBase64 } = getTokenAndKeyFromFragment();

    try {
        const response = await axios.get(`/api/otp/check/${tokenBase64}`);

        if (response.data.exists) {
            state.value = 'ready';
        } else {
            state.value = 'not-found';
        }
    } catch (error) {
        console.error('Error checking password existence:', error);
        state.value = 'not-found';
    }
};

function getTokenAndKeyFromFragment() {
    const fragment = window.location.hash.substring(1); // Remove the '#' character

    const [tokenBase64, encryptionKeyBase64] = fragment.split('.');
    return { tokenBase64, encryptionKeyBase64 };
}

const viewPassword = async () => {
    const { tokenBase64, encryptionKeyBase64 } = getTokenAndKeyFromFragment();

    if (tokenBase64 && encryptionKeyBase64 && state.value === 'ready') {
        try {
            state.value = 'loading';

            // Fetch the encrypted password and IV from the server using the token
            const response = await axios.get(`/api/otp/${tokenBase64}`);

            const encryptedPasswordBase64 = response.data.encryptedPassword;
            const ivBase64 = response.data.iv;

            // Decode the encryption key from URL-safe Base64
            const encryptionKeyBytes = Uint8Array.from(
                atob(encryptionKeyBase64.replace(/-/g, '+').replace(/_/g, '/')),
                c => c.charCodeAt(0)
            );
            const key = await crypto.subtle.importKey(
                "raw",
                encryptionKeyBytes,
                {
                    name: "AES-GCM",
                    length: 128,
                },
                true,
                ["decrypt"]
            );

            // Decode encrypted password and IV from URL-safe Base64
            const encryptedPasswordBuffer = Uint8Array.from(
                atob(encryptedPasswordBase64.replace(/-/g, '+').replace(/_/g, '/')),
                (c) => c.charCodeAt(0)
            );
            const iv = Uint8Array.from(
                atob(ivBase64.replace(/-/g, '+').replace(/_/g, '/')),
                (c) => c.charCodeAt(0)
            );

            // Decrypt the password
            const decryptedBuffer = await crypto.subtle.decrypt(
                {
                    name: 'AES-GCM',
                    iv: iv,
                },
                key,
                encryptedPasswordBuffer
            );

            // Decode the decrypted password
            const dec = new TextDecoder();
            decryptedPassword.value = dec.decode(decryptedBuffer);

            state.value = 'viewed';
        } catch (error) {
            console.error('Error retrieving password:', error);
            state.value = 'not-found';
        }
    }
};
</script>

<style scoped lang="scss">
[v-cloak] {
    display: none;
}
</style>
