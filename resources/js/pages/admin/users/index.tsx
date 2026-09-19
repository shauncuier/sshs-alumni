import { router, useForm } from '@inertiajs/react';
import {
    Edit3,
    KeyRound,
    Plus,
    RotateCcw,
    Search,
    Shield,
    Trash2,
    UserCheck,
    UserX,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';
import { formatDateTime } from '@/lib/format';
import type { Paginated } from '@/types/member';

type StaffUserItem = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: string;
    roles: string[];
    member: {
        id: number;
        full_name: string;
        membership_no: string | null;
    } | null;
    last_active_at: string | null;
    created_at: string | null;
};

type Props = {
    users: Paginated<StaffUserItem>;
    roles: string[];
    filters: {
        search: string | null;
        role: string | null;
        status: string | null;
    };
};

export default function AdminUsersIndex({
    users,
    roles,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [roleFilter, setRoleFilter] = useState(filters.role || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    const [modalOpen, setModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<StaffUserItem | null>(null);

    const { data, setData, post, put, processing, reset, errors } = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        status: 'active',
        roles: [] as string[],
    });

    const handleApplyFilters = () => {
        router.get(
            '/admin/users',
            {
                search: search || undefined,
                role: roleFilter || undefined,
                status: statusFilter || undefined,
            },
            { preserveState: true }
        );
    };

    const handleReset = () => {
        setSearch('');
        setRoleFilter('');
        setStatusFilter('');
        router.get('/admin/users');
    };

    const openCreate = () => {
        setEditingUser(null);
        reset();
        setData({
            name: '',
            email: '',
            phone: '',
            password: '',
            status: 'active',
            roles: ['Member'],
        });
        setModalOpen(true);
    };

    const openEdit = (user: StaffUserItem) => {
        setEditingUser(user);
        setData({
            name: user.name,
            email: user.email,
            phone: user.phone || '',
            password: '',
            status: user.status,
            roles: user.roles,
        });
        setModalOpen(true);
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        if (editingUser) {
            put(`/admin/users/${editingUser.id}`, {
                onSuccess: () => setModalOpen(false),
            });
        } else {
            post('/admin/users', {
                onSuccess: () => setModalOpen(false),
            });
        }
    };

    const handleDelete = (user: StaffUserItem) => {
        if (confirm(`Delete staff account for ${user.name} (${user.email})?`)) {
            router.delete(`/admin/users/${user.id}`);
        }
    };

    const handleRoleToggle = (roleName: string) => {
        if (data.roles.includes(roleName)) {
            setData('roles', data.roles.filter((r) => r !== roleName));
        } else {
            setData('roles', [...data.roles, roleName]);
        }
    };

    return (
        <AdminLayout title="Staff & Users">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <Users className="h-5 w-5 text-teal-400" />
                            <h1 className="text-2xl font-bold tracking-tight text-slate-100">
                                Staff & User Accounts
                            </h1>
                        </div>
                        <p className="mt-1 text-sm text-slate-400">
                            Manage administrator logins, assign committee RBAC roles, and control active status.
                        </p>
                    </div>

                    <Button
                        onClick={openCreate}
                        className="bg-gradient-to-r from-teal-500 to-teal-600 font-semibold text-slate-950 hover:from-teal-400 hover:to-teal-500"
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        Create Staff User
                    </Button>
                </div>

                {/* Filter Bar */}
                <div className="flex flex-wrap items-center gap-3 rounded-xl border border-teal-500/15 bg-slate-900/40 p-4 backdrop-blur-md">
                    <div className="flex-1 min-w-[200px]">
                        <div className="relative">
                            <Search className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                            <Input
                                placeholder="Search by name, email, or mobile..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9 border-teal-500/20 bg-slate-950 text-slate-100"
                            />
                        </div>
                    </div>

                    <div className="w-44">
                        <select
                            value={roleFilter}
                            onChange={(e) => setRoleFilter(e.target.value)}
                            className="w-full rounded-md border border-teal-500/20 bg-slate-950 px-3 py-2 text-sm text-slate-200 outline-none focus:border-teal-400"
                        >
                            <option value="">All Roles</option>
                            {roles.map((r) => (
                                <option key={r} value={r}>
                                    {r}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="w-36">
                        <select
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                            className="w-full rounded-md border border-teal-500/20 bg-slate-950 px-3 py-2 text-sm text-slate-200 outline-none focus:border-teal-400"
                        >
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </div>

                    <Button
                        onClick={handleApplyFilters}
                        size="sm"
                        className="bg-teal-500 font-semibold text-slate-950 hover:bg-teal-400"
                    >
                        Filter
                    </Button>
                    <Button
                        onClick={handleReset}
                        size="sm"
                        variant="ghost"
                        className="text-slate-400 hover:text-slate-200"
                    >
                        <RotateCcw className="h-4 w-4" />
                    </Button>
                </div>

                {/* Users Table */}
                {users.data.length === 0 ? (
                    <EmptyState
                        icon={Users}
                        title="No users found"
                        description="Try adjusting your search criteria or role filters."
                    />
                ) : (
                    <div className="overflow-hidden rounded-xl border border-teal-500/15 bg-slate-900/40 backdrop-blur-md">
                        <table className="w-full text-left text-sm text-slate-300">
                            <thead className="border-b border-teal-500/10 bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th className="px-5 py-3.5">User Identity</th>
                                    <th className="px-5 py-3.5">Assigned Roles</th>
                                    <th className="px-5 py-3.5">Status</th>
                                    <th className="px-5 py-3.5">Alumni Profile</th>
                                    <th className="px-5 py-3.5">Last Active</th>
                                    <th className="px-5 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-teal-500/10">
                                {users.data.map((u) => (
                                    <tr key={u.id} className="transition-colors hover:bg-teal-500/[0.03]">
                                        <td className="px-5 py-4">
                                            <div className="font-semibold text-slate-100">{u.name}</div>
                                            <div className="text-xs text-slate-400">{u.email}</div>
                                            {u.phone && <div className="text-[11px] text-slate-500">{u.phone}</div>}
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="flex flex-wrap gap-1.5">
                                                {u.roles.map((r) => (
                                                    <span
                                                        key={r}
                                                        className="inline-flex rounded-full border border-teal-500/30 bg-teal-500/10 px-2.5 py-0.5 text-xs font-medium text-teal-300"
                                                    >
                                                        {r}
                                                    </span>
                                                ))}
                                            </div>
                                        </td>
                                        <td className="px-5 py-4">
                                            <Badge
                                                variant="outline"
                                                className={`capitalize ${
                                                    u.status === 'active'
                                                        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                                        : 'border-rose-500/30 bg-rose-500/10 text-rose-400'
                                                }`}
                                            >
                                                {u.status}
                                            </Badge>
                                        </td>
                                        <td className="px-5 py-4 text-xs">
                                            {u.member ? (
                                                <div>
                                                    <span className="text-slate-200">{u.member.full_name}</span>
                                                    {u.member.membership_no && (
                                                        <span className="block font-mono text-slate-500">
                                                            {u.member.membership_no}
                                                        </span>
                                                    )}
                                                </div>
                                            ) : (
                                                <span className="text-slate-500 italic">No linked member</span>
                                            )}
                                        </td>
                                        <td className="px-5 py-4 text-xs text-slate-400 whitespace-nowrap">
                                            {u.last_active_at ? formatDateTime(u.last_active_at) : 'Never'}
                                        </td>
                                        <td className="px-5 py-4 text-right">
                                            <div className="flex items-center justify-end gap-1">
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() => openEdit(u)}
                                                    className="h-8 text-teal-400 hover:bg-teal-500/10"
                                                >
                                                    <Edit3 className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() => handleDelete(u)}
                                                    className="h-8 text-rose-400 hover:bg-rose-500/10"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Pagination */}
                <Pagination meta={users.meta} />
            </div>

            {/* Create/Edit Modal */}
            <Dialog open={modalOpen} onOpenChange={setModalOpen}>
                <DialogContent className="border border-teal-500/20 bg-slate-900 text-slate-100 sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingUser ? `Edit User: ${editingUser.name}` : 'Create Staff User'}
                        </DialogTitle>
                        <DialogDescription className="text-slate-400">
                            Assign roles, access privileges, and set authentication credentials.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="user_name">Full Name</Label>
                                <Input
                                    id="user_name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g. Tanzim Ahmed"
                                    className="border-teal-500/20 bg-slate-950 text-slate-100"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="user_email">Email Address</Label>
                                <Input
                                    id="user_email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="e.g. tanzim@sshs-alumni.org"
                                    className="border-teal-500/20 bg-slate-950 text-slate-100"
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="user_phone">Mobile Number</Label>
                                <Input
                                    id="user_phone"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    placeholder="017XXXXXXXX"
                                    className="border-teal-500/20 bg-slate-950 text-slate-100"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="user_status">Account Status</Label>
                                <select
                                    id="user_status"
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                    className="w-full rounded-md border border-teal-500/20 bg-slate-950 px-3 py-2 text-sm text-slate-200 outline-none focus:border-teal-400"
                                >
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="disabled">Disabled</option>
                                </select>
                                <InputError message={errors.status} />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="user_password">
                                {editingUser ? 'New Password (leave blank to keep current)' : 'Password'}
                            </Label>
                            <Input
                                id="user_password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="••••••••"
                                className="border-teal-500/20 bg-slate-950 text-slate-100"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="space-y-2">
                            <Label>Role Assignments</Label>
                            <div className="grid grid-cols-2 gap-2 rounded-lg border border-teal-500/10 bg-slate-950/60 p-3">
                                {roles.map((r) => (
                                    <label
                                        key={r}
                                        className="flex items-center gap-2 text-xs text-slate-300 cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={data.roles.includes(r)}
                                            onChange={() => handleRoleToggle(r)}
                                            className="rounded border-slate-700 bg-slate-900 text-teal-500"
                                        />
                                        <span>{r}</span>
                                    </label>
                                ))}
                            </div>
                            <InputError message={errors.roles} />
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => setModalOpen(false)}
                                className="text-slate-400 hover:text-slate-200"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="bg-teal-500 font-semibold text-slate-950 hover:bg-teal-400"
                            >
                                {editingUser ? 'Update Account' : 'Save Account'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
