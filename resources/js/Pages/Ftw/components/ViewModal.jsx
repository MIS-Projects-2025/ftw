import { Button } from "@/Components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/Components/ui/dialog";
import { Loader2, CheckCircle } from "lucide-react";
import { StatusBadge } from "./StatusBadge";
import { RecordDetails, DiagnosisSection } from "./RecordDetails";

/**
 * Read-only modal showing all details of an FTW record.
 */
export function ViewModal({ record, onClose }) {
    if (!record) return null;

    const isClinic = record.is_clinic ?? false;

    return (
        <Dialog open onOpenChange={(o) => { if (!o) onClose(); }}>
            <DialogContent className="sm:max-w-4xl max-h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle className="text-2xl">FTW Record Details</DialogTitle>
                    <p className="text-sm text-muted-foreground">
                        Record #{record.tbl_id} • Created {record.date_created}
                    </p>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto space-y-5 pr-1">
                    {/* Status + Quick Info */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="rounded-lg border p-4">
                            <p className="text-xs text-muted-foreground uppercase font-semibold">
                                Current Status
                            </p>
                            <div className="mt-2">
                                <StatusBadge status={record.process_status} />
                            </div>
                            <p className="text-xs text-muted-foreground mt-3">
                                Last updated: {record.date_updated || record.date_created}
                            </p>
                        </div>

                        <div className="rounded-lg border p-4">
                            <p className="text-xs text-muted-foreground uppercase font-semibold">
                                Quick Info
                            </p>
                            <div className="mt-2 space-y-1.5">
                                <div className="flex justify-between">
                                    <span className="text-xs text-muted-foreground">Employee:</span>
                                    <span className="text-sm font-medium">{record.emp_name}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-xs text-muted-foreground">Department:</span>
                                    <span className="text-sm">{record.emp_dept}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-xs text-muted-foreground">Recommendation:</span>
                                    <span className="text-sm font-medium">{record.rec_label}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Employee Information */}
                    <div className="rounded-lg border overflow-hidden">
                        <div className="border-b bg-muted/30 px-4 py-3">
                            <p className="text-sm font-semibold flex items-center gap-2">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                Employee Information
                            </p>
                        </div>
                        <div className="p-5">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p className="text-xs text-muted-foreground">Employee ID</p>
                                    <p className="text-sm font-mono mt-1">{record.emp_no}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Shift Schedule</p>
                                    <p className="text-sm mt-1">
                                        {record.emp_shift_label || record.emp_shift || "—"}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Time In</p>
                                    <p className="text-sm mt-1">{record.emp_time_in || "—"}</p>
                                </div>
                                {record.emp_team && (
                                    <div>
                                        <p className="text-xs text-muted-foreground">Team</p>
                                        <p className="text-sm mt-1">{record.emp_team}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Diagnosis */}
                    <DiagnosisSection record={record} />

                    {/* Clinical Details */}
                    <div className="rounded-lg border overflow-hidden">
                        <div className="border-b bg-muted/30 px-4 py-3">
                            <p className="text-sm font-semibold flex items-center gap-2">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M4 4h16v16H4z M8 8h8v8H8z M8 12h8" />
                                </svg>
                                Clinical Details
                            </p>
                        </div>
                        <div className="p-5">
                            <RecordDetails record={record} />
                        </div>
                    </div>

                    {/* Approval Timeline */}
                    {!isClinic && record.approvals?.length > 0 && (
                        <div className="rounded-lg border overflow-hidden">
                            <div className="border-b bg-muted/30 px-4 py-3">
                                <p className="text-sm font-semibold flex items-center gap-2">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 6v6l4 2" />
                                    </svg>
                                    Approval Timeline
                                </p>
                            </div>
                            <div className="p-5 space-y-4">
                                {record.approvals.map((approval, idx) => (
                                    <div key={idx} className="flex items-start gap-3">
                                        <div className="flex-shrink-0 w-8 h-8 rounded-full bg-muted flex items-center justify-center">
                                            {approval.status === "approved" ? (
                                                <CheckCircle className="h-4 w-4 text-green-600" />
                                            ) : approval.status === "pending" ? (
                                                <Loader2 className="h-4 w-4 text-yellow-600 animate-spin" />
                                            ) : (
                                                <svg className="h-4 w-4 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            )}
                                        </div>
                                        <div className="flex-1">
                                            <p className="font-medium text-sm">
                                                {approval.role === "immediate_sup"
                                                    ? "Supervisor Approval"
                                                    : "Employee Acknowledgement"}
                                            </p>
                                            <p className="text-xs text-muted-foreground mt-0.5">
                                                {approval.approver_name || `Approver #${approval.approver_emp}`}
                                            </p>
                                            {approval.date_approved && (
                                                <p className="text-xs text-muted-foreground mt-1">
                                                    {new Date(approval.date_approved).toLocaleString()}
                                                </p>
                                            )}
                                            {approval.remarks && (
                                                <p className="text-xs text-foreground mt-2 p-2 bg-muted/30 rounded">
                                                    "{approval.remarks}"
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
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
