import type { InputHTMLAttributes } from 'react';

interface Props extends InputHTMLAttributes<HTMLInputElement> {
  label: string;
  error?: string;
}

export default function Field({ label, error, ...input }: Props) {
  return (
    <label className="block text-sm font-medium">
      {label}
      <input
        {...input}
        className="mt-1 w-full rounded border border-slate-300 px-3 py-2 font-normal focus:border-indigo-500 focus:outline-none"
      />
      {error && <span className="mt-1 block font-normal text-red-600">{error}</span>}
    </label>
  );
}
