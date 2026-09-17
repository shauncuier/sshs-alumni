import { Link } from '@inertiajs/react';
import { CircleCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import PublicLayout from '@/layouts/public-layout';

export default function JoinDone() {
    const { t } = useTranslation();

    return (
        <PublicLayout title={t('public.join.done.title')} indexable={false}>
            <div className="bg-brand-cream">
                <div className="mx-auto max-w-2xl px-4 py-20 text-center">
                    <CircleCheck
                        className="text-brand-green-800 mx-auto size-14"
                        aria-hidden="true"
                    />

                    <h1 className="text-brand-green-900 mt-6 text-2xl font-semibold sm:text-3xl">
                        {t('public.join.done.title')}
                    </h1>

                    <p className="text-muted-foreground mx-auto mt-4 max-w-prose leading-relaxed">
                        {t('public.join.done.body')}
                    </p>

                    <div className="mt-8 flex flex-wrap justify-center gap-3">
                        <Button asChild>
                            <Link href="/login">
                                {t('common.actions.login')}
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/">{t('public.nav.home')}</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
