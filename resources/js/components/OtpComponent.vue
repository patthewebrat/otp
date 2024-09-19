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

        <div v-else-if="state === 'generated'" class="generated-url" @click="copyToClipboard(generatedUrl)">
            <p>
                Your one-time password link is displayed below. Click the link to save it to your clipboard:<b/>
                <span class="password-link data-format">
                    <a>
                        <template v-if="copySuccess">Copied to clipboard</template>
                        <template v-else>{{ generatedUrl }}</template>
                    </a>
                    <button class="copy-icon" aria-label="Copy to clipboard" @click="copyToClipboard(generatedUrl)">
                        <!-- Using Font Awesome copy icon -->
                        <i :class="copySuccess ? 'fas fa-check' : 'fas fa-copy'"></i>
                    </button>
                </span>
                <button @click="copyToClipboard(generatedUrl)">Copy to clipboard</button>
                <button @click="reset">Share another password</button>
            </p>
        </div>

        <div v-else-if="state === 'ready'">
            <p>This password will self-destruct after you have viewed it.</p>
            <button @click="viewPassword">Click to view</button>
        </div>

        <div v-else-if="state === 'viewed'">
            <p>The password is below. Click to copy to your clipboard:<br/>
                <span class="view-password data-format"  @click="copyToClipboard(generatedUrl)">
                    <a class="display-password">
                        <template v-if="copySuccess">Copied to clipboard</template>
                        <template v-else>{{ decryptedPassword }}</template>
                    </a>
                    <button class="copy-icon" aria-label="Copy to clipboard" @click="copyToClipboard(decryptedPassword)">
                        <!-- Using Font Awesome copy icon -->
                        <i :class="copySuccess ? 'fas fa-check' : 'fas fa-copy'"></i>
                    </button>
                </span>
            </p>
            <button @click="copyToClipboard(decryptedPassword)">Copy to clipboard</button>
            <button @click="reset">Share a new password</button>
        </div>

        <div v-else-if="state === 'not-found'">
            <p>Sorry, this password does not exist. It has either expired or never existed in the first place.</p>
            <button @click="reset">Share a new password</button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';

// Reactive variables
const password = ref('');
const expiry = ref(10080);
const generatedUrl = ref('');
const decryptedPassword = ref('');
const state = ref('loading');
const copySuccess = ref(false);
const passwordInput = ref(null);

const route = useRoute();

onMounted(async () => {
    await initializeState();
});

watch(
    () => route.fullPath,
    async () => {
        await initializeState();
    }
);

watch(state, (newState) => {
    if (newState === 'input') {
        nextTick(() => {
            passwordInput.value?.focus();
        });
    }
});

async function initializeState() {
    const { tokenBase64 } = getTokenAndKeyFromFragment();
    if (tokenBase64) {
        state.value = 'loading';
        await checkPasswordExists();
    } else {
        state.value = 'input';
        nextTick(() => {
            passwordInput.value?.focus();
        });
    }
}

const submitPassword = async () => {
    try {
        state.value = 'loading';

        const { encryptedPasswordBase64, ivBase64, encryptionKeyBase64 } = await encryptPassword(password.value);
        const tokenBase64 = generateToken();

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

async function encryptPassword(passwordText) {
    // Generate a random encryption key (AES-256-GCM)
    const key = await crypto.subtle.generateKey(
        {
            name: 'AES-GCM',
            length: 256,
        },
        true,
        ['encrypt', 'decrypt']
    );

    // Export the key and encode it in URL-safe Base64
    const exportedKey = await crypto.subtle.exportKey('raw', key);
    const encryptionKeyBase64 = base64UrlEncode(exportedKey);

    // Encode the password to an ArrayBuffer
    const enc = new TextEncoder();
    const passwordBuffer = enc.encode(passwordText);

    // Generate a random IV (Initialization Vector)
    const iv = crypto.getRandomValues(new Uint8Array(12));

    // Encrypt the password
    const encryptedPasswordBuffer = await crypto.subtle.encrypt(
        {
            name: 'AES-GCM',
            iv: iv,
        },
        key,
        passwordBuffer
    );

    // Convert encrypted password and IV to URL-safe Base64
    const encryptedPasswordBase64 = base64UrlEncode(encryptedPasswordBuffer);
    const ivBase64 = base64UrlEncode(iv);

    return {
        encryptedPasswordBase64,
        ivBase64,
        encryptionKeyBase64,
    };
}

function generateToken() {
    // Generate a secure token (16 bytes)
    const tokenBytes = crypto.getRandomValues(new Uint8Array(16));
    return base64UrlEncode(tokenBytes);
}

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

function reset() {
    password.value = '';
    state.value = 'input';
}

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
    return Uint8Array.from(atob(base64), (c) => c.charCodeAt(0));
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

            decryptedPassword.value = await decryptPassword(
                encryptedPasswordBase64,
                ivBase64,
                encryptionKeyBase64
            );

            state.value = 'viewed';
        } catch (error) {
            console.error('Error retrieving password:', error);
            state.value = 'not-found';
        }
    }
};

async function decryptPassword(encryptedPasswordBase64, ivBase64, encryptionKeyBase64) {
    // Decode the encryption key from URL-safe Base64
    const encryptionKeyBytes = base64UrlDecode(encryptionKeyBase64);
    const key = await crypto.subtle.importKey(
        'raw',
        encryptionKeyBytes,
        {
            name: 'AES-GCM',
            length: 256,
        },
        true,
        ['decrypt']
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
    return dec.decode(decryptedBuffer);
}
</script>

<style scoped>
[v-cloak] {
    display: none;
}
</style>
