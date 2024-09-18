<template>
    <div id="app" v-cloak>

        <h1>Securely share a password</h1>

        <div v-if="state === 'loading'">
            <!-- Loading state -->
        </div>

        <div v-else-if="state === 'input'">
            <textarea v-model="password" ref="passwordInput" placeholder="Enter the password"></textarea>
            <div>
                <label for="expiry">Set Expiry Time (in minutes): </label>
                <select v-model.number="expiry">
                    <option value="5">5 minutes</option>
                    <option value="15">15 minutes</option>
                    <option value="30">30 minutes</option>
                    <option value="120">2 hours</option>
                    <option value="300">5 hours</option>
                    <option value="600">10 hours</option>
                    <option value="1440">24 hours</option>
                    <option value="2880">2 days</option>
                    <option value="10080">7 days</option>
                    <option value="43200">30 days</option>
                </select>
            </div>
            <button @click="submitPassword">Submit</button>
        </div>

        <div v-else-if="state === 'generated'" @click="copyToClipboard(generatedUrl)" class="generated-url">
            <p>
                Your one-time password link is displayed below. Click the link to save it to your clipboard:
                <span class="password-link">
                    <a>
                        <template v-if="copySuccess">Copied to clipboard</template>
                        <template v-else>{{ generatedUrl }}</template>
                    </a>
                    <button class="copy-icon" aria-label="Copy to clipboard">
                    <!-- Using Font Awesome copy icon -->
                    <i :class="copySuccess ? 'fas fa-check' : 'fas fa-copy'"></i>
                </button>
                </span>
                <button @click="copyToClipboard(generatedUrl)">Copy to clipboard</button> <button @click="state='input'">Share another password</button>

            </p>
            <!-- Display a subtle success message -->
            <!--p v-if="copySuccess" class="copy-success">Copied to clipboard!</p-->
        </div>

        <div v-else-if="state === 'ready'">
            <p>This password will self-destruct after you have viewed it.</p>
            <button @click="viewPassword">Click to view</button>
        </div>

        <div v-else-if="state === 'viewed'">
            <p>The password is: <span class="display-password"><strong>{{ decryptedPassword }}</strong></span></p>
            <button @click="copyToClipboard(decryptedPassword)">Copy to clipboard</button> <button @click="state='input'">Share a new password</button>
        </div>

        <div v-else-if="state === 'not-found'">
            <p>Sorry, this password does not exist. It has either expired or never existed in the first place.</p>
            <button @click="state='input'">Share a new password</button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';

const password = ref('');
const expiry = ref(10080);
const generatedUrl = ref('');
const decryptedPassword = ref('');
const state = ref('loading');
const copySuccess = ref(false); // New reactive variable\// Declare the passwordInput ref
const passwordInput = ref(null);

const route = useRoute();
const router = useRouter();

onMounted(async () => {
    const { tokenBase64 } = getTokenAndKeyFromFragment();
    if (tokenBase64) {
        await checkPasswordExists();
    } else {
        state.value = 'input';

        // Focus on the textarea after the DOM updates
        nextTick(() => {
            if (passwordInput.value) {
                passwordInput.value.focus();
            }
        });
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

watch(state, (newState) => {
    if (newState === 'input') {
        nextTick(() => {
            if (passwordInput.value) {
                passwordInput.value.focus();
            }
        });
    }
});

const submitPassword = async () => {
    try {
        state.value = 'loading';

        // Generate a random encryption key (AES-256-GCM)
        const key = await crypto.subtle.generateKey(
            {
                name: "AES-GCM",
                length: 256, // 256-bit key for AES-256-GCM
            },
            true,
            ["encrypt", "decrypt"]
        );

        // Export the key and encode it in URL-safe Base64
        const exportedKey = await crypto.subtle.exportKey("raw", key);
        const encryptionKeyBase64 = base64UrlEncode(exportedKey);

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
        const encryptedPasswordBase64 = base64UrlEncode(encryptedPasswordBuffer);
        const ivBase64 = base64UrlEncode(iv);

        // Generate a secure token (16 bytes)
        const tokenBytes = crypto.getRandomValues(new Uint8Array(16));
        const tokenBase64 = base64UrlEncode(tokenBytes);

        // Send the encrypted password, IV, and token to the server
        await axios.post('/api/create', {
            token: tokenBase64,
            encryptedPassword: encryptedPasswordBase64,
            iv: ivBase64,
            expiry: expiry.value,
        });

        // Construct the URL with the token and key in the fragment
        generatedUrl.value = `${window.location.origin}/#${tokenBase64}.${encryptionKeyBase64}`;

        state.value = 'generated';
    } catch (error) {
        console.error('Error submitting password:', error);
        state.value = 'input';
        alert('An error occurred while submitting your password. Please try again.');
    }
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        copySuccess.value = true;
        // Reset the copy success indicator after 2 seconds
        setTimeout(() => {
            copySuccess.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

const checkPasswordExists = async () => {
    const { tokenBase64 } = getTokenAndKeyFromFragment();

    try {
        const response = await axios.get(`/api/check/${tokenBase64}`);

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

function base64UrlEncode(arrayBuffer) {
    return btoa(String.fromCharCode(...new Uint8Array(arrayBuffer)))
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/, '');
}

function base64UrlDecode(base64UrlString) {
    const base64 = base64UrlString.replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(base64), c => c.charCodeAt(0));
}

const viewPassword = async () => {
    const { tokenBase64, encryptionKeyBase64 } = getTokenAndKeyFromFragment();

    if (tokenBase64 && encryptionKeyBase64 && state.value === 'ready') {
        try {
            state.value = 'loading';

            // Fetch the encrypted password and IV from the server using the token
            const response = await axios.get(`/api/${tokenBase64}`);

            const encryptedPasswordBase64 = response.data.encryptedPassword;
            const ivBase64 = response.data.iv;

            // Decode the encryption key from URL-safe Base64
            const encryptionKeyBytes = base64UrlDecode(encryptionKeyBase64);
            const key = await crypto.subtle.importKey(
                "raw",
                encryptionKeyBytes,
                {
                    name: "AES-GCM",
                    length: 256,
                },
                true,
                ["decrypt"]
            );

            // Decode encrypted password and IV from URL-safe Base64
            const encryptedPasswordBuffer = base64UrlDecode(encryptedPasswordBase64);
            const iv = base64UrlDecode(ivBase64);

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
