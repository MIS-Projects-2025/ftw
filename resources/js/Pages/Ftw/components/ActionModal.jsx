import axios from "axios";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "sonner";
import { Loader2 } from "lucide-react";
import { RecordDetails } from "./RecordDetails";

/**
 * Approval / acknowledgement modal for a single FTW record.
 *
 * Props:
 *   record       - the FTW record object
 *   isSupervisor - true → approve/disapprove; false → acknowledge/reject
 *   onClose      - called when the dialog should close
 *   onSuccess    - called after a successful action
 */
export function ActionModal({ record, isSupervisor, onClose, onSuccess }) {
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
            await axios.post(route("ftw.action", record.tbl_id), {
                action,
                remarks: remarks.trim() || null,
            });
            toast.success(
                action === "approve"     ? "Record approved."
                : action === "disapprove" ? "Record disapproved."
                : action === "acknowledge" ? "Record acknowledged."
                : "Record rejected.",
            );
            onSuccess();
            onClose();
        } catch (err) {
            toast.error(err.response?.data?.message ?? err.message ?? "Something went wrong.");
        } finally {
            setLoading(false);
        }
    };

    const actionButtons = isSupervisor
        ? [
              { key: "approve",    label: "Approve",    variant: "default" },
              { key: "disapprove", label: "Disapprove", variant: "destructive" },
          ]
        : [
              { key: "acknowledge", label: "Acknowledge", variant: "default" },
              { key: "reject",      label: "Reject",      variant: "destructive" },
          ];

    return (
        <Dialog open onOpenChange={(o) => { if (!o) onClose(); }}>
            <DialogContent className="sm:max-w-3xl max-h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle className="text-2xl">
                        {isSupervisor ? "Supervisor Approval" : "Employee Acknowledgement"}
                    </DialogTitle>
                    <p className="text-sm text-muted-foreground">
                        Record #{record.tbl_id} • {record.emp_name}
                    </p>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto space-y-5 pr-1">
                    {/* Summary cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="rounded-lg border p-4">
                            <p className="text-xs text-muted-foreground uppercase font-semibold">Employee</p>
                            <p className="font-medium mt-1">{record.emp_name}</p>
                            <p className="text-xs text-muted-foreground">{record.emp_dept}</p>
                        </div>
                        <div className="rounded-lg border p-4">
                            <p className="text-xs text-muted-foreground uppercase font-semibold">Recommendation</p>
                            <p className="font-medium mt-1">{record.rec_label}</p>
                            <p className="text-xs text-muted-foreground">{record.date_created}</p>
                        </div>
                    </div>

                    {/* Diagnosis */}
                    {record.emp_diagnose && (
                        <div className="rounded-lg border p-4">
                            <p className="text-xs text-muted-foreground uppercase font-semibold mb-2">
                                Diagnosis
                            </p>
                            <p className="text-sm">{record.emp_diagnose}</p>
                        </div>
                    )}

                    {/* Clinical Details */}
                    <div className="rounded-lg border overflow-hidden">
                        <div className="border-b bg-muted/30 px-4 py-3">
                            <p className="text-sm font-semibold">Clinical Details</p>
                        </div>
                        <div className="p-5">
                            <RecordDetails record={record} />
                        </div>
                    </div>

                    {/* Action selection */}
                    {!action && (
                        <div>
                            <p className="text-sm font-medium mb-3">Select Action</p>
                            <div className="grid grid-cols-2 gap-3">
                                {actionButtons.map((btn) => (
                                    <Button
                                        key={btn.key}
                                        variant={btn.variant}
                                        className="h-11"
                                        onClick={() => setAction(btn.key)}
                                    >
                                        {btn.label}
                                    </Button>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Remarks */}
                    {action && (
                        <div className="space-y-3">
                            <Label htmlFor="modal-remarks" className="text-sm font-medium">
                                Remarks{" "}
                                {needsRemarks && <span className="text-destructive">*</span>}
                            </Label>
                            <Textarea
                                id="modal-remarks"
                                rows={4}
                                placeholder={
                                    needsRemarks
                                        ? "Required — please state your reason."
                                        : "Optional remarks"
                                }
                                value={remarks}
                                onChange={(e) => setRemarks(e.target.value)}
                                className="resize-none"
                                autoFocus
                            />
                            {needsRemarks && remarks.length === 0 && (
                                <p className="text-xs text-destructive">Remarks are required</p>
                            )}
                        </div>
                    )}
                </div>

                <DialogFooter className="gap-2 pt-3 border-t mt-2">
                    <Button
                        variant="outline"
                        disabled={loading}
                        onClick={() => (action ? setAction(null) : onClose())}
                    >
                        {action ? "Back" : "Cancel"}
                    </Button>
                    {action && (
                        <Button onClick={submit} disabled={loading}>
                            {loading && <Loader2 className="mr-1.5 h-4 w-4 animate-spin" />}
                            Confirm {actionButtons.find((btn) => btn.key === action)?.label}
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
