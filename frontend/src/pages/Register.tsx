import { useState, type FormEvent } from 'react';
import { Link, Navigate, useNavigate } from 'react-router-dom';
import { ApiError } from '../api';
import { useAuth } from '../auth';
import Field from '../components/Field';

export default function Register() {
  const { user, register } = useAuth();
  const navigate = useNavigate();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [busy, setBusy] = useState(false);

  if (user) return <Navigate to="/workspaces" replace />;

  async function submit(e: FormEvent) {
    e.preventDefault();
    setBusy(true);
    setErrors({});
    try {
      await register(name, email, password, confirmation);
      navigate('/workspaces', { replace: true });
    } catch (err) {
      setErrors(err instanceof ApiError ? err.errors : { name: ['Something went wrong'] });
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
      <form onSubmit={submit} className="w-full max-w-sm space-y-4 rounded-lg border bg-white p-6 shadow-sm">
        <h1 className="text-xl font-bold">Create your account</h1>
        <Field label="Name" value={name} onChange={(e) => setName(e.target.value)} required error={errors.name?.[0]} />
        <Field
          label="Email"
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
          error={errors.email?.[0]}
        />
        <Field
          label="Password"
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
          error={errors.password?.[0]}
        />
        <Field
          label="Confirm password"
          type="password"
          value={confirmation}
          onChange={(e) => setConfirmation(e.target.value)}
          required
        />
        <button
          type="submit"
          disabled={busy}
          className="w-full rounded bg-indigo-600 py-2 font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
        >
          {busy ? 'Creating…' : 'Register'}
        </button>
        <p className="text-sm text-slate-600">
          Already registered?{' '}
          <Link to="/login" className="text-indigo-600 underline">
            Log in
          </Link>
        </p>
      </form>
    </div>
  );
}