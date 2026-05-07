// ─── Shared helpers ───────────────────────────────────────────────────────────

const empty = <span className="text-muted-foreground font-normal">—</span>;

// ─── RecordDetails ────────────────────────────────────────────────────────────
// Renders the clinical detail section specific to each recommendation type.

export function RecordDetails({ record }) {
    const label = (record.rec_label ?? "").toLowerCase();

    const isFitToWork = label.includes("fit to work");
    const isSdh       = label.includes("sent home") || label.includes("hospital");
    const isUnfit     = label.includes("unfit");
    const isRest      = label.includes("rest");

    if (isFitToWork) {
        return (
            <div className="space-y-4">
                <div>
                    <p className="text-xs text-muted-foreground uppercase font-semibold mb-2">
                        Dates of Absence ({record.absent_count ?? 0})
                    </p>
                    {record.absence_dates?.length ? (
                        <div className="flex flex-wrap gap-2">
                            {record.absence_dates.map((d) => (
                                <span
                                    key={d}
                                    className="inline-flex items-center rounded-md border bg-muted/50 px-2.5 py-1 text-xs font-medium"
                                >
                                    {d}
                                </span>
                            ))}
                        </div>
                    ) : (
                        <span className="text-muted-foreground text-sm">
                            No dates recorded
                        </span>
                    )}
                </div>

                {record.ftw_file && (
                    <div>
                        <p className="text-xs text-muted-foreground uppercase font-semibold mb-2">
                            Attachment
                        </p>
                        <a
                            href={record.ftw_file_url}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                        >
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" />
                                <polyline points="13 2 13 9 20 9" />
                            </svg>
                            {record.ftw_file}
                        </a>
                    </div>
                )}
            </div>
        );
    }

    if (isSdh) {
        const dateLabel = label.includes("hospital")
            ? "Date sent to hospital"
            : "Date sent home";
        return (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p className="text-xs text-muted-foreground uppercase font-semibold">
                        {dateLabel}
                    </p>
                    <p className="text-sm font-medium mt-1">{record.sdh_date || empty}</p>
                </div>
                <div>
                    <p className="text-xs text-muted-foreground uppercase font-semibold">
                        Time Out
                    </p>
                    <p className="text-sm font-medium mt-1">{record.sdh_time || empty}</p>
                </div>
            </div>
        );
    }

    if (isUnfit) {
        return (
            <div>
                <p className="text-xs text-muted-foreground uppercase font-semibold mb-2">
                    Remarks / Reason
                </p>
                <p className="text-sm text-foreground leading-relaxed p-3 bg-muted/30 rounded-lg">
                    {record.remarks || empty}
                </p>
            </div>
        );
    }

    if (isRest) {
        return (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p className="text-xs text-muted-foreground uppercase font-semibold">
                        Rest Date
                    </p>
                    <p className="text-sm font-medium mt-1">{record.rest_date || empty}</p>
                </div>
                <div>
                    <p className="text-xs text-muted-foreground uppercase font-semibold">
                        Time In (SDH)
                    </p>
                    <p className="text-sm font-medium mt-1">{record.rest_time_in || empty}</p>
                </div>
                <div className="md:col-span-2">
                    <div className="rounded-lg bg-muted/30 p-3">
                        <p className="text-xs text-muted-foreground flex items-center gap-2">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 6v6l4 2" />
                            </svg>
                            Note: Time out will be recorded when the employee returns to duty.
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    return null;
}

// ─── DiagnosisSection ─────────────────────────────────────────────────────────

export function DiagnosisSection({ record }) {
    if (!record.emp_diagnose) return null;

    return (
        <div className="rounded-lg border p-4">
            <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">
                Diagnosis / Medical Findings
            </p>
            <p className="text-sm leading-relaxed">{record.emp_diagnose}</p>
        </div>
    );
}
