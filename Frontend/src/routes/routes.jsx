import { Navigate, Route, Routes } from 'react-router-dom';
import { AppLayout } from './layouts/AppLayout.jsx';

// Guards
import { RequireAuth } from './guards/RequireAuth.jsx';
import { RequireVerified } from './guards/RequireVerified.jsx';
import { RequireAdmin } from './guards/RequireAdmin.jsx';

// Páginas (Ejemplos basados en tu backend)
import HomePage from '../pages/HomePage.jsx';
// Importa el resto de tus páginas aquí...

export const AppRoutes = () => {
  return (
    <Routes>
      {/* El AppLayout envuelve el contenido para que el Navbar/Footer se mantenga persistente en la navegación.*/}
      <Route element={<AppLayout />}>

        {/* 1. RUTAS PÚBLICAS */}
        <Route path="/" element={<HomePage />} />
        <Route path="/login" element={<div>Login Page</div>} />
        <Route path="/register" element={<div>Register Page</div>} />
        <Route path="/forbidden" element={<div>Acceso Denegado</div>} />

        {/* 2. RUTAS PROTEGIDAS: Requieren Sesión */}
        <Route element={<RequireAuth />}>
          <Route path="/profile" element={<div>Profile Page</div>} />
          {/* Aquí iría la ruta para reenviar el email de verificación si no están verificados */}
          <Route path="/verify-email" element={<div>Verificar Email</div>} />

          {/* 3. RUTAS PROTEGIDAS + VERIFICADAS: Sesión + Email Verificado + No Baneado */}
          <Route element={<RequireVerified />}>
            <Route path="/my-proposals" element={<div>Mis Propuestas</div>} />
            <Route path="/proposals/new" element={<div>Crear Propuesta</div>} />

            {/* 4. RUTAS DE ADMINISTRACIÓN: Sesión + Verificado + Rol Admin */}
            <Route element={<RequireAdmin />}>
              {/* Puedes crear un layout secundario exclusivo para el admin si lo deseas */}
              <Route path="/admin" element={<div>Admin Dashboard</div>} />
              <Route path="/admin/users" element={<div>Gestión de Usuarios</div>} />
              <Route path="/admin/proposals" element={<div>Revisión de Propuestas</div>} />
            </Route>

          </Route>
        </Route>

        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  );
};