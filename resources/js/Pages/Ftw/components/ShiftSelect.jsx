import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

const SHIFTS = [
    { value: "1", label: "Day Shift" },
    { value: "2", label: "Night Shift" },
    { value: "3", label: "Normal" },
];

/**
 * Reusable shift schedule selector.
 * Shared between CreateFtw form fields and any future form.
 */
export function ShiftSelect({ value, onChange }) {
    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger>
                <SelectValue placeholder="Select shift…" />
            </SelectTrigger>
            <SelectContent>
                {SHIFTS.map((s) => (
                    <SelectItem key={s.value} value={s.value}>
                        {s.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
