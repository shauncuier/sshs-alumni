import { router, useForm } from '@inertiajs/react';
import { Check } from 'lucide-react';
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
            <div className="bg-brand-cream">
                <div className="mx-auto max-w-3xl px-4 py-10">
                    <h1 className="text-brand-green-900 text-2xl font-semibold sm:text-3xl">
                        {t('public.join.title')}
                    </h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        {t('public.join.subtitle')}
                    </p>

                    <ol
                        className="mt-8 flex flex-wrap gap-2"
                        aria-label={t('public.join.title')}
                    >
                        {steps.map((one, position) => {
                            const done = position < index;
                            const current = position === index;

                            return (
                                <li key={one} className="flex-1">
                                    <div
                                        aria-current={
                                            current ? 'step' : undefined
                                        }
                                        className={cn(
                                            'flex items-center gap-2 rounded-md border px-3 py-2 text-xs font-medium',
                                            current &&
                                                'border-brand-green-800 bg-brand-green-800 text-white',
                                            done &&
                                                'border-brand-green-600 text-brand-green-800 bg-card',
                                            !current &&
                                                !done &&
                                                'text-muted-foreground bg-card/60 border-transparent',
                                        )}
                                    >
                                        {done ? (
                                            <Check
                                                className="size-3.5 shrink-0"
                                                aria-hidden="true"
                                            />
                                        ) : (
                                            <span className="tabular-id">
                                                {position + 1}
                                            </span>
                                        )}
                                        <span className="truncate">
                                            {t(`public.join.steps.${one}`)}
                                        </span>
                                    </div>
                                </li>
                            );
                        })}
                    </ol>

                    <form
                        onSubmit={submit}
                        className="bg-card mt-8 space-y-6 rounded-xl border p-6 shadow-sm"
                    >
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
                                        className="border-input focus-visible:ring-ring w-full rounded-md border bg-transparent px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                        value={value('bio')}
                                        onChange={(e) =>
                                            set('bio', e.target.value)
                                        }
                                    />
                                </Field>

                                <fieldset className="space-y-3">
                                    <legend className="text-sm font-semibold">
                                        {t('public.join.privacy.legend')}
                                    </legend>
                                    <p className="text-muted-foreground text-sm">
                                        {t('public.join.privacy.note')}
                                    </p>

                                    {PRIVACY_FLAGS.map((flag) => {
                                        const privacy = form.data
                                            .privacy as Record<string, boolean>;

                                        return (
                                            <div
                                                key={flag}
                                                className="flex items-center gap-2"
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
                                                    className="cursor-pointer text-sm font-normal"
                                                >
                                                    {t(
                                                        `public.join.privacy.${flag}`,
                                                    )}
                                                </Label>
                                            </div>
                                        );
                                    })}
                                </fieldset>

                                <div className="flex items-start gap-2">
                                    <Checkbox
                                        id="terms"
                                        checked={form.data.terms === true}
                                        onCheckedChange={(checked) =>
                                            set('terms', checked === true)
                                        }
                                    />
                                    <Label
                                        htmlFor="terms"
                                        className="cursor-pointer text-sm font-normal"
                                    >
                                        {t('public.join.terms')}
                                    </Label>
                                </div>
                                <InputError message={form.errors.terms} />
                            </>
                        )}

                        <div className="flex items-center gap-3 border-t pt-4">
                            {index > 0 && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() =>
                                        router.get(`/join/${steps[index - 1]}`)
                                    }
                                >
                                    {t('common.actions.previous')}
                                </Button>
                            )}

                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="ms-auto"
                            >
                                {index === steps.length - 1
                                    ? t('common.actions.submit')
                                    : t('common.actions.next')}
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
            <Label className="text-sm font-medium">
                {label}
                {required && (
                    <span
                        className="text-brand-red-700 ms-1"
                        aria-hidden="true"
                    >
                        *
                    </span>
                )}
            </Label>
            {children}
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
            <SelectTrigger>
                <SelectValue placeholder={t('common.states.optional')} />
            </SelectTrigger>
            <SelectContent>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
