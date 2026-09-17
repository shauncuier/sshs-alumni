import { Link, router } from '@inertiajs/react';
import { GripVertical, List } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import AdminLayout from '@/layouts/admin-layout';
import type { Contact, PipelineColumn } from '@/types/crm';

type Props = {
    columns: PipelineColumn[];
    filters: { owner: string | null };
    can: { move: boolean };
};

/**
 * The pipeline board.
 *
 * Drag to advance. The drop posts to the stage endpoint, which goes through
 * PipelineService — so a move made by dragging is recorded on the contact's
 * timeline exactly like one made from the detail page.
 *
 * Native HTML drag and drop rather than a library: eight columns of cards is
 * not worth a dependency, and the keyboard path below (a select on each card)
 * is what makes this usable without a mouse anyway.
 */
export default function Pipeline({ columns, filters, can }: Props) {
    const { t } = useTranslation();
    const [dragging, setDragging] = useState<string | null>(null);
    const [over, setOver] = useState<string | null>(null);

    const move = (ulid: string, stage: string) => {
        router.post(
            `/admin/crm/contacts/${ulid}/stage`,
            { stage },
            { preserveScroll: true, preserveState: false },
        );
    };

    return (
        <AdminLayout title={t('admin.crm.pipeline')}>
            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t('admin.crm.pipeline')}
                    </h1>

                    <div className="flex flex-wrap gap-2">
                        <Select
                            value={filters.owner ?? '__all'}
                            onValueChange={(value) =>
                                router.get(
                                    '/admin/crm/pipeline',
                                    {
                                        owner:
                                            value === '__all'
                                                ? undefined
                                                : value,
                                    },
                                    { preserveState: true, replace: true },
                                )
                            }
                        >
                            <SelectTrigger className="w-auto min-w-40">
                                <SelectValue
                                    placeholder={t('admin.crm.owner')}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all">
                                    {t('admin.crm.owner')}
                                </SelectItem>
                                <SelectItem value="me">
                                    {t('admin.crm.owner_mine')}
                                </SelectItem>
                                <SelectItem value="none">
                                    {t('admin.crm.owner_unassigned')}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Button asChild size="sm" variant="outline">
                            <Link href="/admin/crm/contacts">
                                <List
                                    className="me-1 size-4"
                                    aria-hidden="true"
                                />
                                {t('admin.crm.contacts')}
                            </Link>
                        </Button>
                    </div>
                </div>

                {can.move && (
                    <p className="text-muted-foreground text-sm">
                        {t('admin.crm.move_hint')}
                    </p>
                )}

                <div className="flex gap-4 overflow-x-auto pb-4">
                    {columns.map((column) => (
                        <div
                            key={column.stage}
                            onDragOver={(event) => {
                                if (!can.move) {
                                    return;
                                }

                                event.preventDefault();
                                setOver(column.stage);
                            }}
                            onDragLeave={() => setOver(null)}
                            onDrop={(event) => {
                                event.preventDefault();
                                setOver(null);

                                if (can.move && dragging) {
                                    move(dragging, column.stage);
                                }

                                setDragging(null);
                            }}
                            className={cn(
                                'bg-muted/40 w-72 shrink-0 rounded-lg border p-3',
                                over === column.stage &&
                                    'border-brand-green-600 bg-brand-green-100/50',
                            )}
                        >
                            <div className="mb-3 flex items-center justify-between gap-2">
                                <h2 className="text-sm font-semibold">
                                    {column.label}
                                </h2>
                                <Badge variant="secondary">
                                    {formatNumber(column.total)}
                                </Badge>
                            </div>

                            <div className="space-y-2">
                                {column.contacts.length === 0 ? (
                                    <p className="text-muted-foreground px-1 py-4 text-xs">
                                        {t('admin.crm.pipeline_empty')}
                                    </p>
                                ) : (
                                    column.contacts.map((contact) => (
                                        <ContactCard
                                            key={contact.ulid}
                                            contact={contact}
                                            columns={columns}
                                            draggable={can.move}
                                            onDragStart={() =>
                                                setDragging(contact.ulid)
                                            }
                                            onDragEnd={() => setDragging(null)}
                                            onMove={(stage) =>
                                                move(contact.ulid, stage)
                                            }
                                        />
                                    ))
                                )}

                                {column.total > column.shown && (
                                    <Link
                                        href={`/admin/crm/contacts?stage=${column.stage}`}
                                        className="text-muted-foreground hover:text-foreground block px-1 py-2 text-xs"
                                    >
                                        {t('admin.crm.and_more', {
                                            count: formatNumber(
                                                column.total - column.shown,
                                            ),
                                        })}
                                    </Link>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}

function ContactCard({
    contact,
    columns,
    draggable,
    onDragStart,
    onDragEnd,
    onMove,
}: {
    contact: Contact;
    columns: PipelineColumn[];
    draggable: boolean;
    onDragStart: () => void;
    onDragEnd: () => void;
    onMove: (stage: string) => void;
}) {
    const { t } = useTranslation();

    return (
        <div
            draggable={draggable}
            onDragStart={onDragStart}
            onDragEnd={onDragEnd}
            className="bg-card space-y-2 rounded-md border p-3 shadow-sm"
        >
            <div className="flex items-start gap-2">
                {draggable && (
                    <GripVertical
                        className="text-muted-foreground mt-0.5 size-4 shrink-0 cursor-grab"
                        aria-hidden="true"
                    />
                )}

                <div className="min-w-0 flex-1">
                    <Link
                        href={`/admin/crm/contacts/${contact.ulid}`}
                        className="block truncate text-sm font-medium hover:underline"
                    >
                        {contact.name}
                    </Link>

                    <p className="text-muted-foreground truncate text-xs">
                        {contact.organization_name ??
                            contact.owner?.name ??
                            t('admin.crm.owner_none')}
                    </p>
                </div>
            </div>

            {(contact.tags ?? []).length > 0 && (
                <div className="flex flex-wrap gap-1">
                    {(contact.tags ?? []).map((tag) => (
                        <span
                            key={tag.id}
                            className="rounded-full border px-1.5 py-0.5 text-[0.65rem]"
                            style={
                                tag.color
                                    ? {
                                          borderColor: tag.color,
                                          color: tag.color,
                                      }
                                    : undefined
                            }
                        >
                            {tag.name}
                        </span>
                    ))}
                </div>
            )}

            {/* The keyboard path. Dragging is the fast way; this is the way
                that works without a mouse. */}
            {draggable && (
                <Select value={contact.pipeline_status} onValueChange={onMove}>
                    <SelectTrigger
                        className="h-7 text-xs"
                        aria-label={t('admin.crm.stage')}
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {columns.map((column) => (
                            <SelectItem key={column.stage} value={column.stage}>
                                {column.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}
        </div>
    );
}
