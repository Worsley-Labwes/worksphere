import { useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { api, ApiError } from '../api';
import Field from '../components/Field';
import { useFetch } from '../useFetch';
import type { Invitation, Workspace } from '../types';

export default function Workspaces() {
  const workspaces = useFetch<{ data: Workspace[] }>('/workspaces');
  const invitations = useFetch<{ data: Invitation[] }>('/invitations');
  const navigate = useNavigate();
  const [name, setName] = useState('');
  const [error, setError] = useState<string | undefined>();

  async function create(e: FormEvent) {
    e.preventDefault();
    setError(undefined);
    try {
      const res = await api<{ data: Workspace }>('/workspaces', { method: 'POST', body: { name } });
      navigate(`/workspaces/${res.data.id}`);
    } catch (err) {
      setError(err instanceof ApiError ? (err.errors.name?.[0] ?? err.message) : 'Something went wrong');
    }
  }

  async function respond(token: string, action: 'accept' | 'decline') {
    await api(`/invitations/${token}/${action}`, { method: 'POST' });
    workspaces.reload();
    invitations.reload();
  }

  return (
    <div className="space-y-8">
      <h1 className="text-2xl font-bold">Your workspaces</h1>

      {invitations.data && invitations.data.data.length > 0 && (
        <section className="rounded-lg border border-amber-300 bg-amber-50 p-4">
          <h2 className="mb-2 font-semibold">You have invitations</h2>
          <ul className="space-y-2">
            {invitations.data.data.map((inv) => (
              <li key={inv.id} className="flex items-center justify-between gap-4 text-sm">
                <span>
                  Join <strong>{inv.workspace?.name}</strong> as {inv.role}
                </span>
                <span className="space-x-2">
                  <button
                    onClick={() => inv.token && respond(inv.token, 'accept')}
                    className="rounded bg-indigo-600 px-3 py-1 text-white hover:bg-indigo-700"
                  >
                    Accept
                  </button>
                  <button
                    onClick={() => inv.token && respond(inv.token, 'decline')}
                    className="rounded border px-3 py-1 hover:bg-white"
                  >
                    Decline
                  </button>
                </span>
              </li>
            ))}
          </ul>
        </section>
      )}

      <section>
        {workspaces.loading && <p className="text-slate-500">Loading…</p>}
        {workspaces.error && <p className="text-red-600">{workspaces.error}</p>}
        {workspaces.data && workspaces.data.data.length === 0 && (
          <p className="text-slate-600">You don&apos;t belong to any workspace yet. Create one below.</p>
        )}
        <ul className="grid gap-3 sm:grid-cols-2">
          {workspaces.data?.data.map((w) => (
            <li key={w.id}>
              <Link
                to={`/workspaces/${w.id}`}
                className="block rounded-lg border bg-white p-4 shadow-sm hover:border-indigo-400"
              >
                <span className="font-semibold">{w.name}</span>
                <span className="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs">{w.role}</span>
              </Link>
            </li>
          ))}
        </ul>
      </section>

      <form onSubmit={create} className="max-w-sm space-y-3">
        <h2 className="font-semibold">New workspace</h2>
        <Field label="Name" value={name} onChange={(e) => setName(e.target.value)} required error={error} />
        <button type="submit" className="rounded bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700">
          Create workspace
        </button>
      </form>
    </div>
  );
}