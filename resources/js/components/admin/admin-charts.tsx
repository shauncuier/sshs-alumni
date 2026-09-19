import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { formatNumber } from '@/lib/format';
import { TrendingUp, Users, MapPin, Globe, Briefcase, Calendar, HeartHandshake } from 'lucide-react';

export type ChartData = {
    members_over_time: Array<{ label: string; month_key: string; total: number; approved: number }>;
    members_by_batch: Array<{ label: string; name: string; year: number; count: number }>;
    members_by_country: Array<{ label: string; count: number }>;
    members_by_district: Array<{ label: string; count: number }>;
    members_by_profession: Array<{ label: string; count: number }>;
    event_registration_trend: Array<{ label: string; full_title: string; registrations: number; attendees: number }>;
    donation_trend: Array<{ label: string; month_key: string; amount: number; count: number }>;
};

export function AdminCharts({ data }: { data: ChartData }) {
    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-semibold tracking-tight text-foreground flex items-center gap-2">
                        <TrendingUp className="size-5 text-brand-gold-500" />
                        Platform Analytics & Insights
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Live institutional trends, demographic spreads, and engagement metrics.
                    </p>
                </div>
            </div>

            {/* Top row: 12-Month Membership Growth & 12-Month Donation Trends */}
            <div className="grid gap-6 lg:grid-cols-2">
                <MembershipGrowthChart data={data.members_over_time} />
                <DonationTrendChart data={data.donation_trend} />
            </div>

            {/* Middle row: Cohorts & Event Registrations */}
            <div className="grid gap-6 lg:grid-cols-2">
                <BatchDistributionChart data={data.members_by_batch} />
                <EventRegistrationChart data={data.event_registration_trend} />
            </div>

            {/* Bottom row: Demographics (Country, District, Profession) */}
            <div className="grid gap-6 md:grid-cols-3">
                <HorizontalBreakdownCard
                    title="Alumni by Country"
                    description="International diaspora footprint"
                    icon={Globe}
                    items={data.members_by_country}
                    colorClass="bg-teal-500"
                />
                <HorizontalBreakdownCard
                    title="Alumni by District"
                    description="Domestic alumni presence"
                    icon={MapPin}
                    items={data.members_by_district}
                    colorClass="bg-emerald-500"
                />
                <HorizontalBreakdownCard
                    title="Alumni by Profession"
                    description="Top reported career fields"
                    icon={Briefcase}
                    items={data.members_by_profession}
                    colorClass="bg-amber-500"
                />
            </div>
        </div>
    );
}

