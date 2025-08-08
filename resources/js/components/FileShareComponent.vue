<template>
    <div v-cloak>
        <h1>{{ pageTitle }}</h1>

        <div v-if="state === 'loading'">
            <div v-if="progressStage === 'encrypting' && encryptionProgress > 0" class="progress-container">
                <p class="progress-label">Encrypting file...</p>
                <div class="progress-bar">
                    <div class="progress" :style="{ width: encryptionProgress + '%' }"></div>
                    <span>{{ encryptionProgress }}%</span>
                </div>
            </div>
            <div v-if="progressStage === 'uploading' && uploadProgress > 0" class="progress-container">
                <p class="progress-label">Uploading to server...</p>
                <div class="progress-bar">
                    <div class="progress" :style="{ width: uploadProgress + '%' }"></div>
                    <span>{{ uploadProgress }}%</span>
                </div>
            </div>
        </div>

        <div v-else-if="state === 'input'">
            <div class="file-upload">
                <label for="file-input" class="file-label">
                    <span v-if="!selectedFile">Choose file to share</span>
                    <span v-else>{{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})</span>
                </label>
                <input 
                    type="file" 
                    id="file-input" 
                    @change="handleFileSelection" 
                    aria-label="File input"
                    class="file-input"
                />
                <p class="file-size-info">Maximum file size: {{ formatFileSize(maxFileSize) }}</p>
                <div v-if="fileTooLarge" class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> 
                    This file is too large. Please select a file smaller than {{ formatFileSize(maxFileSize) }}.
                </div>
            </div>
            <div v-if="selectedFile" class="file-upload-controls">
                <div>
                    <label for="expiry">Expiry Time: </label>
                    <select v-model.number="expiry" id="expiry" aria-label="Expiry time selection">
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
                <button 
                    @click="submitFile" 
                    :disabled="isUploading || fileTooLarge" 
                    aria-label="Upload file"
                >
                    {{ isUploading ? 'Encrypting & Uploading...' : 'Upload File' }}
                </button>
                <div v-if="progressStage === 'encrypting' && encryptionProgress > 0 && encryptionProgress < 100" class="progress-container">
                    <p class="progress-label">Encrypting file...</p>
                    <div class="progress-bar">
                        <div class="progress" :style="{ width: encryptionProgress + '%' }"></div>
                        <span>{{ encryptionProgress }}%</span>
                    </div>
                </div>
                <div v-if="progressStage === 'uploading' && uploadProgress > 0 && uploadProgress < 100" class="progress-container">
                    <p class="progress-label">Uploading to server...</p>
                    <div class="progress-bar">
                        <div class="progress" :style="{ width: uploadProgress + '%' }"></div>
                        <span>{{ uploadProgress }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="state === 'generated'" class="generated-url" @click="copyToClipboard(generatedUrl)">
            <p>
                Here is your one-time file sharing link. Copy this and share it with whoever needs it:<br />
                <span class="file-link data-format">
                    <span aria-label="File sharing link">
                        <template v-if="copySuccess">Copied to clipboard</template>
                        <template v-else>{{ generatedUrl }}</template>
                    </span>
                    <button class="copy-icon" aria-label="Copy link" @click="copyToClipboard(generatedUrl)">
                        <i :class="copySuccess ? 'fas fa-check' : 'fas fa-copy'"></i>
                    </button>
                </span>
            </p>
            <p>
                Sharing: {{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})
            </p>
            <button @click="copyToClipboard(generatedUrl)">Copy link</button>
            <button @click="reset">Share another file</button>
        </div>

        <div v-else-if="state === 'ready'">
            <p>This file will be deleted after it's downloaded.</p>
            <p v-if="fileInfo">File: {{ fileInfo.fileName }} ({{ fileInfo.fileSize }})</p>
            <button @click="downloadFile">Download file</button>
        </div>

        <div v-else-if="state === 'downloaded'">
            <p>File downloaded successfully.</p>
            <button @click="reset">Share a new file</button>
        </div>

        <div v-else-if="state === 'not-found'">
            <p>Sorry, this file is unavailable or has expired.</p>
            <p v-if="!getTokenAndKeyFromFragment().tokenBase64">File sharing is not available from your location.</p>
            <button @click="reset" v-if="getTokenAndKeyFromFragment().tokenBase64">Share a new file</button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';
import { useIPAccess } from '../utils/useIPAccess';
import {
    base64UrlEncode,
    base64UrlDecode,
    generateToken,
    generateAesGcmKey,
    exportRawKeyBase64Url,
    importRawKeyBase64Url,
    encryptAesGcm,
    decryptAesGcm,
    generateIv,
    encodeText,
    decodeText,
} from '../utils/crypto';

// Reactive variables
const selectedFile = ref(null);
const expiry = ref(10080);
const generatedUrl = ref('');
const state = ref('loading');
const copySuccess = ref(false);
const isUploading = ref(false);
const uploadProgress = ref(0);
const encryptionProgress = ref(0);
const progressStage = ref(''); // 'encrypting' or 'uploading'
const pageTitle = ref('Securely Share a File');
const fileInfo = ref(null);
// Default to 10MB, will be updated from server
const maxFileSize = ref(10 * 1024 * 1024); 
const fileTooLarge = ref(false);

const route = useRoute();

// Use IP access composable
const { hasFileUploadAccess, checkFileUploadAccess } = useIPAccess();

onMounted(async () => {
    // Check IP access first for file upload features
    const hasUploadAccess = await checkFileUploadAccess();
    if (!hasUploadAccess && !getTokenAndKeyFromFragment().tokenBase64) {
        // If no upload access and not accessing a file, show not found
        state.value = 'not-found';
        return;
    }
    
    await fetchMaxFileSize();
    await initializeState();
});

watch(
    () => route.fullPath,
    async () => {
        await initializeState();
    }
);

watch(
    () => window.location.hash,
    async () => {
        await initializeState();
    }
);

watch(state, (newState) => {
    switch (newState) {
        case 'downloaded':
            pageTitle.value = 'File Downloaded';
            break;
        case 'input':
            pageTitle.value = 'Share a File Securely';
            break;
        case 'generated':
            pageTitle.value = 'Share a File Securely';
            break;
        case 'ready':
            pageTitle.value = 'Download Shared File';
            break;
        case 'loading':
            pageTitle.value = 'Share a File Securely';
            break;
        case 'not-found':
            pageTitle.value = 'File Unavailable';
            break;
        default:
            pageTitle.value = 'Share a File Securely';
    }
});

watch(pageTitle, (newTitle) => {
    document.title = newTitle;
});


async function fetchMaxFileSize() {
    try {
        const response = await axios.get('/api/file/max-size');
        if (response.data && response.data.max_size) {
            maxFileSize.value = response.data.max_size;
        }
    } catch (error) {
        console.error('Error fetching max file size:', error);
        // Keep the default value if request fails
    }
}

async function initializeState() {
    const { tokenBase64 } = getTokenAndKeyFromFragment();
    if (tokenBase64) {
        state.value = 'loading';
        await checkFileExists();
    } else {
        state.value = 'input';
    }
}

function handleFileSelection(event) {
    const file = event.target.files[0];
    if (file) {
        // Check if file is too large
        if (file.size > maxFileSize.value) {
            fileTooLarge.value = true;
            selectedFile.value = file; // Still show the file info
        } else {
            fileTooLarge.value = false;
            selectedFile.value = file;
        }
    } else {
        selectedFile.value = null;
        fileTooLarge.value = false;
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

const submitFile = async () => {
    if (!selectedFile.value) return;
    if (selectedFile.value.size > maxFileSize.value) {
        fileTooLarge.value = true;
        return;
    }
    
    try {
        isUploading.value = true;
        state.value = 'loading';
        uploadProgress.value = 0;
        encryptionProgress.value = 0;
        progressStage.value = 'encrypting';

        // Read the file as an ArrayBuffer
        const fileArrayBuffer = await readFileAsArrayBuffer(selectedFile.value);
        
        // Generate encryption key and encrypt the file
        // The encryptFile function now internally updates encryptionProgress
        const { encryptedFileBlob, ivBase64, encryptionKeyBase64, encryptedFileName } = await encryptFile(fileArrayBuffer, selectedFile.value.name);
        
        // Prepare for upload stage - reset progress
        progressStage.value = 'uploading';
        uploadProgress.value = 0;
        
        // Generate a token for this file
        const tokenBase64 = generateToken();
        
        // Create a FormData object to send the file
        const formData = new FormData();
        formData.append('token', tokenBase64);
        formData.append('encryptedFile', encryptedFileBlob, 'encrypted-file');
        formData.append('fileName', encryptedFileName);
        formData.append('fileSize', formatFileSize(selectedFile.value.size));
        formData.append('iv', ivBase64);
        formData.append('expiry', Number(expiry.value));
        
        // Upload the encrypted file with progress tracking
        await axios.post('/api/file/create', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    uploadProgress.value = percentCompleted;
                }
            }
        });

        // Construct the URL with the token and key in the fragment
        generatedUrl.value = `${window.location.origin}/f#${tokenBase64}.${encryptionKeyBase64}`;

        state.value = 'generated';
    } catch (error) {
        console.error('Error uploading file:', error);
        state.value = 'input';
        alert('An error occurred while uploading your file. Please try again.');
    } finally {
        isUploading.value = false;
        progressStage.value = '';
    }
};

