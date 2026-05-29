import { useState } from "react";
import { Button } from "@/Components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/Components/ui/dialog";
import { Label } from "@/Components/ui/label";
import { Textarea } from "@/Components/ui/textarea";
import { toast } from "sonner";
import { Loader2 } from "lucide-react";

/**
 * Confirmation dialog for bulk approve / bulk disapprove actions.
 *
 * Props:
 *   action    - "approve" | "disapprove"
 *   count     - number of selected records
 *   loading   - whether the request is in flight
 *   onConfirm - called with (remarks: string|null) when confirmed
 *   onClose   - called when the dialog should close
 */
export function BulkActionDialog({ action, count, loading, onConfirm, onClose }) {
    const [remarks, setRemarks] = useState("");
    const isDisapprove = action === "disapprove";

    const handleConfirm = () => {
        if (isDisapprove && !remarks.trim()) {
            toast.error("Remarks are required for disapproval.");
            return;
        }
        onConfirm(remarks.trim() || null);
    };

    return (
        <Dialog open onOpenChange={(o) => { if (!o && !loading) onClose(); }}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {isDisapprove ? "Bulk Disapprove" : "Bulk Approve"}
                    </DialogTitle>
                    <p className="text-sm text-muted-foreground">
                        This will {isDisapprove ? "disapprove" : "approve"}{" "}
                        <span className="font-medium text-foreground">
                            {count} record{count !== 1 ? "s" : ""}
                        </span>
                        .
                    </p>
                </DialogHeader>

                <div className="space-y-2 py-1">
                    <Label htmlFor="bulk-dialog-remarks" className="text-sm font-medium">
                        Remarks{" "}
                        {isDisapprove ? (
                            <span className="text-destructive">*</span>
                        ) : (
                            <span className="text-muted-foreground font-normal text-xs">(optional)</span>
                        )}
                    </Label>
                    <Textarea
                        id="bulk-dialog-remarks"
                        rows={4}
                        placeholder={
                            isDisapprove
                                ? "Required — state your reason for disapproval."
                                : "Optional remarks applied to all selected records."
                        }
                        value={remarks}
                        onChange={(e) => setRemarks(e.target.value)}
                        className="resize-none"
                        autoFocus
                    />
                    {isDisapprove && remarks.length === 0 && (
                        <p className="text-xs text-destructive">Remarks are required</p>
                    )}
                </div>

                <DialogFooter className="gap-2 pt-1">
                    <Button variant="outline" disabled={loading} onClick={onClose}>
                        Cancel
                    </Button>
                    <Button
                        variant={isDisapprove ? "destructive" : "default"}
                        disabled={loading}
                        onClick={handleConfirm}
                    >
                        {loading && <Loader2 className="mr-1.5 h-4 w-4 animate-spin" />}
                        Confirm {isDisapprove ? "Disapprove" : "Approve"}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
