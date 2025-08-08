import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

// Mock axios used by the composable
vi.mock('axios', () => {
  return {
    default: {
      get: vi.fn(),
    },
  }
})

import axios from 'axios'

const importComposable = async () => {
  // Reset module state between tests (composable keeps shared refs)
  vi.resetModules()
  const mod = await import('../../js/utils/useIPAccess')
  return mod
}

describe('useIPAccess composable', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.clearAllMocks()
  })

  it('sets hasFileUploadAccess=true when API returns allowed', async () => {
    ;(axios.get as unknown as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
      data: { allowed: true, ip: '1.2.3.4' },
    })
    const { useIPAccess } = await importComposable()
    const { hasFileUploadAccess, checkFileUploadAccess, error, isLoading } = useIPAccess()

    const allowed = await checkFileUploadAccess()
    expect(allowed).toBe(true)
    expect(hasFileUploadAccess.value).toBe(true)
    expect(error.value).toBeNull()
    expect(isLoading.value).toBe(false)
    expect(axios.get).toHaveBeenCalledWith('/api/file/ip-access')
  })

  it('sets hasFileUploadAccess=false when API returns disallowed', async () => {
    ;(axios.get as unknown as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
      data: { allowed: false, ip: '5.6.7.8' },
    })
    const { useIPAccess } = await importComposable()
    const { hasFileUploadAccess, checkFileUploadAccess } = useIPAccess()

    const allowed = await checkFileUploadAccess()
    expect(allowed).toBe(false)
    expect(hasFileUploadAccess.value).toBe(false)
  })

  it('handles API errors by returning false and setting error ref', async () => {
    const errSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
    ;(axios.get as unknown as ReturnType<typeof vi.fn>).mockRejectedValueOnce(new Error('network'))
    const { useIPAccess } = await importComposable()
    const { hasFileUploadAccess, checkFileUploadAccess, error } = useIPAccess()

    const allowed = await checkFileUploadAccess()
    expect(allowed).toBe(false)
    expect(hasFileUploadAccess.value).toBe(false)
    expect(error.value).toBeInstanceOf(Error)
    errSpy.mockRestore()
  })
})