function readFileAsArrayBuffer(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsArrayBuffer(file);
    });
}

async function encryptFile(fileArrayBuffer, fileName) {
    progressStage.value = 'encrypting';
    encryptionProgress.value = 5; // Start at 5% to show initial progress
    
    // Generate a random encryption key (AES-256-GCM)
    const key = await generateAesGcmKey();
    encryptionProgress.value = 15;

    // Export the key and encode it in URL-safe Base64
    const encryptionKeyBase64 = await exportRawKeyBase64Url(key);
    encryptionProgress.value = 25;

    // Generate a random IV (Initialization Vector)
    const iv = generateIv(12);
    encryptionProgress.value = 35;

    // Simulate encryption progress since we can't get real-time updates from subtle.encrypt
    // Start progress animation
    const progressInterval = setInterval(() => {
        if (encryptionProgress.value < 90) {
            encryptionProgress.value += 1;
        }
    }, 50);

    try {
        // Encrypt the file
        const encryptedFileBuffer = await encryptAesGcm(key, fileArrayBuffer, iv);
        
        // Clear the progress interval
        clearInterval(progressInterval);
        encryptionProgress.value = 95;

        // Encrypt the filename using the same key and IV
        const fileNameBuffer = encodeText(fileName);
        const encryptedFileNameBuffer = await encryptAesGcm(key, fileNameBuffer, iv);
        
        // Convert IV to URL-safe Base64
        const ivBase64 = base64UrlEncode(iv);
        
        // Convert encrypted filename to Base64
        const encryptedFileName = base64UrlEncode(encryptedFileNameBuffer);
        
        // Convert encrypted file to Blob
        const encryptedFileBlob = new Blob([encryptedFileBuffer], { type: 'application/octet-stream' });
        encryptionProgress.value = 100;
        
        return {
            encryptedFileBlob,
            ivBase64,
            encryptionKeyBase64,
            encryptedFileName,
        };
    } catch (error) {
        clearInterval(progressInterval);
        throw error;
    }
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

const checkFileExists = async () => {
    const { tokenBase64, encryptionKeyBase64 } = getTokenAndKeyFromFragment();

    try {
        const response = await axios.get(`/api/file/check/${tokenBase64}`);

        if (response.data.exists) {
            // Decrypt the filename for display
            const decryptedFileName = await decryptFileName(
                response.data.fileName,
                response.data.iv,
                encryptionKeyBase64
            );
            
            fileInfo.value = {
                fileName: decryptedFileName,
                fileSize: response.data.fileSize
            };
            state.value = 'ready';
        } else {
            state.value = 'not-found';
        }
    } catch (error) {
        console.error('Error checking file existence:', error);
        state.value = 'not-found';
    }
};

function reset() {
    selectedFile.value = null;
    state.value = 'input';
    uploadProgress.value = 0;
    fileTooLarge.value = false;
}

function getTokenAndKeyFromFragment() {
    const fragment = window.location.hash.substring(1); // Remove the '#' character
    const [tokenBase64, encryptionKeyBase64] = fragment.split('.');
    return { tokenBase64, encryptionKeyBase64 };
}

// base64 helpers moved to utils/crypto

const downloadFile = async () => {
    const { tokenBase64, encryptionKeyBase64 } = getTokenAndKeyFromFragment();

    if (tokenBase64 && encryptionKeyBase64 && state.value === 'ready') {
        try {
            state.value = 'loading';
            uploadProgress.value = 0;
            encryptionProgress.value = 0;
            progressStage.value = 'uploading'; // Start with download progress first
            
            // Fetch the file details from the server
            const response = await axios.get(`/api/file/${tokenBase64}`);
            
            // Download the encrypted file from the signed URL
            const fileResponse = await axios.get(response.data.fileUrl, {
                responseType: 'arraybuffer',
                onDownloadProgress: (progressEvent) => {
                    if (progressEvent.total) {
                        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        uploadProgress.value = percentCompleted;
                    }
                }
            });
            
            // Switch to decryption progress
            progressStage.value = 'encrypting';
            encryptionProgress.value = 0;
            
            // Start progress animation for decryption
            const progressInterval = setInterval(() => {
                if (encryptionProgress.value < 90) {
                    encryptionProgress.value += 1;
                }
            }, 50);
            
            // Get the IV from the response
            const ivBase64 = response.data.iv;
            
            // Decrypt the filename
            const decryptedFileName = await decryptFileName(
                response.data.fileName,
                ivBase64,
                encryptionKeyBase64
            );
            
            // Decrypt the file
            const decryptedFile = await decryptFile(
                fileResponse.data,
                ivBase64,
                encryptionKeyBase64
            );
            
            // Clear the progress interval
            clearInterval(progressInterval);
            encryptionProgress.value = 100;
            
            // Create a download link for the decrypted file
            const downloadUrl = URL.createObjectURL(
                new Blob([decryptedFile], { type: 'application/octet-stream' })
            );
            
            // Create a temporary link element and trigger the download
            const downloadLink = document.createElement('a');
            downloadLink.href = downloadUrl;
            downloadLink.download = decryptedFileName; 
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            
            // Clean up the blob URL
            URL.revokeObjectURL(downloadUrl);
            
            state.value = 'downloaded';
        } catch (error) {
            console.error('Error downloading file:', error);
            state.value = 'not-found';
        } finally {
            progressStage.value = '';
        }
    }
};

async function decryptFile(encryptedFileArrayBuffer, ivBase64, encryptionKeyBase64) {
    // Decode the encryption key from URL-safe Base64
    const key = await importRawKeyBase64Url(encryptionKeyBase64);

    // Decode IV from URL-safe Base64
    const iv = base64UrlDecode(ivBase64);

    // Decrypt the file
    const decryptedBuffer = await decryptAesGcm(key, encryptedFileArrayBuffer, iv);

    return decryptedBuffer;
}

async function decryptFileName(encryptedFileName, ivBase64, encryptionKeyBase64) {
    // Decode the encryption key from URL-safe Base64
    const key = await importRawKeyBase64Url(encryptionKeyBase64);

    // Decode IV from URL-safe Base64
    const iv = base64UrlDecode(ivBase64);

    // Decode encrypted filename from Base64
    const encryptedFileNameBuffer = base64UrlDecode(encryptedFileName);

    // Decrypt the filename
    const decryptedBuffer = await decryptAesGcm(key, encryptedFileNameBuffer, iv);

    // Convert decrypted buffer to string
    return decodeText(decryptedBuffer);
}
</script>

<style scoped>
/* Component-specific styles only */
.file-upload {
    margin-bottom: 1rem;
}

.file-input {
    position: absolute;
    clip: rect(0, 0, 0, 0);
    pointer-events: none;
}

.file-link {
    font-weight: 500;
}

/* Progress bar container and label styles */
.progress-container {
    margin: 15px 0;
}

.progress-label {
    font-size: 0.9rem;
    margin-bottom: 5px;
    font-weight: 500;
    color: white;
}

/* Override common styles as needed */
.file-label {
    margin-top: 0;
}

.file-size-info {
    margin-top: 5px;
    font-style: italic;
}

.file-upload-controls {
    margin-top: 30px;
}
</style>
