import * as z from 'zod';

export const profileSchema = z.object({
  avatar: z.string().url('Debe ser una URL válida').or(z.literal('')).nullable(),
  biography: z.string().max(500, 'Máximo 500 caracteres').nullable(),
  city: z.string().max(100, 'Máximo 100 caracteres').nullable(),
  birthdate: z.string().nullable(),
});

export const loginSchema = z.object({
  email: z.string().min(1, 'El email es requerido').email('Email no válido'),
  password: z.string().min(1, 'La contraseña es requerida'),
});

export const registerSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres'),
  email: z.string().min(1, 'El email es requerido').email('Email no válido'),
  password: z.string().min(8, 'La contraseña debe tener al menos 8 caracteres'),
  password_confirmation: z.string()
}).refine((data) => data.password === data.password_confirmation, {
  message: "Las contraseñas no coinciden",
  path: ["password_confirmation"],
});

export const proposalSchema = z.object({
  name: z.string().min(2, 'El título debe tener al menos 2 caracteres').max(200, 'El título es muy largo'),
  description: z.string().min(20, 'La descripción debe ser detallada (mínimo 20 caracteres)').max(2000, 'La descripción es muy larga'),
  image_path: z.string().optional(),
  extra_fields: z.array(z.object({
    key: z.string().min(1, 'El nombre es obligatorio'),
    value: z.string().min(1, 'El valor es obligatorio'),
    required: z.boolean().optional(),
    readonlyKey: z.boolean().optional(),
    label: z.string().optional()
  })).max(10, 'Máximo 10 campos extra').optional().default([])
});
