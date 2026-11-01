import {Navigate, Route, Routes} from 'react-router-dom';
import RequireGuest from './guards/RequireGuest.jsx';
import RequireAuth from './guards/RequireAuth.jsx';
import RequireVerified from './guards/RequireVerified.jsx';
import RequireAdmin from './guards/RequireAdmin.jsx';

import HomePage from '../pages/HomePage.jsx';
import ProfilePage from "../pages/profile/ProfilePage.jsx";
import VerifyEmailPage from "../pages/auth/VerifyEmailPage.jsx";
import VotePage from "../pages/VotePage.jsx";
import CategoryPage from "../pages/CategoryPage.jsx";
import RankingPage from "../pages/RankingPage.jsx";
import SearchPage from "../pages/SearchPage.jsx";
import ListPage from "../pages/ListPage.jsx";
import PublicProfilePage from "../pages/PublicProfilePage.jsx";
import WishlistPage from "../pages/WishlistPage.jsx";
import MyListsPage from "../pages/MyListsPage.jsx";
import MyListEditPage from "../pages/MyListEditPage.jsx";
import OnboardingPage from "../pages/OnboardingPage.jsx";
import NewProposalPage from "../pages/NewProposalPage.jsx";
import AuthPage from "../pages/auth/AuthPage.jsx";
import AdminLayout from "../pages/admin/AdminLayout.jsx";
import AdminUsers from "../pages/admin/AdminUsers.jsx";
import AdminCategories from "../pages/admin/AdminCategories.jsx";
import AdminItems from "../pages/admin/AdminItems.jsx";
import AdminProposals from "../pages/admin/AdminProposals.jsx";
import AdminLists from "../pages/admin/AdminLists.jsx";
import AdminReports from "../pages/admin/AdminReports.jsx";
import AdminListDetail from "../pages/admin/AdminListDetail.jsx";
import AdminUserDetail from "../pages/admin/AdminUserDetail.jsx";
import ExplorePage from "../pages/ExplorePage.jsx";
import ItemDetailPage from "../pages/ItemDetailPage.jsx";
import ForgotPasswordPage from "../pages/auth/ForgotPasswordPage.jsx";
import ResetPasswordPage from "../pages/auth/ResetPasswordPage.jsx";
import ForbiddenPage from "../pages/auth/ForbiddenPage.jsx";
import ErrorPage from "../pages/ErrorPage.jsx";
import PrivacyPage from "../pages/legal/PrivacyPage.jsx";
import LegalNoticePage from "../pages/legal/LegalNoticePage.jsx";


const AppRoutes = () => {
  return (
    <Routes>

      <Route path="/" element={<HomePage/>}/>

      <Route element={<RequireGuest/>}>
        <Route path="/login" element={<AuthPage/>}/>
        <Route path="/register" element={<AuthPage/>}/>
        <Route path="/verify-email" element={<VerifyEmailPage/>}/>
        <Route path="/forgot-password" element={<ForgotPasswordPage/>}/>
        <Route path="/password-reset/:token" element={<ResetPasswordPage/>}/>
      </Route>

      <Route path="/ranking" element={<RankingPage/>}/>
      <Route path="/buscar" element={<SearchPage/>}/>
      <Route path="/listas/:slug" element={<ListPage/>}/>
      <Route path="/u/:username" element={<PublicProfilePage/>}/>
      <Route path="/privacidad" element={<PrivacyPage/>}/>
      <Route path="/aviso-legal" element={<LegalNoticePage/>}/>
      <Route path="/:categorySlug" element={<CategoryPage/>}/>
      <Route path="/:categorySlug/explore" element={<ExplorePage/>}/>
      <Route path="/:categorySlug/item/:itemId" element={<ItemDetailPage/>}/>

      <Route path="/forbidden" element={<ForbiddenPage />}/>

      <Route element={<RequireAuth/>}>
        <Route element={<RequireVerified/>}>
          <Route path="/profile" element={<ProfilePage/>}/>
          <Route path="/wishlist" element={<WishlistPage/>}/>
          <Route path="/mis-listas" element={<MyListsPage/>}/>
          <Route path="/mis-listas/:listId" element={<MyListEditPage/>}/>
          <Route path="/onboarding" element={<OnboardingPage/>}/>
          <Route path="/my-proposals" element={<ProfilePage tab={'proposals'}/>}/>
          <Route path="/:categorySlug/proposals/new" element={<NewProposalPage/>}/>
          <Route path="/:categorySlug/proposals/:proposalId/edit" element={<NewProposalPage isEdit={true}/>}/>
          <Route path="/:categorySlug/vote" element={<VotePage/>}/>

          <Route element={<RequireAdmin/>}>
            <Route path="/admin" element={<AdminLayout/>}>
              <Route path="users" element={<AdminUsers/>}/>
              <Route path="users/:userId" element={<AdminUserDetail/>}/>
              <Route path="categories" element={<AdminCategories/>}/>
              <Route path="items" element={<AdminItems/>}/>
              <Route path="proposals" element={<AdminProposals/>}/>
              <Route path="lists" element={<AdminLists/>}/>
              <Route path="lists/:listId" element={<AdminListDetail/>}/>
              <Route path="reports" element={<AdminReports/>}/>
            </Route>
          </Route>
        </Route>
      </Route>

      <Route path="/404" element={<ErrorPage />}/>
      <Route path="*" element={<Navigate to="/404" replace />} />
    </Routes>
  );
};

export default AppRoutes;