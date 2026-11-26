export type Role = 'owner' | 'admin' | 'member';

export interface User {
  id: number;
  name: string;
  email: string;
}

export interface Workspace {
  id: number;
  name: string;
  role: Role;
  created_at: string;
}

export interface Invitation {
  id: number;
  email: string;
  role: Role;
  status: string;
  expires_at: string;
  workspace?: { id: number; name: string };
  token?: string;
}

export interface Project {
  id: number;
  workspace_id: number;
  name: string;
  description: string | null;
  status: 'planning' | 'active' | 'completed' | 'archived';
  deadline: string | null;
  tasks_count?: number;
  done_tasks_count?: number;
  progress?: number;
}

export interface Stats {
  projects: { total: number; by_status: Record<string, number> };
  tasks: {
    total: number;
    by_status: Record<'todo' | 'in_progress' | 'done', number>;
    completion: number;
    overdue: number;
    mine: number;
  };
  team: { id: number; name: string; assigned: number; done: number }[];
}

export interface Activity {
  id: number;
  action: string;
  description: string;
  user: { id: number; name: string } | null;
  created_at: string;
}