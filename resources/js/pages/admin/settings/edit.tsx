import { Link, router, useForm } from '@inertiajs/react';
import {
    Building2,
    Calendar,
    Globe,
    Lock,
    Mail,
    Save,
    Settings,
    Share2,
    Shield,
    Sparkles,
    UserCheck,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';

type Props = {
    currentGroup: string;
    groupSettings: Record<string, any>;
    allSettings: Record<string, Record<string, any>>;
    groups: Array<{ value: string; label: string }>;
};

export default function AdminSettingsEdit({
    currentGroup,
    groupSettings,
    groups,
}: Props) {
    const { data, setData, put, processing } = useForm({
        settings: groupSettings || {},
    });

    const handleFieldChange = (key: string, value: any) => {
        setData('settings', {
            ...data.settings,
            [key]: value,
        });
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        put(`/admin/settings/${currentGroup}`);
    };

    const getGroupIcon = (groupKey: string) => {
        switch (groupKey) {
            case 'organization':
                return <Building2 className="h-4 w-4" />;
            case 'school':
                return <Building2 className="h-4 w-4 text-emerald-400" />;
            case 'contact':
                return <Mail className="h-4 w-4" />;
            case 'social':
                return <Share2 className="h-4 w-4" />;
            case 'registration':
                return <UserCheck className="h-4 w-4" />;
            case 'jubilee':
                return <Sparkles className="h-4 w-4 text-amber-400" />;
            case 'privacy':
                return <Lock className="h-4 w-4" />;
            case 'seo':
                return <Globe className="h-4 w-4" />;
            case 'membership':
            case 'event':
                return <Calendar className="h-4 w-4" />;
            default:
                return <Settings className="h-4 w-4" />;
        }
    };

    return (
        <AdminLayout title="System Settings">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-100">
                            System Settings
                        </h1>
                        <p className="text-sm text-slate-400">
                            Configure institutional identity, registration governance, Golden Jubilee spotlight, and platform policies.
                        </p>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-4">
                    {/* Groups Navigation Sidebar */}
                    <div className="space-y-1">
                        <div className="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Configuration Groups
                        </div>
                        {groups.map((group) => {
                            const isActive = group.value === currentGroup;
                            return (
                                <Link
                                    key={group.value}
                                    href={`/admin/settings?group=${group.value}`}
                                    className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors ${
                                        isActive
                                            ? 'bg-teal-500/15 text-teal-300 font-semibold ring-1 ring-teal-500/30'
                                            : 'text-slate-400 hover:bg-slate-900/60 hover:text-slate-200'
                                    }`}
                                >
                                    {getGroupIcon(group.value)}
                                    <span>{group.label}</span>
                                </Link>
                            );
                        })}
                    </div>

                    {/* Settings Form Panel */}
                    <div className="md:col-span-3">
                        <Card className="border border-teal-500/15 bg-slate-900/40 backdrop-blur-md">
                            <CardHeader className="border-b border-teal-500/10 pb-4">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="text-lg font-semibold text-slate-100 capitalize">
                                            {currentGroup} Settings
                                        </CardTitle>
                                        <CardDescription className="text-xs text-slate-400">
                                            Updates take effect immediately and refresh the cached configuration across the platform.
                                        </CardDescription>
                                    </div>
                                    {currentGroup === 'school' && (
                                        <span className="rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-xs text-emerald-400">
                                            Est. 1976 (EIIN: 105070)
                                        </span>
                                    )}
                                    {currentGroup === 'organization' && (
                                        <span className="rounded-md border border-teal-500/30 bg-teal-500/10 px-2 py-0.5 text-xs text-teal-400">
                                            Association Est. 2015
                                        </span>
                                    )}
                                </div>
                            </CardHeader>

                            <CardContent className="pt-6">
                                <form onSubmit={handleSubmit} className="space-y-5">
                                    {currentGroup === 'organization' && (
                                        <>
                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <div className="space-y-2">
                                                    <Label htmlFor="org_name">Association Name (English)</Label>
                                                    <Input
                                                        id="org_name"
                                                        value={data.settings.name ?? 'SSHS Alumni Association'}
                                                        onChange={(e) => handleFieldChange('name', e.target.value)}
                                                        className="border-teal-500/20 bg-slate-950 text-slate-100"
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="org_name_bn">Association Name (Bangla)</Label>
                                                    <Input
                                                        id="org_name_bn"
                                                        value={data.settings.name_bn ?? 'সবুজ শিক্ষায়তন প্রাক্তন ছাত্র-ছাত্রী পরিষদ'}
                                                        onChange={(e) => handleFieldChange('name_bn', e.target.value)}
                                                        className="border-teal-500/20 bg-slate-950 text-slate-100 font-bengali"
                                                    />
                                                </div>
                                            </div>

                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <div className="space-y-2">
                                                    <Label htmlFor="org_year">Established Year</Label>
                                                    <Input
                                                        id="org_year"
                                                        value={data.settings.established_year ?? '2015'}
                                                        onChange={(e) => handleFieldChange('established_year', e.target.value)}
                                                        className="border-teal-500/20 bg-slate-950 text-slate-100"
                                                    />
                                                    <p className="text-[11px] text-slate-500">
                                                        The Association was officially formed in 2015.
                                                    </p>
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="org_logo">Association Logo URL / Path</Label>
                                                    <Input
                                                        id="org_logo"
                                                        value={data.settings.logo_url ?? '/images/logo.png'}
                                                        onChange={(e) => handleFieldChange('logo_url', e.target.value)}
                                                        className="border-teal-500/20 bg-slate-950 text-slate-100"
                                                    />
                                                </div>
                                            </div>
                                        </>
                                    )}

                                    {currentGroup === 'school' && (
                                        <>
                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <div className="space-y-2">
                                                    <Label htmlFor="school_name">School Name (English)</Label>
                                                    <Input
                                                        id="school_name"
                                                        value={data.settings.name ?? 'Sabuj Shikshayatan High School'}
                                                        onChange={(e) => handleFieldChange('name', e.target.value)}
                                                        className="border-teal-500/20 bg-slate-950 text-slate-100"
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="school_name_bn">School Name (Bangla)</Label>
                                                    <Input
                                                        id="school_name_bn"
                                                        value={data.settings.name_bn ?? 'সবুজ শিক্ষায়তন উচ্চ বিদ্যালয়'}
                                                        onChange={(e) => handleFieldChange('name_bn', e.target.value)}
                                                        className="border-teal-500/20 bg-slate-950 text-slate-100 font-bengali"
                                                    />
                                                </div>
                                            </div>

                                            <div className="grid gap-4 sm:grid-cols-3">
                                                <div className="space-y-2">
                                                    <Label htmlFor="school_year">School Founding Year</Label>
                                                    <Input
                                                        id="school_year"
                                                        value={data.settings.established_year ?? '1976'}
                                                        onChange={(e) => handleFieldChange('established_year', e.target.value)}
                                                        className="border-teal-500/20 bg-slate-950 text-slate-100"
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="school_eiin">School EIIN Number</Label>
                                                    <Input
                                                        id="school_eiin"
                                                        value={data.settings.eiin ?? '105070'}
                                                        onChange={(e) => handleFieldChange('eiin', e.target.value)}
                                                        className="border-teal-500/20 bg-slate-950 text-slate-100"
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="school_board">Education Board</Label>
                                                    <Input
                                                        id="school_board"
                                                        value={data.settings.board ?? 'Chattogram'}
                                                        onChange={(e) => handleFieldChange('board', e.target.value)}
                                                        className="border-teal-500/20 bg-slate-950 text-slate-100"
                                                    />
                                                </div>
                                            </div>
                                        </>
                                    )}

                                    {currentGroup === 'jubilee' && (
                                        <>
                                            <div className="space-y-2">
                                                <Label htmlFor="jubilee_theme">Golden Jubilee Theme Tagline</Label>
                                                <Input
                                                    id="jubilee_theme"
                                                    value={data.settings.theme ?? 'Celebrating 50 Years of Excellence, Heritage & Brotherhood'}
                                                    onChange={(e) => handleFieldChange('theme', e.target.value)}
                                                    className="border-teal-500/20 bg-slate-950 text-slate-100"
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="jubilee_hero">Spotlight Description</Label>
                                                <textarea
                                                    id="jubilee_hero"
                                                    rows={3}
                                                    value={data.settings.description ?? ''}
                                                    onChange={(e) => handleFieldChange('description', e.target.value)}
                                                    className="w-full rounded-md border border-teal-500/20 bg-slate-950 p-3 text-sm text-slate-100 outline-none focus:border-teal-400"
                                                />
                                            </div>

                                            <div className="flex items-center gap-2 pt-2">
                                                <input
                                                    type="checkbox"
                                                    id="countdown_enabled"
                                                    checked={Boolean(data.settings.countdown_enabled)}
                                                    onChange={(e) => handleFieldChange('countdown_enabled', e.target.checked)}
                                                    className="rounded border-slate-700 bg-slate-900 text-teal-500"
                                                />
                                                <Label htmlFor="countdown_enabled" className="text-sm font-medium text-slate-200">
                                                    Enable Jubilee countdown clock on front door
                                                </Label>
                                            </div>
                                        </>
                                    )}

                                    {currentGroup === 'privacy' && (
                                        <div className="space-y-4">
                                            <div className="flex items-center justify-between rounded-lg border border-teal-500/20 bg-slate-950/60 p-4">
                                                <div>
                                                    <div className="font-semibold text-slate-100">Public Alumni Directory</div>
                                                    <div className="text-xs text-slate-400">
                                                        Allow unauthenticated guests to search verified members (governed by individual member privacy opt-ins).
                                                    </div>
                                                </div>
                                                <input
                                                    type="checkbox"
                                                    id="public_dir"
                                                    checked={Boolean(data.settings.public_directory)}
                                                    onChange={(e) => handleFieldChange('public_directory', e.target.checked)}
                                                    className="h-5 w-5 rounded border-slate-700 bg-slate-900 text-teal-500"
                                                />
                                            </div>
                                        </div>
                                    )}

                                    {/* Default Key-Value Fallback for other groups */}
                                    {!['organization', 'school', 'jubilee', 'privacy'].includes(currentGroup) && (
                                        <div className="space-y-4">
                                            {Object.keys(data.settings).length === 0 ? (
                                                <div className="rounded-lg border border-dashed border-teal-500/20 p-6 text-center text-sm text-slate-400">
                                                    No specific configuration keys saved for {currentGroup} yet. Enter custom fields below.
                                                </div>
                                            ) : (
                                                Object.entries(data.settings).map(([k, v]) => (
                                                    <div key={k} className="space-y-1">
                                                        <Label className="capitalize">{k.replace(/_/g, ' ')}</Label>
                                                        <Input
                                                            value={typeof v === 'object' ? JSON.stringify(v) : (v ?? '')}
                                                            onChange={(e) => handleFieldChange(k, e.target.value)}
                                                            className="border-teal-500/20 bg-slate-950 text-slate-100"
                                                        />
                                                    </div>
                                                ))
                                            )}
                                        </div>
                                    )}

                                    <div className="flex justify-end pt-4 border-t border-teal-500/10">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="bg-gradient-to-r from-teal-500 to-teal-600 font-semibold text-slate-950 hover:from-teal-400 hover:to-teal-500"
                                        >
                                            <Save className="mr-2 h-4 w-4" />
                                            Save Settings
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
