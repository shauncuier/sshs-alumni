import { useForm } from '@inertiajs/react';
import { Facebook, Mail, MapPin, Phone, Send, Youtube } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
            <div className="bg-brand-green-900 text-white">
                <div className="mx-auto max-w-5xl px-4 py-12">
                    <h1 className="text-3xl font-semibold">
                        {t('public.contact.title')}
                    </h1>
                    <p className="mt-2 text-white/70">
                        {t('public.contact.subtitle')}
                    </p>
                </div>
            </div>

            <div className="bg-brand-cream">
                <div className="mx-auto grid max-w-5xl gap-8 px-4 py-10 lg:grid-cols-[1fr_1.2fr]">
                    <div className="space-y-6">
                        <section>
                            <h2 className="text-brand-green-900 font-semibold">
                                {t('public.contact.association')}
                            </h2>

                            <ul className="mt-3 space-y-2 text-sm">
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
                        </section>

                        {(school.address || school.phone || school.email) && (
                            <section>
                                <h2 className="text-brand-green-900 font-semibold">
                                    {t('public.contact.school')}
                                </h2>

                                <ul className="mt-3 space-y-2 text-sm">
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
                            </section>
                        )}

                        {(social.facebook || social.youtube) && (
                            <section className="flex gap-3">
                                {social.facebook && (
                                    <a
                                        href={social.facebook}
                                        target="_blank"
                                        rel="noreferrer"
                                        aria-label="Facebook"
                                        className="text-brand-green-800 hover:underline"
                                    >
                                        <Facebook className="size-5" />
                                    </a>
                                )}
                                {social.youtube && (
                                    <a
                                        href={social.youtube}
                                        target="_blank"
                                        rel="noreferrer"
                                        aria-label="YouTube"
                                        className="text-brand-green-800 hover:underline"
                                    >
                                        <Youtube className="size-5" />
                                    </a>
                                )}
                            </section>
                        )}
                    </div>

                    <Card>
                        <CardContent className="pt-6">
                            <h2 className="text-brand-green-900 font-semibold">
                                {t('public.contact.form_title')}
                            </h2>

                            <form onSubmit={submit} className="mt-4 space-y-4">
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
                                    <Label htmlFor="message">
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
                                        className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-1 focus-visible:outline-none"
                                        required
                                    />
                                    <InputError message={form.errors.message} />
                                </div>

                                <p className="text-muted-foreground text-xs">
                                    {t('public.contact.privacy_note')}
                                </p>

                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    <Send
                                        className="me-1 size-4"
                                        aria-hidden="true"
                                    />
                                    {t('public.contact.send')}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
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
        <li className="flex items-start gap-2">
            <Icon
                className="text-brand-green-800 mt-0.5 size-4 shrink-0"
                aria-hidden="true"
            />
            {href ? (
                <a href={href} className="hover:underline">
                    {value}
                </a>
            ) : (
                <span>{value}</span>
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
            <Label htmlFor={id}>{label}</Label>
            <Input
                id={id}
                type={type}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                required={required}
            />
            <InputError message={error} />
        </div>
    );
}
