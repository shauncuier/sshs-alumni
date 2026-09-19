import { useState, useEffect } from 'react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Search } from 'lucide-react';
import { GlobalSearchModal } from '@/components/admin/global-search-modal';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const [searchOpen, setSearchOpen] = useState(false);

    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setSearchOpen((prev) => !prev);
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    return (
        <header className="border-sidebar-border/50 flex h-16 shrink-0 items-center justify-between gap-2 border-b px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <div className="flex items-center gap-2">
                <button
                    type="button"
                    onClick={() => setSearchOpen(true)}
                    className="inline-flex items-center gap-2 px-2.5 py-1.5 text-xs text-muted-foreground hover:text-foreground bg-muted/40 hover:bg-muted/70 border border-border/60 rounded-md transition-colors shadow-xs cursor-pointer"
                >
                    <Search className="size-3.5" />
                    <span className="hidden sm:inline">Search...</span>
                    <kbd className="hidden sm:inline-flex items-center gap-0.5 text-[10px] font-mono bg-background/80 px-1 py-0.2 rounded border border-border/80">
                        ⌘K
                    </kbd>
                </button>
            </div>

            <GlobalSearchModal isOpen={searchOpen} onClose={() => setSearchOpen(false)} />
        </header>
    );
}
