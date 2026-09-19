import { useForm } from '@inertiajs/react';
import { Facebook, Mail, MapPin, MessageSquare, Phone, Send, Youtube } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';

type Props = {
    contact: {
        email: string | null;
        phone: string | null;
        address: string | null;
        map_url: string | null;
    };
    school: {
        name_en: string | null;
        address: string | null;
        phone: string | null;
        email: string | null;
    };
    social: {
        facebook: string | null;
        youtube: string | null;
        linkedin: string | null;
    };
};

/**
 * The contact page.
 *
 * It says that a person reads these and that there is no automatic reply,
 * because there is not one — the notification layer lands in Phase 8. Telling
 * somebody their message was sent and leaving them waiting on an email that
 * was never built is the kind of small dishonesty that costs a volunteer
 * organisation its credibility.
 */
export default function Contact({ contact, school, social }: Props) {
    const { t } = useTranslation();

    const form = useForm({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post('/contact', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <PublicLayout
            title={t('public.contact.title')}
            description={t('public.contact.subtitle')}
        >
            {/* Ambient Hero */}
            <div className="relative overflow-hidden bg-gradient-to-br from-[#060a17] via-[#0b1329] to-[#0d1b3a] text-white py-16 sm:py-20 border-b border-teal-500/20">
                <div className="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-teal-500/15 blur-3xl" />
                <div className="pointer-events-none absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-teal-300 backdrop-blur-md">
                        <MessageSquare className="size-3.5" />
                        Reach Out & Connect
                    </div>
                    <h1 className="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        {t('public.contact.title')}
                    </h1>
                    <p className="mt-3 max-w-2xl text-base sm:text-lg text-slate-300">
                        {t('public.contact.subtitle')}
                    </p>
                </div>
            </div>

            {/* Content Section */}
            <div className="relative bg-gradient-to-b from-slate-50 via-teal-50/15 to-white py-12 sm:py-16">
                <div className="mx-auto grid max-w-5xl gap-8 px-4 sm:px-6 lg:px-8 lg:grid-cols-[1fr_1.25fr]">
                    <div className="space-y-6">
                        <div className="glass-panel-light rounded-3xl p-6 sm:p-7 border border-teal-500/15 shadow-sm">
                            <h2 className="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">
                                {t('public.contact.association')}
                            </h2>

                            <ul className="mt-4 space-y-3 text-sm">
                                {contact.email && (
                                    <ContactLine
                                        icon={Mail}
                                        value={contact.email}
                                        href={`mailto:${contact.email}`}
                                    />
                                )}
                                {contact.phone && (
                                    <ContactLine
                                        icon={Phone}
                                        value={contact.phone}
                                        href={`tel:${contact.phone}`}
                                    />
                                )}
                                {contact.address && (
                                    <ContactLine
                                        icon={MapPin}
                                        value={contact.address}
                                    />
                                )}
                            </ul>
                        </div>

                        {(school.address || school.phone || school.email) && (
                            <div className="rounded-3xl border border-teal-500/15 bg-white/90 p-6 sm:p-7 shadow-sm backdrop-blur-md">
                                <h2 className="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">
                                    {t('public.contact.school')}
                                </h2>

                                <ul className="mt-4 space-y-3 text-sm">
                                    {school.address && (
                                        <ContactLine
                                            icon={MapPin}
                                            value={school.address}
                                        />
                                    )}
                                    {school.phone && (
                                        <ContactLine
                                            icon={Phone}
                                            value={school.phone}
                                            href={`tel:${school.phone}`}
                                        />
                                    )}
                                    {school.email && (
                                        <ContactLine
                                            icon={Mail}
                                            value={school.email}
                                            href={`mailto:${school.email}`}
                                        />
                                    )}
                                </ul>
                            </div>
                        )}

                        {(social.facebook || social.youtube) && (
                            <div className="rounded-3xl border border-teal-500/15 bg-white/90 p-5 shadow-sm backdrop-blur-md flex items-center justify-between">
                                <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Official Channels
                                </span>
                                <div className="flex gap-3">
                                    {social.facebook && (
                                        <a
                                            href={social.facebook}
                                            target="_blank"
                                            rel="noreferrer"
                                            aria-label="Facebook"
                                            className="flex size-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700 transition hover:bg-teal-600 hover:text-white"
                                        >
                                            <Facebook className="size-4" />
                                        </a>
                                    )}
                                    {social.youtube && (
                                        <a
                                            href={social.youtube}
                                            target="_blank"
                                            rel="noreferrer"
                                            aria-label="YouTube"
                                            className="flex size-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700 transition hover:bg-teal-600 hover:text-white"
                                        >
                                            <Youtube className="size-4" />
                                        </a>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="rounded-3xl border border-teal-500/20 bg-white/95 p-6 sm:p-8 shadow-xl shadow-teal-900/5 backdrop-blur-md">
                        <div className="border-b border-slate-100 pb-4">
                            <h2 className="text-xl font-bold text-slate-900">
                                {t('public.contact.form_title')}
                            </h2>
                            <p className="mt-1 text-xs text-slate-500">
                                Send us an inquiry and our voluntary committee will respond.
                            </p>
                        </div>

                        <form onSubmit={submit} className="mt-6 space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field
                                    id="name"
                                    label={t('public.contact.name')}
                                    value={form.data.name}
                                    error={form.errors.name}
                                    onChange={(value) =>
                                        form.setData('name', value)
                                    }
                                    required
                                />

                                <Field
                                    id="email"
                                    type="email"
                                    label={t('public.contact.email')}
                                    value={form.data.email}
                                    error={form.errors.email}
                                    onChange={(value) =>
                                        form.setData('email', value)
                                    }
                                    required
                                />
                            </div>

                            <Field
                                id="phone"
                                label={t('public.contact.phone')}
                                value={form.data.phone}
                                error={form.errors.phone}
                                onChange={(value) =>
                                    form.setData('phone', value)
                                }
                            />

                            <Field
                                id="subject"
                                label={t('public.contact.subject')}
                                value={form.data.subject}
                                error={form.errors.subject}
                                onChange={(value) =>
                                    form.setData('subject', value)
                                }
                                required
                            />

                            <div className="space-y-1.5">
                                <Label htmlFor="message" className="text-xs font-semibold text-slate-700">
                                    {t('public.contact.message')}
                                </Label>
                                <textarea
                                    id="message"
                                    rows={5}
                                    value={form.data.message}
                                    onChange={(event) =>
                                        form.setData(
                                            'message',
                                            event.target.value,
                                        )
                                    }
                                    className="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                                    required
                                />
                                <InputError message={form.errors.message} />
                            </div>

                            <p className="text-xs text-slate-400 leading-relaxed">
                                {t('public.contact.privacy_note')}
                            </p>

                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="w-full bg-gradient-to-r from-teal-500 via-teal-600 to-cyan-600 font-bold text-white shadow-md shadow-teal-500/20 hover:from-teal-600 hover:to-cyan-700 rounded-xl py-2.5 transition"
                            >
                                <Send
                                    className="me-2 size-4"
                                    aria-hidden="true"
                                />
                                {t('public.contact.send')}
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}

function ContactLine({
    icon: Icon,
    value,
    href,
}: {
    icon: typeof Mail;
    value: string;
    href?: string;
}) {
    return (
        <li className="flex items-start gap-3">
            <div className="flex size-7 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700 mt-0.5">
                <Icon
                    className="size-3.5"
                    aria-hidden="true"
                />
            </div>
            {href ? (
                <a href={href} className="text-slate-700 hover:text-teal-700 transition font-medium">
                    {value}
                </a>
            ) : (
                <span className="text-slate-600">{value}</span>
            )}
        </li>
    );
}

function Field({
    id,
    label,
    value,
    error,
    onChange,
    type = 'text',
    required = false,
}: {
    id: string;
    label: string;
    value: string;
    error?: string;
    onChange: (value: string) => void;
    type?: string;
    required?: boolean;
}) {
    return (
        <div className="space-y-1.5">
            <Label htmlFor={id} className="text-xs font-semibold text-slate-700">
                {label}
            </Label>
            <Input
                id={id}
                type={type}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                required={required}
                className="rounded-xl border-slate-200"
            />
            <InputError message={error} />
        </div>
    );
}
