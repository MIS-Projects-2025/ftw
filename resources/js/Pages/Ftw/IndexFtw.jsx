import axios from "axios";
import { useState, useEffect, useRef } from "react";
import { usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import ServerTable from "@/Components/ServerTable";
import { Pagination } from "@/Components/Pagination";
import { Button } from "@/components/ui/button";
import { toast } from "sonner";
import { ClipboardPlus, Search, Loader2, RefreshCw } from "lucide-react";

import { useFtwTable } from "@/hooks/useFtwTable";
import { useBulkSelect } from "@/hooks/useBulkSelect";
import { StatusBadge } from "./components/StatusBadge";
import { ViewModal } from "./components/ViewModal";
import { ActionModal } from "./components/ActionModal";
import { BulkActionDialog } from "./components/BulkActionDialog";
import { RowActions } from "./components/RowActions";

// ─── Base column definitions (shared between pending and history panels) ──────

const BASE_COLUMNS = [
    {
        key: "tbl_id",
        sortKey: "tbl_id",
        label: "#",
        sortable: true,
        className: "text-sm font-medium",
        headerClassName: "w-16",
    },
    {
        key: "date_created",
        sortKey: "date_created",
        label: "Date",
        sortable: true,
        className: "whitespace-nowrap text-xs",
        headerClassName: "w-28",
    },
    {
        key: "emp_name",
        sortKey: "emp_name",
        label: "Employee",
        sortable: true,
        className: "text-sm font-medium",
    },
    {
        key: "emp_dept",
        sortKey: "emp_dept",
        label: "Department",
        sortable: true,
        className: "text-sm text-muted-foreground",
    },
    {
        key: "rec_label",
        label: "Recommendation",
        className: "text-sm",
    },
    {
        key: "process_status",
        label: "Status",
        render: (row) => <StatusBadge status={row.process_status} />,
    },
];

function makeActionColumn(isPendingTab, onView, onHandle) {
    return {
        key: "_action",
        label: "",
        headerClassName: "w-10",
        className: "text-center",
        render: (row) => (
            <RowActions
                row={row}
                canAct={isPendingTab}
                onView={onView}
                onHandle={onHandle}
            />
        ),
    };
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function IndexFtw() {
    const { isSupervisor, isClinic, canCreate } = usePage().props;

    const [tab, setTab] = useState(isClinic ? "history" : "pending");
    const [viewRecord, setViewRecord] = useState(null);
    const [actionRecord, setActionRecord] = useState(null);

    const historyReloadRef = useRef(null);
    const pendingReloadRef = useRef(null);
    const pendingPanelRef  = useRef(null);

    const historyColumns = [
        ...BASE_COLUMNS,
        makeActionColumn(false, setViewRecord, setActionRecord),
    ];
    const pendingColumns = [
        ...BASE_COLUMNS,
        makeActionColumn(true, setViewRecord, setActionRecord),
    ];

    const tabs = isClinic ? ["history"] : ["pending", "history"];

    return (
        <AuthenticatedLayout>
            <div className="max-w-full px-4 sm:px-6 py-6 space-y-5">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            Fit to Work Records
                        </h1>
                        <p className="text-sm text-muted-foreground mt-0.5">
                            Review FTW history and act on pending approvals.
                        </p>
                    </div>
                    {canCreate && (
                        <Button asChild size="sm">
                            <a href={route("ftw.create")}>
                                <ClipboardPlus className="mr-1.5 h-4 w-4" />
                                New FTW
                            </a>
                        </Button>
                    )}
                </div>

                {/* Tabs */}
                <div className="flex border-b">
                    {tabs.map((t) => (
                        <button
                            key={t}
                            onClick={() => setTab(t)}
                            className={[
                                "px-5 py-2.5 text-sm font-medium transition-colors capitalize",
                                tab === t
                                    ? "border-b-2 border-primary text-primary"
                                    : "text-muted-foreground hover:text-foreground",
                            ].join(" ")}
                        >
                            {t === "history" ? "History" : "Pending"}
                        </button>
                    ))}
                </div>

                {/* Pending panel */}
                {tab === "pending" && (
                    <FetchPanel
                        key="pending"
                        fetchUrl={route("ftw.data.pending")}
                        columns={pendingColumns}
                        emptyMessage="No pending actions for you."
                        reloadRef={pendingReloadRef}
                        onMounted={(reload) => { pendingPanelRef.current = reload; }}
                        enableBulk={isSupervisor}
                        onBulkSuccess={() => historyReloadRef.current?.()}
                    />
                )}

                {/* History panel */}
                {tab === "history" && (
                    <FetchPanel
                        key="history"
                        fetchUrl={route("ftw.data.history")}
                        columns={historyColumns}
                        emptyMessage="No FTW records found."
                        reloadRef={historyReloadRef}
                    />
                )}
            </div>

            {viewRecord && (
                <ViewModal record={viewRecord} onClose={() => setViewRecord(null)} />
            )}

            {actionRecord && (
                <ActionModal
                    record={actionRecord}
                    isSupervisor={isSupervisor}
                    onClose={() => setActionRecord(null)}
                    onSuccess={() => {
                        pendingPanelRef.current?.();
                        historyReloadRef.current?.();
                    }}
                />
            )}
        </AuthenticatedLayout>
    );
}

// ─── FetchPanel ───────────────────────────────────────────────────────────────
// Wires useFtwTable + useBulkSelect together into a search/table/pagination UI.

function FetchPanel({
    fetchUrl,
    columns,
    emptyMessage,
    reloadRef,
    onMounted,
    enableBulk = false,
    onBulkSuccess,
}) {
    const {
        data, meta, search, orderBy, orderDir, loading, perPage,
        load, handleSearch, handleSort, handlePageChange, handlePerPageChange,
    } = useFtwTable(fetchUrl);

    const {
        selectedIds, allSelected, someSelected,
        toggleAll, toggleRow, clearSelection,
    } = useBulkSelect(data);

    const [bulkDialogAction, setBulkDialogAction] = useState(null);
    const [bulkLoading, setBulkLoading] = useState(false);

    // Expose reload fn to parent (e.g. after a single-record action on another panel)
    useEffect(() => {
        if (reloadRef) reloadRef.current = () => load();
        if (onMounted) onMounted(() => load());
    }, [load]);

    const handleBulkAction = async (action, remarks) => {
        setBulkLoading(true);
        try {
            const { data: json } = await axios.post(route("ftw.bulk-action"), {
                action,
                ids: [...selectedIds],
                remarks: remarks || null,
            });
            toast.success(
                action === "approve"
                    ? `${json.processed ?? selectedIds.size} record(s) approved.`
                    : `${json.processed ?? selectedIds.size} record(s) disapproved.`,
            );
            clearSelection();
            setBulkDialogAction(null);
            onBulkSuccess?.();
            load();
        } catch (err) {
            toast.error(err.response?.data?.message ?? err.message ?? "Something went wrong.");
        } finally {
            setBulkLoading(false);
        }
    };

    // Prepend a checkbox column when bulk mode is active
    const checkboxCol = {
        key: "_select",
        headerClassName: "w-10",
        label: (
            <input
                type="checkbox"
                className="h-4 w-4 rounded border-input cursor-pointer"
                checked={allSelected}
                ref={(el) => { if (el) el.indeterminate = someSelected; }}
                onChange={toggleAll}
            />
        ),
        className: "text-center",
        render: (row) => (
            <input
                type="checkbox"
                className="h-4 w-4 rounded border-input cursor-pointer"
                checked={selectedIds.has(row.tbl_id)}
                onChange={() => toggleRow(row.tbl_id)}
            />
        ),
    };

    const effectiveColumns = enableBulk ? [checkboxCol, ...columns] : columns;

    return (
        <>
            <div className="space-y-3">
                {/* Search + refresh */}
                <div className="flex items-center gap-2">
                    <div className="relative flex-1 max-w-xs">
                        <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                        <input
                            type="text"
                            placeholder="Search name, dept, or ID…"
                            value={search}
                            onChange={(e) => handleSearch(e.target.value)}
                            className="h-9 w-full rounded-md border border-input bg-background pl-9 pr-3 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>
                    <Button
                        variant="outline"
                        size="icon"
                        className="h-9 w-9"
                        onClick={() => load()}
                        disabled={loading}
                    >
                        {loading ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            <RefreshCw className="h-4 w-4" />
                        )}
                    </Button>
                </div>

                {/* Bulk action bar — appears above the table when rows are selected */}
                {enableBulk && selectedIds.size > 0 && (
                    <div className="flex items-center gap-3 rounded-lg border bg-muted/40 px-4 py-2.5">
                        <span className="text-sm font-medium text-foreground">
                            {selectedIds.size} record{selectedIds.size !== 1 ? "s" : ""} selected
                        </span>
                        <div className="flex items-center gap-2 ml-auto">
                            <Button
                                size="sm"
                                disabled={bulkLoading}
                                onClick={() => setBulkDialogAction("approve")}
                            >
                                Approve Selected
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                disabled={bulkLoading}
                                onClick={() => setBulkDialogAction("disapprove")}
                            >
                                Disapprove Selected
                            </Button>
                            <button
                                onClick={clearSelection}
                                className="text-xs text-muted-foreground hover:text-foreground transition-colors ml-1"
                            >
                                Clear
                            </button>
                        </div>
                    </div>
                )}

                <ServerTable
                    columns={effectiveColumns}
                    data={data}
                    orderBy={orderBy}
                    orderDir={orderDir}
                    onSort={handleSort}
                    emptyMessage={emptyMessage}
                />

                <Pagination
                    meta={meta}
                    onPageChange={handlePageChange}
                    perPage={perPage}
                    onPerPageChange={handlePerPageChange}
                />
            </div>

            {bulkDialogAction && (
                <BulkActionDialog
                    action={bulkDialogAction}
                    count={selectedIds.size}
                    loading={bulkLoading}
                    onConfirm={(remarks) => handleBulkAction(bulkDialogAction, remarks)}
                    onClose={() => setBulkDialogAction(null)}
                />
            )}
        </>
    );
}
