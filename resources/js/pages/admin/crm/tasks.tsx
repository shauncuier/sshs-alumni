import { Link, router, useForm } from '@inertiajs/react';
import { CheckCircle2, ListTodo, Plus, RotateCcw, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/format';
import AdminLayout from '@/layouts/admin-layout';
import type { Option, Task } from '@/types/crm';
import type { Paginated } from '@/types/member';

type Props = {
    tasks: Paginated<Task>;
    filters: {
        status: string | null;
        assignee: string | null;
        overdue: boolean;
    };
    options: {
        statuses: Option[];
        priorities: Option[];
        assignees: Option[];
    };
    can: { create: boolean };
};

const PRIORITY_VARIANT: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    urgent: 'destructive',
    high: 'default',
    normal: 'secondary',
    low: 'outline',
};

export default function Tasks({ tasks, filters, options, can }: Props) {
    const { t } = useTranslation();

    const setFilter = (key: string, value: string | boolean) => {
        router.get(
            '/admin/crm/tasks',
            {
                ...filters,
                [key]: value === '__all' || value === false ? undefined : value,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AdminLayout title={t('admin.crm.tasks')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.crm.tasks')}
                    </h1>

                    {can.create && <CreateTaskDialog options={options} />}
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Select
                        value={filters.status ?? '__all'}
                        onValueChange={(value) => setFilter('status', value)}
                    >
                        <SelectTrigger className="w-auto min-w-40">
                            <SelectValue
                                placeholder={t('common.labels.status')}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all">
                                {t('common.labels.status')}
                            </SelectItem>
                            {options.statuses.map((status) => (
                                <SelectItem
                                    key={status.value}
                                    value={status.value}
                                >
                                    {status.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.assignee ?? '__all'}
                        onValueChange={(value) => setFilter('assignee', value)}
                    >
                        <SelectTrigger className="w-auto min-w-40">
                            <SelectValue
                                placeholder={t('admin.crm.task_assignee')}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all">
                                {t('admin.crm.task_assignee')}
                            </SelectItem>
                            <SelectItem value="me">
                                {t('admin.crm.owner_mine')}
                            </SelectItem>
                            <SelectItem value="none">
                                {t('admin.crm.owner_unassigned')}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={filters.overdue}
                            onCheckedChange={(checked) =>
                                setFilter('overdue', checked === true)
                            }
                        />
                        {t('admin.crm.overdue_only')}
                    </label>
                </div>

                {tasks.data.length === 0 ? (
                    <EmptyState
                        icon={ListTodo}
                        title={t('common.states.empty')}
                        description={t('admin.crm.tasks_empty')}
                    />
                ) : (
                    <div className="divide-y rounded-lg border">
                        {tasks.data.map((task) => (
                            <TaskRow key={task.id} task={task} />
                        ))}
                    </div>
                )}

                <Pagination meta={tasks.meta} />
            </div>
        </AdminLayout>
    );
}

function TaskRow({ task }: { task: Task }) {
    const { t } = useTranslation();

    const done = task.status === 'done';

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 p-4">
            <div className="min-w-0">
                <p
                    className={
                        done
                            ? 'text-muted-foreground truncate font-medium line-through'
                            : 'truncate font-medium'
                    }
                >
                    {task.title}
                </p>

                <p className="text-muted-foreground truncate text-sm">
                    {task.subject ? (
                        <Link
                            href={
                                task.subject.kind === 'contact'
                                    ? `/admin/crm/contacts/${task.subject.ulid}`
                                    : `/admin/members/${task.subject.ulid}`
                            }
                            className="hover:underline"
                        >
                            {task.subject.name}
                        </Link>
                    ) : (
                        t('admin.crm.no_subject')
                    )}
                    {task.assignee ? ` · ${task.assignee.name}` : ''}
                </p>
            </div>

            <div className="flex flex-wrap items-center gap-2">
                {task.due_at && (
                    <span
                        className={
                            task.is_overdue
                                ? 'text-destructive text-sm'
                                : 'text-muted-foreground text-sm'
                        }
                    >
                        {formatDate(task.due_at)}
                    </span>
                )}

                <Badge variant={PRIORITY_VARIANT[task.priority] ?? 'secondary'}>
                    {task.priority_label}
                </Badge>

                {task.is_overdue && (
                    <Badge variant="destructive">
                        {t('admin.crm.task_overdue')}
                    </Badge>
                )}

                <Button
                    size="sm"
                    variant={done ? 'ghost' : 'outline'}
                    onClick={() =>
                        router.put(
                            `/admin/crm/tasks/${task.id}`,
                            { status: done ? 'open' : 'done' },
                            { preserveScroll: true },
                        )
                    }
                >
                    {done ? (
                        <>
                            <RotateCcw
                                className="me-1 size-3.5"
                                aria-hidden="true"
                            />
                            {t('admin.crm.task_reopen')}
                        </>
                    ) : (
                        <>
                            <CheckCircle2
                                className="me-1 size-3.5"
                                aria-hidden="true"
                            />
                            {t('admin.crm.task_complete')}
                        </>
                    )}
                </Button>

                <Button
                    size="icon"
                    variant="ghost"
                    aria-label={t('common.actions.delete')}
                    onClick={() =>
                        router.delete(`/admin/crm/tasks/${task.id}`, {
                            preserveScroll: true,
                        })
                    }
                >
                    <Trash2 className="size-4" aria-hidden="true" />
                </Button>
            </div>
        </div>
    );
}

function CreateTaskDialog({
    options,
}: {
    options: { priorities: Option[]; assignees: Option[] };
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({
        title: '',
        due_at: '',
        priority: 'normal',
        assigned_to: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        form.post('/admin/crm/tasks', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <Button size="sm" onClick={() => setOpen(true)}>
                <Plus className="me-1 size-4" aria-hidden="true" />
                {t('admin.crm.task_add')}
            </Button>
        );
    }

    return (
        <Card className="w-full">
            <CardContent className="p-5">
                <form onSubmit={submit} className="space-y-3">
                    <div className="space-y-1.5">
                        <Label htmlFor="title">
                            {t('admin.crm.task_title')}
                        </Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <div className="grid gap-3 sm:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="due_at">
                                {t('admin.crm.task_due')}
                            </Label>
                            <Input
                                id="due_at"
                                type="date"
                                value={form.data.due_at}
                                onChange={(e) =>
                                    form.setData('due_at', e.target.value)
                                }
                            />
                            <InputError message={form.errors.due_at} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="priority">
                                {t('admin.crm.task_priority')}
                            </Label>
                            <Select
                                value={form.data.priority}
                                onValueChange={(value) =>
                                    form.setData('priority', value)
                                }
                            >
                                <SelectTrigger id="priority">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.priorities.map((priority) => (
                                        <SelectItem
                                            key={priority.value}
                                            value={priority.value}
                                        >
                                            {priority.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="assigned_to">
                                {t('admin.crm.task_assignee')}
                            </Label>
                            <Select
                                value={form.data.assigned_to || '__none'}
                                onValueChange={(value) =>
                                    form.setData(
                                        'assigned_to',
                                        value === '__none' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger id="assigned_to">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__none">
                                        {t('admin.crm.owner_none')}
                                    </SelectItem>
                                    {options.assignees.map((assignee) => (
                                        <SelectItem
                                            key={assignee.value}
                                            value={assignee.value}
                                        >
                                            {assignee.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Button
                            type="submit"
                            size="sm"
                            disabled={form.processing}
                        >
                            {t('common.actions.save')}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            onClick={() => setOpen(false)}
                        >
                            {t('common.actions.cancel')}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
