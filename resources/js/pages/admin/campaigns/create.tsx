import { Link, useForm } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Mail,
    Send,
    Smartphone,
    Sparkles,
} from 'lucide-react';
import { useMemo } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import type { CampaignChannelType, MessageTemplate } from '@/types/campaign';

type BatchOption = { id: number; ssc_year: number };

type Props = {
    channels: CampaignChannelType[];
    audiences: string[];
    batches: BatchOption[];
    roles: string[];
    templates: MessageTemplate[];
};

export default function AdminCampaignCreate({
    channels,
    audiences,
    batches,
    roles,
    templates,
}: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        name: string;
        channel: CampaignChannelType;
        subject: string;
        subject_bn: string;
        body: string;
        body_bn: string;
        audience_type: string;
        audience_filters: Record<string, any>;
        message_template_id: number | null;
        scheduled_at: string;
    }>({
        name: '',
        channel: 'sms',
        subject: '',
        subject_bn: '',
        body: '',
        body_bn: '',
        audience_type: 'all_members',
        audience_filters: {},
        message_template_id: null,
        scheduled_at: '',
    });

    // Real-time SMS computation
    const smsStats = useMemo(() => {
        const text = data.body || '';
        // Detect Bengali Unicode range
        const isUnicode = /[\u0980-\u09FF]/.test(text);
        const length = text.length;

        let segments = 0;
        if (length === 0) {
            segments = 0;
        } else if (isUnicode) {
            segments = length <= 70 ? 1 : Math.ceil(length / 67);
        } else {
            segments = length <= 160 ? 1 : Math.ceil(length / 153);
        }

        return {
            length,
            isUnicode,
            segments,
            segmentLimit: isUnicode ? 70 : 160,
        };
    }, [data.body]);

    const handleTemplateSelect = (templateId: string) => {
        if (!templateId) {
            setData((prev) => ({
                ...prev,
                message_template_id: null,
            }));
            return;
        }

        const t = templates.find((item) => item.id === Number(templateId));
        if (t) {
            setData((prev) => ({
                ...prev,
                message_template_id: t.id,
                channel: t.channel,
                subject: t.subject || prev.subject,
                subject_bn: t.subject_bn || prev.subject_bn,
                body: t.body,
                body_bn: t.body_bn || prev.body_bn,
            }));
        }
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/campaigns');
    };

    return (
        <AdminLayout title="Compose New Campaign">
            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex items-center gap-4">
                    <Link href="/admin/campaigns">
                        <Button variant="ghost" size="sm" className="gap-2 text-slate-400 hover:text-slate-100">
                            <ArrowLeft className="h-4 w-4" />
                            Back to Campaigns
                        </Button>
                    </Link>
                </div>

                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-slate-100">
                        Create Broadcast Campaign
                    </h1>
                    <p className="text-sm text-slate-400">
                        Target members across SMS or Email with personalized tags and live cost estimation.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Template Preset Selector */}
                    {templates.length > 0 && (
                        <Card className="border border-teal-500/15 bg-slate-900/60 backdrop-blur-md">
                            <CardContent className="p-5">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <Label className="flex items-center gap-2 text-sm font-semibold text-teal-300">
                                            <Sparkles className="h-4 w-4" />
                                            Use a Pre-Approved Message Template (Optional)
                                        </Label>
                                        <p className="text-xs text-slate-400">
                                            Auto-fills body, channel, and standard variables.
                                        </p>
                                    </div>
                                    <select
                                        value={data.message_template_id ?? ''}
                                        onChange={(e: ChangeEvent<HTMLSelectElement>) => handleTemplateSelect(e.target.value)}
                                        className="rounded-lg border border-teal-500/20 bg-slate-950 px-3 py-2 text-sm text-slate-200 outline-none focus:border-teal-400 sm:w-64"
                                    >
                                        <option value="">-- Choose Template --</option>
                                        {templates.map((t) => (
                                            <option key={t.id} value={t.id}>
                                                {t.name} ({t.channel.toUpperCase()})
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <Card className="border border-teal-500/15 bg-slate-900/40 backdrop-blur-md">
                        <CardContent className="space-y-5 p-6">
                            {/* Campaign Name */}
                            <div className="space-y-2">
                                <Label htmlFor="name" className="text-sm font-medium text-slate-200">
                                    Campaign Name
                                </Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e: ChangeEvent<HTMLInputElement>) => setData('name', e.target.value)}
                                    placeholder="e.g. Golden Jubilee First Batch Announcement"
                                    className="border-teal-500/20 bg-slate-950/80 text-slate-100 placeholder:text-slate-600 focus:border-teal-400"
                                />
                                <InputError message={errors.name} />
                            </div>

                            {/* Channel Selector */}
                            <div className="space-y-2">
                                <Label className="text-sm font-medium text-slate-200">Delivery Channel</Label>
                                <div className="grid grid-cols-2 gap-4">
                                    <button
                                        type="button"
                                        onClick={() => setData('channel', 'sms')}
                                        className={`flex items-center gap-3 rounded-xl border p-4 text-left transition-all ${
                                            data.channel === 'sms'
                                                ? 'border-teal-500 bg-teal-500/10 text-teal-300 ring-1 ring-teal-500/50'
                                                : 'border-slate-800 bg-slate-950/50 text-slate-400 hover:border-slate-700'
                                        }`}
                                    >
                                        <Smartphone className="h-6 w-6 text-teal-400" />
                                        <div>
                                            <div className="font-semibold text-slate-100">SMS Broadcast</div>
                                            <div className="text-xs text-slate-400">Direct mobile delivery via BulkSMSBD</div>
                                        </div>
                                    </button>

                                    <button
                                        type="button"
                                        onClick={() => setData('channel', 'mail')}
                                        className={`flex items-center gap-3 rounded-xl border p-4 text-left transition-all ${
                                            data.channel === 'mail'
                                                ? 'border-teal-500 bg-teal-500/10 text-teal-300 ring-1 ring-teal-500/50'
                                                : 'border-slate-800 bg-slate-950/50 text-slate-400 hover:border-slate-700'
                                        }`}
                                    >
                                        <Mail className="h-6 w-6 text-teal-400" />
                                        <div>
                                            <div className="font-semibold text-slate-100">Email Notification</div>
                                            <div className="text-xs text-slate-400">Rich HTML formatted transactional email</div>
                                        </div>
                                    </button>
                                </div>
                                <InputError message={errors.channel} />
                            </div>

                            {/* Target Audience */}
                            <div className="space-y-2">
                                <Label htmlFor="audience" className="text-sm font-medium text-slate-200">
                                    Target Audience
                                </Label>
                                <select
                                    id="audience"
                                    value={data.audience_type}
                                    onChange={(e: ChangeEvent<HTMLSelectElement>) => setData('audience_type', e.target.value)}
                                    className="w-full rounded-lg border border-teal-500/20 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-teal-400"
                                >
                                    <option value="all_members">All Approved Alumni Members</option>
                                    <option value="batch">Specific SSC Batch Cohort</option>
                                    <option value="role">Users by Role (e.g. Coordinators, Admins)</option>
                                </select>
                                <InputError message={errors.audience_type} />

                                {/* Sub-filter: Batch */}
                                {data.audience_type === 'batch' && (
                                    <div className="mt-3 rounded-lg border border-teal-500/20 bg-slate-950/60 p-3">
                                        <Label className="text-xs text-slate-400">Select SSC Batch</Label>
                                        <select
                                            value={data.audience_filters.batch_id ?? ''}
                                            onChange={(e: ChangeEvent<HTMLSelectElement>) =>
                                                setData('audience_filters', {
                                                    ...data.audience_filters,
                                                    batch_id: Number(e.target.value),
                                                })
                                            }
                                            className="mt-1 w-full rounded border border-teal-500/20 bg-slate-900 px-3 py-2 text-xs text-slate-200 outline-none"
                                        >
                                            <option value="">-- Choose Batch --</option>
                                            {batches.map((b) => (
                                                <option key={b.id} value={b.id}>
                                                    Batch {b.ssc_year}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                )}

                                {/* Sub-filter: Role */}
                                {data.audience_type === 'role' && (
                                    <div className="mt-3 rounded-lg border border-teal-500/20 bg-slate-950/60 p-3">
                                        <Label className="text-xs text-slate-400">Select System Role</Label>
                                        <select
                                            value={data.audience_filters.role ?? ''}
                                            onChange={(e: ChangeEvent<HTMLSelectElement>) =>
                                                setData('audience_filters', {
                                                    ...data.audience_filters,
                                                    role: e.target.value,
                                                })
                                            }
                                            className="mt-1 w-full rounded border border-teal-500/20 bg-slate-900 px-3 py-2 text-xs text-slate-200 outline-none"
                                        >
                                            <option value="">-- Choose Role --</option>
                                            {roles.map((r) => (
                                                <option key={r} value={r}>
                                                    {r}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                )}
                            </div>

                            {/* Email Subject (if Mail) */}
                            {data.channel === 'mail' && (
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="subject" className="text-sm font-medium text-slate-200">
                                            Email Subject (English)
                                        </Label>
                                        <Input
                                            id="subject"
                                            value={data.subject}
                                            onChange={(e: ChangeEvent<HTMLInputElement>) => setData('subject', e.target.value)}
                                            placeholder="e.g. Important Notice Regarding Golden Jubilee Registration"
                                            className="border-teal-500/20 bg-slate-950/80 text-slate-100"
                                        />
                                        <InputError message={errors.subject} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="subject_bn" className="text-sm font-medium text-slate-200">
                                            Email Subject (Bangla - Optional)
                                        </Label>
                                        <Input
                                            id="subject_bn"
                                            value={data.subject_bn}
                                            onChange={(e: ChangeEvent<HTMLInputElement>) => setData('subject_bn', e.target.value)}
                                            placeholder="সুবর্ণজয়ন্তী সংক্রান্ত জরুরি বিজ্ঞপ্তি"
                                            className="border-teal-500/20 bg-slate-950/80 text-slate-100"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Message Body */}
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="body" className="text-sm font-medium text-slate-200">
                                        Message Content
                                    </Label>
                                    <div className="text-xs text-slate-400">
                                        Available tags:{' '}
                                        <code className="text-teal-400">&#123;name&#125;</code>,{' '}
                                        <code className="text-teal-400">&#123;batch&#125;</code>,{' '}
                                        <code className="text-teal-400">&#123;membership_no&#125;</code>
                                    </div>
                                </div>
                                <Textarea
                                    id="body"
                                    rows={5}
                                    value={data.body}
                                    onChange={(e: ChangeEvent<HTMLTextAreaElement>) => setData('body', e.target.value)}
                                    placeholder="Write your message here..."
                                    className="border-teal-500/20 bg-slate-950/80 text-slate-100 placeholder:text-slate-600 focus:border-teal-400 font-sans"
                                />
                                <InputError message={errors.body} />
                            </div>

                            {/* Live SMS Counter & Segment Estimator */}
                            {data.channel === 'sms' && (
                                <div className="rounded-xl border border-teal-500/20 bg-slate-950/90 p-4">
                                    <div className="flex flex-wrap items-center justify-between gap-3 text-xs">
                                        <div className="flex items-center gap-2">
                                            <span className="text-slate-400">Encoding:</span>
                                            {smsStats.isUnicode ? (
                                                <Badge className="bg-amber-500/20 text-amber-300 border-amber-500/30">
                                                    Unicode (Bangla / Special)
                                                </Badge>
                                            ) : (
                                                <Badge className="bg-emerald-500/20 text-emerald-300 border-emerald-500/30">
                                                    GSM-7 (Standard Latin)
                                                </Badge>
                                            )}
                                        </div>

                                        <div className="flex items-center gap-4 text-slate-300">
                                            <span>
                                                Length:{' '}
                                                <strong className="text-teal-300 font-mono">
                                                    {smsStats.length}
                                                </strong>{' '}
                                                chars
                                            </span>
                                            <span>
                                                Segments:{' '}
                                                <strong className="text-teal-300 font-mono">
                                                    {smsStats.segments}
                                                </strong>{' '}
                                                ({smsStats.segmentLimit} chars/seg)
                                            </span>
                                        </div>
                                    </div>

                                    {smsStats.isUnicode && (
                                        <div className="mt-3 flex items-start gap-2 rounded-lg bg-amber-500/10 p-2.5 text-xs text-amber-300/90 border border-amber-500/20">
                                            <AlertCircle className="h-4 w-4 shrink-0 mt-0.5" />
                                            <span>
                                                Bangla characters require Unicode transmission (70 characters per segment), which bills at approximately 2.3× standard GSM-7 SMS.
                                            </span>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Optional Bangla Body for multilingual templates */}
                            <div className="space-y-2">
                                <Label htmlFor="body_bn" className="text-sm font-medium text-slate-400">
                                    Bangla Message Body (Optional alternate version)
                                </Label>
                                <Textarea
                                    id="body_bn"
                                    rows={4}
                                    value={data.body_bn}
                                    onChange={(e: ChangeEvent<HTMLTextAreaElement>) => setData('body_bn', e.target.value)}
                                    placeholder="বাংলায় বার্তা লিখুন (ঐচ্ছিক)..."
                                    className="border-teal-500/15 bg-slate-950/60 text-slate-100 placeholder:text-slate-600 focus:border-teal-400"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex items-center justify-end gap-3">
                        <Link href="/admin/campaigns">
                            <Button variant="ghost" className="text-slate-400 hover:text-slate-200">
                                Cancel
                            </Button>
                        </Link>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-gradient-to-r from-teal-500 to-teal-600 font-semibold text-slate-950 hover:from-teal-400 hover:to-teal-500"
                        >
                            <Send className="mr-2 h-4 w-4" />
                            Save Draft & Preview Audience
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