function MembershipGrowthChart({ data }: { data: ChartData['members_over_time'] }) {
    const [hoverIndex, setHoverIndex] = useState<number | null>(null);

    const maxVal = Math.max(...data.map((d) => d.total), 10);
    const height = 220;
    const paddingX = 40;
    const paddingY = 20;

    return (
        <Card className="border-border/60 bg-card/60 backdrop-blur-sm">
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle className="text-base font-medium flex items-center gap-2">
                            <Users className="size-4 text-teal-400" />
                            Member Registrations (12 Months)
                        </CardTitle>
                        <CardDescription className="text-xs">
                            New intake vs approved alumni cohorts
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div className="h-64 w-full relative pt-2">
                    <svg className="w-full h-full overflow-visible" viewBox={`0 0 600 ${height}`}>
                        <defs>
                            <linearGradient id="growthGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stopColor="#14b8a6" stopOpacity="0.35" />
                                <stop offset="100%" stopColor="#14b8a6" stopOpacity="0.0" />
                            </linearGradient>
                        </defs>

                        {/* Horizontal grid lines */}
                        {[0, 0.25, 0.5, 0.75, 1].map((ratio, idx) => {
                            const y = paddingY + (height - 2 * paddingY) * (1 - ratio);
                            return (
                                <g key={idx}>
                                    <line
                                        x1={paddingX}
                                        y1={y}
                                        x2={600 - paddingX}
                                        y2={y}
                                        stroke="currentColor"
                                        strokeOpacity="0.08"
                                        strokeDasharray="4 4"
                                    />
                                    <text
                                        x={paddingX - 8}
                                        y={y + 3}
                                        fill="currentColor"
                                        fillOpacity="0.4"
                                        fontSize="9"
                                        textAnchor="end"
                                    >
                                        {Math.round(maxVal * ratio)}
                                    </text>
                                </g>
                            );
                        })}

                        {/* Area and Line Path */}
                        {data.length > 1 && (() => {
                            const stepX = (600 - 2 * paddingX) / (data.length - 1);
                            const points = data.map((d, i) => {
                                const x = paddingX + i * stepX;
                                const y = paddingY + (height - 2 * paddingY) * (1 - d.total / maxVal);
                                return { x, y, data: d };
                            });

                            const lineD = points.reduce((acc, p, i) => `${acc} ${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`, '');
                            const areaD = `${lineD} L ${points[points.length - 1].x} ${height - paddingY} L ${points[0].x} ${height - paddingY} Z`;

                            return (
                                <>
                                    <path d={areaD} fill="url(#growthGradient)" />
                                    <path d={lineD} fill="none" stroke="#14b8a6" strokeWidth="2.5" />
                                    {points.map((p, i) => (
                                        <g key={i} onMouseEnter={() => setHoverIndex(i)} onMouseLeave={() => setHoverIndex(null)}>
                                            <circle
                                                cx={p.x}
                                                cy={p.y}
                                                r={hoverIndex === i ? 6 : 3.5}
                                                className="fill-teal-400 stroke-background transition-all duration-150 cursor-pointer"
                                                strokeWidth="2"
                                            />
                                            {/* X axis labels */}
                                            {i % 2 === 0 && (
                                                <text
                                                    x={p.x}
                                                    y={height - 2}
                                                    fill="currentColor"
                                                    fillOpacity="0.5"
                                                    fontSize="9"
                                                    textAnchor="middle"
                                                >
                                                    {p.data.label}
                                                </text>
                                            )}
                                        </g>
                                    ))}
                                </>
                            );
                        })()}
                    </svg>

                    {/* Tooltip */}
                    {hoverIndex !== null && data[hoverIndex] && (
                        <div className="absolute top-2 right-4 bg-popover/90 text-popover-foreground border border-border text-xs px-2.5 py-1.5 rounded-md shadow-md backdrop-blur-sm pointer-events-none">
                            <p className="font-semibold">{data[hoverIndex].label}</p>
                            <p className="text-teal-400">Total: {data[hoverIndex].total} members</p>
                            <p className="text-emerald-400">Approved: {data[hoverIndex].approved}</p>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

function DonationTrendChart({ data }: { data: ChartData['donation_trend'] }) {
    const [hoverIndex, setHoverIndex] = useState<number | null>(null);

    const maxVal = Math.max(...data.map((d) => d.amount), 5000);
    const height = 220;
    const paddingX = 45;
    const paddingY = 20;

    return (
        <Card className="border-border/60 bg-card/60 backdrop-blur-sm">
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle className="text-base font-medium flex items-center gap-2">
                            <HeartHandshake className="size-4 text-amber-400" />
                            Donations & Giving Trend (12 Months)
                        </CardTitle>
                        <CardDescription className="text-xs">
                            Verified monthly contribution volume in BDT (৳)
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div className="h-64 w-full relative pt-2">
                    <svg className="w-full h-full overflow-visible" viewBox={`0 0 600 ${height}`}>
                        <defs>
                            <linearGradient id="donationGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stopColor="#f59e0b" stopOpacity="0.35" />
                                <stop offset="100%" stopColor="#f59e0b" stopOpacity="0.0" />
                            </linearGradient>
                        </defs>

                        {/* Grid lines */}
                        {[0, 0.25, 0.5, 0.75, 1].map((ratio, idx) => {
                            const y = paddingY + (height - 2 * paddingY) * (1 - ratio);
                            return (
                                <g key={idx}>
                                    <line
                                        x1={paddingX}
                                        y1={y}
                                        x2={600 - paddingX}
                                        y2={y}
                                        stroke="currentColor"
                                        strokeOpacity="0.08"
                                        strokeDasharray="4 4"
                                    />
                                    <text
                                        x={paddingX - 8}
                                        y={y + 3}
                                        fill="currentColor"
                                        fillOpacity="0.4"
                                        fontSize="9"
                                        textAnchor="end"
                                    >
                                        ৳{Math.round(maxVal * ratio).toLocaleString()}
                                    </text>
                                </g>
                            );
                        })}

                        {/* Area and Line Path */}
                        {data.length > 1 && (() => {
                            const stepX = (600 - 2 * paddingX) / (data.length - 1);
                            const points = data.map((d, i) => {
                                const x = paddingX + i * stepX;
                                const y = paddingY + (height - 2 * paddingY) * (1 - d.amount / maxVal);
                                return { x, y, data: d };
                            });

                            const lineD = points.reduce((acc, p, i) => `${acc} ${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`, '');
                            const areaD = `${lineD} L ${points[points.length - 1].x} ${height - paddingY} L ${points[0].x} ${height - paddingY} Z`;

                            return (
                                <>
                                    <path d={areaD} fill="url(#donationGradient)" />
                                    <path d={lineD} fill="none" stroke="#f59e0b" strokeWidth="2.5" />
                                    {points.map((p, i) => (
                                        <g key={i} onMouseEnter={() => setHoverIndex(i)} onMouseLeave={() => setHoverIndex(null)}>
                                            <circle
                                                cx={p.x}
                                                cy={p.y}
                                                r={hoverIndex === i ? 6 : 3.5}
                                                className="fill-amber-400 stroke-background transition-all duration-150 cursor-pointer"
                                                strokeWidth="2"
                                            />
                                            {i % 2 === 0 && (
                                                <text
                                                    x={p.x}
                                                    y={height - 2}
                                                    fill="currentColor"
                                                    fillOpacity="0.5"
                                                    fontSize="9"
                                                    textAnchor="middle"
                                                >
                                                    {p.data.label}
                                                </text>
                                            )}
                                        </g>
                                    ))}
                                </>
                            );
                        })()}
                    </svg>

                    {hoverIndex !== null && data[hoverIndex] && (
                        <div className="absolute top-2 right-4 bg-popover/90 text-popover-foreground border border-border text-xs px-2.5 py-1.5 rounded-md shadow-md backdrop-blur-sm pointer-events-none">
                            <p className="font-semibold">{data[hoverIndex].label}</p>
                            <p className="text-amber-400 font-mono">৳{data[hoverIndex].amount.toLocaleString()}</p>
                            <p className="text-muted-foreground">{data[hoverIndex].count} donations</p>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

function BatchDistributionChart({ data }: { data: ChartData['members_by_batch'] }) {
    const maxVal = Math.max(...data.map((d) => d.count), 5);

    return (
        <Card className="border-border/60 bg-card/60 backdrop-blur-sm">
            <CardHeader className="pb-2">
                <CardTitle className="text-base font-medium flex items-center gap-2">
                    <Users className="size-4 text-emerald-400" />
                    Top Active SSC Batches
                </CardTitle>
                <CardDescription className="text-xs">
                    Cohorts with highest enrolled membership strength
                </CardDescription>
            </CardHeader>
            <CardContent>
                {data.length === 0 ? (
                    <div className="h-56 flex items-center justify-center text-sm text-muted-foreground">
                        No batch data available yet.
                    </div>
                ) : (
                    <div className="h-56 flex items-end gap-2 pt-6 pb-2 px-2">
                        {data.map((b, i) => {
                            const pct = Math.max(8, (b.count / maxVal) * 100);
                            return (
                                <div key={i} className="flex-1 flex flex-col items-center gap-1 group relative">
                                    <div
                                        className="w-full bg-emerald-500/20 hover:bg-emerald-500/40 border border-emerald-500/40 rounded-t-sm transition-all duration-200 cursor-pointer"
                                        style={{ height: `${pct}%` }}
                                    />
                                    <span className="text-[10px] text-muted-foreground font-mono truncate max-w-full">
                                        '{String(b.year).slice(-2)}
                                    </span>

                                    {/* Tooltip on hover */}
                                    <div className="absolute -top-10 opacity-0 group-hover:opacity-100 transition-opacity bg-popover text-popover-foreground border text-[11px] px-2 py-0.5 rounded shadow pointer-events-none whitespace-nowrap z-20">
                                        <span className="font-semibold">{b.name}:</span> {b.count} members
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function EventRegistrationChart({ data }: { data: ChartData['event_registration_trend'] }) {
    const maxVal = Math.max(...data.map((d) => d.registrations), 5);

    return (
        <Card className="border-border/60 bg-card/60 backdrop-blur-sm">
            <CardHeader className="pb-2">
                <CardTitle className="text-base font-medium flex items-center gap-2">
                    <Calendar className="size-4 text-sky-400" />
                    Recent Event Attendance & Registrations
                </CardTitle>
                <CardDescription className="text-xs">
                    Pass registrations vs gate check-in attendance
                </CardDescription>
            </CardHeader>
            <CardContent>
                {data.length === 0 ? (
                    <div className="h-56 flex items-center justify-center text-sm text-muted-foreground">
                        No recent event registrations found.
                    </div>
                ) : (
                    <div className="space-y-3 pt-2">
                        {data.map((e, idx) => {
                            const regPct = Math.min(100, Math.max(5, (e.registrations / maxVal) * 100));
                            const attPct = e.registrations > 0 ? (e.attendees / e.registrations) * 100 : 0;
                            return (
                                <div key={idx} className="space-y-1">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="font-medium text-foreground truncate max-w-[200px]" title={e.full_title}>
                                            {e.label}
                                        </span>
                                        <span className="text-muted-foreground font-mono">
                                            {e.attendees} / {e.registrations} attended ({Math.round(attPct)}%)
                                        </span>
                                    </div>
                                    <div className="h-3 w-full bg-secondary/50 rounded-full overflow-hidden flex">
                                        <div
                                            className="h-full bg-sky-500 rounded-l-full transition-all duration-300"
                                            style={{ width: `${regPct}%` }}
                                        />
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function HorizontalBreakdownCard({
    title,
    description,
    icon: Icon,
    items,
    colorClass,
}: {
    title: string;
    description: string;
    icon: React.ComponentType<{ className?: string }>;
    items: Array<{ label: string; count: number }>;
    colorClass: string;
}) {
    const maxVal = Math.max(...items.map((i) => i.count), 1);

    return (
        <Card className="border-border/60 bg-card/60 backdrop-blur-sm">
            <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium flex items-center gap-2">
                    <Icon className="size-4 text-muted-foreground" />
                    {title}
                </CardTitle>
                <CardDescription className="text-xs">{description}</CardDescription>
            </CardHeader>
            <CardContent>
                {items.length === 0 ? (
                    <p className="text-xs text-muted-foreground py-6 text-center">No demographic records found.</p>
                ) : (
                    <div className="space-y-2.5 pt-1">
                        {items.slice(0, 6).map((item, idx) => {
                            const pct = Math.max(6, (item.count / maxVal) * 100);
                            return (
                                <div key={idx} className="space-y-1">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="truncate max-w-[140px] text-foreground font-medium">{item.label}</span>
                                        <span className="font-mono text-muted-foreground">{formatNumber(item.count)}</span>
                                    </div>
                                    <div className="h-2 w-full bg-secondary/50 rounded-full overflow-hidden">
                                        <div className={`h-full ${colorClass} rounded-full transition-all duration-300`} style={{ width: `${pct}%` }} />
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
