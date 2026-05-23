export const apiBase = import.meta.env.VITE_API_BASE?.trim() || '/api';

export type ApiPreview = {
  url: string;
  method: string;
};

export function buildApiUrl(path: string): string {
  const normalizedBase = apiBase.endsWith('/') ? apiBase.slice(0, -1) : apiBase;
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;

  return `${normalizedBase}${normalizedPath}`;
}

export function getPreviewRequest(path: string, method = 'GET'): ApiPreview {
  return {
    url: buildApiUrl(path),
    method,
  };
}
