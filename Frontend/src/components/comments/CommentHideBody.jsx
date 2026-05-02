import React from 'react';
import { InputField } from '../common/InputField';

export const CommentHideBody = ({ hideReason, setHideReason }) => (
  <div className="flex flex-col gap-4">
    <p className="text-slate-600">
      El comentario se ocultará públicamente pero se mantendrá en el sistema. Los administradores podrán restaurarlo.
    </p>
    <InputField
      label="Razón (obligatoria)"
      placeholder="Motivo para ocultar..."
      value={hideReason}
      onChange={(e) => setHideReason(e.target.value)}
      autoFocus
    />
  </div>
);