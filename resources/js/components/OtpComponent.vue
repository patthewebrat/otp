<!-- resources/js/components/OtpComponent.vue -->
<template>
    <div id="app" v-cloak>
        <img src="https://indulge.digital/sites/all/themes/indulgev4/images/logo-light.svg" alt="Logo" class="logo" />
        <h1>Securely share a password</h1>

        <div v-if="state === 'loading'">

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
import { useRoute } from 'vue-router';

const password = ref('');
const expiry = ref(5);
const generatedUrl = ref('');
const decryptedPassword = ref('');
const state = ref('loading');

const route = useRoute();

onMounted(async () => {
    if (route.query.key) {
        await checkPasswordExists(route.query.key);
    } else {
        state.value = 'input';
    }
});

watch(
    () => route.query.key,
    async (newKey) => {
        if (newKey) {
            state.value = 'loading';
            await checkPasswordExists(newKey);
        } else {
            state.value = 'input';
        }
    }
);

const submitPassword = async () => {
    try {
        state.value = 'loading';
        const response = await axios.post('/api/otp/create', {
            password: password.value,
            expiry: expiry.value,
        });
        generatedUrl.value = response.data.url;
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

const checkPasswordExists = async (combinedKey) => {
    try {
        const response = await axios.get(`/api/otp/check/${combinedKey}`);
        if (response.data.exists) {
            state.value = 'ready';
            decryptedPassword.value = 'encrypted';
        } else {
            state.value = 'not-found';
        }
    } catch (error) {
        console.error('Error checking password existence:', error);
        state.value = 'not-found';
    }
};

const viewPassword = async () => {
    const combinedKey = route.query.key;

    if (combinedKey && state.value === 'ready') {
        try {
            state.value = 'loading';
            const response = await axios.get(`/api/otp/${combinedKey}`);
            decryptedPassword.value = response.data.password;
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
