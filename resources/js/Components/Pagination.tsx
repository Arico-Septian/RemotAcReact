import { Link } from '@inertiajs/react';
import '../../css/pagination.css';

interface PaginationData {
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
}

interface Props {
    pagination: PaginationData;
    label?: string;
}

// Deret nomor halaman dengan ellipsis: tampil maks 5 nomor (1 … cur-1 cur cur+1 … last).
function buildPages(current: number, last: number): (number | 'dots')[] {
    if (last <= 5) return Array.from({ length: last }, (_, i) => i + 1);
    const out: (number | 'dots')[] = [1];
    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);
    if (start > 2) out.push('dots');
    for (let i = start; i <= end; i++) out.push(i);
    if (end < last - 1) out.push('dots');
    out.push(last);
    return out;
}

// URL halaman: pertahankan query (search/filter), set ?page=N.
function pageHref(page: number): string {
    const search = typeof window !== 'undefined' ? window.location.search : '';
    const path = typeof window !== 'undefined' ? window.location.pathname : '';
    const params = new URLSearchParams(search);
    params.set('page', String(page));
    return `${path}?${params.toString()}`;
}

export default function Pagination({ pagination, label = 'item' }: Props) {
    const { current_page, last_page, from, to, total } = pagination;
    if (last_page <= 1) return null;

    const pages = buildPages(current_page, last_page);

    return (
        <div className="pgn">
            <p className="pgn-info">
                Menampilkan <b>{from}–{to}</b> dari <b>{total}</b> {label}
            </p>
            <div className="pgn-list">
                {current_page > 1 ? (
                    <Link href={pageHref(current_page - 1)} className="pgn-btn pgn-arrow" preserveScroll preserveState aria-label="Halaman sebelumnya">
                        <i className="fa-solid fa-chevron-left"></i>
                    </Link>
                ) : (
                    <span className="pgn-btn pgn-arrow is-disabled"><i className="fa-solid fa-chevron-left"></i></span>
                )}

                {/* Indikator ringkas khusus mobile */}
                <span className="pgn-mobile">{current_page} / {last_page}</span>

                {pages.map((p, i) =>
                    p === 'dots' ? (
                        <span key={`d${i}`} className="pgn-dots">…</span>
                    ) : p === current_page ? (
                        <span key={p} className="pgn-btn pgn-num active" aria-current="page">{p}</span>
                    ) : (
                        <Link key={p} href={pageHref(p)} className="pgn-btn pgn-num" preserveScroll preserveState>
                            {p}
                        </Link>
                    ),
                )}

                {current_page < last_page ? (
                    <Link href={pageHref(current_page + 1)} className="pgn-btn pgn-arrow" preserveScroll preserveState aria-label="Halaman berikutnya">
                        <i className="fa-solid fa-chevron-right"></i>
                    </Link>
                ) : (
                    <span className="pgn-btn pgn-arrow is-disabled"><i className="fa-solid fa-chevron-right"></i></span>
                )}
            </div>
        </div>
    );
}
