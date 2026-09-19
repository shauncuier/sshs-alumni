import { Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    FileText,
    Mail,
    Pencil,
    Plus,
    Smartphone,
    Trash2,
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
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import type { CampaignChannelType, MessageTemplate } from '@/types/campaign';
import type { Paginated } from '@/types/member';

type Props = {
    templates: Paginated<MessageTemplate>;
    channels: CampaignChannelType[];
    filters: { channel: string | null };
};

export default function AdminTemplatesIndex({
    templates,
    channels,
    filters,
}: Props) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editingTemplate, setEditingTemplate] = useState<MessageTemplate | null>(null);

    const { data, setData, post, put, processing, reset, errors } = useForm({
        name: '',
        key: '',
        channel: 'sms' as CampaignChannelType,
        subject: '',
        subject_bn: '',
        body: '',
        body_bn: '',
    });

    const openCreate = () => {
        setEditingTemplate(null);
        reset();
        setModalOpen(true);
    };

    const openEdit = (template: MessageTemplate) => {
        setEditingTemplate(template);
        setData({
            name: template.name,
            key: template.key,
            channel: template.channel,
            subject: template.subject || '',
            subject_bn: template.subject_bn || '',
            body: template.body,
            body_bn: template.body_bn || '',
        });
        setModalOpen(true);
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        if (editingTemplate) {
            put(`/admin/message-templates/${editingTemplate.id}`, {
                onSuccess: () => setModalOpen(false),
            });
        } else {
            post('/admin/message-templates', {
                onSuccess: () => setModalOpen(false),
            });
        }
    };

    const handleDelete = (template: MessageTemplate) => {
        if (template.is_system) {
            alert('System templates cannot be deleted.');
            return;
        }

        if (confirm(`Delete message template "${template.name}"?`)) {
            router.delete(`/admin/message-templates/${template.id}`);
        }
    };

    return (
        <AdminLayout title="Message Templates">
            <div className="space-y-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <Link href="/admin/campaigns">
                                <Button variant="ghost" size="sm" className="gap-1 text-slate-400 hover:text-slate-100">
                                    <ArrowLeft className="h-4 w-4" />
                                    Campaigns
                                </Button>
                            </Link>
                        </div>
                        <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-100">
                            Message Templates
                        </h1>
                        <p className="text-sm text-slate-400">
                            Reusable templates for SMS blasts and transactional email notifications.
                        </p>
                    </div>

                    <Button
                        onClick={openCreate}
                        className="bg-gradient-to-r from-teal-500 to-teal-600 font-semibold text-slate-950 hover:from-teal-400 hover:to-teal-500"
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        Create Template
                    </Button>
                </div>

                {/* Templates List */}
                {templates.data.length === 0 ? (
                    <EmptyState
                        icon={FileText}
                        title="No templates created"
                        description="Define reusable templates with personal dynamic variables for SMS and email."
                    />
                ) : (
                    <div className="grid gap-4 md:grid-cols-2">
                        {templates.data.map((t) => (
                            <Card
                                key={t.id}
                                className="border border-teal-500/15 bg-slate-900/40 backdrop-blur-md transition-all hover:border-teal-500/30"
                            >
                                <CardContent className="p-5">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <h3 className="font-semibold text-slate-100">{t.name}</h3>
                                                {t.is_system && (
                                                    <Badge variant="outline" className="border-teal-500/30 text-teal-400 text-[10px]">
                                                        System
                                                    </Badge>
                                                )}
                                            </div>
                                            <div className="mt-1 flex items-center gap-2 text-xs text-slate-400">
                                                <span className="flex items-center gap-1 font-medium text-teal-400">
                                                    {t.channel === 'sms' ? (
                                                        <Smartphone className="h-3 w-3" />
                                                    ) : (
                                                        <Mail className="h-3 w-3" />
                                                    )}
                                                    {t.channel.toUpperCase()}
                                                </span>
                                                <span>•</span>
                                                <code className="text-slate-500">{t.key}</code>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-1">
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => openEdit(t)}
                                                className="h-8 w-8 p-0 text-slate-400 hover:text-teal-400"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                            {!t.is_system && (
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() => handleDelete(t)}
                                                    className="h-8 w-8 p-0 text-slate-400 hover:text-rose-400"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>

                                    {t.subject && (
                                        <div className="mt-3 text-xs font-medium text-slate-300">
                                            Subject: <span className="text-slate-400 font-normal">{t.subject}</span>
                                        </div>
                                    )}

                                    <div className="mt-3 rounded-lg border border-teal-500/10 bg-slate-950/60 p-3 text-xs text-slate-300 whitespace-pre-wrap font-sans">
                                        {t.body}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                <Pagination meta={templates.meta} />
            </div>

            {/* Template Dialog */}
            <Dialog open={modalOpen} onOpenChange={setModalOpen}>
                <DialogContent className="border border-teal-500/20 bg-slate-900 text-slate-100 sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingTemplate ? 'Edit Message Template' : 'Create Message Template'}
                        </DialogTitle>
                        <DialogDescription className="text-slate-400">
                            Configure message content and dynamic placeholder tags.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="t_name">Template Name</Label>
                                <Input
                                    id="t_name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g. Jubilee Ticket Confirmation"
                                    className="border-teal-500/20 bg-slate-950 text-slate-100"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="t_channel">Channel</Label>
                                <select
                                    id="t_channel"
                                    value={data.channel}
                                    onChange={(e) => setData('channel', e.target.value as CampaignChannelType)}
                                    className="w-full rounded-md border border-teal-500/20 bg-slate-950 px-3 py-2 text-sm text-slate-200 outline-none focus:border-teal-400"
                                >
                                    <option value="sms">SMS</option>
                                    <option value="mail">Email</option>
                                </select>
                            </div>
                        </div>

                        {data.channel === 'mail' && (
                            <div className="space-y-2">
                                <Label htmlFor="t_subject">Email Subject</Label>
                                <Input
                                    id="t_subject"
                                    value={data.subject}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    placeholder="e.g. Your Golden Jubilee Registration Confirmation"
                                    className="border-teal-500/20 bg-slate-950 text-slate-100"
                                />
                                <InputError message={errors.subject} />
                            </div>
                        )}

                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="t_body">Message Body</Label>
                                <span className="text-[11px] text-slate-400">
                                    Tags: <code className="text-teal-400">&#123;name&#125;</code>,{' '}
                                    <code className="text-teal-400">&#123;batch&#125;</code>,{' '}
                                    <code className="text-teal-400">&#123;membership_no&#125;</code>
                                </span>
                            </div>
                            <Textarea
                                id="t_body"
                                rows={5}
                                value={data.body}
                                onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setData('body', e.target.value)}
                                placeholder="Dear {name}, thank you for registering..."
                                className="border-teal-500/20 bg-slate-950 text-slate-100"
                            />
                            <InputError message={errors.body} />
                        </div>

                        <DialogFooter className="gap-2">
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
                                className="bg-teal-500 text-slate-950 font-semibold hover:bg-teal-400"
                            >
                                {editingTemplate ? 'Update Template' : 'Save Template'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
