import { useForm } from '@inertiajs/react';
import { Flag } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';

type Option = { value: string; label: string };

type Props = {
    /** Where to POST the report. */
    url: string;
    reasons: Option[];
};

/**
 * Reporting a post or a comment.
 *
 * The description says plainly what a report does and does not do: a moderator
 * reads it, nothing disappears because it was filed, and the author is not
 * told who filed it. People who do not know that either report nothing or
 * report everything.
 */
export function ReportDialog({ url, reasons }: Props) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useForm({ reason: reasons[0]?.value ?? 'other', note: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post(url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    size="sm"
                    className="text-muted-foreground"
                >
                    <Flag className="me-1 size-3.5" aria-hidden="true" />
                    {t('member.community.report')}
                </Button>
            </DialogTrigger>

            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>
                            {t('member.community.report_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('member.community.report_help')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-1.5">
                        <Label htmlFor="reason">
                            {t('member.community.report_reason')}
                        </Label>
                        <Select
                            value={form.data.reason}
                            onValueChange={(value) =>
                                form.setData('reason', value)
                            }
                        >
                            <SelectTrigger id="reason">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {reasons.map((reason) => (
                                    <SelectItem
                                        key={reason.value}
                                        value={reason.value}
                                    >
                                        {reason.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.reason} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="note">
                            {t('member.community.report_note')}
                        </Label>
                        <textarea
                            id="note"
                            rows={3}
                            value={form.data.note}
                            onChange={(event) =>
                                form.setData('note', event.target.value)
                            }
                            className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none"
                        />
                        <InputError message={form.errors.note} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            {t('member.community.report')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
