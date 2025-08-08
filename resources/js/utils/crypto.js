// Reusable cryptographic utilities for browser (Web Crypto API)
// All helpers are designed to keep keys client-side and use URL-safe Base64.

const subtle = globalThis.crypto?.subtle;

// Base64URL encode an ArrayBuffer or Uint8Array
export function base64UrlEncode(input) {
  const bytes = input instanceof Uint8Array ? input : new Uint8Array(input);
  let binary = '';
  const chunkSize = 0x8000; // avoid stack overflows on large buffers
  for (let i = 0; i < bytes.length; i += chunkSize) {
    const chunk = bytes.subarray(i, i + chunkSize);
    binary += String.fromCharCode.apply(null, chunk);
  }
  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

// Base64URL decode to Uint8Array
export function base64UrlDecode(base64UrlString) {
  const base64 = base64UrlString.replace(/-/g, '+').replace(/_/g, '/');
  const pad = base64.length % 4 === 0 ? '' : '='.repeat(4 - (base64.length % 4));
  const data = atob(base64 + pad);
  const bytes = new Uint8Array(data.length);
  for (let i = 0; i < data.length; i++) bytes[i] = data.charCodeAt(i);
  return bytes;
}

// Generate a cryptographically secure random token, Base64URL-encoded
export function generateToken(byteLength = 16) {
  const tokenBytes = new Uint8Array(byteLength);
  crypto.getRandomValues(tokenBytes);
  return base64UrlEncode(tokenBytes);
}

// Generate a random IV for AES-GCM (default 12 bytes)
export function generateIv(byteLength = 12) {
  const iv = new Uint8Array(byteLength);
  crypto.getRandomValues(iv);
  return iv;
}

// Generate AES-GCM 256-bit key
export async function generateAesGcmKey() {
  return await subtle.generateKey(
    { name: 'AES-GCM', length: 256 },
    true,
    ['encrypt', 'decrypt']
  );
}

// Export AES key (raw) and return Base64URL string
export async function exportRawKeyBase64Url(key) {
  const raw = await subtle.exportKey('raw', key); // ArrayBuffer
  return base64UrlEncode(raw);
}

// Import AES key (raw) from Base64URL string
export async function importRawKeyBase64Url(keyBase64Url) {
  const rawBytes = base64UrlDecode(keyBase64Url);
  return await subtle.importKey(
    'raw',
    rawBytes,
    { name: 'AES-GCM', length: 256 },
    true,
    ['encrypt', 'decrypt']
  );
}

// Encrypt ArrayBuffer/Uint8Array with AES-GCM
export async function encryptAesGcm(key, data, iv) {
  const bytes = data instanceof Uint8Array ? data : new Uint8Array(data);
  return await subtle.encrypt({ name: 'AES-GCM', iv }, key, bytes);
}

// Decrypt ArrayBuffer/Uint8Array with AES-GCM
export async function decryptAesGcm(key, data, iv) {
  const bytes = data instanceof Uint8Array ? data : new Uint8Array(data);
  return await subtle.decrypt({ name: 'AES-GCM', iv }, key, bytes);
}

// Convenience: encode/decode text
export function encodeText(str) {
  return new TextEncoder().encode(str);
}

export function decodeText(buffer) {
  return new TextDecoder().decode(buffer);
}

