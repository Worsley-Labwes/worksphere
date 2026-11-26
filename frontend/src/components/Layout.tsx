import { Link, Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../auth';

export default function Layout() {
  const { user, loading, logout } = useAuth();
  const location = useLocation();

  if (loading) return <p className="p-8 text-slate-500">Loading…</p>;
  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />;

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <header className="border-b bg-white">
        <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
          <Link to="/workspaces" className="text-lg font-bold">
            WorkSphere
          </Link>
          <div className="flex items-center gap-4 text-sm">
            <span className="text-slate-600">{user.name}</span>
            <button onClick={logout} className="rounded border px-3 py-1 hover:bg-slate-100">
              Log out
            </button>
          </div>
        </div>
      </header>
      <main className="mx-auto max-w-5xl px-4 py-8">
        <Outlet />
      </main>
    </div>
  );
}