import { Button } from "@/components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Eye, MoreHorizontal, CheckCircle } from "lucide-react";

/**
 * Per-row actions dropdown for the FTW table.
 *
 * Props:
 *   row      - the data row object
 *   canAct   - whether the "Handle" action should be shown
 *   onView   - called with row when View is clicked
 *   onHandle - called with row when Handle is clicked
 */
export function RowActions({ row, canAct, onView, onHandle }) {
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
