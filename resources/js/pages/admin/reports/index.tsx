import { Link } from '@inertiajs/react';
import {
    Award,
    Building2,
    CalendarCheck,
    Coins,
    Download,
    Eye,
    GraduationCap,
    HeartHandshake,
    MessageSquare,
    Shield,
    Ticket,
    UserCheck,
    Users,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';

type ReportMeta = {
    title: string;
    description: string;
    category: string;
};

type Props = {
    reports: Record<string, ReportMeta>;
};

export default function AdminReportsIndex({ reports }: Props) {
    const getReportIcon = (key: string) => {
        switch (key) {
            case 'members':
                return <Users className="h-5 w-5 text-teal-400" />;
            case 'batches':
                return <GraduationCap className="h-5 w-5 text-cyan-400" />;
            case 'registrations':
                return <UserCheck className="h-5 w-5 text-emerald-400" />;
            case 'verifications':
                return <Shield className="h-5 w-5 text-indigo-400" />;
            case 'event-registrations':
                return <Ticket className="h-5 w-5 text-amber-400" />;
            case 'attendance':
                return <CalendarCheck className="h-5 w-5 text-teal-400" />;
            case 'payments':
                return <Coins className="h-5 w-5 text-emerald-400" />;
            case 'donations':
                return <HeartHandshake className="h-5 w-5 text-rose-400" />;
            case 'sponsors':
                return <Building2 className="h-5 w-5 text-amber-400" />;
            case 'volunteers':
                return <Award className="h-5 w-5 text-cyan-400" />;
            case 'engagement':
                return <MessageSquare className="h-5 w-5 text-teal-400" />;
            default:
                return <Award className="h-5 w-5 text-teal-400" />;
        }
    };

    return (
        <AdminLayout title="Reports & Analytics">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <Award className="h-5 w-5 text-teal-400" />
                            <h1 className="text-2xl font-bold tracking-tight text-slate-100">
                                Analytical Reports & Data Exports
                            </h1>
                        </div>
                        <p className="mt-1 text-sm text-slate-400">
                            Pre-configured administrative data extractions with audited CSV export capabilities.
                        </p>
                    </div>
                </div>

                {/* Report Grid */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {Object.entries(reports).map(([key, info]) => (
                        <Card
                            key={key}
                            className="flex flex-col justify-between border border-teal-500/15 bg-slate-900/40 backdrop-blur-md transition-all hover:border-teal-500/30"
                        >
                            <CardHeader className="pb-3">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="rounded-lg border border-teal-500/20 bg-slate-950 p-2.5">
                                        {getReportIcon(key)}
                                    </div>
                                    <Badge variant="outline" className="border-teal-500/30 text-teal-400 text-[10px]">
                                        {info.category}
                                    </Badge>
                                </div>
                                <CardTitle className="mt-3 text-base font-semibold text-slate-100">
                                    {info.title}
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-400 leading-relaxed">
                                    {info.description}
                                </CardDescription>
                            </CardHeader>

                            <CardContent className="pt-0">
                                <div className="flex items-center gap-2 pt-3 border-t border-teal-500/10">
                                    <Link href={`/admin/reports/${key}`} className="flex-1">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            className="w-full text-xs text-teal-400 hover:bg-teal-500/10"
                                        >
                                            <Eye className="mr-1.5 h-3.5 w-3.5" />
                                            Preview Data
                                        </Button>
                                    </Link>
                                    <form action={`/admin/reports/${key}/export`} method="POST">
                                        <input
                                            type="hidden"
                                            name="_token"
                                            value={(document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''}
                                        />
                                        <Button
                                            size="sm"
                                            className="bg-teal-500 font-semibold text-slate-950 hover:bg-teal-400 text-xs"
                                        >
                                            <Download className="mr-1.5 h-3.5 w-3.5" />
                                            Export CSV
                                        </Button>
                                    </form>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
