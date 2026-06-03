import * as z from 'zod';

/**
 * Esquema de validación para el formulario de perfil.
 * Reglas:
 * - avatar: Debe ser una URL válida o puede estar vacío (null o cadena vacía).
 * - biography: Máximo 500 caracteres, puede ser nulo.
 * - city: Máximo 100 caracteres, puede ser nulo.
 * - birthdate: Puede ser nulo (se valida en el backend).
 * */
export const profileSchema = z.object({
  avatar: z.string().url('Debe ser una URL válida').or(z.literal('')).nullable(),
  biography: z.string().max(500, 'Máximo 500 caracteres').nullable(),
  city: z.string().max(100, 'Máximo 100 caracteres').nullable(),
  birthdate: z.string().nullable(),
});

/**
 * Esquema de validación para el formulario de login.
 * Reglas:
 * - email: Requerido, debe ser un email válido.
 * - password: Requerido.
 * */
export const loginSchema = z.object({
  email: z.string().min(1, 'El email es obligatorio').email('Email no válido'),
  password: z.string().min(1, 'La contraseña es obligatoria'),
});

/**
 * Esquema de validación para el formulario de registro.
 * Reglas:
 * - name: Requerido, mínimo 2 caracteres.
 * - email: Requerido, debe ser un email válido.
 * - password: Requerido, mínimo 8 caracteres.
 * - password_confirmation: Requerido, debe coincidir con password.
 * */
export const registerSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres'),
  email: z.string().min(1, 'El email es obligatorio').email('Email no válido'),
  password: z.string().min(8, 'La contraseña debe tener al menos 8 caracteres'),
  password_confirmation: z.string()
}).refine((data) => data.password === data.password_confirmation, {
  message: "Las contraseñas no coinciden",
  path: ["password_confirmation"],
});

/**
 * Esquema de validación para el formulario de propuesta.
 * Reglas:
 * - name: Requerido, mínimo 2 caracteres, máximo 50 caracteres.
 * - description: Requerido, mínimo 20 caracteres, máximo 2000 caracteres.
 * - image_path: Opcional, si se proporciona debe ser una URL válida.
 * */
export const proposalSchema = z.object({
  name: z.string().min(2, 'El título debe tener al menos 2 caracteres').max(50, 'El título es muy largo'),
  description: z.string().min(20, 'La descripción debe ser detallada (mínimo 20 caracteres)').max(2000, 'La descripción es muy larga'),
  image_path: z.string().trim().url('La imagen debe ser una URL válida').or(z.literal('')).optional(),
});

/**
 * Esquema de validación para el formulario de olvido de contraseña.
 * Reglas:
 * - email: Requerido, debe ser un email válido.
 * */
export const forgotPasswordSchema = z.object({
  email: z.string().min(1, 'El email es obligatorio').email('Formato de email inválido'),
});

/**
 * Esquema de validación para el formulario de restablecimiento de contraseña.
 * Reglas:
 * - password: Requerido, mínimo 8 caracteres.
 * - password_confirmation: Requerido, debe coincidir con password.
 * */
export const resetPasswordSchema = z.object({
  password: z.string().min(8, 'La contraseña debe tener al menos 8 caracteres'),
  password_confirmation: z.string().min(1, 'Debes confirmar la contraseña'),
}).refine((data) => data.password === data.password_confirmation, {
  message: "Las contraseñas no coinciden",
  path: ["password_confirmation"],
});
