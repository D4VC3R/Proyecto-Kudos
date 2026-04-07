import { Navigate, Route, Routes } from 'react-router-dom';
import { RequireAdmin } from './guards/RequireAdmin.jsx';
import { RequireAuth } from './guards/RequireAuth.jsx';
import { RequireVerified } from './guards/RequireVerified.jsx';
import { AdminCategoriesPage } from '../pages/AdminCategoriesPage.jsx';
import { AdminItemsPage } from '../pages/AdminItemsPage.jsx';
import { AdminPage } from '../pages/AdminPage.jsx';
import { AdminProposalsPage } from '../pages/AdminProposalsPage.jsx';
import { AdminUserDetailPage } from '../pages/AdminUserDetailPage.jsx';
import { AdminUsersPage } from '../pages/AdminUsersPage.jsx';
import { ForbiddenPage } from '../pages/ForbiddenPage.jsx';
import { HomePage } from '../pages/HomePage.jsx';
import { LoginPage } from '../pages/LoginPage.jsx';
import { NotFoundPage } from '../pages/NotFoundPage.jsx';
import { ProfilePage } from '../pages/ProfilePage.jsx';
import { UserRankingPage } from '../pages/UserRankingPage.jsx';
import { VotePage } from '../pages/VotePage.jsx';
import {AppLayout} from "./layouts/AppLayout.jsx";

export const AppRoutes = () => {
  return (
    <Routes>
      <Route element={<AppLayout />}>
        <Route element={<HomePage />} path="/" />
        <Route element={<UserRankingPage />} path="/ranking" />
        <Route element={<LoginPage />} path="/login" />
        <Route element={<ForbiddenPage />} path="/forbidden" />

        <Route element={<RequireAuth />}>
          <Route element={<RequireVerified />}>
            <Route element={<ProfilePage />} path="/profile" />
            <Route element={<VotePage />} path="/categories/:categoryId/vote" />

            <Route element={<RequireAdmin />}>
              <Route element={<AdminPage />} path="/admin" />
              <Route element={<AdminUsersPage />} path="/admin/users" />
              <Route element={<AdminUserDetailPage />} path="/admin/users/:userId" />
              <Route element={<AdminItemsPage />} path="/admin/items" />
              <Route element={<AdminCategoriesPage />} path="/admin/categories" />
              <Route element={<AdminProposalsPage />} path="/admin/proposals" />
            </Route>
          </Route>
        </Route>

        <Route element={<Navigate replace to="/" />} path="/home" />
        <Route element={<NotFoundPage />} path="*" />
      </Route>
    </Routes>
  );
};
