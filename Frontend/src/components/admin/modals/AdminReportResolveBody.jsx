import React from 'react';
import TextAreaField from '../../ui/TextAreaField.jsx';

const ACTION_TEXT = {
  hide: 'Ocultar',
  delete: 'Eliminar',
  dismiss: 'Descartar',
};

// Cuerpo del modal para resolver un reporte (F3-04): ocultar, eliminar o descartar.
const AdminReportResolveBody = ({ actionType, report, note, setNote }) => {
  const isDismiss = actionType === 'dismiss';
  const isUser = report?.reportable_type === 'user';

  return (
    <>
      <p className="text-nav-item">
        Estás a punto de <span className="font-bold">{ACTION_TEXT[actionType]}</span>
        {isDismiss ? ' el reporte' : isUser ? ' el contenido del perfil' : ' el contenido reportado'}
        {report?.content?.excerpt && (
          <span className="font-bold text-text-highlight"> «{report.content.excerpt}»</span>
        )}.
      </p>

      <div className="mt-2">
        <TextAreaField
          label={isDismiss ? 'Nota interna (opcional)' : 'Motivo para el autor (obligatorio)'}
          placeholder={isDismiss ? 'Por qué no se actúa sobre este reporte...' : 'Explica al autor por qué se retira el contenido...'}
          value={note}
          onChange={(e) => setNote(e.target.value)}
          maxLength={1000}
        />
      </div>

      {!isDismiss && (
        <div className="p-3 mt-2 bg-blue-50 rounded-xl text-sm text-blue-700 font-medium">
          El autor recibirá una notificación con este motivo y el correo para reclamar. Se cerrarán también los demás reportes pendientes de este contenido.
          {isUser && ' Se borrarán la biografía, los enlaces, la ciudad y el avatar del perfil.'}
        </div>
      )}
    </>
  );
};

export default AdminReportResolveBody;
