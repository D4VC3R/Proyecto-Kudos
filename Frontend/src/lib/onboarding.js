// Onboarding (F2-07): ruta, pasos y cuándo mostrarlo.
export const ONBOARDING_PATH = '/onboarding';

export const ONBOARDING_STEPS = {
  INTERESTS: 'interests',
  RATING: 'rating',
};

// Se muestra al entrar si la cuenta está verificada y no tiene intereses. Sin el campo (respuesta antigua) no se muestra.
export const needsOnboarding = (user) =>
  Boolean(user?.email_verified_at || user?.is_verified)
  && Array.isArray(user?.interests)
  && user.interests.length === 0;
