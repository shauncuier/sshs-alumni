import { router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    Briefcase,
    Check,
    GraduationCap,
    Lock,
    MapPin,
    ShieldCheck,
    Sparkles,
    User,
} from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { cn } from '@/lib/utils';
import PublicLayout from '@/layouts/public-layout';

type Option = { value: string; label: string };
type BatchOption = { id: number; label: string; ssc_year: number };

/**
 * The draft is a wide, sparse record because each step contributes different
 * fields. Values are constrained to what Inertia can serialise — `unknown`
 * does not satisfy its FormDataType constraint.
 */
type JoinValue =
    | string
    | number
    | boolean
    | null
    | undefined
    | Record<string, boolean>;

type JoinFormData = Record<string, JoinValue>;

type Props = {
    step: string;
    steps: string[];
    draft: Record<string, JoinValue>;
    options: {
        relation_types: Option[];
        genders: Option[];
        blood_groups: Option[];
        link_types: Option[];
        batches: BatchOption[];
    };
};

const PRIVACY_FLAGS = [
    'show_profile',
    'show_phone',
    'show_email',
    'show_workplace',
    'show_location',
    'show_date_of_birth',
    'show_in_batch_list',
] as const;

const STEP_ICONS: Record<string, React.ComponentType<{ className?: string }>> = {
    basic: User,
    academic: GraduationCap,
    professional: Briefcase,
    location: MapPin,
    review: ShieldCheck,
};

