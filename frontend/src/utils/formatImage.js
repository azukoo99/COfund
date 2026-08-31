export function formatImageUrl(url) {
  if (!url) return 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=800'
  if (url.startsWith('http')) return url
  const base = import.meta.env.VITE_STORAGE_URL || 'http://127.0.0.1:8000'
  return base + url
}
