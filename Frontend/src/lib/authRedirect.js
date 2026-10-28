// Rutas solo para invitados (RequireGuest): tras el login nunca se vuelve a ellas (F0-24).
export const AUTH_PATHS = ['/login', '/register', '/verify-email', '/forgot-password'];
export const AUTH_PATH_PREFIXES = ['/password-reset/'];

// Indica si la ruta es de autenticación. '/login/' cuenta igual que '/login'.
export const isAuthPath = (pathname) => {
  if (typeof pathname !== 'string') return false;
  const path = pathname.length > 1 ? pathname.replace(/\/+$/, '') : pathname;
  return AUTH_PATHS.includes(path) || AUTH_PATH_PREFIXES.some((prefix) => pathname.startsWith(prefix));
};

// Destino tras el login: pathname + search + hash del location de origen.
// '/' si falta, si no es una ruta interna o si es una ruta de autenticación.
export const getReturnPath = (from) => {
  const {pathname} = from ?? {};
  if (typeof pathname !== 'string' || !pathname.startsWith('/') || pathname.startsWith('//') || isAuthPath(pathname)) {
    return '/';
  }
  return `${pathname}${from.search ?? ''}${from.hash ?? ''}`;
};

// Estado de los enlaces a /login y /register. En una página de autenticación conserva el origen
// anterior (location.state.from); en cualquier otra, el origen es la página actual.
export const buildAuthLinkState = (location) => ({
  from: isAuthPath(location?.pathname) ? (location?.state?.from ?? location) : location,
});
