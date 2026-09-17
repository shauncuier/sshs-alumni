import { useForm } from '@inertiajs/react';
import { Lock, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';

type Role = {
    id: number;
    name: string;
    users_count: number;
    permissions: string[];
    is_protected: boolean;
};

type PermissionEntry = { name: string; action: string };

type Props = {
    roles: Role[];
    permissionGroups: Record<string, PermissionEntry[]>;
};

export default function RolesIndex({ roles, permissionGroups }: Props) {
    const { t } = useTranslation();
    const [selectedId, setSelectedId] = useState<number>(
        roles.find((role) => !role.is_protected)?.id ?? roles[0].id,
    );

    const selected = roles.find((role) => role.id === selectedId) ?? roles[0];

    return (
        <AdminLayout title={t('admin.nav.roles')}>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {t('admin.nav.roles')}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {t('admin.roles.intro')}
                    </p>
                </div>

                <div className="grid gap-6 lg:grid-cols-[18rem_1fr]">
                    <nav
                        aria-label={t('admin.nav.roles')}
                        className="flex flex-col gap-1"
                    >
                        {roles.map((role) => (
                            <button
                                key={role.id}
                                type="button"
                                onClick={() => setSelectedId(role.id)}
                                aria-current={role.id === selectedId}
                                className={`flex items-center gap-2 rounded-md px-3 py-2 text-start text-sm transition-colors ${
                                    role.id === selectedId
                                        ? 'bg-brand-green-100 text-brand-green-800 dark:bg-sidebar-accent dark:text-foreground font-medium'
                                        : 'hover:bg-accent'
                                }`}
                            >
                                <ShieldCheck
                                    className="size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                <span className="flex-1 truncate">
                                    {role.name}
                                </span>
                                {role.is_protected && (
                                    <Lock
                                        className="text-muted-foreground size-3.5 shrink-0"
                                        aria-label={t('admin.roles.locked')}
                                    />
                                )}
                                <Badge
                                    variant="secondary"
                                    className="tabular-nums"
                                >
                                    {formatNumber(role.users_count)}
                                </Badge>
                            </button>
                        ))}
                    </nav>

                    <RoleEditor
                        key={selected.id}
                        role={selected}
                        permissionGroups={permissionGroups}
                    />
                </div>
            </div>
        </AdminLayout>
    );
}

function RoleEditor({
    role,
    permissionGroups,
}: {
    role: Role;
    permissionGroups: Record<string, PermissionEntry[]>;
}) {
    const { t } = useTranslation();

    const form = useForm<{ permissions: string[] }>({
        permissions: role.permissions,
    });

    const toggle = (name: string, checked: boolean) => {
        form.setData(
            'permissions',
            checked
                ? [...form.data.permissions, name]
                : form.data.permissions.filter((one) => one !== name),
        );
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.put(`/admin/roles/${role.id}`, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            {role.is_protected && (
                <div className="border-brand-gold-500/50 bg-brand-gold-100/40 text-brand-ink flex items-start gap-2 rounded-lg border p-4 text-sm">
                    <Lock
                        className="mt-0.5 size-4 shrink-0"
                        aria-hidden="true"
                    />
                    <p>{t('admin.roles.protected_note')}</p>
                </div>
            )}

            <div className="grid gap-4 md:grid-cols-2">
                {Object.entries(permissionGroups).map(([module, entries]) => (
                    <Card key={module}>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold capitalize">
                                {module}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2.5">
                            {entries.map((entry) => (
                                <div
                                    key={entry.name}
                                    className="flex items-center gap-2"
                                >
                                    <Checkbox
                                        id={`${role.id}-${entry.name}`}
                                        checked={form.data.permissions.includes(
                                            entry.name,
                                        )}
                                        disabled={role.is_protected}
                                        onCheckedChange={(checked) =>
                                            toggle(entry.name, checked === true)
                                        }
                                    />
                                    <Label
                                        htmlFor={`${role.id}-${entry.name}`}
                                        className="cursor-pointer text-sm font-normal"
                                    >
                                        {entry.action}
                                    </Label>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                ))}
            </div>

            {!role.is_protected && (
                <div className="flex items-center gap-3">
                    <Button type="submit" disabled={form.processing}>
                        {t('common.actions.save')}
                    </Button>
                    {form.recentlySuccessful && (
                        <span className="text-muted-foreground text-sm">
                            {t('common.states.saved')}
                        </span>
                    )}
                </div>
            )}
        </form>
    );
}
