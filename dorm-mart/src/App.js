import { createBrowserRouter, RouterProvider, Navigate } from 'react-router-dom'
import RootLayout from './pages/RootLayout';
import LoginPage from './pages/LoginPage';
import HomePage from './pages/HomePage';
import ItemDetailPage from './pages/PurchaseHistory/ItemDetailPage';
import PurchaseHistoryPage from './pages/PurchaseHistory/PurchaseHistoryPage';
import PurchaseHistoryLayout from './pages/PurchaseHistory/PurchaseHistoryLayout';
import UserPreferences from './pages/UserPreferences/UserPreferences';

/* Browser router with basename so URLs are clean under the app's subpath. */

export const router = createBrowserRouter([
  // Redirect root to /landing
  {
    path: "/",
    element: <Navigate to="/landing" replace />,
  },
  // Optional: keep a login route
  {
    path: "/login",
    element: <LoginPage />,
  },
  // Dedicated Landing route
  {
    path: "/landing",
    element: <RootLayout />,
    children: [
      { index: true, element: <HomePage /> },
    ],
  },
  // User Preferences at clean top-level path
  {
    path: "/userpreferences",
    element: <RootLayout />,
    children: [
      { index: true, element: <UserPreferences /> },
    ],
  },
  // Alias with space redirects to clean path
  { path: "/userpreferences page", element: <Navigate to="/userpreferences" replace /> },
  // Keep legacy /app routes minimally wired
  {
    path: "/app",
    element: <RootLayout />,
    children: [
      { index: true, element: <HomePage /> },
      {
        path: "purchase-history",
        element: <PurchaseHistoryLayout />,
        children: [
          { index: true, element: <PurchaseHistoryPage /> },
          { path: "item-detail/:id", element: <ItemDetailPage /> },
        ],
      },
    ],
  },
], { basename: process.env.PUBLIC_URL || '/' });

export default function App() {
  return <RouterProvider router={router} />;
}
