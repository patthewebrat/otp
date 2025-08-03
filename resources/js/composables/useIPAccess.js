import { ref, readonly } from 'vue'
import axios from 'axios'

// Shared state for IP access
const hasFileUploadAccess = ref(false)
const isLoading = ref(false)
const error = ref(null)

export function useIPAccess() {
    const checkFileUploadAccess = async () => {
        if (isLoading.value) return hasFileUploadAccess.value
        
        try {
            isLoading.value = true
            error.value = null
            
            const response = await axios.get('/api/file/ip-access')

            hasFileUploadAccess.value = response.data?.allowed || false

            return hasFileUploadAccess.value
        } catch (err) {
            console.error('Error checking file upload access:', err)
            error.value = err
            hasFileUploadAccess.value = false
            return false
        } finally {
            isLoading.value = false
        }
    }

    return {
        hasFileUploadAccess: readonly(hasFileUploadAccess),
        isLoading: readonly(isLoading),
        error: readonly(error),
        checkFileUploadAccess
    }
}