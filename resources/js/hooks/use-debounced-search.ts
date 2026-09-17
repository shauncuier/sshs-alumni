import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

/**
 * A search box that waits for the typist to pause before asking the server.
 *
 * Without the debounce, a ten-character name is ten round trips — noticeable
 * on the mobile connections most of this audience uses, and ten times the
 * database work for one search.
 */
export function useDebouncedSearch(initial: string, url: string, delay = 350) {
    const [term, setTerm] = useState(initial);
    const isFirstRender = useRef(true);

    useEffect(() => {
        // Do not re-query on mount with the value the server just sent us.
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timer = setTimeout(() => {
            router.get(
                url,
                { q: term || undefined },
                { preserveState: true, replace: true, preserveScroll: true },
            );
        }, delay);

        return () => clearTimeout(timer);
    }, [term, url, delay]);

    return { term, setTerm };
}
