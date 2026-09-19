import { useEffect, useState, useRef } from 'react';
import { router } from '@inertiajs/react';
import { Search, Loader2, ArrowRight, Users, GraduationCap, Calendar, UserCheck, FileText, Bell, BookOpen, X } from 'lucide-react';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';

type SearchResultItem = {
    id: string | number;
    title: string;
    subtitle: string | null;
    badge: string | null;
    url: string;
};

type GroupedResults = Record<string, SearchResultItem[]>;

const ICON_MAP: Record<string, React.ComponentType<{ className?: string }>> = {
    Members: Users,
    Batches: GraduationCap,
    Events: Calendar,
    'CRM Contacts': UserCheck,
    News: BookOpen,
    Announcements: Bell,
    Pages: FileText,
};

export function GlobalSearchModal({
    isOpen,
    onClose,
}: {
    isOpen: boolean;
    onClose: () => void;
}) {
    const [query, setQuery] = useState('');
    const [loading, setLoading] = useState(false);
    const [results, setResults] = useState<GroupedResults>({});
    const [selectedIndex, setSelectedIndex] = useState(0);
    const inputRef = useRef<HTMLInputElement>(null);

    // Flatten results for keyboard navigation
    const flatItems: SearchResultItem[] = Object.values(results).flat();

    // Auto-focus input when opened
    useEffect(() => {
        if (isOpen) {
            setTimeout(() => inputRef.current?.focus(), 50);
            setQuery('');
            setResults({});
            setSelectedIndex(0);
        }
    }, [isOpen]);

    // Live debounced search
    useEffect(() => {
        if (!isOpen) return;

        const trimmed = query.trim();
        if (trimmed.length < 2) {
            setResults({});
            setLoading(false);
            return;
        }

        setLoading(true);
        const timer = setTimeout(() => {
            fetch(`/admin/search?q=${encodeURIComponent(trimmed)}`, {
                headers: {
                    Accept: 'application/json',
                },
            })
                .then((res) => res.json())
                .then((data) => {
                    setResults(data.results || {});
                    setSelectedIndex(0);
                })
                .catch(() => setResults({}))
                .finally(() => setLoading(false));
        }, 250);

        return () => clearTimeout(timer);
    }, [query, isOpen]);

    // Keyboard navigation
    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (flatItems.length > 0) {
                setSelectedIndex((prev) => (prev + 1) % flatItems.length);
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (flatItems.length > 0) {
                setSelectedIndex((prev) => (prev - 1 + flatItems.length) % flatItems.length);
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (flatItems[selectedIndex]) {
                handleSelect(flatItems[selectedIndex]);
            }
        } else if (e.key === 'Escape') {
            onClose();
        }
    };

    const handleSelect = (item: SearchResultItem) => {
        onClose();
        router.visit(item.url);
    };

    let itemCounter = 0;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-2xl p-0 gap-0 overflow-hidden border-border/80 bg-popover/95 backdrop-blur-md shadow-2xl rounded-xl">
                <DialogTitle className="sr-only">Global Administrative Search</DialogTitle>

                {/* Input Header */}
                <div className="flex items-center gap-3 px-4 py-3.5 border-b border-border/60 bg-muted/20">
                    <Search className="size-5 text-muted-foreground shrink-0" />
                    <input
                        ref={inputRef}
                        type="text"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        onKeyDown={handleKeyDown}
                        placeholder="Search members, cohorts, events, CRM contacts, news..."
                        className="flex-1 bg-transparent border-0 outline-none text-base text-foreground placeholder:text-muted-foreground focus:ring-0"
                    />
                    {loading ? (
                        <Loader2 className="size-4 text-muted-foreground animate-spin shrink-0" />
                    ) : query ? (
                        <button
                            type="button"
                            onClick={() => setQuery('')}
                            className="text-muted-foreground hover:text-foreground"
                        >
                            <X className="size-4" />
                        </button>
                    ) : (
                        <kbd className="hidden sm:inline-flex items-center gap-1 text-[10px] font-mono bg-muted/60 text-muted-foreground px-1.5 py-0.5 rounded border border-border/60">
                            ESC
                        </kbd>
                    )}
                </div>

                {/* Results Container */}
                <div className="max-h-[60vh] overflow-y-auto p-2 divide-y divide-border/20">
                    {query.trim().length < 2 && (
                        <div className="py-12 text-center text-sm text-muted-foreground">
                            Type at least 2 characters to search across the platform...
                        </div>
                    )}

                    {!loading && query.trim().length >= 2 && flatItems.length === 0 && (
                        <div className="py-12 text-center text-sm text-muted-foreground">
                            No matching records found for "<span className="text-foreground">{query}</span>"
                        </div>
                    )}

                    {Object.entries(results).map(([category, items]) => {
                        const CategoryIcon = ICON_MAP[category] || FileText;

                        return (
                            <div key={category} className="py-2 first:pt-0 last:pb-0">
                                <div className="px-3 py-1.5 text-[11px] font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                                    <CategoryIcon className="size-3.5" />
                                    {category}
                                </div>
                                <div className="space-y-0.5 mt-1">
                                    {items.map((item) => {
                                        const currentIndex = itemCounter++;
                                        const isSelected = currentIndex === selectedIndex;

                                        return (
                                            <button
                                                key={`${category}-${item.id}`}
                                                type="button"
                                                onClick={() => handleSelect(item)}
                                                onMouseEnter={() => setSelectedIndex(currentIndex)}
                                                className={`w-full flex items-center justify-between px-3 py-2 text-left rounded-lg text-sm transition-colors ${
                                                    isSelected
                                                        ? 'bg-accent text-accent-foreground'
                                                        : 'hover:bg-muted/50 text-foreground'
                                                }`}
                                            >
                                                <div className="flex-1 min-w-0 pr-3">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium truncate">{item.title}</span>
                                                        {item.badge && (
                                                            <span className="inline-flex items-center px-1.5 py-0.2 text-[10px] font-mono rounded bg-secondary text-secondary-foreground border border-border/50 shrink-0">
                                                                {item.badge}
                                                            </span>
                                                        )}
                                                    </div>
                                                    {item.subtitle && (
                                                        <p className="text-xs text-muted-foreground truncate mt-0.5">
                                                            {item.subtitle}
                                                        </p>
                                                    )}
                                                </div>
                                                <ArrowRight
                                                    className={`size-4 text-muted-foreground shrink-0 transition-transform ${
                                                        isSelected ? 'translate-x-0.5 text-foreground' : 'opacity-40'
                                                    }`}
                                                />
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Footer hints */}
                <div className="flex items-center justify-between px-4 py-2 border-t border-border/50 bg-muted/20 text-[11px] text-muted-foreground">
                    <div className="flex items-center gap-3">
                        <span><kbd className="font-mono bg-muted/80 px-1 py-0.5 rounded border">↑</kbd> <kbd className="font-mono bg-muted/80 px-1 py-0.5 rounded border">↓</kbd> navigate</span>
                        <span><kbd className="font-mono bg-muted/80 px-1 py-0.5 rounded border">↵</kbd> select</span>
                    </div>
                    <span>Instant Global Search</span>
                </div>
            </DialogContent>
        </Dialog>
    );
}