export default function Join({ step, steps, draft, options }: Props) {
    const { t } = useTranslation();

    const index = steps.indexOf(step);

    const form = useForm<JoinFormData>({
        ...(draft as JoinFormData),
        // Privacy defaults mirror the server's: contact details hidden unless
        // the applicant opts in.
        privacy: (draft.privacy as Record<string, boolean>) ?? {
            show_profile: true,
            show_phone: false,
            show_email: false,
            show_workplace: true,
            show_location: true,
            show_date_of_birth: false,
            show_in_batch_list: true,
        },
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/join/${step}`, { preserveScroll: true });
    };

    const set = (field: string, value: JoinValue) => form.setData(field, value);
    const value = (field: string) => (form.data[field] as string) ?? '';

    return (
        <PublicLayout
            title={t('public.join.title')}
            description={t('public.join.subtitle')}
        >
            {/* ── Hero Header ────────────────────────────────────────────── */}
            <header className="relative overflow-hidden bg-gradient-to-b from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white">
                {/* Ambient Glow Orbs */}
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2 h-96 w-[40rem] rounded-full bg-gradient-to-tr from-teal-500/15 via-cyan-500/15 to-transparent blur-3xl"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute top-1/4 -right-20 h-72 w-72 rounded-full bg-amber-500/10 blur-3xl"
                />

                <div className="relative mx-auto max-w-4xl px-4 py-12 text-center sm:py-16">
                    {/* Badge */}
                    <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-4 py-1.5 text-xs font-semibold tracking-wide text-teal-300 uppercase shadow-inner backdrop-blur-md">
                        <Sparkles className="size-3.5 text-teal-400" />
                        <span>Alumni Onboarding • প্রাক্তন শিক্ষার্থী নিবন্ধন</span>
                    </div>

                    <h1 className="bg-gradient-to-r from-white via-slate-100 to-slate-300 bg-clip-text text-3xl font-extrabold text-transparent sm:text-5xl">
                        {t('public.join.title')}
                    </h1>
                    <p className="mx-auto mt-3 max-w-2xl text-sm text-slate-300 sm:text-base">
                        {t('public.join.subtitle')}
                    </p>

                    {/* ── Modern Stepper ─────────────────────────────────────── */}
                    <ol
                        className="mt-10 grid grid-cols-2 gap-2 sm:grid-cols-5"
                        aria-label={t('public.join.title')}
                    >
                        {steps.map((one, position) => {
                            const done = position < index;
                            const current = position === index;
                            const Icon = STEP_ICONS[one] ?? User;

                            return (
                                <li key={one} className="col-span-1">
                                    <div
                                        aria-current={
                                            current ? 'step' : undefined
                                        }
                                        className={cn(
                                            'group flex flex-col items-center justify-center gap-1.5 rounded-2xl border p-3 text-center transition-all duration-300 backdrop-blur-md',
                                            current &&
                                                'border-teal-400/60 bg-gradient-to-b from-teal-500/20 to-teal-500/5 text-white shadow-lg shadow-teal-500/20 ring-2 ring-teal-400/30',
                                            done &&
                                                'border-teal-500/30 bg-teal-500/10 text-teal-300 shadow-xs',
                                            !current &&
                                                !done &&
                                                'border-white/10 bg-white/5 text-slate-400 hover:border-white/20',
                                        )}
                                    >
                                        <div
                                            className={cn(
                                                'flex size-7 items-center justify-center rounded-xl text-xs font-bold transition-transform group-hover:scale-105',
                                                current &&
                                                    'bg-gradient-to-br from-teal-400 to-cyan-500 text-slate-950 shadow-md',
                                                done &&
                                                    'bg-teal-500 text-slate-950 shadow-xs',
                                                !current &&
                                                    !done &&
                                                    'bg-white/10 text-slate-400',
                                            )}
                                        >
                                            {done ? (
                                                <Check
                                                    className="size-3.5 stroke-[3]"
                                                    aria-hidden="true"
                                                />
                                            ) : (
                                                <Icon className="size-3.5" />
                                            )}
                                        </div>
                                        <div className="w-full">
                                            <p className="text-[0.65rem] font-semibold tracking-wider text-slate-400 uppercase">
                                                Step {position + 1}
                                            </p>
                                            <p
                                                className={cn(
                                                    'truncate text-xs font-semibold',
                                                    current
                                                        ? 'text-white'
                                                        : done
                                                          ? 'text-teal-200'
                                                          : 'text-slate-400',
                                                )}
                                            >
                                                {t(`public.join.steps.${one}`)}
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            );
                        })}
                    </ol>
                </div>
            </header>

            {/* ── Main Form Canvas ────────────────────────────────────────── */}
            <div className="relative min-h-[60vh] bg-gradient-to-b from-[#f0f7f9] via-white to-[#f0f7f9] py-12 sm:py-16">
                <div className="relative mx-auto max-w-3xl px-4">
                    <form
                        onSubmit={submit}
                        className="glass-panel-light relative overflow-hidden rounded-3xl border border-teal-500/15 p-6 shadow-xl shadow-teal-900/5 sm:p-10"
                    >
                        {/* Decorative Top Accent Gradient */}
                        <div
                            aria-hidden="true"
                            className="absolute top-0 left-0 h-1.5 w-full bg-gradient-to-r from-amber-400 via-teal-500 to-cyan-600"
                        />

                        {/* Step Title Header */}
                        <div className="mb-8 border-b border-teal-100/70 pb-5">
                            <span className="inline-block rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-800">
                                {t(`public.join.steps.${step}`)}
                            </span>
                            <h2 className="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                                {t(`public.join.steps.${step}`)}
                            </h2>
                            <p className="mt-1 text-xs text-slate-500">
                                Please provide accurate information to verify your alumni credentials.
                            </p>
                        </div>
                        {step === 'basic' && (
                            <>
                                <Field
                                    label={t('public.join.fields.full_name')}
                                    error={form.errors.full_name}
                                    required
                                >
                                    <Input
                                        value={value('full_name')}
                                        onChange={(e) =>
                                            set('full_name', e.target.value)
                                        }
                                        autoComplete="name"
                                        required
                                    />
                                </Field>

                                <Field
                                    label={t(
                                        'public.join.fields.relation_type',
                                    )}
                                    error={form.errors.relation_type}
                                    required
                                >
                                    <Choice
                                        options={options.relation_types}
                                        value={value('relation_type')}
                                        onChange={(next) =>
                                            set('relation_type', next)
                                        }
                                    />
                                </Field>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label={t('public.join.fields.gender')}
                                        error={form.errors.gender}
                                    >
                                        <Choice
                                            options={options.genders}
                                            value={value('gender')}
                                            onChange={(next) =>
                                                set('gender', next)
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label={t(
                                            'public.join.fields.blood_group',
                                        )}
                                        error={form.errors.blood_group}
                                    >
                                        <Choice
                                            options={options.blood_groups}
                                            value={value('blood_group')}
                                            onChange={(next) =>
                                                set('blood_group', next)
                                            }
                                        />
                                    </Field>
                                </div>

                                <Field
                                    label={t(
                                        'public.join.fields.date_of_birth',
                                    )}
                                    error={form.errors.date_of_birth}
                                >
                                    <Input
                                        type="date"
                                        value={value('date_of_birth')}
                                        onChange={(e) =>
                                            set('date_of_birth', e.target.value)
                                        }
                                    />
                                </Field>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label={t('public.join.fields.mobile')}
                                        error={form.errors.mobile}
                                        required
                                    >
                                        <Input
                                            inputMode="tel"
                                            className="tabular-id"
                                            placeholder="01XXXXXXXXX"
                                            value={value('mobile')}
                                            onChange={(e) =>
                                                set('mobile', e.target.value)
                                            }
                                            required
                                        />
                                    </Field>

                                    <Field
                                        label={t('public.join.fields.whatsapp')}
                                        error={form.errors.whatsapp}
                                    >
                                        <Input
                                            inputMode="tel"
                                            className="tabular-id"
                                            value={value('whatsapp')}
                                            onChange={(e) =>
                                                set('whatsapp', e.target.value)
                                            }
                                        />
                                    </Field>
                                </div>

                                <Field
                                    label={t('public.join.fields.email')}
                                    error={form.errors.email}
                                    required
                                >
                                    <Input
                                        type="email"
                                        autoComplete="email"
                                        value={value('email')}
                                        onChange={(e) =>
                                            set('email', e.target.value)
                                        }
                                        required
                                    />
                                </Field>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label={t('public.join.fields.password')}
                                        error={form.errors.password}
                                        required
                                    >
                                        <Input
                                            type="password"
                                            autoComplete="new-password"
                                            value={value('password')}
                                            onChange={(e) =>
                                                set('password', e.target.value)
                                            }
                                            required
                                        />
                                    </Field>

                                    <Field
                                        label={t(
                                            'public.join.fields.password_confirmation',
                                        )}
                                        required
                                    >
                                        <Input
                                            type="password"
                                            autoComplete="new-password"
                                            value={value(
                                                'password_confirmation',
                                            )}
                                            onChange={(e) =>
                                                set(
                                                    'password_confirmation',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                    </Field>
                                </div>
                            </>
                        )}

                        {step === 'academic' && (
                            <>
                                <Field
                                    label={t('public.join.fields.batch')}
                                    error={form.errors.batch_id}
                                >
                                    <Select
                                        value={value('batch_id')}
                                        onValueChange={(next) => {
                                            set('batch_id', next);
                                            const batch = options.batches.find(
                                                (one) =>
                                                    String(one.id) === next,
                                            );
                                            if (batch) {
                                                set(
                                                    'ssc_year',
                                                    String(batch.ssc_year),
                                                );
                                            }
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder={t(
                                                    'common.actions.search',
                                                )}
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {options.batches.map((batch) => (
                                                <SelectItem
                                                    key={batch.id}
                                                    value={String(batch.id)}
                                                >
                                                    {batch.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </Field>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label={t(
                                            'public.join.fields.student_id',
                                        )}
                                        error={form.errors.student_id}
                                    >
                                        <Input
                                            className="tabular-id"
                                            value={value('student_id')}
                                            onChange={(e) =>
                                                set(
                                                    'student_id',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label={t(
                                            'public.join.fields.admission_year',
                                        )}
                                        error={form.errors.admission_year}
                                    >
                                        <Input
                                            inputMode="numeric"
                                            className="tabular-id"
                                            value={value('admission_year')}
                                            onChange={(e) =>
                                                set(
                                                    'admission_year',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-3">
                                    <Field
                                        label={t(
                                            'public.join.fields.group_stream',
                                        )}
                                        error={form.errors.group_stream}
                                    >
                                        <Input
                                            value={value('group_stream')}
                                            onChange={(e) =>
                                                set(
                                                    'group_stream',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <Field
                                        label={t('public.join.fields.section')}
                                        error={form.errors.section}
                                    >
                                        <Input
                                            value={value('section')}
                                            onChange={(e) =>
                                                set('section', e.target.value)
                                            }
                                        />
                                    </Field>
                                    <Field
                                        label={t('public.join.fields.house')}
                                        error={form.errors.house}
                                    >
                                        <Input
                                            value={value('house')}
                                            onChange={(e) =>
                                                set('house', e.target.value)
                                            }
                                        />
                                    </Field>
                                </div>
                            </>
                        )}

                        {step === 'professional' && (
                            <>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label={t(
                                            'public.join.fields.occupation',
                                        )}
                                        error={form.errors.occupation}
                                    >
                                        <Input
                                            value={value('occupation')}
                                            onChange={(e) =>
                                                set(
                                                    'occupation',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <Field
                                        label={t(
                                            'public.join.fields.organization',
                                        )}
                                        error={form.errors.organization}
                                    >
                                        <Input
                                            value={value('organization')}
                                            onChange={(e) =>
                                                set(
                                                    'organization',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label={t(
                                            'public.join.fields.job_title',
                                        )}
                                        error={form.errors.job_title}
                                    >
                                        <Input
                                            value={value('job_title')}
                                            onChange={(e) =>
                                                set('job_title', e.target.value)
                                            }
                                        />
                                    </Field>
                                    <Field
                                        label={t('public.join.fields.industry')}
                                        error={form.errors.industry}
                                    >
                                        <Input
                                            value={value('industry')}
                                            onChange={(e) =>
                                                set('industry', e.target.value)
                                            }
                                        />
                                    </Field>
                                </div>
                            </>
                        )}

                        {step === 'location' && (
                            <>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label={t('public.join.fields.country')}
                                        error={form.errors.country}
                                        required
                                    >
                                        <Input
                                            value={
                                                value('country') || 'Bangladesh'
                                            }
                                            onChange={(e) =>
                                                set('country', e.target.value)
                                            }
                                            required
                                        />
                                    </Field>
                                    <Field
                                        label={t('public.join.fields.division')}
                                        error={form.errors.division}
                                    >
                                        <Input
                                            value={value('division')}
                                            onChange={(e) =>
                                                set('division', e.target.value)
                                            }
                                        />
                                    </Field>
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label={t('public.join.fields.district')}
                                        error={form.errors.district}
                                    >
                                        <Input
                                            value={value('district')}
                                            onChange={(e) =>
                                                set('district', e.target.value)
                                            }
                                        />
                                    </Field>
                                    <Field
                                        label={t('public.join.fields.city')}
                                        error={form.errors.city}
                                    >
                                        <Input
                                            value={value('city')}
                                            onChange={(e) =>
                                                set('city', e.target.value)
                                            }
                                        />
                                    </Field>
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label={t(
                                            'public.join.fields.emergency_contact_name',
                                        )}
                                        error={
                                            form.errors.emergency_contact_name
                                        }
                                    >
                                        <Input
                                            value={value(
                                                'emergency_contact_name',
                                            )}
                                            onChange={(e) =>
                                                set(
                                                    'emergency_contact_name',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <Field
                                        label={t(
                                            'public.join.fields.emergency_contact_phone',
                                        )}
                                        error={
                                            form.errors.emergency_contact_phone
                                        }
                                    >
                                        <Input
                                            className="tabular-id"
                                            value={value(
                                                'emergency_contact_phone',
                                            )}
                                            onChange={(e) =>
                                                set(
                                                    'emergency_contact_phone',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>
                            </>
                        )}

                        {step === 'review' && (
                            <>
                                <Field
                                    label={t('public.join.fields.bio')}
                                    error={form.errors.bio}
                                >
                                    <textarea
                                        rows={4}
                                        className="w-full rounded-2xl border border-slate-200/80 bg-white/80 p-4 text-sm text-slate-900 shadow-xs transition-all placeholder:text-slate-400 focus-visible:border-teal-500 focus-visible:ring-2 focus-visible:ring-teal-500/20 focus-visible:outline-none"
                                        placeholder="Tell fellow alumni about your journey, interests, or fond school memories..."
                                        value={value('bio')}
                                        onChange={(e) =>
                                            set('bio', e.target.value)
                                        }
                                    />
                                </Field>

                                <fieldset className="glass-card-hover rounded-2xl border border-teal-500/15 bg-white/70 p-6 space-y-4 shadow-xs">
                                    <div className="flex items-center gap-2.5">
                                        <div className="flex size-8 items-center justify-center rounded-xl bg-teal-100 text-teal-800">
                                            <ShieldCheck className="size-4" />
                                        </div>
                                        <div>
                                            <legend className="text-base font-bold text-slate-900">
                                                {t('public.join.privacy.legend')}
                                            </legend>
                                            <p className="text-xs text-slate-500">
                                                {t('public.join.privacy.note')}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="grid gap-3 pt-2 sm:grid-cols-2">
                                        {PRIVACY_FLAGS.map((flag) => {
                                            const privacy = form.data
                                                .privacy as Record<string, boolean>;

                                            return (
                                                <div
                                                    key={flag}
                                                    className="flex items-center gap-2.5 rounded-xl border border-slate-100 bg-white/60 p-2.5 transition-colors hover:bg-white"
                                                >
                                                    <Checkbox
                                                        id={flag}
                                                        checked={privacy[flag]}
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            set('privacy', {
                                                                ...privacy,
                                                                [flag]:
                                                                    checked ===
                                                                    true,
                                                                })
                                                        }
                                                    />
                                                    <Label
                                                        htmlFor={flag}
                                                        className="cursor-pointer text-xs font-medium text-slate-700 select-none"
                                                    >
                                                        {t(
                                                            `public.join.privacy.${flag}`,
                                                        )}
                                                    </Label>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </fieldset>

                                <div className="flex items-start gap-3 rounded-2xl border border-teal-500/20 bg-teal-50/50 p-4 shadow-xs">
                                    <Checkbox
                                        id="terms"
                                        checked={form.data.terms === true}
                                        onCheckedChange={(checked) =>
                                            set('terms', checked === true)
                                        }
                                        className="mt-0.5"
                                    />
                                    <Label
                                        htmlFor="terms"
                                        className="cursor-pointer text-xs leading-relaxed font-medium text-slate-700 select-none sm:text-sm"
                                    >
                                        {t('public.join.terms')}
                                    </Label>
                                </div>
                                <InputError message={form.errors.terms} />
                            </>
                        )}

                        <div className="flex items-center gap-3 border-t border-teal-100/70 pt-6">
                            {index > 0 && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="h-11 rounded-xl border-slate-300 px-6 font-semibold text-slate-700 hover:bg-slate-100"
                                    onClick={() =>
                                        router.get(`/join/${steps[index - 1]}`)
                                    }
                                >
                                    <ArrowLeft className="size-4" />
                                    <span>{t('common.actions.previous')}</span>
                                </Button>
                            )}

                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="ms-auto h-11 rounded-xl bg-gradient-to-r from-teal-600 via-teal-500 to-cyan-600 px-8 font-bold text-white shadow-lg shadow-teal-500/25 transition-all duration-300 hover:from-teal-500 hover:to-cyan-500 hover:shadow-xl hover:shadow-teal-500/35 hover:-translate-y-0.5"
                            >
                                <span>
                                    {index === steps.length - 1
                                        ? t('common.actions.submit')
                                        : t('common.actions.next')}
                                </span>
                                <ArrowRight className="size-4" />
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </PublicLayout>
    );
}

function Field({
    label,
    error,
    required,
    children,
}: {
    label: string;
    error?: string;
    required?: boolean;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label className="text-xs font-bold text-slate-800 tracking-wide sm:text-sm">
                {label}
                {required && (
                    <span
                        className="text-rose-600 ms-1 font-bold"
                        aria-hidden="true"
                    >
                        *
                    </span>
                )}
            </Label>
            <div className="[&>input]:rounded-xl [&>input]:border-slate-200/80 [&>input]:bg-white/80 [&>input]:shadow-xs [&>input:focus-visible]:border-teal-500 [&>input:focus-visible]:ring-2 [&>input:focus-visible]:ring-teal-500/20">
                {children}
            </div>
            <InputError message={error} />
        </div>
    );
}

function Choice({
    options,
    value,
    onChange,
}: {
    options: Option[];
    value: string;
    onChange: (value: string) => void;
}) {
    const { t } = useTranslation();

    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger className="h-10 rounded-xl border-slate-200/80 bg-white/80 shadow-xs focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500">
                <SelectValue placeholder={t('common.states.optional')} />
            </SelectTrigger>
            <SelectContent className="rounded-xl shadow-xl">
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
