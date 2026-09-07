export const environment = {
  production: true,
  // For a same-origin deployment (e.g. Nginx reverse-proxying /api to the
  // backend, as infrastructure/frontend/nginx.conf assumes), '/api' is
  // correct as-is. For a split-domain deployment (frontend on Vercel,
  // backend on Railway/Render/etc.), replace this with the backend's full
  // public URL, e.g. 'https://afriflow-backend.up.railway.app/api'.
  apiUrl: '/api',
};
