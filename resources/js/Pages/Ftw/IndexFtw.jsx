import { useState, useEffect, useCallback, useRef } from "react";
import { usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import ServerTable from "@/Components/ServerTable";
import { Pagination } from "@/Components/Pagination";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/components/ui/dialog";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "sonner";
import {
    ClipboardPlus,
    Search,
    Loader2,
    RefreshCw,
    Eye,
    MoreHorizontal,
    CheckCircle,
} from "lucide-react";

// ─── Status badge ─────────────────────────────────────────────────────────────

const STATUS_MAP = {
    1: {
        label: "For Supervisor Approval",
        cls: "bg-blue-100 text-blue-700 border-blue-200",
    },
    2: {
        label: "For Employee Acknowledgement",
        cls: "bg-amber-100 text-amber-700 border-amber-200",
    },
    3: {
        label: "Completed",
        cls: "bg-green-100 text-green-700 border-green-200",
    },
    4: {
        label: "Disapproved / Rejected",
        cls: "bg-red-100 text-red-700 border-red-200",
    },
};

function StatusBadge({ status }) {
    const s = STATUS_MAP[status] ?? {
        label: `Status ${status}`,
        cls: "bg-muted text-muted-foreground border-border",
    };
    return (
        <span
            className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium ${s.cls}`}
        >
            {s.label}
        </span>
    );
}

// ─── Detail field ─────────────────────────────────────────────────────────────

function DetailField({ label, children, className = "" }) {
    return (
        <div className={`flex flex-col gap-1 ${className}`}>
            <span className="text-[11px] text-muted-foreground uppercase tracking-wide font-medium">
                {label}
            </span>
            <div className="text-sm font-medium">{children}</div>
        </div>
    );
}

// ─── Rec-specific details (shared by both modals) ─────────────────────────────

function RecordDetails({ record }) {
    const label = (record.rec_label ?? "").toLowerCase();

    const isFitToWork = label.includes("fit to work");
    const isSdh = label.includes("sent home") || label.includes("hospital");
    const isUnfit = label.includes("unfit");
    const isRest = label.includes("rest");

    const empty = <span className="text-muted-foreground font-normal">—</span>;

    if (isFitToWork) {
        return (
            <div className="grid grid-cols-2 gap-x-4 gap-y-3">
                <DetailField label="Time In">
                    {record.emp_time_in || empty}
                </DetailField>
                <DetailField label="Shift">
                    {record.emp_shift_label || record.emp_shift || empty}
                </DetailField>
                <DetailField label="Diagnosis" className="col-span-2">
                    <p className="whitespace-pre-wrap leading-relaxed font-normal text-sm">
                        {record.emp_diagnose || empty}
                    </p>
                </DetailField>
                <DetailField
                    label={`Dates of Absence (${record.absent_count ?? 0})`}
                    className="col-span-2"
                >
                    {record.absence_dates?.length ? (
                        <div className="flex flex-wrap gap-1.5 mt-0.5">
                            {record.absence_dates.map((d) => (
                                <span
                                    key={d}
                                    className="rounded-md border bg-muted/50 px-2 py-0.5 text-xs font-normal"
                                >
                                    {d}
                                </span>
                            ))}
                        </div>
                    ) : (
                        <span className="text-muted-foreground font-normal">
                            None
                        </span>
                    )}
                </DetailField>
                {record.ftw_file && (
                    <DetailField label="Attached File" className="col-span-2">
                        <a
                            href={record.ftw_file_url}
                            target="_blank"
                            rel="noreferrer"
                            className="text-primary underline underline-offset-2 text-sm font-normal"
                        >
                            {record.ftw_file}
                        </a>
                    </DetailField>
                )}
            </div>
        );
    }

    if (isSdh) {
        const dateLabel = label.includes("hospital")
            ? "Date sent to hospital"
            : "Date sent home";
        return (
            <div className="grid grid-cols-2 gap-x-4 gap-y-3">
                <DetailField label="Diagnosis" className="col-span-2">
                    <p className="whitespace-pre-wrap leading-relaxed font-normal text-sm">
                        {record.emp_diagnose || empty}
                    </p>
                </DetailField>
                <DetailField label="Shift">
                    {record.emp_shift_label || record.emp_shift || empty}
                </DetailField>
                <DetailField label={dateLabel}>
                    {record.sdh_date || empty}
                </DetailField>
                <DetailField label="Time Out">
                    {record.sdh_time || empty}
                </DetailField>
            </div>
        );
    }

    if (isUnfit) {
        return (
            <div className="grid grid-cols-2 gap-x-4 gap-y-3">
                <DetailField label="Remarks" className="col-span-2">
                    <p className="whitespace-pre-wrap leading-relaxed font-normal text-sm">
                        {record.remarks || empty}
                    </p>
                </DetailField>
                <DetailField label="Shift">
                    {record.emp_shift_label || record.emp_shift || empty}
                </DetailField>
                <DetailField label="Date">
                    {record.sdh_date || empty}
                </DetailField>
            </div>
        );
    }

    if (isRest) {
        return (
            <div className="grid grid-cols-2 gap-x-4 gap-y-3">
                <DetailField label="Diagnosis" className="col-span-2">
                    <p className="whitespace-pre-wrap leading-relaxed font-normal text-sm">
                        {record.emp_diagnose || empty}
                    </p>
                </DetailField>
                <DetailField label="Date">
                    {record.rest_date || empty}
                </DetailField>
                <DetailField label="Time In (SDH)">
                    {record.rest_time_in || empty}
                </DetailField>
                <DetailField label="Time Out">
                    <span className="text-muted-foreground italic text-xs font-normal">
                        Set when employee returns
                    </span>
                </DetailField>
            </div>
        );
    }

    return null;
}

// ─── Shared record summary rows ───────────────────────────────────────────────

function RecordSummary({ record }) {
    return (
        <div className="rounded-md border bg-muted/40 p-4 text-sm space-y-1.5">
            {[
                ["Employee", record.emp_name],
                ["Department", record.emp_dept],
                [
                    "Recommendation",
                    <span className="font-medium">{record.rec_label}</span>,
                ],
                ["Date", record.date_created],
                ["Status", <StatusBadge status={record.process_status} />],
            ].map(([lbl, val]) => (
                <div key={lbl} className="flex gap-2">
                    <span className="w-32 text-muted-foreground shrink-0">
                        {lbl}
                    </span>
                    <span>{val}</span>
                </div>
            ))}
        </div>
    );
}

// ─── View modal (read-only) ───────────────────────────────────────────────────

function ViewModal({ record, onClose }) {
    if (!record) return null;
    return (
        <Dialog
            open
            onOpenChange={(o) => {
                if (!o) onClose();
            }}
        >
            <DialogContent className="sm:max-w-lg max-h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>FTW Record Details</DialogTitle>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto space-y-4 pr-1">
                    <RecordSummary record={record} />
                    <div className="rounded-md border p-4">
                        <p className="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider mb-3">
                            Record Details
                        </p>
                        <RecordDetails record={record} />
                    </div>
                </div>

                <DialogFooter className="pt-3 border-t mt-2">
                    <Button variant="outline" onClick={onClose}>
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

// ─── Action modal ─────────────────────────────────────────────────────────────

function ActionModal({ record, isSupervisor, onClose, onSuccess }) {
    const [action, setAction] = useState(null);
    const [remarks, setRemarks] = useState("");
    const [loading, setLoading] = useState(false);

    if (!record) return null;

    const needsRemarks = action === "disapprove" || action === "reject";

    const submit = async () => {
        if (needsRemarks && !remarks.trim()) {
            toast.error("Remarks are required.");
            return;
        }
        setLoading(true);
        try {
            const res = await fetch(route("ftw.action", record.tbl_id), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content ?? "",
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    action,
                    remarks: remarks.trim() || null,
                }),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message ?? "Request failed");
            toast.success(
                action === "approve"
                    ? "Record approved."
                    : action === "disapprove"
                      ? "Record disapproved."
                      : action === "acknowledge"
                        ? "Record acknowledged."
                        : "Record rejected.",
            );
            onSuccess();
            onClose();
        } catch (err) {
            toast.error(err.message ?? "Something went wrong.");
        } finally {
            setLoading(false);
        }
    };

    const actionButtons = isSupervisor
        ? [
              {
                  key: "approve",
                  label: "Approve",
                  cls: "bg-green-600 hover:bg-green-700 text-white",
              },
              {
                  key: "disapprove",
                  label: "Disapprove",
                  cls: "bg-destructive hover:bg-destructive/90 text-destructive-foreground",
              },
          ]
        : [
              {
                  key: "acknowledge",
                  label: "Acknowledge",
                  cls: "bg-green-600 hover:bg-green-700 text-white",
              },
              {
                  key: "reject",
                  label: "Reject",
                  cls: "bg-destructive hover:bg-destructive/90 text-destructive-foreground",
              },
          ];

    return (
        <Dialog
            open
            onOpenChange={(o) => {
                if (!o) onClose();
            }}
        >
            <DialogContent className="sm:max-w-lg max-h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>
                        {isSupervisor
                            ? "Supervisor Approval"
                            : "Employee Acknowledgement"}
                    </DialogTitle>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto space-y-4 pr-1">
                    <RecordSummary record={record} />

                    <div className="rounded-md border p-4">
                        <p className="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider mb-3">
                            Record Details
                        </p>
                        <RecordDetails record={record} />
                    </div>

                    {/* Step 1 — choose action */}
                    {!action && (
                        <div className="flex gap-2 justify-end">
                            {actionButtons.map((btn) => (
                                <Button
                                    key={btn.key}
                                    className={btn.cls}
                                    onClick={() => setAction(btn.key)}
                                >
                                    {btn.label}
                                </Button>
                            ))}
                        </div>
                    )}

                    {/* Step 2 — remarks */}
                    {action && (
                        <div className="space-y-2">
                            <Label htmlFor="modal-remarks">
                                Remarks{" "}
                                {needsRemarks && (
                                    <span className="text-destructive">*</span>
                                )}
                            </Label>
                            <Textarea
                                id="modal-remarks"
                                rows={3}
                                placeholder={
                                    needsRemarks
                                        ? "Required — please state your reason."
                                        : "Optional"
                                }
                                value={remarks}
                                onChange={(e) => setRemarks(e.target.value)}
                            />
                        </div>
                    )}
                </div>

                <DialogFooter className="gap-2 pt-3 border-t mt-2">
                    <Button
                        variant="outline"
                        disabled={loading}
                        onClick={() => {
                            action ? setAction(null) : onClose();
                        }}
                    >
                        {action ? "Back" : "Cancel"}
                    </Button>
                    {action && (
                        <Button onClick={submit} disabled={loading}>
                            {loading && (
                                <Loader2 className="mr-1.5 h-4 w-4 animate-spin" />
                            )}
                            Confirm
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

// ─── Row actions dropdown ─────────────────────────────────────────────────────

function RowActions({ row, canAct, onView, onHandle }) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="h-7 w-7">
                    <MoreHorizontal className="h-4 w-4" />
                    <span className="sr-only">Open actions</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-40">
                <DropdownMenuItem onClick={() => onView(row)}>
                    <Eye className="mr-2 h-4 w-4" />
                    View
                </DropdownMenuItem>
                {canAct && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={() => onHandle(row)}>
                            <CheckCircle className="mr-2 h-4 w-4" />
                            Handle
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function IndexFtw() {
    const { isSupervisor, isClinic } = usePage().props;

    const [tab, setTab] = useState("pending");
    const [viewRecord, setViewRecord] = useState(null);
    const [actionRecord, setActionRecord] = useState(null);

    const historyReloadRef = useRef(null);
    const pendingReloadRef = useRef(null);
    const pendingPanelRef = useRef(null); // also holds the reload fn for the pending panel

    // ── Column definitions ────────────────────────────────────────────────

    const makeActionColumn = (isPendingTab) => ({
        key: "_action",
        label: "",
        headerClassName: "w-10",
        className: "text-center",
        render: (row) => (
            <RowActions
                row={row}
                canAct={isPendingTab}
                onView={setViewRecord}
                onHandle={setActionRecord}
            />
        ),
    });

    const baseColumns = [
        {
            key: "date_created",
            sortKey: "date_created",
            label: "Date",
            sortable: true,
            className: "whitespace-nowrap text-xs",
            headerClassName: "w-28",
        },
        {
            key: "tbl_id",
            sortKey: "tbl_id",
            label: "#",
            sortable: true,
            className: "text-sm font-medium",
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

    const historyColumns = [...baseColumns, makeActionColumn(false)];
    const pendingColumns = [...baseColumns, makeActionColumn(true)];

    // ── Render ────────────────────────────────────────────────────────────

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
                    <Button asChild size="sm">
                        <a href={route("ftw.create")}>
                            <ClipboardPlus className="mr-1.5 h-4 w-4" />
                            New FTW
                        </a>
                    </Button>
                </div>

                {/* Tabs */}
                <div className="flex border-b">
                    {["pending", "history"].map((t) => (
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
                        emptyMessage={
                            isClinic
                                ? "Clinic users have no pending actions."
                                : "No pending actions for you."
                        }
                        reloadRef={pendingReloadRef}
                        onMounted={(reload) => {
                            pendingPanelRef.current = reload;
                        }}
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

            {/* View modal — always available, both tabs */}
            {viewRecord && (
                <ViewModal
                    record={viewRecord}
                    onClose={() => setViewRecord(null)}
                />
            )}

            {/* Action modal — pending tab only */}
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

// ─── Data-fetching panel ──────────────────────────────────────────────────────

function FetchPanel({ fetchUrl, columns, emptyMessage, reloadRef, onMounted }) {
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
    const debounceRef = useRef(null);

    // Keep a ref to current state so `load` never goes stale
    const stateRef = useRef({ search, orderBy, orderDir, page, perPage });
    stateRef.current = { search, orderBy, orderDir, page, perPage };

    const load = useCallback(
        async (overrides = {}) => {
            const { search, orderBy, orderDir, page, perPage } =
                stateRef.current;
            setLoading(true);
            const params = new URLSearchParams({
                search: overrides.search ?? search,
                order_by: overrides.orderBy ?? orderBy,
                order_dir: overrides.orderDir ?? orderDir,
                page: overrides.page ?? page,
                per_page: overrides.perPage ?? perPage,
            });
            try {
                const res = await fetch(`${fetchUrl}?${params}`, {
                    headers: { Accept: "application/json" },
                });
                const json = await res.json();
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

    // Expose reload fn to parent
    useEffect(() => {
        if (reloadRef) reloadRef.current = () => load();
        if (onMounted) onMounted(() => load());
    }, [load]);

    // Reload on pagination / sort change
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

    return (
        <div className="space-y-3">
            {/* Search + refresh */}
            <div className="flex items-center gap-2">
                <div className="relative flex-1 max-w-xs">
                    <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                    <input
                        type="text"
                        placeholder="Search name or dept…"
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

            <ServerTable
                columns={columns}
                data={data}
                orderBy={orderBy}
                orderDir={orderDir}
                onSort={handleSort}
                emptyMessage={emptyMessage}
            />

            <Pagination
                meta={meta}
                onPageChange={(p) => setPage(p)}
                perPage={perPage}
                onPerPageChange={(v) => {
                    setPerPage(v);
                    setPage(1);
                }}
            />
        </div>
    );
}
