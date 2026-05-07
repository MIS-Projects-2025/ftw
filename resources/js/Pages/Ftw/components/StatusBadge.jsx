export const STATUS_MAP = {
    1: { label: "For Supervisor Approval",      cls: "bg-blue-100 text-blue-700 border-blue-200" },
    2: { label: "For Employee Acknowledgement", cls: "bg-amber-100 text-amber-700 border-amber-200" },
    3: { label: "Completed",                    cls: "bg-green-100 text-green-700 border-green-200" },
    6: { label: "Disapproved / Rejected",       cls: "bg-red-100 text-red-700 border-red-200" },
};

export function StatusBadge({ status }) {
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
