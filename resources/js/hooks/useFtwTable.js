import { useState, useEffect, useCallback, useRef } from "react";
import axios from "axios";
import { toast } from "sonner";

/**
 * Manages server-side pagination, sorting, search, and data loading
 * for an FTW data table panel.
 *
 * @param {string} fetchUrl - The GET endpoint to fetch rows from.
 */
export function useFtwTable(fetchUrl) {
    const [data, setData] = useState([]);
    const [meta, setMeta] = useState({
        current_page: 1,
        last_page: 1,
        total: 0,
        from: 0,
        to: 0,
    });
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState("10");
    const [search, setSearch] = useState("");
    const [orderBy, setOrderBy] = useState("date_created");
    const [orderDir, setOrderDir] = useState("desc");
    const [loading, setLoading] = useState(false);

    // Stable ref so `load` never captures stale closure values
    const stateRef = useRef({ search, orderBy, orderDir, page, perPage });
    stateRef.current = { search, orderBy, orderDir, page, perPage };

    const debounceRef = useRef(null);

    const load = useCallback(
        async (overrides = {}) => {
            const s = stateRef.current;
            setLoading(true);
            try {
                const { data: json } = await axios.get(fetchUrl, {
                    params: {
                        search:    overrides.search    ?? s.search,
                        order_by:  overrides.orderBy   ?? s.orderBy,
                        order_dir: overrides.orderDir  ?? s.orderDir,
                        page:      overrides.page      ?? s.page,
                        per_page:  overrides.perPage   ?? s.perPage,
                    },
                });
                setData(json.data ?? []);
                setMeta(
                    json.meta ?? {
                        current_page: 1,
                        last_page: 1,
                        total: 0,
                        from: 0,
                        to: 0,
                    },
                );
            } catch {
                toast.error("Failed to load data.");
            } finally {
                setLoading(false);
            }
        },
        [fetchUrl],
    );

    // Reload whenever pagination or sort parameters change (also fires on mount)
    useEffect(() => {
        load();
    }, [page, perPage, orderBy, orderDir]);

    const handleSearch = (val) => {
        setSearch(val);
        clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            setPage(1);
            load({ search: val, page: 1 });
        }, 350);
    };

    const handleSort = (key) => {
        const newDir = orderBy === key && orderDir === "asc" ? "desc" : "asc";
        setOrderBy(key);
        setOrderDir(newDir);
        setPage(1);
        load({ orderBy: key, orderDir: newDir, page: 1 });
    };

    const handlePageChange = (p) => setPage(p);

    const handlePerPageChange = (v) => {
        setPerPage(v);
        setPage(1);
    };

    return {
        data,
        meta,
        page,
        perPage,
        search,
        orderBy,
        orderDir,
        loading,
        load,
        handleSearch,
        handleSort,
        handlePageChange,
        handlePerPageChange,
    };
}
