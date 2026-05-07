import { useState, useEffect } from "react";

/**
 * Manages bulk row selection state for a data table.
 *
 * @param {object[]} data    - Current page rows.
 * @param {string}   idKey  - The field name used as the row identifier.
 */
export function useBulkSelect(data, idKey = "tbl_id") {
    const [selectedIds, setSelectedIds] = useState(new Set());

    // Clear selection whenever the page data is replaced
    useEffect(() => {
        setSelectedIds(new Set());
    }, [data]);

    const allPageIds = data.map((r) => r[idKey]);
    const allSelected =
        allPageIds.length > 0 && allPageIds.every((id) => selectedIds.has(id));
    const someSelected =
        !allSelected && allPageIds.some((id) => selectedIds.has(id));

    const toggleAll = () =>
        setSelectedIds(allSelected ? new Set() : new Set(allPageIds));

    const toggleRow = (id) =>
        setSelectedIds((prev) => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });

    const clearSelection = () => setSelectedIds(new Set());

    return {
        selectedIds,
        allSelected,
        someSelected,
        toggleAll,
        toggleRow,
        clearSelection,
    };
}
