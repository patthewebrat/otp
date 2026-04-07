import { ref, readonly } from 'vue'
import axios from 'axios'

// Shared state for IP access
const hasFileUploadAccess = ref(false)
const isLoading = ref(false)
const checked = ref(false)
const error = ref(null)
let pendingCheck = null

export function useIPAccess() {
    const checkFileUploadAccess = async () => {
        // If already checked, return cached result
        if (checked.value) return hasFileUploadAccess.value

        // If a check is in flight, wait for it
        if (pendingCheck) return pendingCheck

        pendingCheck = (async () => {
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
                checked.value = true
                pendingCheck = null
            }
        })()

        return pendingCheck
    }

    return {
        hasFileUploadAccess: readonly(hasFileUploadAccess),
        isLoading: readonly(isLoading),
        error: readonly(error),
        checkFileUploadAccess
    }
}