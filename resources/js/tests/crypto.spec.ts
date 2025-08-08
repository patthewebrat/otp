import { describe, it, expect } from 'vitest'
import {
  base64UrlEncode,
  base64UrlDecode,
  generateToken,
  generateIv,
  generateAesGcmKey,
  exportRawKeyBase64Url,
  importRawKeyBase64Url,
  encryptAesGcm,
  decryptAesGcm,
  encodeText,
  decodeText,
} from '../../js/utils/crypto'

describe('crypto utils', () => {
  it('base64url encode/decode roundtrip', () => {
    const data = encodeText('hello world')
    const enc = base64UrlEncode(data)
    const dec = base64UrlDecode(enc)
    expect(Array.from(dec)).toEqual(Array.from(data))
  })

  it('base64url encoding is URL safe and unpadded', () => {
    const bytes = new Uint8Array([251, 255, 239, 127]) // would be "+/v/ f/" with padding in base64
    const enc = base64UrlEncode(bytes)
    expect(enc).not.toMatch(/[+/=]/)
    const dec = base64UrlDecode(enc)
    expect(Array.from(dec)).toEqual(Array.from(bytes))
  })

  it('base64url handles large buffers (chunking)', () => {
    const large = new Uint8Array(70_000)
    for (let i = 0; i < large.length; i++) large[i] = i % 256
    const enc = base64UrlEncode(large)
    const dec = base64UrlDecode(enc)
    expect(dec.length).toBe(large.length)
    expect(Array.from(dec.subarray(0, 1000))).toEqual(Array.from(large.subarray(0, 1000)))
    expect(dec[69999]).toBe(large[69999])
  })

  it('generateToken default is 16 bytes (base64url decodes to 16)', () => {
    const token = generateToken()
    const raw = base64UrlDecode(token)
    expect(raw.length).toBe(16)
    // token should be URL-safe
    expect(token).toMatch(/^[A-Za-z0-9_-]+$/)
  })

  it('generateToken(32) decodes to 32 bytes', () => {
    const token = generateToken(32)
    const raw = base64UrlDecode(token)
    expect(raw.length).toBe(32)
  })

  it('generateIv default is 12 bytes', () => {
    const iv = generateIv()
    expect(iv.length).toBe(12)
  })

  it('AES-GCM encrypt/decrypt roundtrip (text)', async () => {
    const key = await generateAesGcmKey()
    const iv = generateIv()
    const msg = 's3cr3t message'
    const ciphertext = await encryptAesGcm(key, encodeText(msg), iv)
    const plaintext = await decryptAesGcm(key, ciphertext, iv)
    expect(decodeText(plaintext)).toBe(msg)
  })

  it('export/import key and decrypt works', async () => {
    const key = await generateAesGcmKey()
    const exported = await exportRawKeyBase64Url(key)
    const imported = await importRawKeyBase64Url(exported)
    const iv = generateIv()
    const msg = 'another message'
    const ct = await encryptAesGcm(imported, encodeText(msg), iv)
    const pt = await decryptAesGcm(imported, ct, iv)
    expect(decodeText(pt)).toBe(msg)
  })

  it('encodeText/decodeText roundtrip', () => {
    const s = 'Unicode ✓ café 𝌆'
    const enc = encodeText(s)
    const dec = decodeText(enc)
    expect(dec).toBe(s)
  })

  it('generateIv produces different values and correct length', () => {
    const a = generateIv()
    const b = generateIv()
    expect(a.length).toBe(12)
    expect(b.length).toBe(12)
    expect(base64UrlEncode(a)).not.toBe(base64UrlEncode(b))
  })

  it('decrypt fails with wrong IV (auth fails)', async () => {
    const key = await generateAesGcmKey()
    const iv1 = generateIv()
    const iv2 = generateIv()
    const msg = encodeText('tamper check')
    const ct = await encryptAesGcm(key, msg, iv1)
    await expect(decryptAesGcm(key, ct, iv2)).rejects.toBeDefined()
  })

  it('importRawKeyBase64Url rejects invalid length', async () => {
    await expect(importRawKeyBase64Url('YWJj')) // 'abc'
      .rejects.toBeDefined()
  })

  it('encrypt/decrypt works with Uint8Array input as well as ArrayBuffer', async () => {
    const key = await generateAesGcmKey()
    const iv = generateIv()
    const bytes = new Uint8Array([1, 2, 3, 4, 5])
    const ct = await encryptAesGcm(key, bytes, iv)
    const pt = await decryptAesGcm(key, ct, iv)
    expect(Array.from(new Uint8Array(pt))).toEqual(Array.from(bytes))
  })
})
