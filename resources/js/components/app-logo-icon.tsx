import type { ImgHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function AppLogoIcon({
    className,
    alt = 'SSHS Former Students Association Logo',
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/brand/logo-association.png"
            alt={alt}
            className={cn('aspect-square object-contain', className)}
            {...props}
        />
    );
}

