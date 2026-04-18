import * as z from 'zod';

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
  })).max(10, 'Máximo 10 campos extra')
});

