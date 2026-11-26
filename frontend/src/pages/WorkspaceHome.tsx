import { Link, useParams } from 'react-router-dom';
import { useFetch } from '../useFetch';
import type { Activity, Project, Stats, Workspace } from '../types';

function Tile({ label, value, alert = false }: { label: string; value: string | number; alert?: boolean }) {
  return (
    <div className="rounded-lg border bg-white p-4 shadow-sm">
      <div className={`text-2xl font-bold ${alert ? 'text-red-600' : ''}`}>{value}</div>
      <div className="text-sm text-slate-500">{label}</div>
    </div>
  );
}

export default function WorkspaceHome() {
  const { id } = useParams();
  const workspace = useFetch<{ data: Workspace }>(`/workspaces/${id}`);
  const stats = useFetch<{ data: Stats }>(`/workspaces/${id}/stats`);
  const activity = useFetch<{ data: Activity[] }>(`/workspaces/${id}/activity`);
  const projects = useFetch<{ data: Project[] }>(`/workspaces/${id}/projects`);

  if (workspace.error) {
    return (
      <div className="space-y-2">
        <p className="text-red-600">Workspace not found.</p>
        <Link to="/workspaces" className="text-indigo-600 underline">
          Back to your workspaces
        </Link>
      </div>
    );
  }
  if (!workspace.data) return <p className="text-slate-500">Loading…</p>;

  const s = stats.data?.data;

  return (
    <div className="space-y-8">
      <div>
        <Link to="/workspaces" className="text-sm text-indigo-600 underline">
          ← All workspaces
        </Link>
        <h1 className="mt-1 text-2xl font-bold">
          {workspace.data.data.name}
          <span className="ml-2 rounded-full bg-slate-100 px-2 py-0.5 align-middle text-xs font-normal">
            {workspace.data.data.role}
          </span>
        </h1>
      </div>

      {s && (
        <section className="grid grid-cols-2 gap-3 sm:grid-cols-5">
          <Tile label="Projects" value={s.projects.total} />
          <Tile label="Tasks" value={s.tasks.total} />
          <Tile label="Completed" value={`${s.tasks.completion}%`} />
          <Tile label="Overdue" value={s.tasks.overdue} alert={s.tasks.overdue > 0} />
          <Tile label="My open tasks" value={s.tasks.mine} />
        </section>
      )}

      <section>
        <h2 className="mb-3 text-lg font-semibold">Projects</h2>
        {projects.data && projects.data.data.length === 0 && <p className="text-slate-600">No projects yet.</p>}
        <ul className="grid gap-3 sm:grid-cols-2">
          {projects.data?.data.map((p) => (
            <li key={p.id} className="rounded-lg border bg-white p-4 shadow-sm">
              <div className="flex items-center justify-between">
                <span className="font-semibold">{p.name}</span>
                <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{p.status}</span>
              </div>
              <div className="mt-3 h-2 rounded bg-slate-200">
                <div className="h-2 rounded bg-indigo-600" style={{ width: `${p.progress ?? 0}%` }} />
              </div>
              <div className="mt-1 text-xs text-slate-500">
                {p.done_tasks_count ?? 0}/{p.tasks_count ?? 0} tasks done
                {p.deadline ? ` · due ${p.deadline}` : ''}
              </div>
            </li>
          ))}
        </ul>
      </section>

      {s && s.team.length > 0 && (
        <section>
          <h2 className="mb-3 text-lg font-semibold">Team productivity</h2>
          <table className="w-full overflow-hidden rounded-lg border bg-white text-sm">
            <thead className="bg-slate-100 text-left">
              <tr>
                <th className="px-3 py-2">Member</th>
                <th className="px-3 py-2">Assigned</th>
                <th className="px-3 py-2">Done</th>
              </tr>
            </thead>
            <tbody>
              {s.team.map((m) => (
                <tr key={m.id} className="border-t">
                  <td className="px-3 py-2">{m.name}</td>
                  <td className="px-3 py-2">{m.assigned}</td>
                  <td className="px-3 py-2">{m.done}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </section>
      )}

      <section>
        <h2 className="mb-3 text-lg font-semibold">Recent activity</h2>
        {activity.data && activity.data.data.length === 0 && <p className="text-slate-600">Nothing yet.</p>}
        <ul className="space-y-2 text-sm">
          {activity.data?.data.map((a) => (
            <li key={a.id} className="rounded border bg-white px-3 py-2">
              <strong>{a.user?.name ?? 'Someone'}</strong> {a.description}
              <span className="ml-2 text-xs text-slate-500">{new Date(a.created_at).toLocaleString()}</span>
            </li>
          ))}
        </ul>
      </section>
    </div>
  );
}