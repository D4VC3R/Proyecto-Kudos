import { Navigate, Route, Routes } from 'react-router-dom';
import { AppLayout } from './layouts/AppLayout.jsx';
import { RequireAuth } from './guards/RequireAuth.jsx';
import { RequireVerified } from './guards/RequireVerified.jsx';
import { RequireAdmin } from './guards/RequireAdmin.jsx';

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


export const AppRoutes = () => {
  return (
    <Routes>
      <Route element={<AppLayout />}>

        <Route path="/" element={<HomePage />} />
        <Route path="/ranking" element={<RankingPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/verify-email" element={<VerifyEmailPage />} />
        <Route path="/:categorySlug" element={<CategoryDetail />} />
        <Route path="/forbidden" element={<div>Acceso Denegado</div>} />

        {/* RUTAS PROTEGIDAS: Requieren Sesión */}
        <Route element={<RequireAuth />}>
          <Route path="/profile" element={<ProfilePage />} />

          {/* RUTAS PROTEGIDAS + VERIFICADAS: Sesión + Email Verificado + No Baneado */}
          <Route element={<RequireVerified />}>
            <Route path="/my-proposals" element={<MyProposalsPage />} />
            <Route path="/:categorySlug/proposals/new" element={<NewProposalPage />} />
            <Route path="/:categorySlug/vote" element={<VotePage />} />

            {/* RUTAS DE ADMINISTRACIÓN: Sesión + Verificado + Rol Admin */}
            <Route element={<RequireAdmin />}>
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