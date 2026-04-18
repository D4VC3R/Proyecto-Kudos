import * as z from 'zod';

export const profileSchema = z.object({
  avatar: z.string().url('Debe ser una URL válida').or(z.literal('')).nullable(),
  biography: z.string().max(500, 'Máximo 500 caracteres').nullable(),
  city: z.string().max(100, 'Máximo 100 caracteres').nullable(),
  birthdate: z.string().nullable(),
});

