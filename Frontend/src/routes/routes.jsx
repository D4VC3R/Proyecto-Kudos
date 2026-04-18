import { Navigate, Route, Routes } from 'react-router-dom';
import { AppLayout } from './layouts/AppLayout.jsx';

// Guards
import { RequireAuth } from './guards/RequireAuth.jsx';
import { RequireVerified } from './guards/RequireVerified.jsx';
import { RequireAdmin } from './guards/RequireAdmin.jsx';

// Páginas (Ejemplos basados en tu backend)
import HomePage from '../pages/HomePage.jsx';
import ProfilePage from "../pages/profile/ProfilePage.jsx";
import {LoginPage} from "../pages/LoginPage.jsx";
import {RegisterPage} from "../pages/RegisterPage.jsx";
import {VerifyEmailPage} from "../pages/auth/VerifyEmailPage.jsx";
import VotePage from "../pages/VotePage.jsx";
import CategoryDetail from "../pages/CategoryDetail.jsx";
import RankingPage from "../pages/RankingPage.jsx";
import NewProposalPage from "../pages/proposals/NewProposalPage.jsx";
import MyProposalsPage from "../pages/proposals/MyProposalsPage.jsx";
// Importa el resto de tus páginas aquí...

export const AppRoutes = () => {
  return (
    <Routes>
      {/* El AppLayout envuelve el contenido para que el Navbar/Footer se mantenga persistente en la navegación.*/}
      <Route element={<AppLayout />}>

        {/* 1. RUTAS PÚBLICAS */}
        <Route path="/" element={<HomePage />} />
        <Route path="/ranking" element={<RankingPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/verify-email" element={<VerifyEmailPage />} />
        <Route path="/:categorySlug" element={<CategoryDetail />} />
        <Route path="/forbidden" element={<div>Acceso Denegado</div>} />

        {/* 2. RUTAS PROTEGIDAS: Requieren Sesión */}
        <Route element={<RequireAuth />}>
          <Route path="/profile" element={<ProfilePage />} />

          {/* 3. RUTAS PROTEGIDAS + VERIFICADAS: Sesión + Email Verificado + No Baneado */}
          <Route element={<RequireVerified />}>
            <Route path="/my-proposals" element={<MyProposalsPage />} />
            <Route path="/:categorySlug/proposals/new" element={<NewProposalPage />} />
            <Route path="/:categorySlug/vote" element={<VotePage />} />

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