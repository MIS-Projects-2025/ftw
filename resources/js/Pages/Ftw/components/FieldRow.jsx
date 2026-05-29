import { Label } from "@/Components/ui/label";

/**
 * A labelled form field wrapper with optional inline error message.
 * Shared between CreateFtw and any modal forms.
 */
export function FieldRow({ label, error, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-sm">{label}</Label>
            {children}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}
