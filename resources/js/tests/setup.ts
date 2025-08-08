// Ensure Web Crypto API is available during tests (Node environment)
import { webcrypto } from 'node:crypto'

if (!globalThis.crypto || !('subtle' in globalThis.crypto)) {
  // @ts-ignore
  globalThis.crypto = webcrypto as unknown as Crypto
}

